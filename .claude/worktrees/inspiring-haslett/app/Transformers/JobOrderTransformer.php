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
            'department_lark' => $this->normalizeDepartmentLark($dto->departmentRaw), // Raw text
            'department_id' => $this->normalizeDepartmentId($dto->departmentRaw), // FK lookup
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
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
