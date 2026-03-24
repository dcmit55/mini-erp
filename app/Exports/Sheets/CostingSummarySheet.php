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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CostingSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected array $rows;
    protected array $filters;

    public function __construct(array $rows, array $filters = [])
    {
        $this->rows    = $rows;
        $this->filters = $filters;
    }

    public function array(): array
    {
        $data = [];
        $no   = 1;

        foreach ($this->rows as $r) {
            $data[] = [
                $no++,
                $r['project_name'],
                $r['type_dept']   ?? '-',
                $r['sales']       ?? '-',
                $r['deadline']    ?? '-',
                $r['intl_po'],
                $r['local_po'],
                $r['usage_idr'],
                $r['intl_po'] + $r['local_po'],         // selling price
                $r['usage_idr'],                        // actual cost
                ($r['intl_po'] + $r['local_po']) - $r['usage_idr'], // profit
            ];
        }

        // Grand total row
        $totalIntlPo   = array_sum(array_column($this->rows, 'intl_po'));
        $totalLocalPo  = array_sum(array_column($this->rows, 'local_po'));
        $totalUsage    = array_sum(array_column($this->rows, 'usage_idr'));
        $totalSelling  = $totalIntlPo + $totalLocalPo;
        $totalProfit   = $totalSelling - $totalUsage;

        $data[] = [
            '',
            'GRAND TOTAL',
            '', '', '',
            $totalIntlPo,
            $totalLocalPo,
            $totalUsage,
            $totalSelling,
            $totalUsage,
            $totalProfit,
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Project Name',
            'Type / Dept',
            'Sales / Creator',
            'Deadline',
            'INT\'L PO (Rp)',
            'LOCAL PO (Rp)',
            'Material Usage (Rp)',
            'Selling Price (Rp)',
            'Actual Cost (Rp)',
            'Profit (Rp)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count($this->rows) + 2; // +1 heading +1 grand total

        // Header
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        // Grand total row
        $sheet->getStyle("A{$last}:K{$last}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
        ]);

        // Currency format for numeric columns (F-K), skip header and total
        $dataLast = $last - 1;
        foreach (['F', 'G', 'H', 'I', 'J', 'K'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$dataLast}")
                  ->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("{$col}{$last}")
                  ->getNumberFormat()->setFormatCode('"Rp "#,##0');
        }

        // Alternating row colour for data rows
        for ($i = 2; $i <= $dataLast; $i++) {
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$i}:K{$i}")
                      ->getFill()->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('F2F2F2');
            }
        }

        // Borders for all data
        $sheet->getStyle("A1:K{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Project Summary';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 42,
            'C' => 16,
            'D' => 22,
            'E' => 14,
            'F' => 20,
            'G' => 20,
            'H' => 22,
            'I' => 22,
            'J' => 22,
            'K' => 22,
        ];
    }

    public function registerEvents(): array
    {
        $filters  = $this->filters;
        $rowCount = count($this->rows);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($filters, $rowCount) {
                $sheet = $event->sheet->getDelegate();

                // Freeze header row
                $sheet->freezePane('A2');

                // Auto-filter
                $sheet->setAutoFilter("A1:K1");

                // Insert 3 info rows at top, shift data down
                $sheet->insertNewRowBefore(1, 3);

                $sheet->setCellValue('A1', 'Project Costing Report');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $filterText = 'Filters: ';
                $parts = [];
                if (!empty($filters['department'])) $parts[] = 'Dept: ' . $filters['department'];
                if (!empty($filters['sales']))       $parts[] = 'Sales: ' . $filters['sales'];
                if (!empty($filters['date_from']))   $parts[] = 'From: ' . $filters['date_from'];
                if (!empty($filters['date_to']))     $parts[] = 'To: ' . $filters['date_to'];
                $filterText .= empty($parts) ? 'All Projects' : implode(' | ', $parts);

                $sheet->setCellValue('A2', $filterText);
                $sheet->setCellValue('A3', 'Generated: ' . now()->format('d M Y H:i'));
                $sheet->setCellValue('A4', 'Total Projects: ' . $rowCount);

                $sheet->getStyle('A2:A4')->getFont()->setSize(10)->setItalic(true);
                $sheet->getStyle('A2:A4')->getFont()->getColor()->setRGB('555555');
            },
        ];
    }
}
