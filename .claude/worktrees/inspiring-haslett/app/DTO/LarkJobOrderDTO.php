<?php

namespace App\DTO;

/**
 * Lark Job Order Data Transfer Object
 *
 * Bertugas HANYA memetakan data mentah dari Lark API
 * DILARANG: database operation, normalisasi, business logic
 *
 * Extends BaseLarkDTO untuk reusable extractField() method
 */
class LarkJobOrderDTO extends BaseLarkDTO
{
    public readonly ?string $nameRaw;
    public readonly ?string $projectRaw;
    public readonly ?string $departmentRaw;

    /**
     * Field mapping dari internal key → Lark field_name
     *
     * PENTING: Lark API mengembalikan field_name (bukan field_id) di response.
     *
     * Field Names dari Lark Base (Job Orders table) berdasarkan actual API response:
     * - "Job Order Name / Description" → job_orders.name (nama job order)
     * - "Project List"                → job_orders.project_lark (project yang terkait)
     * - "Dept-in-charge"              → job_orders.department_lark (department)
     *
     * CATATAN: Field name bisa berubah jika user rename field di Lark UI.
     * Jika ada perubahan nama field, mapping ini harus diupdate.
     *
     * Cara verify field name yang benar:
     * 1. Akses route: /job-orders/lark-raw-data (super admin only)
     * 2. Lihat "fields" object untuk semua field name yang tersedia
     */
    protected const FIELD_MAPPING = [
        'job_orders.name' => 'Job Order Name / Description',
        'job_orders.project_lark' => 'Project List',
        'job_orders.department_lark' => 'Dept-in-charge',
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

        $this->nameRaw = $this->extractField($fields, 'job_orders.name');
        $this->projectRaw = $this->extractField($fields, 'job_orders.project_lark');
        $this->departmentRaw = $this->extractField($fields, 'job_orders.department_lark');
    }

    /**
     * Convert DTO to array (untuk debugging)
     */
    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'name_raw' => $this->nameRaw,
            'project_raw' => $this->projectRaw,
            'department_raw' => $this->departmentRaw,
        ];
    }
}
