<?php

namespace App\Exports;

use App\Models\Hr\DailyAttendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DailyAttendanceExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles,
    WithColumnFormatting
{
    protected string  $startDate;
    protected string  $endDate;
    protected ?int    $departmentId;
    protected ?int    $employeeId;
    protected int     $rowNo = 0;

    public function __construct(
        string $startDate,
        string $endDate,
        ?int   $departmentId = null,
        ?int   $employeeId   = null
    ) {
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
        $this->departmentId = $departmentId;
        $this->employeeId   = $employeeId;
    }

    // ── Data ────────────────────────────────────────────────────────────────────

    public function query()
    {
        return DailyAttendance::with(['employee.department'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->when($this->departmentId, function ($q) {
                $q->whereHas('employee', fn($e) => $e->where('department_id', $this->departmentId));
            })
            ->when($this->employeeId, fn($q) => $q->where('employee_id', $this->employeeId))
            ->orderBy('date')
            ->orderBy('employee_id');
    }

    // ── Header ──────────────────────────────────────────────────────────────────

    public function headings(): array
    {
        return [
            'No',
            'Employee No',
            'Employee Name',
            'Department',
            'Date',
            'Day',
            'Clock In',
            'Clock Out',
            'Total Hours',
            'Status',
            'Late (min)',
            'Late Deduction (Rp)',
            'Early Leave (min)',
            'Early Leave Deduction (Rp)',
            'Overtime (min)',
            'Overtime Pay (Rp)',
            'Remarks',
        ];
    }

    // ── Baris data ──────────────────────────────────────────────────────────────

    public function map($row): array
    {
        $this->rowNo++;

        return [
            $this->rowNo,
            $row->employee?->employee_no      ?? '-',
            $row->employee?->name             ?? '-',
            $row->employee?->department?->name ?? '-',
            $row->date?->format('Y-m-d')      ?? '-',
            $row->date?->format('l')           ?? '-',
            $row->clock_in  ? \Carbon\Carbon::parse($row->clock_in)->format('H:i:s')  : '-',
            $row->clock_out ? \Carbon\Carbon::parse($row->clock_out)->format('H:i:s') : '-',
            $row->total_hours            ?? 0,
            $row->status                 ?? '-',
            $row->late_minutes           ?? 0,
            $row->late_deduction         ?? 0,
            $row->early_leave_minutes    ?? 0,
            $row->early_leave_deduction  ?? 0,
            $row->overtime_minutes       ?? 0,
            $row->overtime_pay           ?? 0,
            $row->remarks                ?? '',
        ];
    }

    // ── Style ───────────────────────────────────────────────────────────────────

    public function title(): string
    {
        return 'Daily Attendance';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header bold + background abu
            1 => [
                'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'    => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Late Deduction
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Early Leave Deduction
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Overtime Pay
        ];
    }
}
