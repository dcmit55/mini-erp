<?php

namespace App\Exports;

use App\Models\Hr\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB; // Tambahkan ini

class SymcoreExport implements FromArray, WithStyles, WithEvents
{
    protected string $startDate;
    protected string $endDate;
    protected ?int   $departmentId;

    public function __construct(string $startDate, string $endDate, ?int $departmentId = null)
    {
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
        $this->departmentId = $departmentId;
    }

    public function array(): array
    {
        $employees = Employee::where('status', 'active')
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->orderBy('employee_no')
            ->get();

        // OT data: approved HR only
        $otData = DB::table('overtime_requests')
            ->whereBetween(DB::raw('DATE(start_time)'), [$this->startDate, $this->endDate])
            ->where('hr_approval_status', 'approved')
            ->whereNull('deleted_at')
            ->select(
                'employee_id',
                DB::raw('COALESCE(SUM(net_hours), 0) as total_ot_hours'),
                DB::raw('GROUP_CONCAT(DISTINCT ot_code ORDER BY ot_code SEPARATOR ", ") as ot_types')
            )
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Attendance: late days (weekday/saturday split), leave hours, absence, hadir
        $attendanceData = DB::table('daily_attendances')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->select(
                'employee_id',
                DB::raw('COALESCE(SUM(CASE WHEN late_minutes > 0 AND DAYOFWEEK(date) IN (2,3,4,5,6) THEN 1 ELSE 0 END), 0) as late_weekday_days'),
                DB::raw('COALESCE(SUM(
                    CASE WHEN late_minutes > 60 THEN late_minutes / 60.0 ELSE 0 END +
                    CASE WHEN early_leave_minutes > 0 THEN early_leave_minutes / 60.0 ELSE 0 END
                ), 0) as leave_hours'),
                DB::raw('COALESCE(SUM(CASE WHEN status = "Alpha" THEN 1 ELSE 0 END), 0) as absence_days'),
                DB::raw('COALESCE(SUM(CASE WHEN status IN ("Present","Late") THEN 1 ELSE 0 END), 0) as attendance_days'),
                DB::raw('COALESCE(SUM(CASE WHEN late_minutes BETWEEN 20 AND 39 AND DAYOFWEEK(date) = 7 THEN 1 ELSE 0 END), 0) as late_sat_20_39'),
                DB::raw('COALESCE(SUM(CASE WHEN late_minutes >= 40 AND DAYOFWEEK(date) = 7 THEN 1 ELSE 0 END), 0) as late_sat_40_plus')
            )
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Kasbon: total outstanding (jumlah_disetujui - total dibayar) untuk kasbon aktif
        $kasbonData = DB::table('kasbon_requests')
            ->whereIn('kasbon_requests.status', ['approved', 'disbursed', 'repaying'])
            ->leftJoin('kasbon_installments', 'kasbon_requests.id', '=', 'kasbon_installments.kasbon_id')
            ->select(
                'kasbon_requests.employee_id',
                DB::raw('COALESCE(SUM(kasbon_requests.jumlah_disetujui), 0) - COALESCE(SUM(kasbon_installments.jumlah_dibayar), 0) as total_outstanding')
            )
            ->groupBy('kasbon_requests.employee_id')
            ->get()
            ->keyBy('employee_id');

        // Leave data: split by type, fully approved (approval_1 + approval_2)
        $leaveData = DB::table('leave_requests')
            ->where(function ($q) {
                $q->whereBetween('start_date', [$this->startDate, $this->endDate])
                  ->orWhereBetween('end_date', [$this->startDate, $this->endDate]);
            })
            ->where('approval_1', 'approved')
            ->where('approval_2', 'approved')
            ->select(
                'employee_id',
                DB::raw('COALESCE(SUM(duration), 0) as total_leave_days'),
                DB::raw('COALESCE(SUM(CASE WHEN type IN ("SICK","MENSTRUATION") THEN duration ELSE 0 END), 0) as sick_days'),
                DB::raw('COALESCE(SUM(CASE WHEN type = "UNPAID" THEN duration ELSE 0 END), 0) as unpaid_days'),
                DB::raw('COALESCE(SUM(CASE WHEN type IN ("WEDDING","SONWED","BIRTHCHILD","DEATH","DEATH_2","BAPTISM","PATERNITY","MATERNITY","HAJJ") THEN duration ELSE 0 END), 0) as alasan_penting_days')
            )
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $rows = [];

