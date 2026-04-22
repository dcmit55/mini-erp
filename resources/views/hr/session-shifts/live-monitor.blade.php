@extends('layouts.app')
@section('title', 'Live Monitor')

@section('content')
<style>
/* ── Refresh progress bar ───────────────────────────── */
#refresh-bar {
    position: fixed; top: 0; left: 0; height: 3px;
    background: #0d6efd; z-index: 9999;
    transition: width 1s linear;
}

/* ── Bar animations ─────────────────────────────────── */
@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 0 rgba(13,110,253,.5); }
    50%       { box-shadow: 0 0 0 5px rgba(13,110,253,0); }
}
.bar-track       { position:absolute; border-radius:6px; overflow:hidden; z-index:2; background:#e9ecef; }
.bar-fill-active { height:100%; background:linear-gradient(90deg,#0d6efd,#6ea8fe); border-radius:6px 0 0 6px; }
.bar-fill-done   { height:100%; background:linear-gradient(90deg,#198754,#51cf66); border-radius:6px; }
.bar-leave-fill  { height:100%; background:linear-gradient(90deg,#fd7e14,#ffa94d); border-radius:6px; }
.pulse-dot {
    position:absolute; top:50%; width:10px; height:10px;
    background:#0d6efd; border:2px solid #fff;
    border-radius:50%; transform:translateY(-50%);
    animation: pulse-dot 1.4s ease-in-out infinite;
}

/* ── Timeline grid ──────────────────────────────────── */
.hour-tick       { position:absolute; top:0; bottom:0; width:1px; background:#f0f0f0; pointer-events:none; }
.hour-tick.major { background:#dee2e6; }
.now-line {
    position:absolute; top:0; bottom:0; width:2px;
    background:rgba(220,53,69,.8); z-index:10; pointer-events:none;
}
.now-line::before {
    content:''; position:absolute; top:50%; left:-4px;
    width:10px; height:10px; background:#dc3545;
    border-radius:50%; transform:translateY(-50%);
    box-shadow:0 0 0 3px rgba(220,53,69,.2);
}

/* ── Table structure ────────────────────────────────── */
.emp-col {
    width:230px; min-width:230px;
    position:sticky; left:0; z-index:5;
    background:#fff;
    border-right:1px solid #e9ecef !important;
}
.tl-row { border-bottom:1px solid #f3f4f6; }
.tl-row:last-child { border-bottom:none; }
.tl-row:hover .emp-col,
.tl-row:hover .tl-cell { background:#f8faff !important; }
.tl-cell { padding:0; vertical-align:middle; }

/* row type backgrounds */
.row-active  .emp-col { border-left:3px solid #0d6efd !important; }
.row-done    .emp-col { border-left:3px solid #198754 !important; }
.row-leave   .emp-col { border-left:3px solid #fd7e14 !important; }
.row-absent  .emp-col { border-left:3px solid #dee2e6 !important; }
</style>

{{-- Refresh progress bar --}}
<div id="refresh-bar" style="width:100%;"></div>

<div class="container-fluid py-3">
<div class="col-12">

    {{-- ── Header ───────────────────────────────────── --}}
    @php
        $backUrl = request('from') === 'summary'
            ? route('attendance-logs.summary')
            : route('session-shifts.index');
    @endphp
    <div class="mb-3">
        <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="fw-bold mb-0">Live Monitor</h5>
            <span class="d-flex align-items-center gap-1 text-muted" style="font-size:.8rem;">
                <span class="rounded-circle bg-success d-inline-block" style="width:7px;height:7px;"></span>
                {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                @if($isToday) · <span class="text-danger fw-semibold" id="live-clock">{{ $now->format('H:i') }}</span> @endif
            </span>
        </div>
    </div>

    {{-- ── Stat cards ───────────────────────────────── --}}
    @php
        $totalIn    = $attendances->whereNull('clock_out')->count();
        $totalDone  = $attendances->whereNotNull('clock_out')->count();
        $totalLeave = $leaves->count();
        $totalNotIn = $notClockedIn->count();
        $PX         = 1.5;
    @endphp
    <div class="row g-3 mb-4">
        @foreach([
            ['value' => $totalIn,    'label' => 'In Office',   'color' => '#0d6efd', 'bg' => '#eff6ff', 'icon' => 'fa-building'],
            ['value' => $totalDone,  'label' => 'Clocked Out', 'color' => '#198754', 'bg' => '#f0fdf4', 'icon' => 'fa-circle-check'],
            ['value' => $totalLeave, 'label' => 'On Leave',    'color' => '#fd7e14', 'bg' => '#fff7ed', 'icon' => 'fa-umbrella-beach'],
            ['value' => $totalNotIn, 'label' => 'Not Yet In',  'color' => '#6c757d', 'bg' => '#f8f9fa', 'icon' => 'fa-clock'],
        ] as $stat)
        <div class="col-6 col-md-3">
            <div class="rounded-3 px-3 py-3 d-flex align-items-center gap-3" style="background:{{ $stat['bg'] }}; border:1px solid {{ $stat['color'] }}22;">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:38px;height:38px;background:{{ $stat['color'] }}18;">
                    <i class="fas {{ $stat['icon'] }}" style="color:{{ $stat['color'] }};font-size:.85rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.35rem;line-height:1;color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
                    <div class="text-muted" style="font-size:.72rem;">{{ $stat['label'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Filters + Color Guide ───────────────────── --}}
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-2">
        {{-- Filters (left) --}}
        <div class="d-flex gap-2 align-items-end flex-wrap">
            <form method="GET" action="{{ route('session-shifts.live-monitor') }}" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small text-muted mb-1 d-block">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm rounded-2"
                           value="{{ $date }}" onchange="this.form.submit()" style="font-size:.82rem;">
                </div>
                <div>
                    <label class="form-label small text-muted mb-1 d-block">Department</label>
                    <select name="department_id" class="form-select form-select-sm rounded-2" onchange="this.form.submit()" style="font-size:.82rem;">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <div>
                <label class="form-label small text-muted mb-1 d-block">Search Employee</label>
                <input type="text" id="empSearch" class="form-control form-control-sm rounded-2"
                       placeholder="Type name or ID..." style="min-width:200px; font-size:.82rem;" autocomplete="off">
            </div>
        </div>

        {{-- Color Guide + Countdown (right) --}}
        <div class="d-flex align-items-center flex-wrap gap-3">
            @foreach([
                ['#0d6efd','Active / In Office'],
                ['#198754','Clocked Out'],
                ['#fd7e14','On Leave'],
                ['#adb5bd','Not Yet In'],
                ['#dc3545','Current Time'],
            ] as [$c,$l])
            <span class="d-flex align-items-center gap-1" style="font-size:.72rem;color:#495057;">
                <span class="rounded-1 d-inline-block flex-shrink-0" style="width:14px;height:8px;background:{{ $c }};"></span>
                {{ $l }}
            </span>
            @endforeach
            <span class="d-flex align-items-center gap-1" style="font-size:.72rem;color:#495057;">
                <span class="rounded-1 d-inline-block flex-shrink-0" style="width:14px;height:8px;background:rgba(255,255,255,0.5);border:1px dashed #adb5bd;"></span>
                Break
            </span>
            <span class="text-muted" style="font-size:.7rem;border-left:1px solid #dee2e6;padding-left:.75rem;" id="countdown-label">Refresh in 60s</span>
        </div>
    </div>

    {{-- ── Timeline ─────────────────────────────────── --}}
    @php
        $START_HOUR   = 5;
        $TL           = round(24 * 60 * $PX);
        $toDisplayPx  = fn($min) => round((($min - $START_HOUR * 60 + 1440) % 1440) * $PX);
        $nowDisplayPx = $toDisplayPx($currentMinutes);
        $COL_W        = round(60 * $PX);
    @endphp

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">

        <div style="overflow-x:scroll; -webkit-overflow-scrolling:touch;">
            <table class="table table-borderless mb-0" style="min-width:{{ 230 + $TL }}px; table-layout:fixed; border-collapse:collapse;">

                {{-- Hour header --}}
                <thead>
                    <tr>
                        <th class="emp-col py-2 px-3" style="background:#f8f9fa; font-size:.7rem; font-weight:700; color:#9ca3af; letter-spacing:.06em; border-bottom:2px solid #e9ecef;">
                            EMPLOYEE
                        </th>
                        <th style="padding:0; background:#f8f9fa; border-bottom:2px solid #e9ecef; width:{{ $TL }}px;">
                            <div style="position:relative; width:{{ $TL }}px; height:36px;">
                                @for($i = 0; $i < 24; $i++)
                                @php $h = ($START_HOUR + $i) % 24; $hx = round($i * 60 * $PX); @endphp
                                <div style="position:absolute; left:{{ $hx }}px; top:0; width:{{ $COL_W }}px; height:100%;
                                            display:flex; align-items:center; justify-content:center;
                                            font-size:.63rem; color:{{ $h >= 7 && $h <= 18 ? '#374151' : '#9ca3af' }};
                                            font-weight:400;
                                            border-left:1px solid #e9ecef;">
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}
                                </div>
                                @endfor
                                @if($isToday)
                                <div style="position:absolute; left:{{ $nowDisplayPx }}px; top:0; bottom:0; width:2px; background:rgba(220,53,69,.3); z-index:4;"></div>
                                <div style="position:absolute; left:{{ $nowDisplayPx - 12 }}px; bottom:2px; font-size:.58rem; color:#dc3545; font-weight:700; z-index:5; white-space:nowrap;">
                                    {{ $now->format('H:i') }}
                                </div>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody>
                @if($attendances->isEmpty() && $leaves->isEmpty() && $notClockedIn->isEmpty())
                <tr>
                    <td colspan="2" class="text-center py-5" style="color:#9ca3af;">
                        <i class="fas fa-calendar-times fa-2x mb-2 d-block" style="opacity:.2;"></i>
                        No records for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                    </td>
                </tr>
                @endif

                {{-- Active / Done rows --}}
                @foreach($attendances as $att)
                @php
                    $inMin    = (int)$att->clock_in->format('H') * 60 + (int)$att->clock_in->format('i');
                    $isActive = is_null($att->clock_out);

                    // Determine shift end from session shift
                    if ($att->sessionShift && $att->sessionShift->end_time) {
                        $seH         = (int) substr($att->sessionShift->end_time, 0, 2);
                        $seM         = (int) substr($att->sessionShift->end_time, 3, 2);
                        $shiftEndMin = $seH * 60 + $seM;
                        if ($shiftEndMin <= $inMin) $shiftEndMin += 1440;
                    } else {
                        $shiftEndMin = $inMin + 480;
                    }

                    // Saturday: override shift end from employee work policy
                    if ($isSaturday) {
                        $wp = $workPolicies->get($att->employee_id);
                        if ($wp && $wp->saturday_end) {
                            $satEnd    = \Carbon\Carbon::parse($wp->saturday_end);
                            $satEndMin = $satEnd->hour * 60 + $satEnd->minute;
                            if ($satEndMin > $inMin) $shiftEndMin = $satEndMin;
                        }
                    }

                    // Filled time
                    if ($isActive) {
                        $filledMin = ($isToday && $currentMinutes >= $inMin) ? $currentMinutes : $inMin;
                    } else {
                        $filledMin = (int)$att->clock_out->format('H') * 60 + (int)$att->clock_out->format('i');
                        if ($filledMin < $inMin) $filledMin += 1440;
                    }

                    $totalDuration  = max(1, $shiftEndMin - $inMin);
                    $filledDuration = max(0, min($filledMin - $inMin, $totalDuration));

                    // Bar width & fill %
                    // Active  → bar extends to shift end (OT: stays 100% with different color)
                    // Done    → bar extends only to clock-out (accurate stop), fill = 100%
                    if ($isActive) {
                        $barWidth = round($totalDuration * $PX);
                        if ($isToday && $currentMinutes >= $shiftEndMin) {
                            // Past shift end but not clocked out — keep bar full & blue
                            $pct = 100;
                        } else {
                            $pct = round($filledDuration / $totalDuration * 100);
                        }
                    } else {
                        $barWidth = max(6, round(($filledMin - $inMin) * $PX));
                        $pct      = 100;
                    }

                    // Right label
                    if ($isActive && $isToday && $currentMinutes >= $shiftEndMin) {
                        $extMin     = $currentMinutes - $shiftEndMin;
                        $extH       = (int) floor($extMin / 60);
                        $extM       = $extMin % 60;
                        $rightLabel = '+' . ($extH > 0 ? $extH . 'h ' : '') . $extM . 'm';
                    } elseif ($isActive && $isToday) {
                        $remMin     = max(0, $shiftEndMin - $currentMinutes);
                        $remH       = (int) floor($remMin / 60);
                        $remM       = $remMin % 60;
                        $rightLabel = ($remH > 0 ? $remH . 'h ' : '') . $remM . 'm left';
                    } elseif (!$isActive) {
                        $wrkMin     = $filledMin - $inMin;
                        $wrkH       = (int) floor($wrkMin / 60);
                        $wrkM       = $wrkMin % 60;
                        $rightLabel = ($wrkH > 0 ? $wrkH . 'h ' : '') . $wrkM . 'm';
                    } else {
                        $rightLabel = $att->sessionShift ? substr($att->sessionShift->end_time, 0, 5) : '—';
                    }

                    $barLeft     = $toDisplayPx($inMin);
                    $shiftLabel  = $att->sessionShift->type_of_shift ?? null;
                    $clockInStr  = $att->clock_in->format('H:i');
                    $shiftEndStr = $att->sessionShift ? substr($att->sessionShift->end_time, 0, 5) : '—';
                    $clockOutStr = $isActive ? null : $att->clock_out->format('H:i');
                    $rowType     = $isActive ? 'row-active' : 'row-done';
                    $accentColor = $isActive ? '#0d6efd' : '#198754';

                    // Calculate break window positions (only if shift has breaks configured)
                    $breakSegments = [];
                    if ($att->sessionShift) {
                        foreach ([
                            ['start' => $att->sessionShift->break_start,  'end' => $att->sessionShift->break_end],
                            ['start' => $att->sessionShift->break2_start, 'end' => $att->sessionShift->break2_end],
                        ] as $brk) {
                            if ($brk['start'] && $brk['end']) {
                                $bsH = (int) substr($brk['start'], 0, 2);
                                $bsM = (int) substr($brk['start'], 3, 2);
                                $beH = (int) substr($brk['end'],   0, 2);
                                $beM = (int) substr($brk['end'],   3, 2);
                                $bStartMin = $bsH * 60 + $bsM;
                                $bEndMin   = $beH * 60 + $beM;
                                // Only render break if it falls within the displayed bar range
                                if ($bStartMin >= $inMin && $bEndMin <= $shiftEndMin && $bEndMin > $bStartMin) {
                                    $breakSegments[] = [
                                        'left'  => round(($bStartMin - $inMin) * $PX),
                                        'width' => round(($bEndMin - $bStartMin) * $PX),
                                        'label' => substr($brk['start'], 0, 5) . '–' . substr($brk['end'], 0, 5),
                                    ];
                                }
                            }
                        }
                    }
                @endphp
                <tr class="tl-row {{ $rowType }}" data-emp="{{ strtolower($att->employee->name ?? '') }}">
                    <td class="emp-col py-0 px-3" style="height:52px; vertical-align:middle;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:.63rem;font-weight:700;letter-spacing:.02em;
                                        background:{{ $accentColor }}18; color:{{ $accentColor }};">
                                {{ strtoupper(substr($att->employee->name ?? '?', 0, 2)) }}
                            </div>
                            <div style="min-width:0; flex:1;">
                                <div class="fw-semibold text-truncate" style="font-size:.8rem; color:#111827; max-width:150px;">
                                    {{ $att->employee->name ?? '—' }}
                                </div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span style="font-size:.63rem; color:#9ca3af;">{{ $att->employee->employee_no ?? '' }}</span>
                                    @if($shiftLabel)
                                    <span class="rounded-1 px-1" style="font-size:.58rem; background:{{ $accentColor }}15; color:{{ $accentColor }};">{{ $shiftLabel }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="tl-cell" style="height:52px; width:{{ $TL }}px; position:relative;">
                        @for($i = 0; $i < 24; $i++)
                        <div class="hour-tick {{ $i % 6 === 0 ? 'major' : '' }}" style="left:{{ round($i*60*$PX) }}px;"></div>
                        @endfor
                        @if($isToday)<div class="now-line" style="left:{{ $nowDisplayPx }}px;"></div>@endif

                        <div class="bar-track"
                             style="top:14px; height:24px; left:{{ $barLeft }}px; width:{{ $barWidth }}px;"
                             title="{{ $att->employee->name }}: {{ $clockInStr }} – {{ $clockOutStr ?? $shiftEndStr }}"
                             @if($isActive)
                                 data-live="1"
                                 data-in-min="{{ $inMin }}"
                                 data-shift-end-min="{{ $shiftEndMin }}"
                                 data-total-dur="{{ $totalDuration }}"
                             @endif>
                            <div class="{{ $isActive ? 'bar-fill-active' : 'bar-fill-done' }}" style="width:{{ $pct }}%;"></div>
                            <span style="position:absolute; left:6px; top:50%; transform:translateY(-50%); font-size:.58rem; font-weight:700; white-space:nowrap; color:#fff; text-shadow:0 0 3px rgba(0,0,0,0.5); z-index:1;">
                                {{ $clockInStr }}
                            </span>
                            <span class="{{ $isActive ? 'bar-remaining' : '' }}"
                                  style="position:absolute; right:6px; top:50%; transform:translateY(-50%); font-size:.58rem; white-space:nowrap; color:{{ $pct > 60 ? '#fff' : '#9ca3af' }}; z-index:1;">
                                {{ $rightLabel }}
                            </span>
                            @if($isActive)
                            <div class="pulse-dot" style="left:calc({{ $pct }}% - 5px);{{ ($pct <= 1 || $pct >= 99) ? 'display:none;' : '' }}"></div>
                            @endif
                            @foreach($breakSegments as $bseg)
                            <div title="Break: {{ $bseg['label'] }}"
                                 style="position:absolute; top:0; left:{{ $bseg['left'] }}px; width:{{ $bseg['width'] }}px; height:100%;
                                        background:rgba(255,255,255,0.38); z-index:4;
                                        border-left:1px dashed rgba(255,255,255,0.65);
                                        border-right:1px dashed rgba(255,255,255,0.65);">
                                @if($bseg['width'] >= 20)
                                <span style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                                             font-size:.52rem; color:rgba(255,255,255,0.9); white-space:nowrap; pointer-events:none;">
                                    Break
                                </span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- Leave rows --}}
                @foreach($leaves as $leave)
                @php
                    $lLeft  = 0; $lWidth = $TL;
                    if ($leave->leave_time_from && $leave->leave_time_to) {
                        $lfMin  = (int)\Carbon\Carbon::parse($leave->leave_time_from)->format('H') * 60
                                + (int)\Carbon\Carbon::parse($leave->leave_time_from)->format('i');
                        $ltMin  = (int)\Carbon\Carbon::parse($leave->leave_time_to)->format('H') * 60
                                + (int)\Carbon\Carbon::parse($leave->leave_time_to)->format('i');
                        $lLeft  = $toDisplayPx($lfMin);
                        $lWidth = round(($ltMin - $lfMin) * $PX);
                    }
                @endphp
                <tr class="tl-row row-leave" data-emp="{{ strtolower($leave->employee->name ?? '') }}">
                    <td class="emp-col py-0 px-3" style="height:52px; vertical-align:middle;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:.63rem;font-weight:700;background:#fff7ed;color:#ea580c;">
                                {{ strtoupper(substr($leave->employee->name ?? '?', 0, 2)) }}
                            </div>
                            <div style="min-width:0;">
                                <div class="fw-semibold text-truncate" style="font-size:.8rem; color:#111827; max-width:150px;">{{ $leave->employee->name ?? '—' }}</div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span style="font-size:.63rem; color:#9ca3af;">{{ $leave->employee->employee_no ?? '' }}</span>
                                    <span class="rounded-1 px-1" style="font-size:.58rem; background:#fff7ed; color:#ea580c;">Leave</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="tl-cell" style="height:52px; width:{{ $TL }}px; position:relative;">
                        @for($i = 0; $i < 24; $i++)
                        <div class="hour-tick {{ $i % 6 === 0 ? 'major' : '' }}" style="left:{{ round($i*60*$PX) }}px;"></div>
                        @endfor
                        @if($isToday)<div class="now-line" style="left:{{ $nowDisplayPx }}px;"></div>@endif
                        <div class="bar-track" style="top:14px; height:24px; left:{{ $lLeft }}px; width:{{ $lWidth }}px;">
                            <div class="bar-leave-fill" style="width:100%;"></div>
                            <span style="position:absolute; left:8px; top:50%; transform:translateY(-50%); font-size:.58rem; font-weight:700; color:#fff; white-space:nowrap; z-index:1;">On Leave</span>
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- Not clocked in rows --}}
                @foreach($notClockedIn as $emp)
                <tr class="tl-row row-absent" data-emp="{{ strtolower($emp->name ?? '') }}">
                    <td class="emp-col py-0 px-3" style="height:48px; vertical-align:middle; opacity:.55;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:.63rem;font-weight:700;background:#f3f4f6;color:#9ca3af;">
                                {{ strtoupper(substr($emp->name ?? '?', 0, 2)) }}
                            </div>
                            <div style="min-width:0;">
                                <div class="fw-semibold text-truncate" style="font-size:.8rem; color:#6b7280; max-width:150px;">{{ $emp->name ?? '—' }}</div>
                                <div style="font-size:.63rem; color:#adb5bd;">{{ $emp->employee_no ?? '' }} · Not clocked in</div>
                            </div>
                        </div>
                    </td>
                    <td class="tl-cell" style="height:48px; width:{{ $TL }}px; position:relative;">
                        @for($i = 0; $i < 24; $i++)
                        <div class="hour-tick {{ $i % 6 === 0 ? 'major' : '' }}" style="left:{{ round($i*60*$PX) }}px;"></div>
                        @endfor
                        @if($isToday)<div class="now-line" style="left:{{ $nowDisplayPx }}px; opacity:.4;"></div>@endif
                        <div class="bar-track" style="top:18px; height:12px; left:0; width:{{ $TL }}px; opacity:.25;"></div>
                    </td>
                </tr>
                @endforeach
                </tbody>

            </table>
        </div>
    </div>

</div>
</div>

<script>
(function () {
    const TOTAL = 60;
    let remaining = TOTAL;
    const bar   = document.getElementById('refresh-bar');
    const label = document.getElementById('countdown-label');

    function tick() {
        remaining--;
        const pct = ((TOTAL - remaining) / TOTAL) * 100;
        if (bar)   bar.style.width = pct + '%';
        if (label) label.textContent = 'Refresh in ' + remaining + 's';
        if (remaining <= 0) window.location.href = window.location.href;
    }

    if (bar) bar.style.width = '0%';
    setInterval(tick, 1000);
})();

// ── Real-time bar & clock update ─────────────────────────
(function () {
    const START_MIN = 5 * 60; // 05:00 AM
    const PX        = 1.5;
    const isToday   = {{ $isToday ? 'true' : 'false' }};

    function toDisplayPx(min) {
        return Math.round(((min - START_MIN + 1440) % 1440) * PX);
    }

    function fmt2(n) { return String(n).padStart(2, '0'); }

    function updateLiveBars() {
        const now        = new Date();
        const currentMin = now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60;

        // Update live clock in header
        const clock = document.getElementById('live-clock');
        if (clock) clock.textContent = fmt2(now.getHours()) + ':' + fmt2(now.getMinutes());

        // Update now-line positions
        const nowPx = toDisplayPx(Math.floor(currentMin));
        document.querySelectorAll('.now-line').forEach(function (line) {
            line.style.left = nowPx + 'px';
        });

        if (!isToday) return;

        // Update each active bar
        document.querySelectorAll('.bar-track[data-live]').forEach(function (track) {
            const inMin       = parseInt(track.dataset.inMin);
            const shiftEndMin = parseInt(track.dataset.shiftEndMin);
            const totalDur    = parseInt(track.dataset.totalDur);
            const isOT        = currentMin >= shiftEndMin;

            const fill     = track.querySelector('.bar-fill-active, .bar-fill-ot');
            const dot      = track.querySelector('.pulse-dot');
            const remLabel = track.querySelector('.bar-remaining');
            const leftSpan = track.querySelectorAll('span')[0];

            const pastShiftEnd = currentMin >= shiftEndMin;

            if (pastShiftEnd) {
                // Past shift end, not yet clocked out — bar stays blue & full
                if (fill) { fill.className = 'bar-fill-active'; fill.style.width = '100%'; }
                if (dot)  dot.style.display = 'none';
                if (leftSpan) leftSpan.style.color = '#fff';
                if (remLabel) {
                    remLabel.style.color = '#fff';
                    const extMin = Math.ceil(currentMin - shiftEndMin);
                    const h = Math.floor(extMin / 60);
                    const m = extMin % 60;
                    remLabel.textContent = '+' + (h > 0 ? h + 'h ' : '') + m + 'm';
                }
            } else {
                // Normal progress
                const filledDur = Math.max(0, Math.min(currentMin - inMin, totalDur));
                const pct       = Math.round(filledDur / totalDur * 100);

                if (fill) { fill.className = 'bar-fill-active'; fill.style.width = pct + '%'; }
                if (dot) {
                    dot.style.left    = 'calc(' + pct + '% - 5px)';
                    dot.style.display = (pct > 1 && pct < 99) ? '' : 'none';
                }
                if (leftSpan) { leftSpan.style.color = '#fff'; leftSpan.style.textShadow = '0 0 3px rgba(0,0,0,0.5)'; }
                if (remLabel) {
                    remLabel.style.color = pct > 60 ? '#fff' : '#9ca3af';
                    const remMin = Math.max(0, Math.ceil(shiftEndMin - currentMin));
                    const h = Math.floor(remMin / 60);
                    const m = remMin % 60;
                    remLabel.textContent = (h > 0 ? h + 'h ' : '') + m + 'm left';
                }
            }
        });
    }

    setInterval(updateLiveBars, 1000);
    updateLiveBars();
})();

document.getElementById('empSearch').addEventListener('input', function () {
    const val = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.tl-row').forEach(function (row) {
        row.style.display = (!val || row.dataset.emp.includes(val)) ? '' : 'none';
    });
});
</script>
@endsection
