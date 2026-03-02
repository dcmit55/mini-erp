<?php

namespace App\DTO;

class LarkJobOrderTypeGradingDTO extends BaseLarkDTO
{
    public readonly ?string $jobTypeGradeRaw;
    public readonly ?string $scoreRaw;
    public readonly ?string $gradingRaw;
    public readonly ?string $jobTypeRaw;
    public readonly ?string $productSubCategoryRaw;
    public readonly ?string $otherDetailsRaw;
    public readonly ?string $categoryRaw;
    public readonly ?string $departmentRaw;
    public readonly ?string $parentItemsRaw;

    /**
     * Field mapping dari internal key → Lark field_name
     *
     * CATATAN: Field name bisa berubah jika user rename field di Lark UI.
     * Verify via: /job-orders/lark-raw-type-gradings (super admin only)
     */
    protected const FIELD_MAPPING = [
        'grading.job_type_grade' => 'Job type Full Ref',   // Kombinasi Grading + Job Type
        'grading.score' => 'Score',
        'grading.grading' => 'Grading',
        'grading.job_type' => 'Job Type',
        'grading.product_sub_category' => 'Product Sub-category',
        'grading.other_details' => 'Other Details',
        'grading.category' => 'Category',
        'grading.department' => 'Dept-in-charge',
        'grading.parent_items' => 'Parent items',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);

        $fields = $larkRecord['fields'] ?? [];

        $this->jobTypeGradeRaw = $this->extractField($fields, 'grading.job_type_grade');
        $this->scoreRaw = $this->extractField($fields, 'grading.score');
        $this->gradingRaw = $this->extractField($fields, 'grading.grading');
        $this->jobTypeRaw = $this->extractField($fields, 'grading.job_type');
        $this->productSubCategoryRaw = $this->extractField($fields, 'grading.product_sub_category');
        $this->otherDetailsRaw = $this->extractField($fields, 'grading.other_details');
        $this->categoryRaw = $this->extractField($fields, 'grading.category');
        $this->departmentRaw = $this->extractField($fields, 'grading.department');
        $this->parentItemsRaw = $this->extractField($fields, 'grading.parent_items');
    }

    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'job_type_grade_raw' => $this->jobTypeGradeRaw,
            'score_raw' => $this->scoreRaw,
            'grading_raw' => $this->gradingRaw,
            'job_type_raw' => $this->jobTypeRaw,
            'product_sub_category_raw' => $this->productSubCategoryRaw,
            'other_details_raw' => $this->otherDetailsRaw,
            'category_raw' => $this->categoryRaw,
            'department_raw' => $this->departmentRaw,
            'parent_items_raw' => $this->parentItemsRaw,
        ];
    }
}