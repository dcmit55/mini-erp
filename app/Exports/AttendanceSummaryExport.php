<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceSummaryExport implements FromArray, WithStyles, WithTitle
{
    protected $employees;
    protected int $daysInMonth;
    protected array $dayInfo;
    protected $dailiesMap;
    protected array $summary;
    protected int $month;
    protected int $year;

    // Status Map: [Singkatan, Warna Hex]
    protected const STATUS_MAP = [
        'Present'          => ['P',    '86efac'],
        'Late'             => ['L',    'fde047'],
        'Less Hours'       => ['LH',   'fb923c'],
        'Late, Less Hours' => ['L+LH', 'fde047'],
        'Alpha'            => ['A',    'f87171'],
        'Annual Leave'     => ['Lv',   '93c5fd'],
        'Sick Leave'       => ['Sk',   '67e8f9'],
        'Unpaid Leave'     => ['Up',   'a5b4fc'],
        'Early Leave'      => ['EL',   'a78bfa'],
        'Permission Out'   => ['Po',   'a78bfa'],
    ];

    // Legend Map: Label => Warna Hex
    protected const LEGEND = [
        'Present'    => '16a34a',
        'Late'       => 'ca8a04',
        'Less Hours' => 'fb923c',
        'Alpha'      => 'f87171',
        'Ann.Leave'  => '93c5fd',
        'Sick'       => '67e8f9',
        'Oth.Leave'  => 'a78bfa',
        'Unpaid'     => 'a5b4fc',
        'Sun'        => '94a3b8',
        'Nat.Hol'    => 'fda4af',
        'Co.Hol'     => '5eead4',
        'Hol-Ded'    => 'f9a8d4',
        'Hol-Unp'    => 'fcd34d',
        'No Data'    => 'f1f5f9',
    ];

    public function __construct($employees, int $daysInMonth, array $dayInfo, $dailiesMap, array $summary, int $month, int $year)
    {
        $this->employees   = $employees->values();
        $this->daysInMonth = $daysInMonth;
        $this->dayInfo     = $dayInfo;
        $this->dailiesMap  = $dailiesMap;
        $this->summary     = $summary;
        $this->month       = $month;
        $this->year        = $year;
    }

    public function title(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('M Y');
    }

    public function array(): array
    {
        $rows = [];

        // Baris 1: Judul
        $rows[] = ['Attendance Summary — ' . Carbon::create($this->year, $this->month, 1)->isoFormat('MMMM YYYY')];

        // Baris 2: Nomor Tanggal
        $row2 = ['Employee'];
        for ($d = 1; $d <= $this->daysInMonth; $d++) { $row2[] = $d; }
        $row2[] = 'P'; $row2[] = 'L'; $row2[] = 'A'; $row2[] = 'Cuti';
        $rows[] = $row2;

        // Baris 3: Nama Hari
        $row3 = [''];
        for ($d = 1; $d <= $this->daysInMonth; $d++) { $row3[] = $this->dayInfo[$d]['dayName']; }
        $row3[] = 'Present'; $row3[] = 'Late'; $row3[] = 'Alpha'; $row3[] = 'Leave';
        $rows[] = $row3;

        // Baris 4 s/d (n): Data Karyawan
        foreach ($this->employees as $emp) {
            $s   = $this->summary[$emp->id];
            $row = [$emp->name];

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $info   = $this->dayInfo[$d];
                $record = $this->dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                $status = $record ? trim($record->status) : null;

                if ($info['isSunday'] || $info['national'] || $info['company'] || !$status) {
                    $row[] = '';
                } else {
                    $row[] = self::STATUS_MAP[$status][0] ?? 'Ot';
                }
            }

            $row[] = $s['present'] + $s['late'];
            $row[] = $s['late'];
            $row[] = $s['alpha'];
            $row[] = $s['annual'] + $s['sick'] + $s['leave_other'];
            $rows[] = $row;
        }

        // Keterangan Legend (Harus sinkron dengan styles)
        $rows[] = ['']; // Baris pemisah kosong
        $rows[] = ['Keterangan:'];
        foreach (array_keys(self::LEGEND) as $label) {
            $rows[] = [$label];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $empCount  = $this->employees->count();
        $dataEnd   = $empCount + 3; 
        $totalCols = $this->daysInMonth + 5;
        $lastCol   = Coordinate::stringFromColumnIndex($totalCols);

        // --- Style Judul ---
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
        ]);

        // --- Style Header Kolom ---
        $sheet->getStyle('A2:' . $lastCol . '3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);

        // --- Warna Libur ---
        $dayColColor = [];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $info = $this->dayInfo[$d];
            if ($info['isSunday']) { $dayColColor[$d] = '94a3b8'; }
            elseif ($info['national']) { $dayColColor[$d] = 'fda4af'; }
            elseif ($info['company']) {
                $dayColColor[$d] = match($info['company']->type) {
                    'paid_leave_deduction' => 'f9a8d4',
                    'unpaid'               => 'fcd34d',
                    default                => '5eead4',
                };
            }
        }

        // --- Looping Data Karyawan (Pewarnaan Kotak Absensi) ---
        for ($i = 0; $i < $empCount; $i++) {
            $rowNum = $i + 4;
            $emp = $this->employees[$i];

            // Nama Karyawan
            $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $colNum = $d + 1;
                $colStr = Coordinate::stringFromColumnIndex($colNum);
                $info   = $this->dayInfo[$d];
                $record = $this->dailiesMap->get($emp->id . '_' . $info['date'])?->first();
                $status = $record ? trim($record->status) : null;

                $bg = 'FFFFFF'; // Putih default
                if (isset($dayColColor[$d])) {
                    $bg = $dayColColor[$d];
                } elseif ($status && isset(self::STATUS_MAP[$status])) {
                    $bg = self::STATUS_MAP[$status][1];
                } elseif ($status) {
                    $bg = 'f1f5f9';
                }

                $sheet->getStyle($colStr . $rowNum)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF' . strtoupper($bg)],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                ]);
            }

            // Kolom Ringkasan (P, L, A, Cuti)
            $summaryColors = ['86efac', 'fde047', 'f87171', '93c5fd'];
            foreach ($summaryColors as $idx => $sColor) {
                $sCol = Coordinate::stringFromColumnIndex($this->daysInMonth + 2 + $idx);
                $sheet->getStyle($sCol . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . strtoupper($sColor)]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
            }
        }

        // --- Legend Section (Keterangan) ---
        $keteranganRow = $empCount + 5;
        $sheet->getStyle('A' . $keteranganRow)->getFont()->setBold(true);

        $legendStart = $empCount + 6;
        $lIdx = 0;
        foreach (self::LEGEND as $label => $lColor) {
            $currentRow = $legendStart + $lIdx;
            $sheet->getStyle('A' . $currentRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . strtoupper($lColor)],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => in_array(strtolower($lColor), ['16a34a', 'ca8a04', '94a3b8', '1e293b']) ? 'FFFFFFFF' : 'FF000000']
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
            ]);
            $lIdx++;
        }

        // Ukuran Kolom
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->freezePane('B4');

        return [];
    }
}