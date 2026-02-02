<?php

namespace App\Transformers;

use App\DTO\LarkProjectDTO;
use App\Models\Admin\Department;

/**
 * Project Transformer
 *
 * Bertugas melakukan normalisasi data dari DTO ke format database
 * TANGGUNG JAWAB:
 * - Trim string
 * - Normalisasi nilai
 * - Konversi tipe data
 * - Validasi business rules
 *
 * DILARANG:
 * - Database operation
 * - API calls
 */
class ProjectTransformer
{
    /**
     * Transform Lark DTO to database-ready array
     *
     * @param LarkProjectDTO $dto
     * @return array Data siap disimpan ke database
     */
    public function transform(LarkProjectDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'name' => $this->normalizeName($dto->nameRaw),
            'type_dept' => $this->normalizeTypeDept($dto->departmentRaw), // Raw text dari Lark (staging)
            'department_id' => $this->normalizeDepartmentId($dto->departmentRaw), // Converted ke FK
            'sales' => $this->normalizeSales($dto->salesRaw),
            'stage' => $this->normalizeStage($dto->stageRaw),
            'project_status' => $this->normalizeProjectStatus($dto->projectStatusRaw),
            'qty' => $this->normalizeQty($dto->qtyRaw),
            'deadline' => $this->normalizeDate($dto->deadlineRaw),
            'img' => $this->normalizeImage($dto->imgRaw),
            'submission_form' => $this->normalizeUrl($dto->submissionFormRaw),
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
        ];
    }

    /**
     * Normalize project name
     */
    private function normalizeName(?string $value): string
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Project name cannot be empty');
        }

        // Trim dan remove multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $value));

        // Limit panjang sesuai database
        return substr($normalized, 0, 255);
    }

    /**
     * Normalize type/dept text (raw dari Lark - untuk staging)
     *
     * CATATAN: Kolom type_dept hanya untuk menyimpan data mentah dari Lark
     * Data sebenarnya digunakan dari department_id (relasi)
     */
    private function normalizeTypeDept(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        // Simpan as-is dari Lark (bisa comma-separated)
        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize department to department_id
     *
     * Convert department name dari Lark ke department_id
     * Jika ada multiple departments (comma-separated), ambil yang pertama untuk primary department_id
     *
     * CATATAN: Type of Project di Lark bisa null
     */
    private function normalizeDepartmentId(?string $value): ?int
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        // Jika ada multiple departments (comma-separated), ambil yang pertama
        $departmentNames = array_map('trim', explode(',', $value));
        $primaryDepartmentName = $departmentNames[0];

        // Cari department berdasarkan nama (case-insensitive)
        $department = Department::whereRaw('LOWER(name) = ?', [strtolower($primaryDepartmentName)])->first();

        if (!$department) {
            // Log jika department tidak ditemukan
            \Log::warning('Department not found during sync', [
                'department_name' => $primaryDepartmentName,
                'raw_value' => $value,
            ]);
            return null;
        }

        return $department->id;
    }

    /**
     * Normalize sales person name
     *
     * CATATAN: Sales / Ops IC di Lark bisa null (tidak semua project punya PIC)
     */
    private function normalizeSales(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null; // OK untuk null - tidak semua project punya sales IC
        }

        // Trim dan capitalize
        $normalized = trim($value);

        // Limit panjang sesuai database
        return substr($normalized, 0, 255);
    }

    /**
     * Normalize stage value
     *
     * CATATAN: Project Status (single select) di Lark bisa null
     */
    private function normalizeStage(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null; // OK untuk null - beberapa project belum ada status
        }

        // Trim dan lowercase untuk konsistensi
        $normalized = trim(strtolower($value));

        // Mapping stage values jika perlu
        $stageMapping = [
            'in progress' => 'in_progress',
            'on hold' => 'on_hold',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'pending confirmation' => 'pending_confirmation',
        ];

        $normalized = $stageMapping[$normalized] ?? $normalized;

        return substr($normalized, 0, 255);
    }

    /**
     * Normalize project status (multi-select)
     *
     * CATATAN: Batam Job Order Statuses di Lark adalah multi-select field
     * Bisa berisi multiple values, kita simpan sebagai comma-separated string
     */
    private function normalizeProjectStatus($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Jika value adalah array (multi-select dari Lark)
        if (is_array($value)) {
            // Filter empty values dan join dengan comma
            $statuses = array_filter(array_map('trim', $value));
            return !empty($statuses) ? implode(', ', $statuses) : null;
        }

        // Jika value adalah string
        if (is_string($value)) {
            $trimmed = trim($value);
            return !empty($trimmed) ? $trimmed : null;
        }

        return null;
    }

    /**
     * Normalize quantity value
     *
     * CATATAN: Qty in DCM Production di Lark bisa null atau 0
     */
    private function normalizeQty(?string $value): ?int
    {
        if (empty($value) && $value !== '0') {
            return null; // OK untuk null
        }

        // Convert to integer
        $qty = (int) $value;

        // Ensure non-negative
        return max(0, $qty);
    }

    /**
     * Normalize URL/link value
     *
     * CATATAN: Submission Form Link di Lark bisa null
     */
    private function normalizeUrl(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null; // OK untuk null
        }

        $url = trim($value);

        // Validate URL format (basic)
        // Note: Lark bisa return URL yang valid atau text biasa
        // Kita terima keduanya
        return substr($url, 0, 500); // Limit panjang untuk TEXT column
    }

    /**
     * Normalize date value
     *
     * CATATAN: Client Deadline di Lark bisa null atau dalam format timestamp
     */
    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Jika value adalah timestamp (milliseconds dari Lark)
        if (is_numeric($value)) {
            $timestamp = (int) $value;
            // Lark biasanya return timestamp dalam milliseconds
            if ($timestamp > 9999999999) {
                $timestamp = $timestamp / 1000;
            }
            return date('Y-m-d', $timestamp);
        }

        // Jika value adalah string date
        if (is_string($value)) {
            try {
                $date = new \DateTime($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date', ['value' => $value, 'error' => $e->getMessage()]);
                return null;
            }
        }

        return null;
    }

    /**
     * Normalize image/attachment value
     *
     * CATATAN: Project Images/Data di Lark bisa berisi URL attachment
     */
    private function normalizeImage($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Jika value adalah array (multiple attachments dari Lark)
        if (is_array($value)) {
            // Ambil attachment pertama saja atau join dengan comma
            // Asumsi: kita simpan URL attachment pertama
            if (isset($value[0])) {
                // Jika value[0] adalah object/array dengan property 'url' atau 'tmp_url'
                if (is_array($value[0])) {
                    $url = $value[0]['url'] ?? ($value[0]['tmp_url'] ?? null);
                    return $url ? substr(trim($url), 0, 255) : null;
                }
                // Jika value[0] adalah string URL
                return substr(trim($value[0]), 0, 255);
            }
        }

        // Jika value adalah string URL
        if (is_string($value)) {
            return substr(trim($value), 0, 255);
        }

        return null;
    }

    /**
     * Validate transformed data before save
     *
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): bool
    {
        if (empty($data['lark_record_id'])) {
            throw new \InvalidArgumentException('lark_record_id is required');
        }

        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Project name is required');
        }

        return true;
    }
}
