<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllProjectsCostingExport implements WithMultipleSheets
{
    protected $projectsData;

    public function __construct($projectsData)
    {
        $this->projectsData = $projectsData;
    }

    public function sheets(): array
    {
        $sheets = [];

        // First sheet: Summary
        $sheets[] = new ProjectCostingSummarySheet($this->projectsData);

        // Detail sheets per project
        foreach ($this->projectsData as $projectData) {
            $materialCount = is_array($projectData['materials']) ? count($projectData['materials']) : 0;

            if ($materialCount > 0) {
                $sheets[] = new ProjectCostingDetailSheet($projectData['project_name'], $projectData['materials'], $projectData['grand_total']);
            }
        }

        return $sheets;
    }
}
