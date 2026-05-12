{{-- Modal content loaded via AJAX --}}
<style>
    .mc-status-pill {
        display:inline-flex;align-items:center;gap:5px;
        padding:3px 11px;border-radius:50px;
        font-size:.7rem;font-weight:700;letter-spacing:.4px;
    }
    .mc-status-pill.running { background:rgba(25,135,84,.15);  color:#2ecc71; border:1px solid rgba(25,135,84,.35); }
    .mc-status-pill.frozen  { background:rgba(255,193,7,.12);  color:#ffc107; border:1px solid rgba(255,193,7,.3); }
    .mc-status-pill.break   { background:rgba(13,202,240,.12); color:#0dcaf0; border:1px solid rgba(13,202,240,.3); }
    .mc-status-pill.done    { background:rgba(108,117,125,.15);color:#adb5bd; border:1px solid rgba(108,117,125,.3); }
    @keyframes mc-blink { 0%,100%{opacity:1}50%{opacity:.2} }
    .mc-live-dot { width:6px;height:6px;border-radius:50%;background:currentColor;animation:mc-blink 1.4s infinite;display:inline-block; }

    .mc-timer-card {
        background:linear-gradient(150deg,#0c0e14,#181b22);
        border:1px solid rgba(255,255,255,.07);
        border-radius:14px;padding:1.25rem 1rem 1rem;
        text-align:center;
    }
    .mc-timer {
        font-size:2.8rem;font-weight:800;
        font-family:'Courier New',monospace;
        letter-spacing:4px;line-height:1;
    }
    .mc-timer-sub { font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;color:#4e5368;margin-top:.3rem; }

    .mc-stat-grid {
        display:grid;grid-template-columns:1fr 1fr;
        gap:1px;background:rgba(255,255,255,.06);
        border:1px solid rgba(255,255,255,.06);
        border-radius:10px;overflow:hidden;margin-top:1rem;
    }
    .mc-stat-grid .mc-sc {
        background:rgba(255,255,255,.02);
        padding:.65rem .75rem;text-align:center;
    }
    .mc-sc .mc-sl { font-size:.6rem;text-transform:uppercase;letter-spacing:1px;color:#4e5368;margin-bottom:2px; }
    .mc-sc .mc-sv { font-family:'Courier New',monospace;font-size:.88rem;font-weight:700; }

    .mc-info-grid {
        display:grid;grid-template-columns:1fr 1fr;
        gap:1px;background:#e9ecef;
        border:1px solid #e9ecef;
        border-radius:12px;overflow:hidden;
    }
    .mc-info-grid .mc-ic {
        background:#fff;
        padding:.65rem 1rem;
    }
    .mc-ic.full { grid-column:1/-1; }
    .mc-ic .mc-il { font-size:.6rem;text-transform:uppercase;letter-spacing:.8px;color:#9da0b3;margin-bottom:2px; }
    .mc-ic .mc-iv { font-size:.82rem;font-weight:600;color:#1a1d24; }

    .mc-section-label {
        font-size:.62rem;text-transform:uppercase;letter-spacing:1px;
        color:#4e5368;font-weight:700;margin-bottom:.5rem;
        display:flex;align-items:center;gap:6px;
    }
    .mc-section-label::after { content:'';flex:1;height:1px;background:rgba(255,255,255,.06); }

    .mc-pause-row {
        display:flex;align-items:center;gap:.6rem;
        padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.04);
    }
    .mc-pause-row:last-child { border-bottom:none; }
    .mc-pi {
        width:26px;height:26px;border-radius:50%;flex-shrink:0;
        display:flex;align-items:center;justify-content:center;font-size:.68rem;
    }
    .mc-pi.auto   { background:rgba(13,202,240,.12);color:#0dcaf0; }
    .mc-pi.manual { background:rgba(255,193,7,.12); color:#ffc107; }
    @keyframes mc-pulse { 0%{box-shadow:0 0 0 0 rgba(255,193,7,.5)}100%{box-shadow:0 0 0 7px rgba(255,193,7,0)} }
    .mc-pi.active { background:rgba(255,193,7,.18);color:#ffc107;animation:mc-pulse 1.6s ease-out infinite; }

    .mc-prog-track { height:4px;border-radius:2px;background:rgba(255,255,255,.07);overflow:hidden; }
    .mc-prog-fill  { height:100%;border-radius:2px;transition:width .4s; }

    @media (max-width:480px) {
        .mc-timer { font-size:2rem; letter-spacing:2px; }
        .mc-stat-grid { grid-template-columns:1fr 1fr; }
        .mc-info-grid { grid-template-columns:1fr; }
        .mc-ic.full { grid-column:1; }
        .mc-timer-card { padding:.9rem .75rem .75rem; }
    }
</style>

{{-- Employee header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    @if ($timing->employee->photo)
        <img src="{{ asset('storage/'.$timing->employee->photo) }}"
             style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.1);flex-shrink:0;">
    @else
        <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-person-fill text-secondary"></i>
        </div>
    @endif
    <div class="flex-grow-1 min-w-0">
        <div class="fw-bold" style="font-size:.92rem;">{{ $timing->employee->name }}</div>
        <div class="text-muted" style="font-size:.75rem;">{{ $timing->employee->position ?? 'N/A' }} &middot; {{ $deptName }}</div>
    </div>
    @if ($isCompleted)
        <span class="mc-status-pill done"><i class="bi bi-check-circle-fill"></i> Done</span>
    @elseif ($isFrozen)
        @if (!empty($deptData['auto_break_paused']))
            <span class="mc-status-pill break"><i class="bi bi-cup-hot-fill"></i> Break</span>
        @else
            <span class="mc-status-pill frozen"><i class="bi bi-pause-fill"></i> Paused</span>
        @endif
    @else
        <span class="mc-status-pill running"><span class="mc-live-dot"></span> Running</span>
    @endif
</div>

{{-- Timer --}}
<div class="mc-timer-card mb-3">
    @if ($isFrozen)
        <div class="mc-timer text-warning" id="modal-big-timer">{{ $deptData['frozen_duration'] ?? '00:00:00' }}</div>
        <div class="mc-timer-sub">Paused at {{ $timing->paused_at?->format('H:i') ?? '-' }}</div>
    @elseif ($isCompleted)
        <div class="mc-timer text-secondary">{{ \App\Helpers\TimeHelper::minutesToHHMM($timing->duration_minutes ?? 0) }}</div>
        <div class="mc-timer-sub">Final Duration</div>
    @else
        @php
            $mH = str_pad(floor($netActiveSeconds/3600),2,'0',STR_PAD_LEFT);
            $mM = str_pad(floor(($netActiveSeconds%3600)/60),2,'0',STR_PAD_LEFT);
            $mS = str_pad($netActiveSeconds%60,2,'0',STR_PAD_LEFT);
        @endphp
        <div class="mc-timer text-success" id="modal-big-timer">{{ "{$mH}:{$mM}:{$mS}" }}</div>
        <div class="mc-timer-sub">Since {{ $timing->started_at?->format('H:i:s') ?? $timing->start_time }}</div>
    @endif

    <div class="mc-stat-grid">
        <div class="mc-sc">
            <div class="mc-sl">Start</div>
            <div class="mc-sv text-white">{{ $timing->started_at?->format('H:i:s') ?? $timing->start_time ?? '-' }}</div>
        </div>
        <div class="mc-sc">
            <div class="mc-sl">Gross</div>
            <div class="mc-sv text-white" id="modal-gross">{{ \App\Helpers\TimeHelper::minutesToHHMM($grossMinutes) }}</div>
        </div>
        <div class="mc-sc">
            <div class="mc-sl">Paused</div>
            <div class="mc-sv text-warning" id="modal-paused">{{ $totalPaused > 0 ? $totalPaused.' min' : '—' }}</div>
        </div>
        <div class="mc-sc">
            <div class="mc-sl">Net Active</div>
            <div class="mc-sv text-success" id="modal-net">{{ \App\Helpers\TimeHelper::minutesToHHMM($netActiveMinutes) }}</div>
        </div>
    </div>

    @if ($breakDeducted > 0)
    <div class="mt-2">
        <span style="font-size:.68rem;color:#0dcaf0;background:rgba(13,202,240,.08);border:1px solid rgba(13,202,240,.2);padding:2px 9px;border-radius:20px;">
            <i class="bi bi-scissors me-1"></i>Break deducted: {{ $breakDeducted }} min
        </span>
    </div>
    @endif
</div>

{{-- Session Info --}}
<div class="mc-section-label"><i class="bi bi-briefcase-fill"></i> Session Info</div>
<div class="mc-info-grid mb-3">
    <div class="mc-ic full">
        <div class="mc-il">Job Order</div>
        <div class="mc-iv">{{ $timing->jobOrder->name ?? $timing->job_order_id ?? '-' }}</div>
    </div>
    <div class="mc-ic full">
        <div class="mc-il">Project</div>
        <div class="mc-iv">{{ $timing->project->name ?? 'N/A' }}</div>
    </div>
    <div class="mc-ic">
        <div class="mc-il">Step / Task</div>
        <div class="mc-iv">{{ $timing->step ?? '-' }}</div>
    </div>
    <div class="mc-ic">
        <div class="mc-il">Date</div>
        <div class="mc-iv">{{ $timing->tanggal?->format('d M Y') ?? '-' }}</div>
    </div>
    <div class="mc-ic full">
        <div class="mc-il">Session ID</div>
        <div class="mc-iv font-monospace" style="color:#adb5bd;">#{{ $timing->id }}</div>
    </div>
    @if ($timing->parts && $timing->parts !== 'N/A')
    <div class="mc-ic">
        <div class="mc-il">Parts</div>
        <div class="mc-iv">{{ $timing->parts }}</div>
    </div>
    @endif
    @if ($isCompleted && $timing->measurement_value)
    <div class="mc-ic full">
        <div class="mc-il">Output</div>
        <div class="mc-iv">{{ $timing->measurement_value }} {{ $timing->measurement_type }}</div>
    </div>
    @endif
</div>

{{-- Progress --}}
@php
    $trackingMode  = $deptData['tracking_mode'] ?? null;
    $hasProgress   = isset($deptData['previous_progress']);
    $trackingLabel = $trackingMode ? ucwords(str_replace('_',' ', strtolower($trackingMode))) : null;
@endphp
@if ($hasProgress)
<div class="mc-section-label"><i class="bi bi-bar-chart-steps"></i> Progress
    @if ($trackingLabel)
        <span style="font-size:.62rem;color:#ffc107;font-weight:600;text-transform:none;letter-spacing:0;">{{ $trackingLabel }}</span>
    @endif
</div>
<div class="mb-3">
    <div class="d-flex justify-content-between mb-1" style="font-size:.75rem;">
        <span class="text-muted">Previous</span>
        <span class="fw-semibold">{{ $deptData['previous_progress'] ?? 0 }}%</span>
    </div>
    <div class="mc-prog-track"><div class="mc-prog-fill bg-secondary" style="width:{{ $deptData['previous_progress'] ?? 0 }}%"></div></div>
    @if ($isCompleted && isset($deptData['current_progress']))
    <div class="d-flex justify-content-between mt-2 mb-1" style="font-size:.75rem;">
        <span class="text-muted">Stage {{ $deptData['current_stage'] ?? '-' }} — Final</span>
        <span class="fw-semibold text-success">{{ $deptData['current_progress'] }}%</span>
    </div>
    <div class="mc-prog-track"><div class="mc-prog-fill bg-success" style="width:{{ $deptData['current_progress'] }}%"></div></div>
    @endif
</div>
@endif

{{-- Pause History --}}
@php $pauseLog = $timing->pause_log ?? []; @endphp
<div class="mc-section-label">
    <i class="bi bi-clock-history"></i> Pause History
    @if (count($pauseLog))
        <span class="badge bg-secondary" style="font-size:.58rem;">{{ count($pauseLog) }}</span>
    @endif
    @if ($totalPaused > 0)
        <span style="font-size:.68rem;color:#ffc107;font-weight:600;text-transform:none;letter-spacing:0;margin-left:auto;">{{ $totalPaused }} min total</span>
    @endif
</div>
@if (empty($pauseLog))
    <div class="text-center text-muted py-3" style="font-size:.78rem;">
        <i class="bi bi-clock" style="opacity:.3;font-size:1.25rem;display:block;margin-bottom:.35rem;"></i>
        No pauses yet
    </div>
@else
    @foreach ($pauseLog as $event)
        @php
            $isAuto   = ($event['type'] ?? '') === 'auto_break';
            $isActive = $event['resumed_at'] === null;
            $iconCls  = $isActive ? 'active' : ($isAuto ? 'auto' : 'manual');
        @endphp
        <div class="mc-pause-row">
            <div class="mc-pi {{ $iconCls }}">
                <i class="bi {{ $isAuto ? 'bi-cup-hot' : 'bi-pause-fill' }}"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div style="font-size:.68rem;font-weight:700;color:{{ $isAuto ? '#0dcaf0' : '#ffc107' }};">
                    {{ $isAuto ? 'Auto Break' : 'Manual' }}
                </div>
                <div class="font-monospace text-muted" style="font-size:.72rem;">
                    {{ substr($event['paused_at'],0,5) }}
                    @if($event['resumed_at'])
                        <span style="opacity:.4;margin:0 3px;">→</span>{{ substr($event['resumed_at'],0,5) }}
                    @endif
                </div>
            </div>
            <div class="flex-shrink-0">
                @if (($event['duration_minutes'] ?? 0) > 0)
                    <span class="badge bg-secondary" style="font-size:.65rem;">{{ $event['duration_minutes'] }}m</span>
                @elseif ($isActive)
                    <span style="font-size:.65rem;color:#ffc107;font-weight:600;">ongoing</span>
                @else
                    <span class="text-muted" style="font-size:.65rem;">&lt;1m</span>
                @endif
            </div>
        </div>
    @endforeach
@endif

<div id="modal-data"
    data-timing-id="{{ $timing->id }}"
    data-is-running="{{ $isRunning ? '1' : '0' }}"
    data-is-frozen="{{ $isFrozen ? '1' : '0' }}"
    data-is-completed="{{ $isCompleted ? '1' : '0' }}"
    data-net-seconds="{{ $netActiveSeconds }}"
    data-stats-url="{{ route('timing.detail.live-stats', $timing->id) }}"
    style="display:none;">
</div>