        // Row 1: Headers
        $rows[] = [
            'Employee ID',
            'Employee Name',
            'OT Hours',
            'OT Type',
            'Late Weekday Days',
            'Leave Hours',
            'Unpaid Leave Days',
            'Absence Days',
            'Attendance',
            'Sick',
            'Leave Days',
            'Regular Leave',
            'Leave Balance',
            'Uniform Depo',
            'Late Sat 20-39min Days',
            'Late Sat 40+min Days',
            'Penalty (Rp)',
            'Kasbon (Rp)',
        ];

        // Data rows
        foreach ($employees as $emp) {
            $ot         = $otData->get($emp->id);
            $attendance = $attendanceData->get($emp->id);
            $leave      = $leaveData->get($emp->id);
            $kasbon     = $kasbonData->get($emp->id);

            $rows[] = [
                $emp->employee_no,
                $emp->name,
                $ot ? round((float) $ot->total_ot_hours, 2) : 0,
                $ot && $ot->ot_types ? $ot->ot_types : '',
                $attendance ? (int) $attendance->late_weekday_days : 0,
                $attendance ? round((float) $attendance->leave_hours, 2) : 0,
                $leave ? round((float) $leave->unpaid_days, 1) : 0,
                $attendance ? (int) $attendance->absence_days : 0,
                $attendance ? (int) $attendance->attendance_days : 0,
                $leave ? round((float) $leave->sick_days, 1) : 0,
                $leave ? round((float) $leave->total_leave_days, 1) : 0,
                $leave ? round((float) $leave->alasan_penting_days, 1) : 0,
                $emp->saldo_cuti ?? 0,
                '',   // Uniform Depo — data belum tersedia
                $attendance ? (int) $attendance->late_sat_20_39 : 0,
                $attendance ? (int) $attendance->late_sat_40_plus : 0,
                '',   // Penalty — data belum tersedia
                $kasbon ? (float) $kasbon->total_outstanding : 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A5F'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set column widths
                $widths = [
                    'A' => 14,  // Employee ID
                    'B' => 28,  // Employee Name
                    'C' => 10,  // OT Hours
                    'D' => 22,  // OT Type
                    'E' => 18,  // Late Weekday Days
                    'F' => 12,  // Leave Hours
                    'G' => 18,  // Unpaid Leave Days
                    'H' => 13,  // Absence Days
                    'I' => 12,  // Attendance
                    'J' => 10,  // Sick
                    'K' => 12,  // Leave Days
                    'L' => 22,  // Regular Leave
                    'M' => 14,  // Leave Balance
                    'N' => 14,  // Uniform Depo
                    'O' => 22,  // Late Sat 20-39min Days
                    'P' => 20,  // Late Sat 40+min Days
                    'Q' => 20,  // Penalty (Rp)
                    'R' => 14,  // Kasbon (Rp)
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(22);

                // Freeze header
                $sheet->freezePane('A2');

                // Apply border to header
                $sheet->getStyle('A1:R1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF8FA8C8'],
                        ],
                    ],
                ]);

                // Get last row with data
                $lastRow = $sheet->getHighestRow();
                
                // Apply borders to all data cells
                if ($lastRow > 1) {
                    $sheet->getStyle('A1:R' . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFD0D0D0'],
                            ],
                        ],
                    ]);
                }

                // Alternating row color (data mulai row 2)
                for ($i = 2; $i <= $lastRow; $i++) {
                    $color = ($i % 2 === 0) ? 'FFF5F8FF' : 'FFFFFFFF';
                    $sheet->getStyle("A{$i}:R{$i}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $color],
                        ],
                    ]);
                }

                // Center align numeric columns
                $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E2:R' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Left align text columns (Employee ID and Name)
                $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                
                // Auto-size rows for better readability
                foreach (range(2, $lastRow) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }
            },
        ];
    }
}