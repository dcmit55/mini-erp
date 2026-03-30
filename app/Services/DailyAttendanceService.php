<?php

namespace App\Services;

use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\SessionShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyAttendanceService
{
    /**
     * Regenerate daily attendance for a single employee only.
     * Use this from webhooks (per-tap) to avoid looping all 100+ employees.
     */
    public function generateForEmployee(Employee $employee, Carbon $date, $updatedBy = null): void
    {
        $employee->loadMissing('department');
        $dateStr = $date->format('Y-m-d');
        $this->processEmployee($employee, $dateStr, $updatedBy);
    }

    public function generateForDate(Carbon $date, $updatedBy = null): void
    {
        Log::info("===== GENERATING DAILY FOR DATE: " . $date->format('Y-m-d') . " =====");

        $employees = Employee::with('department')->where('status', 'active')->get();
        $dateStr   = $date->format('Y-m-d');

        foreach ($employees as $employee) {
            $this->processEmployee($employee, $dateStr, $updatedBy);
        }

        Log::info("===== FINISHED GENERATING DAILY FOR DATE: " . $date->format('Y-m-d') . " =====\n");
    }

    /**
     * Core logic for a single employee on a single date.
     * Called by both generateForDate (batch) and generateForEmployee (per-tap).
     */
    private function processEmployee(Employee $employee, string $dateStr, $updatedBy = null): void
    {
        $date = Carbon::parse($dateStr);

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('date', $dateStr)
            ->orderBy('clock_in')
            ->get();

        if ($logs->isNotEmpty()) {
            $clockIn  = $logs->min('clock_in');
            $clockOut = $logs->max('clock_out');

            $detectedShift = null;
            if ($clockIn && $employee->department_id) {
                $clockInForShift = Carbon::parse($clockIn)->format('H:i:s');
                $detectedShift   = SessionShift::detectFromClockIn(
                    $employee->department_id,
                    $clockInForShift,
                    (bool) $employee->is_wna
                );
            }

            // Fallback: jika tidak terdeteksi, assign shift default berdasarkan departemen
            if (! $detectedShift) {
                $detectedShift = $this->getFallbackShift($employee);
            }

            $status  = $this->determineStatus($employee, $date, $clockIn, $clockOut, $detectedShift);
            $remarks = null;

            if (!$clockIn && $clockOut) {
                $remarks = 'Missing clock in';
            } elseif ($clockIn && !$clockOut) {
                $remarks = 'Missing clock out';
            }

            $totalHours = null;
            if ($clockIn && $clockOut) {
                $clockInTime  = Carbon::parse($clockIn)->setDate($date->year, $date->month, $date->day);
                $clockOutTime = Carbon::parse($clockOut)->setDate($date->year, $date->month, $date->day);
                $totalHours   = $clockOutTime->diffInMinutes($clockInTime) / 60;
            }

            $clockInFormatted  = $clockIn  ? Carbon::parse($clockIn)->format('H:i:s')  : null;
            $clockOutFormatted = $clockOut ? Carbon::parse($clockOut)->format('H:i:s') : null;

            try {
                if ($detectedShift) {
                    Log::info("Shift detected for {$employee->name}: {$detectedShift->type_of_shift}");
                }

                $daily = DailyAttendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $dateStr],
                    [
                        'clock_in'         => $clockInFormatted,
                        'clock_out'        => $clockOutFormatted,
                        'total_hours'      => $totalHours,
                        'status'           => $status,
                        'remarks'          => $remarks,
                        'updated_by'       => $updatedBy,
                        'session_shift_id' => $detectedShift?->id,
                    ]
                );

                if ($clockIn) {
                    $this->calculateAttendanceFields($daily, $detectedShift);
                    if ($clockOut) {
                        $this->recalcActualWorkHours($daily);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to save daily for employee {$employee->id}: " . $e->getMessage());
            }
        } else {
            $leave = LeaveRequest::where('employee_id', $employee->id)
                ->where('start_date', '<=', $dateStr)
                ->where('end_date', '>=', $dateStr)
                ->where('approval_1', 'approved')
                ->where('approval_2', 'approved')
                ->first();

            $status  = $leave ? $this->mapLeaveTypeToStatus($leave->type) : 'Alpha';
            $remarks = $leave ? $leave->reason : null;

            $defaultShift = $this->getFallbackShift($employee);

            try {
                DailyAttendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $dateStr],
                    [
                        'clock_in'         => null,
                        'clock_out'        => null,
                        'total_hours'      => null,
                        'status'           => $status,
                        'remarks'          => $remarks,
                        'updated_by'       => $updatedBy,
                        'session_shift_id' => $defaultShift?->id,
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Failed to save alpha for employee {$employee->id}: " . $e->getMessage());
            }
        }
    }

    // ─── Helper: shift default berdasarkan departemen ────────────────────────

    /** @var array<string, SessionShift|null> */
    private array $shiftFallbackCache = [];

    /**
     * Kembalikan shift default berdasarkan nama departemen karyawan.
     *  - Costume   → c8
     *  - Lainnya   → a9
     */
    private function getFallbackShift(Employee $employee): ?SessionShift
    {
        $deptName    = strtolower($employee->department?->name ?? '');
        $typeOfShift = str_contains($deptName, 'costume') ? 'c8' : 'a9';

        if (! array_key_exists($typeOfShift, $this->shiftFallbackCache)) {
            $this->shiftFallbackCache[$typeOfShift] = SessionShift::where('type_of_shift', $typeOfShift)
                ->where('is_active', true)
                ->first();
        }

        return $this->shiftFallbackCache[$typeOfShift];
    }

    // ─── Helper: jam standar per hari ────────────────────────────────────────

    private function getStandardTimes($policy, string $dayOfWeek): array
    {
        if (in_array($dayOfWeek, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])) {
            return [$policy->weekday_start, $policy->weekday_end];
        } elseif ($dayOfWeek === 'saturday') {
            return [$policy->saturday_start, $policy->saturday_end];
        } elseif ($dayOfWeek === 'sunday') {
            return [$policy->sunday_start, $policy->sunday_end];
        }
        return [null, null];
    }

    // ─── Helper: tentukan status (Present / Late / Alpha) ────────────────────

    public function determineStatus($employee, Carbon $date, $clockIn, $clockOut, ?SessionShift $shift = null): string
    {
        if (!$clockIn && !$clockOut) {
            return 'Alpha';
        }
        if (!$clockIn && $clockOut) {
            return 'Present';
        }

        // If a session shift is detected, use its start_time as the reference (fixed shift departments)
        if ($shift) {
            $standardStart     = $shift->start_time;
            $clockInTime       = Carbon::parse($clockIn)->setDate($date->year, $date->month, $date->day);
            $standardStartTime = Carbon::parse($standardStart)->setDate($date->year, $date->month, $date->day);
            $lateSeconds       = $clockInTime->diffInSeconds($standardStartTime, false) * -1;
            if ($lateSeconds <= 0) {
                return 'Present';
            }
            return $lateSeconds <= 239 ? 'Present' : 'Late'; // toleransi 3 menit 59 detik, 08:04:00 sudah Late
        }

        $policy = $employee->workPolicy;
        if (!$policy) {
            return 'Present';
        }

        $dayOfWeek = strtolower($date->format('l'));
        [$standardStart] = $this->getStandardTimes($policy, $dayOfWeek);

        if (!$standardStart || trim($standardStart) === '00:00:00') {
            return 'Present';
        }

        $clockInTime       = Carbon::parse($clockIn)->setDate($date->year, $date->month, $date->day);
        $standardStartTime = Carbon::parse($standardStart)->setDate($date->year, $date->month, $date->day);

        // Toleransi keterlambatan: <= 4 menit masih dianggap Present
        $lateSeconds = $clockInTime->diffInSeconds($standardStartTime, false) * -1;
        if ($lateSeconds <= 0) {
            return 'Present';
        }
        return $lateSeconds <= 239 ? 'Present' : 'Late'; // toleransi 3 menit 59 detik, 08:04:00 sudah Late // 240 detik = 4 menit tepat
    }

    // ─── Helper: peta jenis cuti → status ────────────────────────────────────

    public function mapLeaveTypeToStatus(string $type): string
    {
        return match(strtoupper($type)) {
            'ANNUAL'               => 'Annual Leave',
            'SICK', 'MENSTRUATION' => 'Sick Leave',
            'MATERNITY'            => 'Maternity Leave',
            'PATERNITY'            => 'Paternity Leave',
            'WEDDING', 'SONWED'    => 'Wedding Leave',
            'BIRTHCHILD'           => 'Birth Leave',
            'DEATH', 'DEATH_2'     => 'Bereavement Leave',
            'BAPTISM'              => 'Child Event Leave',
            'HAJJ'                 => 'Hajj Leave',
            'UNPAID'               => 'Unpaid Leave',
            'EARLY_LEAVE'          => 'Early Leave',
            'PERMISSION_OUT'       => 'Permission Out',
            default                => 'Excused',
        };
    }

    // ─── Helper: hitung potongan, lembur, dll. ───────────────────────────────

    public function calculateAttendanceFields(DailyAttendance $attendance, ?SessionShift $shift = null): void
    {
        $employee = $attendance->employee;

        $monthlySalary = $employee->salary ?? 0;
        $hourlyRate    = $monthlySalary > 0 ? $monthlySalary / 173 : 0;

        $dayOfWeek = strtolower($attendance->date->format('l'));

        // Use session shift times when available (fixed-shift departments)
        if ($shift) {
            $standardStart = $shift->start_time;
            $standardEnd   = $shift->end_time;
        } else {
            $policy = $employee->workPolicy;
            if (!$policy) {
                return;
            }
            [$standardStart, $standardEnd] = $this->getStandardTimes($policy, $dayOfWeek);
        }

        if (
            !$standardStart || !$standardEnd
            || trim($standardStart) === '00:00:00'
            || trim($standardEnd) === '00:00:00'
        ) {
            return;
        }

        if ($attendance->clock_in && $attendance->clock_out) {
            $start = Carbon::parse($attendance->clock_in)
                ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);
            $end   = Carbon::parse($attendance->clock_out)
                ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);

            $standardStartTime = Carbon::parse($standardStart)
                ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);
            $standardEndTime   = Carbon::parse($standardEnd)
                ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);

            $attendance->total_hours           = $end->diffInMinutes($start) / 60;
            $attendance->late_minutes          = 0;
            $attendance->late_deduction        = 0;
            $attendance->early_leave_minutes   = 0;
            $attendance->early_leave_deduction = 0;
            $attendance->overtime_minutes      = 0;
            $attendance->overtime_pay          = 0;

            // Keterlambatan
            if ($start->gt($standardStartTime)) {
                $lateMinutes              = $standardStartTime->diffInMinutes($start);
                $attendance->late_minutes = $lateMinutes;

                if ($dayOfWeek === 'saturday') {
                    // Aturan khusus Sabtu: telat 20-39 menit = 30 menit, >= 40 menit = 60 menit
                    if ($lateMinutes < 20) {
                        $attendance->late_deduction = 0;
                    } elseif ($lateMinutes <= 39) {
                        $attendance->late_deduction = (30 / 60) * $hourlyRate;
                    } else {
                        $attendance->late_deduction = (60 / 60) * $hourlyRate;
                    }
                } else {
                    if ($lateMinutes <= 3) {
                        $attendance->late_deduction = 0;
                    } elseif ($lateMinutes < 60) {
                        $attendance->late_deduction = 25000;
                    } else {
                        $attendance->late_deduction = ($lateMinutes / 60) * $hourlyRate;
                    }
                }
            }

            // Pulang awal
            if ($end->lt($standardEndTime)) {
                $earlyMinutes                      = $end->diffInMinutes($standardEndTime);
                $attendance->early_leave_minutes   = $earlyMinutes;
                $attendance->early_leave_deduction = ($earlyMinutes / 60) * $hourlyRate;
            }

            // Lembur
            if ($end->gt($standardEndTime)) {
                $overtimeMinutes             = $standardEndTime->diffInMinutes($end);
                $attendance->overtime_minutes = $overtimeMinutes;
                $attendance->overtime_pay     = ($overtimeMinutes / 60) * $hourlyRate * 1.5;
            }
        } else {
            // Hanya clock_in: catat keterlambatan saja
            if ($attendance->clock_in && !$attendance->clock_out) {
                $start = Carbon::parse($attendance->clock_in)
                    ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);
                $standardStartTime = Carbon::parse($standardStart)
                    ->setDate($attendance->date->year, $attendance->date->month, $attendance->date->day);

                if ($start->gt($standardStartTime)) {
                    $lateMinutes              = $standardStartTime->diffInMinutes($start);
                    $attendance->late_minutes = $lateMinutes;

                    if ($dayOfWeek === 'saturday') {
                        // Aturan khusus Sabtu: telat 20-39 menit = 30 menit, >= 40 menit = 60 menit
                        if ($lateMinutes < 20) {
                            $attendance->late_deduction = 0;
                        } elseif ($lateMinutes <= 39) {
                            $attendance->late_deduction = (30 / 60) * $hourlyRate;
                        } else {
                            $attendance->late_deduction = (60 / 60) * $hourlyRate;
                        }
                    } else {
                        if ($lateMinutes <= 3) {
                            $attendance->late_deduction = 0;
                        } elseif ($lateMinutes < 60) {
                            $attendance->late_deduction = 25000;
                        } else {
                            $attendance->late_deduction = ($lateMinutes / 60) * $hourlyRate;
                        }
                    }
                }
            }
            $attendance->total_hours = null;
        }

        $attendance->save();
    }

    /**
     * Hitung actual_work_hours dari clock_in_datetime / clock_out_datetime.
     * Dipanggil setelah record disimpan agar tidak bergantung pada GENERATED column.
     */
    public function recalcActualWorkHours(DailyAttendance $attendance): void
    {
        if (!$attendance->clock_in_datetime || !$attendance->clock_out_datetime) {
            return;
        }

        $grossMins   = Carbon::parse($attendance->clock_in_datetime)
            ->diffInMinutes(Carbon::parse($attendance->clock_out_datetime));
        $breakMins   = (int) ($attendance->total_break_mins ?? 0);
        $netMins     = max(0, $grossMins - $breakMins);
        $actualHours = round($netMins / 60, 2);

        $attendance->actual_work_hours = $actualHours;
        $attendance->saveQuietly();
    }
}
