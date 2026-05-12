<?php

namespace App\Services\Timing;

use App\Models\Hr\DailyAttendance;
use App\Models\Hr\SessionShift;
use App\Models\Production\Timing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TimingBreakService
{
    /**
     * Freeze active timings in break window, unfreeze when break ends.
     * Safe to call frequently — uses a 45-second cache lock to avoid double-processing.
     */
    public function run(): void
    {
        // Prevent concurrent duplicate runs (e.g. multiple AJAX calls at same time)
        if (Cache::has('timing_break_last_run')) {
            return;
        }
        Cache::put('timing_break_last_run', true, now()->addSeconds(5));

        $now   = now()->format('H:i:s');
        $today = today()->format('Y-m-d');

        // ── 1. Freeze active timings now inside a break window ──────────────
        $activeTimings = Timing::whereDate('tanggal', $today)
            ->where('status', 'on progress')
            ->whereNull('end_time')
            ->with('employee.department')
            ->get();

        foreach ($activeTimings as $timing) {
            $shift = $this->resolveShift($timing, $today);
            if (! $shift || ! $this->isInBreak($shift, $now)) {
                continue;
            }
            $this->freezeForBreak($timing);
        }

        // ── 2. Unfreeze auto-paused timings whose break window ended ────────
        $frozenTimings = Timing::whereDate('tanggal', $today)
            ->where('status', 'frozen')
            ->whereNull('end_time')
            ->with('employee.department')
            ->get();

        foreach ($frozenTimings as $timing) {
            $deptData = $timing->department_specific_data ?? [];
            if (empty($deptData['auto_break_paused'])) {
                continue; // manually frozen — leave it alone
            }

            $shift = $this->resolveShift($timing, $today);
            if ($shift && $this->isInBreak($shift, $now)) {
                continue; // still within break window
            }

            $this->unfreezeAfterBreak($timing);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private ?SessionShift $defaultShiftA9 = null;
    private ?SessionShift $defaultShiftC8 = null;

    /**
     * Resolve shift untuk sebuah timing:
     *  1. Dari daily_attendance.session_shift_id (paling akurat)
     *  2. Fallback ke default per-department: Costume→C8, lain→A9
     */
    private function resolveShift(Timing $timing, string $today): ?SessionShift
    {
        $daily = DailyAttendance::where('employee_id', $timing->employee_id)
            ->whereDate('date', $today)
            ->with('sessionShift')
            ->first();

        if ($daily?->sessionShift) {
            return $daily->sessionShift;
        }

        $this->defaultShiftA9 ??= SessionShift::where('type_of_shift', 'A9')->whereNull('department_id')->where('is_active', true)->first();
        $this->defaultShiftC8 ??= SessionShift::where('type_of_shift', 'C8')->where('is_active', true)->first();

        $deptName = strtolower($timing->employee?->department?->name ?? '');

        return str_contains($deptName, 'costume') || str_contains($deptName, 'sewing') || str_contains($deptName, 'plush')
            ? $this->defaultShiftC8
            : $this->defaultShiftA9;
    }

    private function isInBreak(SessionShift $shift, string $now): bool
    {
        $in1 = $shift->break_start && $shift->break_end
            && $now >= $shift->break_start && $now < $shift->break_end;

        $in2 = $shift->break2_start && $shift->break2_end
            && $now >= $shift->break2_start && $now < $shift->break2_end;

        return $in1 || $in2;
    }

    private function freezeForBreak(Timing $timing): void
    {
        $frozenAt = now();
        $today    = $frozenAt->format('Y-m-d');

        $frozenDuration = '00:00:00';
        if ($timing->start_time) {
            $start = Carbon::parse($today . ' ' . $timing->start_time);
            $diff  = $start->diff($frozenAt);
            $frozenDuration = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
        }

        $deptData                      = $timing->department_specific_data ?? [];
        $deptData['frozen_at']         = $frozenAt->format('H:i:s');
        $deptData['frozen_duration']   = $frozenDuration;
        $deptData['auto_break_paused'] = true;

        // Append auto-break pause event to log
        $pauseLog   = $timing->pause_log ?? [];
        $pauseLog[] = ['type' => 'auto_break', 'paused_at' => $frozenAt->format('H:i:s'), 'resumed_at' => null, 'duration_minutes' => null];

        $timing->status                   = 'frozen';
        $timing->paused_at                = $frozenAt;
        $timing->department_specific_data = $deptData;
        $timing->pause_log                = $pauseLog;
        $timing->save();

        Log::info("TimingBreakService → frozen #{$timing->id} (emp {$timing->employee_id}), elapsed={$frozenDuration}");
    }

    private function unfreezeAfterBreak(Timing $timing): void
    {
        $deptData       = $timing->department_specific_data ?? [];
        $frozenDuration = $deptData['frozen_duration'] ?? '00:00:00';

        [$h, $m, $s]   = array_map('intval', explode(':', $frozenDuration));
        $frozenSeconds  = $h * 3600 + $m * 60 + $s;
        $newStartTime   = now()->subSeconds($frozenSeconds)->format('H:i:s');
        $pausedMinutes  = $timing->paused_at ? (int) $timing->paused_at->diffInMinutes(now()) : 0;

        unset($deptData['frozen_at'], $deptData['frozen_duration'], $deptData['auto_break_paused']);

        // Update last open pause log entry with resume time
        $pauseLog = $timing->pause_log ?? [];
        $resumedAt = now()->format('H:i:s');
        if (!empty($pauseLog)) {
            $last = &$pauseLog[count($pauseLog) - 1];
            if ($last['resumed_at'] === null) {
                $last['resumed_at']       = $resumedAt;
                $last['duration_minutes'] = $pausedMinutes;
            }
        }

        $timing->status                   = 'on progress';
        $timing->start_time               = $newStartTime;
        $timing->paused_at                = null;
        $timing->total_paused_minutes     = ($timing->total_paused_minutes ?? 0) + $pausedMinutes;
        $timing->department_specific_data = $deptData ?: null;
        $timing->pause_log                = $pauseLog ?: null;
        $timing->save();

        Log::info("TimingBreakService → unfrozen #{$timing->id} (emp {$timing->employee_id}), break={$pausedMinutes}m, new_start={$newStartTime}");
    }
}
