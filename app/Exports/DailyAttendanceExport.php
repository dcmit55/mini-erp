<?php

namespace App\Exports;

use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DailyAttendanceExport implements
    FromCollection,
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

    public function collection()
    {
        // Load semua karyawan aktif (filter dept/employee jika ada)
        $employees = Employee::with('department')
            ->active()
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->when($this->employeeId,   fn($q) => $q->where('id', $this->employeeId))
            ->orderBy('department_id')
            ->orderBy('name')
            ->get();

        // Load semua record attendance dalam range, key by "employee_id_date"
        $records = DailyAttendance::with(['employee.department'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->when($this->departmentId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $this->departmentId)))
            ->when($this->employeeId,   fn($q) => $q->where('employee_id', $this->employeeId))
            ->get()
            ->keyBy(fn($r) => $r->employee_id . '_' . Carbon::parse($r->date)->format('Y-m-d'));

        // Buat baris: setiap tanggal × setiap karyawan
        $rows = collect();
        foreach (CarbonPeriod::create($this->startDate, $this->endDate) as $date) {
            $dateStr = $date->format('Y-m-d');
            foreach ($employees as $employee) {
                $record = $records->get($employee->id . '_' . $dateStr);
                $rows->push([
                    'employee' => $employee,
                    'date'     => $date->copy(),
                    'record'   => $record,
                ]);
            }
        }

        return $rows;
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
        $employee = $row['employee'];
        $date     = $row['date'];
        $rec      = $row['record'];

        return [
            $this->rowNo,
            $employee->employee_no                ?? '-',
            $employee->name                       ?? '-',
            $employee->department?->name          ?? '-',
            $date->format('Y-m-d'),
            $date->format('l'),
            $rec && $rec->clock_in  ? Carbon::parse($rec->clock_in)->format('H:i:s')  : '-',
            $rec && $rec->clock_out ? Carbon::parse($rec->clock_out)->format('H:i:s') : '-',
            $rec ? ($rec->total_hours           ?? 0) : 0,
            $rec ? ($rec->status                ?? '-') : 'ABSENT',
            $rec ? ($rec->late_minutes          ?? 0) : 0,
            $rec ? ($rec->late_deduction        ?? 0) : 0,
            $rec ? ($rec->early_leave_minutes   ?? 0) : 0,
            $rec ? ($rec->early_leave_deduction ?? 0) : 0,
            $rec ? ($rec->overtime_minutes      ?? 0) : 0,
            $rec ? ($rec->overtime_pay          ?? 0) : 0,
            $rec ? ($rec->remarks               ?? '') : '',
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
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
