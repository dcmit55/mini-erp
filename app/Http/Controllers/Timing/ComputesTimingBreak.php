<?php

namespace App\Http\Controllers\Timing;

use App\Models\FingerprintLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\Employee;
use App\Models\Production\Timing;
use Carbon\Carbon;

/**
 * Trait ComputesTimingBreak
 *
 * Provides a single method that calculates net work duration in minutes
 * by subtracting break-window overlap from the gross elapsed time.
 *
 * Two scenarios:
 *   A) Scheduler ran → auto-freeze/unfreeze adjusted start_time AND set total_paused_minutes.
 *      Gross (end − adjusted_start) is already break-free.  No further subtraction needed.
 *
 *   B) Scheduler was down → total_paused_minutes = 0, start_time is original.
 *      We compute the overlap between [start_time, end_time] and each break window
 *      defined on the employee's SessionShift, then subtract it.
 */
trait ComputesTimingBreak
{
    /**
     * Return net work minutes AND break minutes deducted, in one call (single DB query).
     *
     * Returns: ['net' => int, 'break' => int]
     *
     * @param  Timing  $timing    The timing record (employee_id, start_time, total_paused_minutes)
     * @param  string  $today     Y-m-d string
     * @param  string  $startTime H:i:s  (may be auto-adjusted after scheduler unfreeze)
     * @param  string  $endTime   H:i:s
     * @return array{net: int, break: int}
     */
    protected function computeTimingDuration(
        Timing $timing,
        string $today,
        string $startTime,
        string $endTime
    ): array {
        $start = Carbon::parse($today . ' ' . $startTime);
        $end   = Carbon::parse($today . ' ' . $endTime);
        $gross = max(0, $start->diffInMinutes($end));

        // Scenario A: scheduler ran → start_time already adjusted, total_paused_minutes records the break.
        if (($timing->total_paused_minutes ?? 0) > 0) {
            return ['net' => $gross, 'break' => $timing->total_paused_minutes];
        }

        // Scenario B: scheduler was down → compute break overlap as fallback.
        $breakMins = $this->computeBreakOverlapMinutes($timing->employee_id, $today, $startTime, $endTime);

        return ['net' => max(0, $gross - $breakMins), 'break' => $breakMins];
    }

    /**
     * Convenience wrapper — returns only net minutes (for callers that don't need break amount).
     */
    protected function netDurationMinutes(
        Timing $timing,
        string $today,
        string $startTime,
        string $endTime
    ): int {
        return $this->computeTimingDuration($timing, $today, $startTime, $endTime)['net'];
    }

    /**
     * Check if an enrolled employee has tapped IN on the fingerprint machine today.
     *
     * Returns:
     *   null  — employee is NOT enrolled (biometric_enrolled_at is null) → skip validation
     *   true  — enrolled and has a valid tap-in log today (status 0=IN or 4=OT-IN)
     *   false — enrolled but NO tap-in found today → block session start
     */
    protected function checkFingerprintTapIn(Employee $employee, string $today): ?bool
    {
        if (is_null($employee->biometric_enrolled_at)) {
            return null;
        }

        // Normalize PIN: strip 'DCM-' prefix and leading zeros (matches cloud_id in fingerprint_logs)
        $pin = ltrim(str_replace('DCM-', '', $employee->employee_no ?? ''), '0') ?: '0';

        $tapped = FingerprintLog::where('cloud_id', $pin)
            ->whereDate('event_time', $today)
            ->where(function ($q) {
                $q->where('payload->status', 0)
                  ->orWhere('payload->status', 4);
            })
            ->exists();

        return $tapped;
    }

    /**
     * Calculate how many minutes of [workStart, workEnd] fall inside the employee's break windows.
     */
    private function computeBreakOverlapMinutes(
        int    $employeeId,
        string $today,
        string $workStart,
        string $workEnd
    ): int {
        $daily = DailyAttendance::where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->with('sessionShift')
            ->first();

        $shift = $daily?->sessionShift;
        if (! $shift) {
            return 0;
        }

        $total = 0;

        foreach ([
            [$shift->break_start,  $shift->break_end],
            [$shift->break2_start, $shift->break2_end],
        ] as [$bStart, $bEnd]) {
            if (! $bStart || ! $bEnd) {
                continue;
            }

            // Overlap = max(0, min(workEnd, breakEnd) − max(workStart, breakStart))
            $overlapStart = max($workStart, $bStart);
            $overlapEnd   = min($workEnd,   $bEnd);

            if ($overlapEnd > $overlapStart) {
                $s = Carbon::parse($today . ' ' . $overlapStart);
                $e = Carbon::parse($today . ' ' . $overlapEnd);
                $total += $s->diffInMinutes($e);
            }
        }

        return $total;
    }
}
