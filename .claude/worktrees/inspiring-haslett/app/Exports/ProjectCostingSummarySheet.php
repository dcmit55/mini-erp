<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProjectCostingSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $projectsData;

    public function __construct($projectsData)
    {
        $this->projectsData = $projectsData;
    }

    public function collection()
    {
        $data = collect();
        $totalGrandCost = 0;
        $rowNumber = 1;

        foreach ($this->projectsData as $projectData) {
            $materialCount = is_array($projectData['materials']) ? count($projectData['materials']) : 0;

            $data->push([
                'no' => $rowNumber++,
                'project_name' => $projectData['project_name'],
                'total_materials' => $materialCount,
                'grand_total_idr' => number_format($projectData['grand_total'], 2, '.', ','),
            ]);

            $totalGrandCost += $projectData['grand_total'];
        }

        // Grand total row
        $data->push([
            'no' => '',
            'project_name' => 'GRAND TOTAL ALL PROJECTS',
            'total_materials' => '',
            'grand_total_idr' => number_format($totalGrandCost, 2, '.', ','),
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['No', 'Project Name', 'Total Materials', 'Total Cost (IDR)'];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->projectsData) + 2;

        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Grand total row
        $sheet->getStyle("A{$lastRow}:D{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 40,
            'C' => 18,
            'D' => 20,
        ];
    }
}
