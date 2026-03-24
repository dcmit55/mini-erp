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

class CostingWorkmanshipSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
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
                $r['employee'],
                $r['position'],
                $r['date'],
                $r['start_time'],
                $r['end_time'],
                $r['hours'],
                $r['job_order'],
                $r['step']        ?? '-',
                $r['hourly_rate'] ?? 0,
                $r['labor_cost']  ?? 0,
            ];
        }

        $totalHours = array_sum(array_column($this->rows, 'hours'));
        $totalCost  = array_sum(array_column($this->rows, 'labor_cost'));

        $data[] = [
            '', 'TOTAL', '', '', '', '',
            $totalHours,
            '', '', '',
            $totalCost,
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee',
            'Position',
            'Date',
            'Start',
            'End',
            'Hours',
            'Job Order',
            'Step / Task',
            'Hourly Rate (Rp)',
            'Labor Cost (Rp)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count($this->rows) + 2;

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7030A0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$last}:K{$last}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAE0F0']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        $dataLast = $last - 1;
        foreach (['J', 'K'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")
                  ->getNumberFormat()->setFormatCode('"Rp "#,##0');
        }
        $sheet->getStyle("G2:G{$last}")
              ->getNumberFormat()->setFormatCode('0.00');

        $sheet->getStyle("A1:K{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Workmanship Cost';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 24,
            'C' => 18,
            'D' => 14,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 28,
            'I' => 22,
            'J' => 18,
            'K' => 18,
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
                $sheet->setCellValue('A1', 'Workmanship Cost — ' . $projectName);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i'));
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
            },
        ];
    }
}
