<?php

namespace App\Console\Commands\Timing;

use App\Models\FingerprintLog;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Production\Timing;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Auto-stop active timing sessions for employees who have already clocked out.
 *
 * This is a safety-net scheduler for cases where the fingerspot webhook
 * did not fire (connectivity issues, webhook downtime, etc.).
 *
 * It checks both AttendanceLog (raw fingerspot) and DailyAttendance (reconciled)
 * for a clock_out recorded today, then stops any still-active timing session
 * using that clock_out time as the end_time.
 */
class AutoStopClockoutTimingCommand extends Command
{
    protected $signature   = 'timing:auto-stop-clockout';
    protected $description = 'Auto-stop active timing sessions for employees who have already clocked out today';

    public function handle(): int
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $stopped   = 0;

        // ── 1. Collect all active timing sessions for today AND yesterday ─────
        // Yesterday is included to handle overnight / 24-hour shifts (e.g. Mascot)
        $activeTimings = Timing::whereIn('status', ['on progress', 'frozen'])
            ->whereNull('end_time')
            ->whereIn('tanggal', [$today, $yesterday])
            ->with('employee')
            ->get();

        if ($activeTimings->isEmpty()) {
            return Command::SUCCESS;
        }

        // ── 2. For each active session check if employee has already clocked out ─
        foreach ($activeTimings as $timing) {
            $employeeId  = $timing->employee_id;
            $sessionDate = $timing->tanggal instanceof Carbon
                ? $timing->tanggal->format('Y-m-d')
                : (string) $timing->tanggal;

            // Check today AND yesterday attendance to handle overnight sessions
            $checkDates = array_unique([$today, $yesterday]);
            $clockOutRecord = null;
            $clockOutDate   = null;

            // Derive employee PIN from employee_no (e.g. "DCM-0012" → "0012")
            $employeeNo  = $timing->employee->employee_no ?? '';
            $employeePin = ltrim(str_replace('DCM-', '', $employeeNo), '');

            foreach ($checkDates as $checkDate) {
                // 1. AttendanceLog (sudah direkonsiliasi)
                $val = AttendanceLog::where('employee_id', $employeeId)
                    ->whereDate('date', $checkDate)
                    ->whereNotNull('clock_out')
                    ->value('clock_out');

                // 2. DailyAttendance (sudah digenerate)
                if (!$val) {
                    $val = DailyAttendance::where('employee_id', $employeeId)
                        ->whereDate('date', $checkDate)
                        ->whereNotNull('clock_out')
                        ->value('clock_out');
                }

                // 3. FingerprintLog langsung (fallback jika reconcile belum/gagal jalan)
                if (!$val && $employeePin !== '') {
                    $fpLog = FingerprintLog::whereDate('event_time', $checkDate)
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.pin')) = ?", [$employeePin])
                        ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.status')) AS UNSIGNED) = 1")
                        ->orderByDesc('event_time')
                        ->first();

                    if ($fpLog) {
                        $val = $fpLog->event_time->format('H:i:s');
                        Log::info("timing:auto-stop-clockout: using FingerprintLog fallback for employee {$employeeNo} on {$checkDate}");
                    }
                }

                if ($val) {
                    $clockOutRecord = $val;
                    $clockOutDate   = $checkDate;
                    break;
                }
            }

            if (!$clockOutRecord) {
                continue; // Employee has not clocked out yet — skip
            }

            // Parse clock-out to Carbon — AttendanceLog stores as H:i:s string
            $endTimeStr = $clockOutRecord instanceof Carbon
                ? $clockOutRecord->format('H:i:s')
                : Carbon::parse($clockOutDate . ' ' . $clockOutRecord)->format('H:i:s');

            $stoppedAt = Carbon::parse($clockOutDate . ' ' . $endTimeStr);
            $startedAt = Carbon::parse($sessionDate . ' ' . $timing->start_time);

            // Guard: don't set end_time before start_time
            if ($stoppedAt->lte($startedAt)) {
                continue;
            }

            $gross = max(0, $startedAt->diffInMinutes($stoppedAt));
            $net   = max(0, $gross - ($timing->total_paused_minutes ?? 0));

            $deptData                    = $timing->department_specific_data ?? [];
            $deptData['auto_stopped']    = 'scheduler_clockout';
            $deptData['auto_stopped_at'] = now()->toDateTimeString();

            $timing->end_time                 = $endTimeStr;
            $timing->stopped_at               = $stoppedAt;
            $timing->stop_reason              = 'Auto-stopped by scheduler: employee clocked out' . ($clockOutDate !== $sessionDate ? ' (overnight session)' : '');
            $timing->status                   = 'complete';
            $timing->duration_minutes         = $net;
            $timing->department_specific_data = $deptData;
            $timing->save();

            $stopped++;

            Log::info("timing:auto-stop-clockout: stopped timing #{$timing->id} for {$timing->employee->name} (clock-out {$endTimeStr})");
        }

        if ($stopped > 0) {
            $this->info("Auto-stopped {$stopped} timing session(s) for employees who clocked out.");
            Log::info("timing:auto-stop-clockout: auto-stopped {$stopped} session(s) on {$today}");
        }

        return Command::SUCCESS;
    }
}
