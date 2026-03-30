<?php

namespace App\Http\Controllers\Timing;

use App\Http\Controllers\Controller;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\SessionShift;
use App\Models\Production\Timing;
use App\Services\Timing\TimingBreakService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimingDetailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show detail page for a single timing session (running, paused, or completed).
     */
    public function show(int $id)
    {
        $timing = Timing::with(['employee.department', 'project', 'jobOrder', 'sessionShift'])
            ->findOrFail($id);

        $deptData    = $timing->department_specific_data ?? [];
        $isFrozen    = $timing->isFrozen();
        $isRunning   = $timing->isRunning();
        $isCompleted = $timing->isStopped();

        // --- Elapsed from (possibly shifted) start_time to now / end_time ---
        // Note: start_time may have been shifted forward by unfreezeAfterBreak,
        // so (end - start) already equals net work time, NOT wall-clock gross.
        $elapsedMinutes = 0;
        if ($timing->start_time) {
            $dateStr = $timing->tanggal ? $timing->tanggal->format('Y-m-d') : now()->format('Y-m-d');
            $start   = Carbon::parse($dateStr . ' ' . $timing->start_time);
            if ($timing->end_time) {
                $end = Carbon::parse($dateStr . ' ' . $timing->end_time);
            } elseif ($isFrozen) {
                $frozenAt = $deptData['frozen_at'] ?? null;
                $end = $frozenAt ? Carbon::parse($dateStr . ' ' . $frozenAt) : now();
            } else {
                $end = now();
            }
            if ($end->lt($start)) {
                $end->addDay();
            }
            $elapsedMinutes = max(0, (int) $start->diffInMinutes($end));
        }

        $totalPaused       = $timing->total_paused_minutes ?? 0;
        $breakDeducted     = $timing->break_deducted_minutes ?? 0;
        // Gross = elapsed + paused (real wall-clock), Net = elapsed (actual work)
        $grossMinutes      = $elapsedMinutes + $totalPaused;
        $netActiveMinutes  = $elapsedMinutes;

        // Net active seconds — used by JS timer so it starts from the correct value
        // (avoids timezone-parsing issues with new Date('Y-m-dTH:i:s') in browsers)
        $netActiveSeconds = 0;
        if ($isRunning && $timing->start_time) {
            $dateStrSec = $timing->tanggal ? $timing->tanggal->format('Y-m-d') : now()->format('Y-m-d');
            $startSec   = Carbon::parse($dateStrSec . ' ' . $timing->start_time);
            $endSec     = now();
            if ($endSec->lt($startSec)) $endSec->addDay();
            $netActiveSeconds = max(0, (int) $startSec->diffInSeconds($endSec));
        }

        // Current pause duration (if currently frozen)
        $currentPauseMins = 0;
        if ($isFrozen && $timing->paused_at) {
            $currentPauseMins = (int) $timing->paused_at->diffInMinutes(now());
        }

        // Department label
        $deptName = $timing->employee->department->name ?? 'N/A';

        return view('timing.detail', compact(
            'timing',
            'deptData',
            'isFrozen',
            'isRunning',
            'isCompleted',
            'grossMinutes',
            'totalPaused',
            'breakDeducted',
            'netActiveMinutes',
            'netActiveSeconds',
            'currentPauseMins',
            'deptName',
        ));
    }

    /**
     * AJAX — return rendered partial for modal display.
     */
    public function showPartial(int $id)
    {
        $timing = Timing::with(['employee.department', 'project', 'jobOrder', 'sessionShift'])
            ->findOrFail($id);

        $deptData    = $timing->department_specific_data ?? [];
        $isFrozen    = $timing->isFrozen();
        $isRunning   = $timing->isRunning();
        $isCompleted = $timing->isStopped();

        $elapsedMinutes = 0;
        if ($timing->start_time) {
            $dateStr = $timing->tanggal ? $timing->tanggal->format('Y-m-d') : now()->format('Y-m-d');
            $start   = Carbon::parse($dateStr . ' ' . $timing->start_time);
            if ($timing->end_time) {
                $end = Carbon::parse($dateStr . ' ' . $timing->end_time);
            } elseif ($isFrozen) {
                $frozenAt = $deptData['frozen_at'] ?? null;
                $end = $frozenAt ? Carbon::parse($dateStr . ' ' . $frozenAt) : now();
            } else {
                $end = now();
            }
            if ($end->lt($start)) {
                $end->addDay();
            }
            $elapsedMinutes = max(0, (int) $start->diffInMinutes($end));
        }

        $totalPaused      = $timing->total_paused_minutes ?? 0;
        $breakDeducted    = $timing->break_deducted_minutes ?? 0;
        $grossMinutes     = $elapsedMinutes + $totalPaused;
        $netActiveMinutes = $elapsedMinutes;
        $deptName         = $timing->employee->department->name ?? 'N/A';

        $netActiveSeconds = 0;
        if ($isRunning && $timing->start_time) {
            $dateStr2 = $timing->tanggal ? $timing->tanggal->format('Y-m-d') : now()->format('Y-m-d');
            $startSec = Carbon::parse($dateStr2 . ' ' . $timing->start_time);
            $endSec   = now();
            if ($endSec->lt($startSec)) $endSec->addDay();
            $netActiveSeconds = max(0, (int) $startSec->diffInSeconds($endSec));
        }

        return view('timing.partials.detail-modal-content', compact(
            'timing', 'deptData', 'isFrozen', 'isRunning', 'isCompleted',
            'grossMinutes', 'totalPaused', 'breakDeducted', 'netActiveMinutes', 'netActiveSeconds', 'deptName',
        ));
    }

    /**
     * AJAX — return live stats for a running session (used by the JS poller).
     */
    public function liveStats(int $id)
    {
        $timing = Timing::findOrFail($id);

        $deptData = $timing->department_specific_data ?? [];
        $isFrozen = $timing->isFrozen();

        $elapsedMinutes = 0;
        if ($timing->start_time) {
            $dateStr = $timing->tanggal ? $timing->tanggal->format('Y-m-d') : now()->format('Y-m-d');
            $start   = Carbon::parse($dateStr . ' ' . $timing->start_time);
            if ($timing->end_time) {
                $end = Carbon::parse($dateStr . ' ' . $timing->end_time);
            } elseif ($isFrozen) {
                $frozenAt = $deptData['frozen_at'] ?? null;
                $end = $frozenAt ? Carbon::parse($dateStr . ' ' . $frozenAt) : now();
            } else {
                $end = now();
            }
            if ($end->lt($start)) {
                $end->addDay();
            }
            $elapsedMinutes = max(0, (int) $start->diffInMinutes($end));
        }

        $totalPaused      = $timing->total_paused_minutes ?? 0;
        $grossMinutes     = $elapsedMinutes + $totalPaused;
        $netActiveMinutes = $elapsedMinutes;

        $currentPauseMins = 0;
        if ($isFrozen && $timing->paused_at) {
            $currentPauseMins = (int) $timing->paused_at->diffInMinutes(now());
        }

        return response()->json([
            'status'             => $timing->status,
            'is_frozen'          => $isFrozen,
            'is_completed'       => $timing->isStopped(),
            'gross_minutes'      => $grossMinutes,
            'total_paused'       => $totalPaused,
            'break_deducted'     => $timing->break_deducted_minutes ?? 0,
            'net_active_minutes' => $netActiveMinutes,
            'current_pause_mins' => $currentPauseMins,
            'frozen_duration'    => $deptData['frozen_duration'] ?? '00:00:00',
            'auto_break_paused'  => !empty($deptData['auto_break_paused']),
        ]);
    }

    /**
     * Lightweight endpoint: run break service + return current break windows.
     * Called by JS every 30s from any timing page — replaces scheduler.
     */
    public function heartbeat(TimingBreakService $breakService)
    {
        // Always run break service (5-second cache guard inside)
        $breakService->run();

        // Collect distinct break windows from today's active timings
        $activeEmployeeIds = Timing::whereDate('tanggal', today())
            ->whereIn('status', ['on progress', 'frozen'])
            ->whereNull('end_time')
            ->pluck('employee_id')
            ->unique();

        $breakWindows = [];
        foreach ($activeEmployeeIds as $empId) {
            $da = DailyAttendance::where('employee_id', $empId)
                ->whereDate('date', today())
                ->first();
            $shift = $da && $da->session_shift_id
                ? SessionShift::find($da->session_shift_id)
                : null;

            if (!$shift) {
                // Fallback to default A9
                $shift = SessionShift::where('type_of_shift', 'A9')
                    ->whereNull('department_id')
                    ->where('is_active', true)
                    ->first();
            }

            if ($shift && !isset($breakWindows[$shift->id])) {
                $windows = [];
                if ($shift->break_start && $shift->break_end) {
                    $windows[] = ['start' => $shift->break_start, 'end' => $shift->break_end];
                }
                if ($shift->break2_start && $shift->break2_end) {
                    $windows[] = ['start' => $shift->break2_start, 'end' => $shift->break2_end];
                }
                $breakWindows[$shift->id] = [
                    'shift_type' => $shift->type_of_shift,
                    'windows'    => $windows,
                ];
            }
        }

        return response()->json([
            'success'       => true,
            'now'           => now()->format('H:i:s'),
            'break_windows' => array_values($breakWindows),
        ]);
    }
}
