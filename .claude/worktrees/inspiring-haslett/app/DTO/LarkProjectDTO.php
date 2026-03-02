<?php

namespace App\DTO;

/**
 * Lark Project Data Transfer Object
 *
 * Bertugas HANYA memetakan data mentah dari Lark API
 * DILARANG: database operation, normalisasi, business logic
 *
 * Extends BaseLarkDTO untuk reusable extractField() method
 */
class LarkProjectDTO extends BaseLarkDTO
{
    public readonly ?string $nameRaw;
    public readonly ?string $departmentRaw;
    public readonly ?string $salesRaw;
    public readonly ?string $stageRaw;
    public readonly ?string $projectStatusRaw;
    public readonly ?string $qtyRaw;
    public readonly ?string $deadlineRaw;
    public readonly ?string $imgRaw;
    public readonly ?string $submissionFormRaw;

    /**
     * Field mapping dari internal key → Lark field_name
     *
     * PENTING: Lark API mengembalikan field_name (bukan field_id) di response.
     * Ini disebabkan API endpoint v1 tidak support field_key='id' parameter.
     *
     * Field Names dari Lark Base (Job Orders table) berdasarkan actual API response:
     * - "Project Label" → projects.name (nama project)
     * - "Type of Project" → projects.department (tipe/departemen project)
     * - "Sales / Ops IC" → projects.sales (PIC sales)
     * - "Project Status" → projects.stage (status project - single select)
     * - "Batam Job Order Statuses" → projects.project_status (statuses - multi select)
     * - "Qty in DCM Production" → projects.qty (quantity)
     * - "Client Deadline" → projects.deadline (deadline dari client)
     * - "Project Images/Data" → projects.img (gambar/attachment project)
     * - "Submission Form Link" → projects.submission_form (link form submission)
     *
     * CATATAN: Field name bisa berubah jika user rename field di Lark UI.
     * Jika ada perubahan nama field, mapping ini harus diupdate.
     *
     * Cara verify field name yang benar:
     * 1. Run: php debug_lark_response.php
     * 2. Lihat "Field Key:" di output untuk semua field name yang tersedia
     */
    protected const FIELD_MAPPING = [
        'projects.name' => 'Project Label', // Nama project
        'projects.department' => 'Type of Project', // Tipe/Department project
        'projects.sales' => 'Sales / Ops IC', // PIC Sales (bisa null)
        'projects.stage' => 'Project Status', // Status project (single select)
        'projects.project_status' => 'Batam Job Order Statuses', // Statuses (multi select)
        'projects.qty' => 'Qty in DCM Production', // Quantity
        'projects.deadline' => 'Client Deadline', // Client deadline
        'projects.img' => 'Project Images/Data', // Project images/attachments
        'projects.submission_form' => 'Submission Form Link', // Submission form link
    ];

    /**
     * Construct dari raw Lark record
     *
     * @param array $larkRecord Raw record dari Lark API response
     */
    public function __construct(array $larkRecord)
    {
        // Call parent untuk set recordId
        parent::__construct($larkRecord);

        // Extract fields berdasarkan mapping
        $fields = $larkRecord['fields'] ?? [];

        $this->nameRaw = $this->extractField($fields, 'projects.name');
        $this->departmentRaw = $this->extractField($fields, 'projects.department');
        $this->salesRaw = $this->extractField($fields, 'projects.sales');
        $this->stageRaw = $this->extractField($fields, 'projects.stage');

        // Project status needs special handling - resolve option IDs to text
        $this->projectStatusRaw = $this->extractProjectStatus($fields);

        $this->qtyRaw = $this->extractField($fields, 'projects.qty');
        $this->deadlineRaw = $this->extractField($fields, 'projects.deadline');
        $this->imgRaw = $this->extractField($fields, 'projects.img');
        $this->submissionFormRaw = $this->extractField($fields, 'projects.submission_form');
    }

    /**
     * Extract project status with option ID resolution
     *
     * "Batam Job Order Statuses" is a Lookup field that returns option IDs like ["optMkjZCKi"]
     * We need to resolve these IDs to readable text from the source table
     *
     * Source: table tblXJcCC3h7gF5aF, field fldNuI0pAS (Job Status)
     */
    private function extractProjectStatus(array $fields): ?string
    {
        $fieldName = self::FIELD_MAPPING['projects.project_status'];
        $value = $fields[$fieldName] ?? null;

        if ($value === null) {
            return null;
        }

        // Get option IDs (array of strings like ["optMkjZCKi", "optXXX"])
        $optionIds = is_array($value) ? $value : [$value];

        // Resolve using LarkOptionResolver
        $resolver = app(\App\Services\Lark\LarkOptionResolver::class);
        $resolvedTexts = $resolver->resolveOptions(
            $optionIds,
            'tblXJcCC3h7gF5aF', // Source table ID
            'fldNuI0pAS', // Source field ID (Job Status)
        );

        return !empty($resolvedTexts) ? implode(', ', $resolvedTexts) : null;
    }

    /**
     * Convert to array (untuk logging)
     */
    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'name_raw' => $this->nameRaw,
            'department_raw' => $this->departmentRaw,
            'sales_raw' => $this->salesRaw,
            'stage_raw' => $this->stageRaw,
            'project_status_raw' => $this->projectStatusRaw,
            'qty_raw' => $this->qtyRaw,
            'deadline_raw' => $this->deadlineRaw,
            'img_raw' => $this->imgRaw,
            'submission_form_raw' => $this->submissionFormRaw,
        ];
    }
}
