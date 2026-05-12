<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\CostingSummarySheet;
use App\Exports\Sheets\CostingMaterialSheet;
use App\Exports\Sheets\CostingWorkmanshipSheet;
use App\Exports\Sheets\CostingFreightSheet;

/**
 * Project Costing Multi-Sheet Export
 *
 * Sheets:
 *  1. Project Summary
 *  2. Material Cost
 *  3. Workmanship Cost
 *  4. Freight Cost
 */
class ProjectCostingMultiSheetExport implements WithMultipleSheets
{
    protected string $projectName;
    protected array $summaryRows;
    protected array $materialRows;
    protected array $workmanshipRows;
    protected array $freightRows;
    protected array $filters;

    public function __construct(string $projectName, array $summaryRows, array $materialRows, array $workmanshipRows, array $freightRows, array $filters = [])
    {
        $this->projectName = $projectName;
        $this->summaryRows = $summaryRows;
        $this->materialRows = $materialRows;
        $this->workmanshipRows = $workmanshipRows;
        $this->freightRows = $freightRows;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [new CostingSummarySheet($this->summaryRows, $this->filters), new CostingMaterialSheet($this->materialRows, $this->projectName), new CostingWorkmanshipSheet($this->workmanshipRows, $this->projectName), new CostingFreightSheet($this->freightRows, $this->projectName)];
    }
}
