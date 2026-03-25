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
        $this->rows = $rows;
        $this->projectName = $projectName;
    }

    // Track row metadata so styles() can colour section headers
    protected array $sectionHeaderRows = [];
    protected array $subtotalRows = [];

    public function array(): array
    {
        $data = [];

        // Split into BT→SG and SG→BT
        $btSg = array_values(array_filter($this->rows, fn($r) => ($r['direction'] ?? '') === 'BT → SG'));
        $sgBt = array_values(array_filter($this->rows, fn($r) => ($r['direction'] ?? '') === 'SG → BT'));
        // Anything else (safety net)
        $other = array_values(array_filter($this->rows, fn($r) => !in_array($r['direction'] ?? '', ['BT → SG', 'SG → BT'])));

        $rowNum = 1; // data row counter (1-based within this array, header added later in registerEvents)

        foreach (
            [
                'BT → SG (Batam to Singapore)' => $btSg,
                'SG → BT (Singapore to Batam)' => $sgBt,
                'Other Directions' => $other,
            ]
            as $sectionTitle => $rows
        ) {
            if (empty($rows)) {
                continue;
            }

            // Section header row
            $this->sectionHeaderRows[] = $rowNum;
            $data[] = [$sectionTitle, '', '', '', '', '', '', '', ''];
            $rowNum++;

            $no = 1;
            $subTransport = 0;
            $subBaggage = 0;
            $subGst = 0;
            $subTotal = 0;

            foreach ($rows as $r) {
                $data[] = [$no++, $r['courier_name'], $r['direction'], $r['date'], $r['items_count'], $r['transport_cost'], $r['baggage_cost'], $r['gst_cost'], $r['total_idr']];
                $subTransport += $r['transport_cost'];
                $subBaggage += $r['baggage_cost'];
                $subGst += $r['gst_cost'];
                $subTotal += $r['total_idr'];
                $rowNum++;
            }

            // Subtotal row for this section
            $this->subtotalRows[] = $rowNum;
            $data[] = ['', 'Subtotal', '', '', '', $subTransport, $subBaggage, $subGst, $subTotal];
            $rowNum++;

            // Blank spacer
            $data[] = ['', '', '', '', '', '', '', '', ''];
            $rowNum++;
        }

        // Grand total
        $this->subtotalRows[] = $rowNum;
        $data[] = ['', 'GRAND TOTAL', '', '', '', array_sum(array_column($this->rows, 'transport_cost')), array_sum(array_column($this->rows, 'baggage_cost')), array_sum(array_column($this->rows, 'gst_cost')), array_sum(array_column($this->rows, 'total_idr'))];

        return $data;
    }

    public function headings(): array
    {
        return ['No', 'Courier', 'Direction', 'Date', 'Items', 'Transport (Rp)', 'Baggage (Rp)', 'GST (Rp)', 'Total Cost (Rp)'];
    }

    public function styles(Worksheet $sheet): array
    {
        // Column header row (row 1)
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C55A11']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Section header rows (+2 offset because registerEvents inserts 2 rows at top)
        foreach ($this->sectionHeaderRows as $r) {
            $excelRow = $r + 2; // +1 for headings row, +2 for info rows inserted by registerEvents
            $sheet->getStyle("A{$excelRow}:I{$excelRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
            ]);
            $sheet->mergeCells("A{$excelRow}:I{$excelRow}");
            $sheet
                ->getStyle("A{$excelRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Subtotal rows
        foreach ($this->subtotalRows as $r) {
            $excelRow = $r + 2;
            $sheet->getStyle("A{$excelRow}:I{$excelRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4D6']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }

        // Currency format on all data rows
        $totalDataRows = count($this->array()) + 2; // rough last row
        foreach (['F', 'G', 'H', 'I'] as $col) {
            $sheet
                ->getStyle("{$col}2:{$col}{$totalDataRows}")
                ->getNumberFormat()
                ->setFormatCode('"Rp "#,##0');
        }

        $sheet->getStyle("A1:I{$totalDataRows}")->applyFromArray([
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
