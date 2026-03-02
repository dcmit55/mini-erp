<?php

namespace App\Transformers;

use App\DTO\LarkJobOrderTypeGradingDTO;
use App\Models\Logistic\Category;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\Log;

/**
 * Job Order Type Grading Transformer
 *
 * Normalisasi data dari DTO ke format database
 * - Trim string, konversi tipe data
 * - Lookup foreign keys (category_id)
 * - Resolve department names untuk pivot sync
 */
class JobOrderTypeGradingTransformer
{
    /**
     * Transform Lark DTO to database-ready array
     *
     * @return array Data siap disimpan + 'department_names' untuk pivot
     */
    public function transform(LarkJobOrderTypeGradingDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'job_type_grade' => $this->normalizeString($dto->jobTypeGradeRaw, 255),
            'score' => $this->normalizeScore($dto->scoreRaw),
            'grading' => $this->normalizeString($dto->gradingRaw, 255),
            'job_type' => $this->normalizeString($dto->jobTypeRaw, 255),
            'product_sub_category' => $this->normalizeString($dto->productSubCategoryRaw, 255),
            'other_details' => $dto->otherDetailsRaw ? trim($dto->otherDetailsRaw) : null,
            'category_id' => $this->normalizeCategoryId($dto->categoryRaw),
            'parent_items' => $this->normalizeString($dto->parentItemsRaw, 255),
            // department_names digunakan oleh sync service untuk pivot, bukan disimpan ke tabel utama
            '_department_names' => $this->parseDepartmentNames($dto->departmentRaw),
        ];
    }

    /**
     * Validate transformed data
     */
    public function validate(array $data): void
    {
        if (empty($data['job_type_grade']) || trim($data['job_type_grade']) === '') {
            throw new \InvalidArgumentException('Job Type Grade is required');
        }

        if (empty($data['lark_record_id'])) {
            throw new \InvalidArgumentException('Lark record_id is required');
        }
    }

    private function normalizeString(?string $value, int $maxLength): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim(preg_replace('/\s+/', ' ', $value)), 0, $maxLength);
    }

    private function normalizeScore(?string $value): float
    {
        if (empty($value)) {
            return 0;
        }

        return round((float) $value, 2);
    }

    private function normalizeCategoryId(?string $value): ?int
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        $categoryName = trim($value);

        $category = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();

        if (!$category) {
            Log::warning('Type Grading sync: Category not found in database', [
                'lark_category_name' => $categoryName,
            ]);
            return null;
        }

        return $category->id;
    }

    /**
     * Parse department names dari Lark (bisa comma-separated)
     *
     * @return array List of department names
     */
    private function parseDepartmentNames(?string $value): array
    {
        if (empty($value) || trim($value) === '') {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $value)),
            fn($name) => $name !== ''
        );
    }

    /**
     * Resolve department names ke IDs
     *
     * @param array $names Department names dari Lark
     * @return array Department IDs
     */
    public function resolveDepartmentIds(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $ids = [];
        foreach ($names as $name) {
            $department = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();

            if ($department) {
                $ids[] = $department->id;
            } else {
                Log::warning('Type Grading sync: Department not found in database', [
                    'lark_department_name' => $name,
                ]);
            }
        }

        return $ids;
    }
}