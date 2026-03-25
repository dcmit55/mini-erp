<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CostingMaterialSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected array $rows;
    protected string $projectName;

    public function __construct(array $rows, string $projectName)
    {
        $this->rows = $rows;
        $this->projectName = $projectName;
    }

    public function array(): array
    {
        $data = [];
        $no = 1;

        foreach ($this->rows as $r) {
            $data[] = [$no++, $r['job_order_name'], $r['material_name'], $r['qty'], $r['unit'], $r['currency'], $r['unit_price'], $r['domestic_freight'], $r['intl_freight'], $r['total_unit_cost'], $r['total_idr']];
        }

        // Total row
        $data[] = ['', '', 'TOTAL MATERIAL COST', '', '', '', '', '', '', '', array_sum(array_column($this->rows, 'total_idr'))];

        return $data;
    }

    public function headings(): array
    {
        return ['No', 'Job Order', 'Material Name', 'Qty', 'Unit', 'Currency', 'Unit Price', 'Domestic Freight', 'Intl Freight', 'Total Unit Cost', 'Total Cost (Rp)'];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count($this->rows) + 2;

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '548235']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$last}:K{$last}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // Currency format for unit_price, freights, total columns
        $dataLast = $last - 1;
        foreach (['G', 'H', 'I', 'J'] as $col) {
            $sheet
                ->getStyle("{$col}2:{$col}{$dataLast}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
        foreach (['K'] as $col) {
            $sheet
                ->getStyle("{$col}2:{$col}{$last}")
                ->getNumberFormat()
                ->setFormatCode('"Rp "#,##0');
        }

        $sheet->getStyle("A1:K{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Material Cost';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 26,
            'C' => 36,
            'D' => 8,
            'E' => 10,
            'F' => 10,
            'G' => 16,
            'H' => 18,
            'I' => 16,
            'J' => 18,
            'K' => 22,
        ];
    }

    public function registerEvents(): array
    {
        $projectName = $this->projectName;
        return [
            AfterSheet::class => function (AfterSheet $event) use ($projectName) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:K1');

                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Material Cost — ' . $projectName);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i'));
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
            },
        ];
    }
}
