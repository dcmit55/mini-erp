@extends('layouts.app')

@section('styles')
<style>
    .td-wrap { max-width: 1000px; margin: 0 auto; }

    /* ── Status Pill ── */
    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 50px;
        font-size: 0.72rem; font-weight: 700; letter-spacing: 0.4px;
    }
    .status-pill.running { background: rgba(25,135,84,.15);   color: #2ecc71; border: 1px solid rgba(25,135,84,.35); }
    .status-pill.frozen  { background: rgba(255,193,7,.12);   color: #ffc107; border: 1px solid rgba(255,193,7,.3); }
    .status-pill.break   { background: rgba(13,202,240,.12);  color: #0dcaf0; border: 1px solid rgba(13,202,240,.3); }
    .status-pill.done    { background: rgba(108,117,125,.15); color: #adb5bd; border: 1px solid rgba(108,117,125,.3); }
    @keyframes blink-dot { 0%,100%{opacity:1} 50%{opacity:.2} }
    .live-dot { width:7px; height:7px; border-radius:50%; background:currentColor; animation:blink-dot 1.4s infinite; }

    /* ── Timer card ── */
    .timer-card {
        background: linear-gradient(150deg,#0c0e14 0%,#181b22 100%);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 18px;
        padding: 1.75rem 1.5rem 1.25rem;
    }
    .big-timer {
        font-size: 3.6rem; font-weight: 800;
        font-family: 'Courier New', monospace;
        letter-spacing: 5px; line-height: 1;
    }
    .timer-sub {
        font-size: 0.7rem; text-transform: uppercase;
        letter-spacing: 1.5px; color: #4e5368; margin-top: .35rem;
    }

    /* ── Stat grid (2×2) ── */
    .stat-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 1px; background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 12px; overflow: hidden;
        margin-top: 1.25rem;
    }
    .stat-grid .sg-cell {
        background: rgba(255,255,255,.02);
        padding: .8rem 1rem; text-align: center;
    }
    .sg-cell .sg-lbl {
        font-size: .62rem; text-transform: uppercase;
        letter-spacing: 1px; color: #4e5368; margin-bottom: 3px;
    }
    .sg-cell .sg-val {
        font-family: 'Courier New', monospace;
        font-size: .95rem; font-weight: 700;
    }

    /* ── Info card ── */
    .i-card {
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 16px; overflow: hidden;
        background: rgba(255,255,255,.015);
    }
    .i-card-head {
        padding: .75rem 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,.06);
        font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
        color: #4e5368; display: flex; align-items: center; gap: 7px;
    }
    .i-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 1px; background: rgba(255,255,255,.05);
    }
    .i-cell {
        padding: .8rem 1.25rem;
        background: rgba(12,14,20,.95);
    }
    .i-cell.full { grid-column: 1/-1; }
    .i-cell .lbl {
        font-size: .62rem; text-transform: uppercase;
        letter-spacing: .8px; color: #4e5368; margin-bottom: 3px;
    }
    .i-cell .val { font-size: .88rem; font-weight: 600; color: #dde0ea; }

    /* ── Pause rows ── */
    .pause-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .65rem 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,.04);
    }
    .pause-row:last-child { border-bottom: none; }
    .pause-icon {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: .75rem;
    }
    .pause-icon.auto   { background: rgba(13,202,240,.12); color: #0dcaf0; }
    .pause-icon.manual { background: rgba(255,193,7,.12);  color: #ffc107; }
    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0   rgba(255,193,7,.5); }
        100% { box-shadow: 0 0 0 8px rgba(255,193,7,0); }
    }
    .pause-icon.active { background: rgba(255,193,7,.2); color: #ffc107; animation: pulse-ring 1.6s ease-out infinite; }

    /* ── Progress bar ── */
    .prog-track { height: 5px; border-radius: 3px; background: rgba(255,255,255,.07); overflow: hidden; }
    .prog-fill   { height: 100%; border-radius: 3px; transition: width .4s; }

    /* ── Avatar ── */
    .emp-avatar    { width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.1);flex-shrink:0; }
    .emp-avatar-ph { width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center;flex-shrink:0; }

    @media(max-width:767px) {
        .big-timer { font-size: 2.6rem; letter-spacing: 3px; }
        .stat-grid  { grid-template-columns: 1fr 1fr; }
        .i-grid     { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="td-wrap py-3 px-2">

    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary mb-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back
    </a>

    {{-- ── Employee header ── --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        @if ($timing->employee->photo)
            <img src="{{ asset('storage/'.$timing->employee->photo) }}" class="emp-avatar">
        @else
            <div class="emp-avatar-ph"><i class="bi bi-person-fill text-secondary fs-5"></i></div>
        @endif
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold" style="font-size:1rem;">{{ $timing->employee->name }}</div>
            <div class="text-muted small">{{ $timing->employee->position ?? 'N/A' }} &middot; {{ $deptName }}</div>
        </div>
        @if ($isCompleted)
            <span class="status-pill done" id="status-badge"><i class="bi bi-check-circle-fill"></i> Completed</span>
        @elseif ($isFrozen)
            @if (!empty($deptData['auto_break_paused']))
                <span class="status-pill break" id="status-badge"><i class="bi bi-cup-hot-fill"></i> Break</span>
            @else
                <span class="status-pill frozen" id="status-badge"><i class="bi bi-pause-fill"></i> Paused</span>
            @endif
        @else
            <span class="status-pill running" id="status-badge"><span class="live-dot"></span> Running</span>
        @endif
    </div>

    {{-- ── Two-column layout ── --}}
    <div class="row g-3">

        {{-- LEFT: Timer + Pause History --}}
        <div class="col-md-5">

            {{-- Timer Card --}}
            <div class="timer-card mb-3">
                <div class="text-center">
                    @if ($isFrozen)
                        <div class="big-timer text-warning" id="big-timer">{{ $deptData['frozen_duration'] ?? '00:00:00' }}</div>
                        <div class="timer-sub">Paused at {{ $timing->paused_at?->format('H:i') ?? '-' }}</div>
                    @elseif ($isCompleted)
                        <div class="big-timer text-secondary">{{ \App\Helpers\TimeHelper::minutesToHHMM($timing->duration_minutes ?? 0) }}</div>
                        <div class="timer-sub">Final Duration</div>
                    @else
                        @php
                            $iH = str_pad(floor($netActiveSeconds/3600),2,'0',STR_PAD_LEFT);
                            $iM = str_pad(floor(($netActiveSeconds%3600)/60),2,'0',STR_PAD_LEFT);
                            $iS = str_pad($netActiveSeconds%60,2,'0',STR_PAD_LEFT);
                        @endphp
                        <div class="big-timer text-success" id="big-timer">{{ "{$iH}:{$iM}:{$iS}" }}</div>
                        <div class="timer-sub">Since {{ $timing->started_at?->format('H:i:s') ?? $timing->start_time }}</div>
                    @endif
                </div>

                <div class="stat-grid">
                    <div class="sg-cell">
                        <div class="sg-lbl">Start</div>
                        <div class="sg-val text-white">{{ $timing->started_at?->format('H:i:s') ?? $timing->start_time ?? '-' }}</div>
                    </div>
                    @if ($isCompleted && $timing->end_time)
                    <div class="sg-cell">
                        <div class="sg-lbl">Stop</div>
                        <div class="sg-val text-white">{{ $timing->stopped_at?->format('H:i:s') ?? $timing->end_time }}</div>
                    </div>
                    @else
                    <div class="sg-cell">
                        <div class="sg-lbl">Gross</div>
                        <div class="sg-val text-white" id="gross-display">{{ \App\Helpers\TimeHelper::minutesToHHMM($grossMinutes) }}</div>
                    </div>
                    @endif
                    <div class="sg-cell">
                        <div class="sg-lbl">Paused</div>
                        <div class="sg-val text-warning" id="paused-display">
                            {{ $totalPaused > 0 ? $totalPaused.' min' : '—' }}
                        </div>
                    </div>
                    <div class="sg-cell">
                        <div class="sg-lbl">Net Active</div>
                        <div class="sg-val text-success" id="net-display">{{ \App\Helpers\TimeHelper::minutesToHHMM($netActiveMinutes) }}</div>
                    </div>
                </div>

                @if ($breakDeducted > 0)
                <div class="text-center mt-2">
                    <span style="font-size:.7rem;color:#0dcaf0;background:rgba(13,202,240,.08);border:1px solid rgba(13,202,240,.2);padding:3px 10px;border-radius:20px;">
                        <i class="bi bi-scissors me-1"></i>Break deducted: {{ $breakDeducted }} min
                    </span>
                </div>
                @endif
            </div>

            {{-- Pause History --}}
            @php $pauseLog = $timing->pause_log ?? []; @endphp
            <div class="i-card">
                <div class="i-card-head">
                    <i class="bi bi-clock-history"></i> Pause History
                    @if (count($pauseLog))
                        <span class="badge bg-secondary ms-1" style="font-size:.6rem;">{{ count($pauseLog) }}</span>
                    @endif
                    @if ($totalPaused > 0)
                        <span class="ms-auto" style="font-size:.7rem;color:#ffc107;font-weight:600;">{{ $totalPaused }} min total</span>
                    @endif
                </div>

                @if (empty($pauseLog))
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-clock" style="font-size:1.5rem;opacity:.25;"></i>
                        <div class="small mt-2">No pauses yet</div>
                    </div>
                @else
                    @foreach ($pauseLog as $event)
                        @php
                            $isAuto   = ($event['type'] ?? '') === 'auto_break';
                            $isActive = $event['resumed_at'] === null;
                            $iconCls  = $isActive ? 'active' : ($isAuto ? 'auto' : 'manual');
                            $icon     = $isAuto ? 'bi-cup-hot' : 'bi-pause-fill';
                        @endphp
                        <div class="pause-row">
                            <div class="pause-icon {{ $iconCls }}">
                                <i class="bi {{ $icon }}"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div style="font-size:.72rem;font-weight:700;color:{{ $isAuto ? '#0dcaf0' : '#ffc107' }};">
                                    {{ $isAuto ? 'Auto Break' : 'Manual' }}
                                </div>
                                <div class="font-monospace text-muted" style="font-size:.75rem;">
                                    {{ substr($event['paused_at'],0,5) }}
                                    @if($event['resumed_at'])
                                        <span style="opacity:.4;margin:0 3px;">→</span>{{ substr($event['resumed_at'],0,5) }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if ($event['duration_minutes'] > 0)
                                    <span class="badge bg-secondary" style="font-size:.68rem;">{{ $event['duration_minutes'] }}m</span>
                                @elseif ($isActive)
                                    <span style="font-size:.68rem;color:#ffc107;font-weight:600;">ongoing</span>
                                @else
                                    <span class="text-muted" style="font-size:.68rem;">&lt;1m</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        </div>{{-- /col left --}}

        {{-- RIGHT: Session Info + Progress --}}
        <div class="col-md-7">

            {{-- Session Info --}}
            <div class="i-card mb-3">
                <div class="i-card-head"><i class="bi bi-briefcase-fill"></i> Session Info</div>
                <div class="i-grid">
                    <div class="i-cell full">
                        <div class="lbl">Job Order</div>
                        <div class="val">{{ $timing->jobOrder->name ?? $timing->job_order_id ?? '-' }}</div>
                    </div>
                    <div class="i-cell full">
                        <div class="lbl">Project</div>
                        <div class="val">{{ $timing->project->name ?? 'N/A' }}</div>
                    </div>
                    <div class="i-cell">
                        <div class="lbl">Step / Task</div>
                        <div class="val">{{ $timing->step ?? '-' }}</div>
                    </div>
                    @if ($timing->parts && $timing->parts !== 'N/A')
                    <div class="i-cell">
                        <div class="lbl">Parts</div>
                        <div class="val">{{ $timing->parts }}</div>
                    </div>
                    @endif
                    <div class="i-cell">
                        <div class="lbl">Date</div>
                        <div class="val">{{ $timing->tanggal?->format('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="i-cell">
                        <div class="lbl">Session ID</div>
                        <div class="val font-monospace" style="color:#5a5f73;">#{{ $timing->id }}</div>
                    </div>
                    @if ($isCompleted && $timing->measurement_value)
                    <div class="i-cell">
                        <div class="lbl">Output</div>
                        <div class="val">{{ $timing->measurement_value }} {{ $timing->measurement_type }}</div>
                    </div>
                    @endif
                    @if ($timing->remarks)
                    <div class="i-cell full">
                        <div class="lbl">Remarks</div>
                        <div class="val" style="font-weight:400;color:#9da0b3;">{{ $timing->remarks }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Progress --}}
            @php
                $trackingMode   = $deptData['tracking_mode'] ?? null;
                $hasMascotStage = isset($deptData['previous_stage']) || isset($deptData['current_stage']);
                $hasProgress    = isset($deptData['previous_progress']);
                $trackingLabel  = $trackingMode ? ucwords(str_replace('_',' ', strtolower($trackingMode))) : null;
            @endphp
            @if ($hasMascotStage || $hasProgress)
            <div class="i-card">
                <div class="i-card-head">
                    <i class="bi bi-bar-chart-steps"></i> Progress
                    @if ($trackingLabel)
                        <span class="ms-auto" style="font-size:.67rem;color:#ffc107;font-weight:600;">{{ $trackingLabel }}</span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="{{ $isCompleted && isset($deptData['current_progress']) ? 'mb-3' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted" style="font-size:.78rem;">
                                @if (isset($deptData['previous_stage']))
                                    Stage {{ $deptData['previous_stage'] }}
                                @else
                                    Previous
                                @endif
                            </span>
                            <span class="fw-semibold" style="font-size:.78rem;">{{ $deptData['previous_progress'] ?? 0 }}%</span>
                        </div>
                        <div class="prog-track">
                            <div class="prog-fill bg-secondary" style="width:{{ $deptData['previous_progress'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    @if ($isCompleted && isset($deptData['current_progress']))
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted" style="font-size:.78rem;">
                                Stage {{ $deptData['current_stage'] ?? $deptData['stage'] ?? '-' }} — Final
                            </span>
                            <span class="fw-semibold text-success" style="font-size:.78rem;">{{ $deptData['current_progress'] }}%</span>
                        </div>
                        <div class="prog-track">
                            <div class="prog-fill bg-success" style="width:{{ $deptData['current_progress'] }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>{{-- /col right --}}
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    if (@json($isCompleted)) return;

    @if ($isRunning && $timing->start_time)
    (function () {
        let s = {{ $netActiveSeconds }};
        const pad = n => String(n).padStart(2,'0');
        const el  = document.getElementById('big-timer');
        function tick() {
            if (el) el.textContent = pad(Math.floor(s/3600))+':'+pad(Math.floor(s%3600/60))+':'+pad(s%60);
            s++;
        }
        tick(); setInterval(tick, 1000);
    })();
    @endif

    const statsUrl = '{{ route("timing.detail.live-stats", $timing->id) }}';
    const pad2   = n => String(n).padStart(2,'0');
    const toHHMM = m => pad2(Math.floor(m/60))+':'+pad2(m%60);

    function poll() {
        fetch(statsUrl, { headers:{'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.json())
            .then(d => {
                const el = id => document.getElementById(id);
                if (el('gross-display'))  el('gross-display').textContent  = toHHMM(d.gross_minutes);
                if (el('paused-display')) el('paused-display').textContent = d.total_paused > 0 ? d.total_paused+' min' : '—';
                if (el('net-display'))    el('net-display').textContent    = toHHMM(d.net_active_minutes);

                const badge = el('status-badge');
                if (!badge) return;
                if (d.is_completed) {
                    badge.className = 'status-pill done';
                    badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Completed';
                } else if (d.is_frozen) {
                    if (d.auto_break_paused) {
                        badge.className = 'status-pill break';
                        badge.innerHTML = '<i class="bi bi-cup-hot-fill"></i> Break';
                    } else {
                        badge.className = 'status-pill frozen';
                        badge.innerHTML = '<i class="bi bi-pause-fill"></i> Paused';
                    }
                } else {
                    badge.className = 'status-pill running';
                    badge.innerHTML = '<span class="live-dot"></span> Running';
                }
            })
            .catch(()=>{});
    }

    setInterval(poll, 15000);
    poll();
})();
</script>
@endpush
