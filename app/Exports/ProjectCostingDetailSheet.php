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

class ProjectCostingDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $projectName;
    protected $materials;
    protected $grandTotal;

    public function __construct($projectName, $materials, $grandTotal)
    {
        $this->projectName = $projectName;
        $this->materials = $materials;
        $this->grandTotal = $grandTotal;
    }

    public function collection()
    {
        $data = collect();
        $rowNumber = 1;

        foreach ($this->materials as $material) {
            $data->push([
                'no' => $rowNumber++,
                'material' => $material['material_name'],
                'qty' => $material['qty'],
                'unit' => $material['unit'],
                'currency' => $material['currency'],
                'unit_price' => number_format($material['unit_price'], 2, '.', ','),
                'domestic_freight' => number_format($material['domestic_freight'], 2, '.', ','),
                'intl_freight' => number_format($material['intl_freight'], 2, '.', ','),
                'total_unit_cost' => number_format($material['total_unit_cost'], 2, '.', ','),
                'total_cost_idr' => number_format($material['total_cost_idr'], 2, '.', ','),
            ]);
        }

        // Grand total row
        $data->push([
            'no' => '',
            'material' => 'GRAND TOTAL',
            'qty' => '',
            'unit' => '',
            'currency' => '',
            'unit_price' => '',
            'domestic_freight' => '',
            'intl_freight' => '',
            'total_unit_cost' => '',
            'total_cost_idr' => number_format($this->grandTotal, 2, '.', ','),
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['No', 'Material Name', 'Qty', 'Unit', 'Currency', 'Unit Price', 'Domestic Freight', 'Intl Freight', 'Total Unit Cost', 'Total Cost (IDR)'];
    }

    public function styles(Worksheet $sheet)
    {
        $materialCount = is_array($this->materials) ? count($this->materials) : 0;
        $lastRow = $materialCount + 2;

        // Header row - green background like image
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Grand total row - yellow background
        $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
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
        return substr($this->projectName, 0, 31); // Excel limit 31 chars
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 35,
            'C' => 8,
            'D' => 10,
            'E' => 10,
            'F' => 12,
            'G' => 16,
            'H' => 14,
            'I' => 16,
            'J' => 18,
        ];
    }
}
