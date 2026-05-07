<div class="session-card p-3 border-bottom {{ $session->status === 'frozen' ? 'frozen' : '' }}"
    data-timing-id="{{ $session->id }}">
    <div class="d-flex align-items-start gap-2">
        <input type="checkbox" class="form-check-input bulk-session-cb mt-1" value="{{ $session->id }}">
        <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold d-flex align-items-center gap-1" style="font-size:.85rem;">
                {{ $session->employee->name ?? '-' }}
                @if ($session->session_type === 'repair')
                    <span class="badge bg-warning text-dark" style="font-size:.65rem;">Repair</span>
                @else
                    <span class="badge bg-success" style="font-size:.65rem;">Mass Prod</span>
                @endif
                @if ($session->status === 'frozen')
                    <span class="badge bg-secondary" style="font-size:.65rem;">Paused</span>
                @endif
            </div>
            <div class="text-muted" style="font-size:.75rem;">
                {{ $session->jobOrder->name ?? '-' }} &bull; {{ $session->project->name ?? '-' }}
            </div>
            <div class="text-muted" style="font-size:.72rem;">
                Task: {{ $session->step ?: '—' }} &bull; Start:
                {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i') : '-' }}
            </div>
        </div>
        <div class="d-flex gap-1">
            @if ($session->status === 'frozen')
                <button class="btn btn-xs btn-outline-success unfreeze-btn" data-timing-id="{{ $session->id }}"
                    style="font-size:.7rem;padding:2px 8px;" title="Resume">
                    <i class="bi bi-play-fill"></i>
                </button>
            @else
                <button class="btn btn-xs btn-outline-warning freeze-btn" data-timing-id="{{ $session->id }}"
                    style="font-size:.7rem;padding:2px 8px;" title="Pause">
                    <i class="bi bi-pause-fill"></i>
                </button>
            @endif
            <button class="btn btn-xs btn-outline-danger stop-btn" data-timing-id="{{ $session->id }}"
                style="font-size:.7rem;padding:2px 8px;" title="Stop">
                <i class="bi bi-stop-fill"></i>
            </button>
        </div>
    </div>
</div>
