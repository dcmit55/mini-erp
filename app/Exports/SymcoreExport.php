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
        $otData = \DB::table('overtime_requests')
            ->whereBetween(\DB::raw('DATE(start_time)'), [$this->startDate, $this->endDate])
            ->where('hr_approval_status', 'approved')
            ->whereNull('deleted_at')
            ->select(
                'employee_id',
                \DB::raw('SUM(net_hours) as total_ot_hours'),
                \DB::raw('GROUP_CONCAT(DISTINCT ot_code ORDER BY ot_code SEPARATOR ", ") as ot_types')
            )
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Attendance: late days, leave hours, absence days
        $attendanceData = \DB::table('daily_attendances')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->select(
                'employee_id',
                \DB::raw('SUM(CASE WHEN late_minutes > 0 AND late_minutes <= 60 THEN 1 ELSE 0 END) as late_days'),
                \DB::raw('SUM(
                    CASE WHEN late_minutes > 60 THEN late_minutes / 60.0 ELSE 0 END +
                    CASE WHEN early_leave_minutes > 0 THEN early_leave_minutes / 60.0 ELSE 0 END
                ) as leave_hours'),
                \DB::raw('SUM(CASE WHEN status = "Alpha" THEN 1 ELSE 0 END) as absence_days')
            )
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Leave days: approved leave requests
        $leaveData = \DB::table('leave_requests')
            ->where(function ($q) {
                $q->whereBetween('start_date', [$this->startDate, $this->endDate])
                  ->orWhereBetween('end_date', [$this->startDate, $this->endDate]);
            })
            ->where('approval_1', 'approved')
            ->where('approval_2', 'approved')
            ->select('employee_id', \DB::raw('SUM(duration) as total_leave_days'))
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
            'Late Days',
            'Leave Hours',
            'Leave Days',
            'Absence Days',
            'Leave Balance',
            'Kasbon',
            'Uniform Deposit',
        ];

        // Data rows
        foreach ($employees as $emp) {
            $ot         = $otData->get($emp->id);
            $attendance = $attendanceData->get($emp->id);
            $leave      = $leaveData->get($emp->id);

            $rows[] = [
                $emp->employee_no,
                $emp->name,
                $ot ? round((float) $ot->total_ot_hours, 2) : 0,
                $ot ? $ot->ot_types : '',
                $attendance ? (int) $attendance->late_days : 0,
                $attendance ? round((float) $attendance->leave_hours, 2) : 0,
                $leave ? (int) $leave->total_leave_days : 0,
                $attendance ? (int) $attendance->absence_days : 0,
                $emp->saldo_cuti ?? 0,
                '',
                '',
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

                // Manual column widths — narrow for numeric, wider for text
                $widths = [
                    'A' => 14,  // Employee ID
                    'B' => 28,  // Employee Name
                    'C' => 10,  // OT Hours
                    'D' => 22,  // OT Type (weekday, sunday, public holiday)
                    'E' => 10,  // Late Days
                    'F' => 12,  // Leave Hours
                    'G' => 10,  // Leave Days
                    'H' => 12,  // Absence Days
                    'I' => 13,  // Leave Balance
                    'J' => 10,  // Kasbon
                    'K' => 15,  // Uniform Deposit
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(22);

                // Freeze header
                $sheet->freezePane('A2');

                // Border pada header
                $sheet->getStyle('A1:K1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF8FA8C8'],
                        ],
                    ],
                ]);

                // Alternating row color (data mulai row 2)
                $lastRow = $sheet->getHighestRow();
                for ($i = 2; $i <= $lastRow; $i++) {
                    $color = ($i % 2 === 0) ? 'FFF5F8FF' : 'FFFFFFFF';
                    $sheet->getStyle("A{$i}:K{$i}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $color],
                        ],
                    ]);
                }

                // Center align numeric columns
                $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E2:K' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
