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
    public readonly ?array $departmentsArray; // Array of department names
    public readonly ?string $deliveryDateRaw; // Delivery date from Lark (YYYY-MM-DD or Unix timestamp)
    public readonly ?string $statusRaw; // Job status from Lark
    public readonly ?string $finalImageRaw; // Final Image (Before Delivery) attachment URL(s) from Lark

    /**
     * Field mapping dari internal key → Lark field_name
     *
     * PENTING: Lark API mengembalikan field_name (bukan field_id) di response.
     *
     * Field Names dari Lark Base (Job Orders table) berdasarkan actual API response:
     * - "Job Order Name / Description" → job_orders.name (nama job order)
     * - "Project List"                → job_orders.project_lark (project yang terkait)
     * - "Dept-in-charge"              → job_orders.department_lark (department)
     * - "Delivery Date"               → job_orders.delivery_date (delivery date)
     * - "Job Status"                  → job_orders.status (current status)
    /**
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
        'job_orders.delivery_date' => 'Delivery Date', // Format: YYYY-MM-DD or Unix timestamp
        'job_orders.status' => 'Job Status', // Status from Lark (e.g., "Preparing", "Delivered")
        'job_orders.final_image' => 'Final Image (Before Delivery)', // Attachment field
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

        // Extract department as string (comma-separated if multiple)
        $this->departmentRaw = $this->extractField($fields, 'job_orders.department_lark');

        // Extract departments as array for relational mapping
        $this->departmentsArray = $this->extractDepartmentsArray($fields);

        // Extract delivery date (YYYY-MM-DD format or Unix timestamp in milliseconds)
        $this->deliveryDateRaw = $this->extractField($fields, 'job_orders.delivery_date');

        // Extract job status from Lark
        $this->statusRaw = $this->extractField($fields, 'job_orders.status');

        // Extract final image URL(s) from Lark attachment field
        $this->finalImageRaw = $this->extractField($fields, 'job_orders.final_image');
    }

    /**
     * Extract departments array from Lark field
     * Handles multiple department formats from Lark API
     *
     * @param array $fields
     * @return array|null Array of department names or null
     */
    private function extractDepartmentsArray(array $fields): ?array
    {
        $fieldName = self::FIELD_MAPPING['job_orders.department_lark'] ?? null;

        if (!$fieldName || !isset($fields[$fieldName])) {
            return null;
        }

        $value = $fields[$fieldName];

        // Handle array format from Lark
        if (is_array($value) && !empty($value)) {
            $departments = [];

            foreach ($value as $item) {
                // Format: [{"text": "Dept Name"}]
                if (is_array($item) && isset($item['text'])) {
                    $departments[] = trim($item['text']);
                }
                // Format: ["Dept Name"]
                elseif (is_string($item)) {
                    $departments[] = trim($item);
                }
            }

            return !empty($departments) ? $departments : null;
        }

        // Handle single string
        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }

        return null;
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
            'departments_array' => $this->departmentsArray,
            'delivery_date_raw' => $this->deliveryDateRaw,
            'final_image_raw' => $this->finalImageRaw,
        ];
    }
}
