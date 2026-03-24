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

class CostingFreightSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected array $rows;
    protected string $projectName;

    public function __construct(array $rows, string $projectName)
    {
        $this->rows        = $rows;
        $this->projectName = $projectName;
    }

    public function array(): array
    {
        $data = [];
        $no   = 1;

        foreach ($this->rows as $r) {
            $data[] = [
                $no++,
                $r['courier_name'],
                $r['direction'],
                $r['date'],
                $r['items_count'],
                $r['transport_cost'],
                $r['baggage_cost'],
                $r['gst_cost'],
                $r['total_idr'],
            ];
        }

        $data[] = [
            '', 'TOTAL', '', '', '',
            array_sum(array_column($this->rows, 'transport_cost')),
            array_sum(array_column($this->rows, 'baggage_cost')),
            array_sum(array_column($this->rows, 'gst_cost')),
            array_sum(array_column($this->rows, 'total_idr')),
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Courier',
            'Direction',
            'Date',
            'Items',
            'Transport (Rp)',
            'Baggage (Rp)',
            'GST (Rp)',
            'Total Cost (Rp)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count($this->rows) + 2;

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C55A11']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$last}:I{$last}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4D6']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        $dataLast = $last - 1;
        foreach (['F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")
                  ->getNumberFormat()->setFormatCode('"Rp "#,##0');
        }

        $sheet->getStyle("A1:I{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Freight Cost';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 14,
            'D' => 14,
            'E' => 8,
            'F' => 18,
            'G' => 16,
            'H' => 14,
            'I' => 20,
        ];
    }

    public function registerEvents(): array
    {
        $projectName = $this->projectName;
        return [
            AfterSheet::class => function (AfterSheet $event) use ($projectName) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:I1');

                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Freight Cost — ' . $projectName);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i'));
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
            },
        ];
    }
}
