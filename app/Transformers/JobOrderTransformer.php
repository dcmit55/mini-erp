<?php

namespace App\Transformers;

use App\DTO\LarkJobOrderDTO;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\Log;

/**
 * Job Order Transformer
 *
 * Bertugas melakukan normalisasi data dari DTO ke format database
 * TANGGUNG JAWAB:
 * - Trim string
 * - Normalisasi nilai
 * - Konversi tipe data
 * - Lookup foreign keys (project_id, department_id)
 * - Validasi business rules
 *
 * DILARANG:
 * - Database operation (kecuali lookup read-only)
 * - API calls
 */
class JobOrderTransformer
{
    /**
     * Transform Lark DTO to database-ready array
     *
     * @param LarkJobOrderDTO $dto
     * @return array Data siap disimpan ke database
     * @throws \InvalidArgumentException
     */
    public function transform(LarkJobOrderDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'name' => $this->normalizeName($dto->nameRaw),
            'project_lark' => $this->normalizeProjectLark($dto->projectRaw), // Raw text
            'project_id' => $this->normalizeProjectId($dto->projectRaw), // FK lookup
            'department_lark' => $this->normalizeDepartmentLark($dto->departmentRaw), // Raw text (deprecated, keep for archive)
            'department_id' => $this->normalizePrimaryDepartmentId($dto->departmentsArray), // FK lookup - first dept only
            'delivery_date' => $this->parseDeliveryDate($dto->deliveryDateRaw), // Parse date from Lark
            'status' => $this->normalizeStatus($dto->statusRaw), // Job status from Lark
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
            // Return array of department IDs for pivot sync (handled separately)
            '_department_ids' => $this->normalizeDepartmentIds($dto->departmentsArray),
        ];
    }

    /**
     * Normalize job order name
     */
    private function normalizeName(?string $value): string
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Job Order name cannot be empty');
        }

        // Trim dan remove multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $value));

        // Limit panjang sesuai database
        return substr($normalized, 0, 255);
    }

    /**
     * Normalize project text (raw dari Lark - untuk staging)
     *
     * CATATAN: Kolom project_lark hanya untuk menyimpan data mentah dari Lark
     * Data sebenarnya digunakan dari project_id (relasi)
     */
    private function normalizeProjectLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        // Simpan as-is dari Lark
        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize project to project_id
     *
     * Convert project name dari Lark ke project_id
     *
     * CATATAN: Project List di Lark bisa null
     */
    private function normalizeProjectId(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        $projectName = trim($value);

        // Cari project berdasarkan nama (case-insensitive)
        $project = Project::whereRaw('LOWER(name) = ?', [strtolower($projectName)])->first();

        if (!$project) {
            Log::warning('Job Order sync: Project not found in database', [
                'lark_project_name' => $projectName,
            ]);
            return null;
        }

        return $project->id;
    }

    /**
     * Normalize department text (raw dari Lark - untuk staging)
     */
    private function normalizeDepartmentLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize department to department_id
     *
     * Convert department name dari Lark ke department_id
     */
    private function normalizeDepartmentId(?string $value): ?int
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        $departmentName = trim($value);

        // Cari department berdasarkan nama (case-insensitive)
        $department = Department::whereRaw('LOWER(name) = ?', [strtolower($departmentName)])->first();

        if (!$department) {
            Log::warning('Job Order sync: Department not found in database', [
                'lark_department_name' => $departmentName,
            ]);
            return null;
        }

        return $department->id;
    }

    /**
     * Normalize primary department (first in array) to department_id
     * For backward compatibility with existing department_id column
     *
     * @param array|null $departmentsArray Array of department names from Lark
     * @return int|null Department ID or null
     */
    private function normalizePrimaryDepartmentId(?array $departmentsArray): ?int
    {
        if (empty($departmentsArray)) {
            return null;
        }

        // Take first department as primary
        $primaryDeptName = trim($departmentsArray[0]);

        return $this->normalizeDepartmentId($primaryDeptName);
    }

    /**
     * Normalize all departments to array of department IDs
     * For many-to-many pivot table sync
     *
     * @param array|null $departmentsArray Array of department names from Lark
     * @return array Array of department IDs (may be empty)
     */
    private function normalizeDepartmentIds(?array $departmentsArray): array
    {
        if (empty($departmentsArray)) {
            return [];
        }

        $departmentIds = [];

        foreach ($departmentsArray as $deptName) {
            $deptName = trim($deptName);

            if (empty($deptName)) {
                continue;
            }

            // Lookup department by name (case-insensitive)
            $department = Department::whereRaw('LOWER(name) = ?', [strtolower($deptName)])->first();

            if ($department) {
                $departmentIds[] = $department->id;
            } else {
                Log::warning('Job Order sync: Department not found for pivot', [
                    'lark_department_name' => $deptName,
                ]);
            }
        }

        return array_unique($departmentIds);
    }

    /**
     * Parse delivery date from Lark format
     *
     * Examples:
     * - "2025-12-31" → Carbon date
     * - "2026-03-15" → Carbon date
     * - null/empty → null
     *
     * @param string|null $deliveryDateRaw Raw delivery date from Lark (YYYY-MM-DD)
     * @return string|null Formatted date string or null
     */
    private function parseDeliveryDate(?string $deliveryDateRaw): ?string
    {
        if (empty($deliveryDateRaw)) {
            return null;
        }

        try {
            // Check if it's a timestamp (integer as string)
            if (is_numeric($deliveryDateRaw)) {
                // Lark sends Unix timestamp in milliseconds
                $timestamp = (int) $deliveryDateRaw;

                // If timestamp > 10 digits, it's in milliseconds - convert to seconds
                if ($timestamp > 9999999999) {
                    $timestamp = (int) ($timestamp / 1000);
                }

                $date = \Carbon\Carbon::createFromTimestamp($timestamp);
                return $date->format('Y-m-d');
            }

            // Try parsing as date string (YYYY-MM-DD format)
            $date = \Carbon\Carbon::parse($deliveryDateRaw);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Job Order sync: Could not parse delivery date', [
                'raw_value' => $deliveryDateRaw,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalize job status from Lark
     *
     * Handles various status formats from Lark and normalizes to consistent format
     *
     * Examples from Lark:
     * - "Preparing" → "Preparing"
     * - "Delivered" → "Delivered"
     * - "In Progress" → "In Progress"
     * - null/empty → null
     *
     * @param string|null $statusRaw Raw status from Lark
     * @return string|null Normalized status or null
     */
    private function normalizeStatus(?string $statusRaw): ?string
    {
        if (empty($statusRaw) || trim($statusRaw) === '') {
            return null;
        }

        // Trim and limit to 50 chars (database column limit)
        $status = trim($statusRaw);

        if (strlen($status) > 50) {
            Log::warning('Job Order sync: Status too long, truncating', [
                'raw_status' => $status,
                'truncated' => substr($status, 0, 50),
            ]);
            $status = substr($status, 0, 50);
        }

        return $status;
    }

    /**
     * Parse countdown days from Lark text format
     *
     * DEPRECATED: Not used anymore, replaced by delivery_date
     *
     * Examples:
     * - "2 days left" → 2
     * - "1 day left" → 1
     * - "5 Days Left" → 5 (case-insensitive)
     * - null/empty → null
     *
     * @param string|null $countdownRaw Raw countdown text from Lark
     * @return int|null Parsed integer or null
     */
    private function parseCountdownDays(?string $countdownRaw): ?int
    {
        if (empty($countdownRaw)) {
            return null;
        }

        // Extract number from text using regex
        // Match patterns: "2 days left", "1 day left", "3 Days", etc.
        if (preg_match('/(\d+)\s*day/i', $countdownRaw, $matches)) {
            return (int) $matches[1];
        }

        // If no pattern matched, log warning and return null
        Log::warning('Job Order sync: Could not parse countdown days', [
            'raw_value' => $countdownRaw,
        ]);

        return null;
    }

    /**
     * Validate transformed data
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): void
    {
        // Skip if name is empty or only whitespace
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new \InvalidArgumentException('Job Order name is required');
        }

        if (empty($data['lark_record_id'])) {
            throw new \InvalidArgumentException('Lark record_id is required');
        }

        // Project dan Department tidak wajib (bisa null)
        // Hanya log warning jika tidak ditemukan (sudah di-handle di normalize methods)
    }
}
