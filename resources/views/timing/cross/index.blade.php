@extends('layouts.app')

@push('styles')
    <style>
        /* ── CROSS TIMING THEME: Teal-Cyan (distinct from Mascot purple-blue) ── */
        .bg-gradient-cross {
            background: linear-gradient(135deg, #0f766e 0%, #0891b2 100%);
        }

        .jo-card {
            cursor: pointer;
            border: 2px solid #dee2e6;
            transition: all .22s;
            border-radius: .5rem;
        }

        .emp-card {
            cursor: pointer;
            border: 2px solid #dee2e6;
            transition: all .18s;
            border-radius: .5rem;
            user-select: none;
        }

        .emp-card:hover:not(.emp-selected):not(.emp-disabled) {
            border-color: #0891b2;
            background: #f0fdfe;
        }

        .emp-card.emp-selected {
            border-color: #0f766e !important;
            background: linear-gradient(135deg, rgba(15, 118, 110, .1) 0%, rgba(8, 145, 178, .07) 100%);
        }

        .emp-card.emp-disabled {
            opacity: .55;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .dept-chip {
            border-radius: 20px !important;
            font-size: .68rem !important;
            padding: 2px 11px !important;
        }

        .dept-chip.active {
            background: #0f766e !important;
            border-color: #0f766e !important;
            color: #fff !important;
        }

        .jo-card.jo-selected {
            border-color: #0f766e !important;
            background: linear-gradient(135deg, rgba(15, 118, 110, .15) 0%, rgba(8, 145, 178, .1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(15, 118, 110, .35);
        }

        .jo-card:hover:not(.jo-selected) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, .12);
            border-color: #0891b2 !important;
        }

        .session-card {
            transition: .15s;
        }

        .session-card:hover {
            background: #f0fdfe;
        }

        .session-card.selected-for-bulk {
            background: #fef2f2;
        }

        .session-card {
            transition: all 0.2s;
        }

        .session-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, .1) !important;
        }

        #sessions-container {
            padding: 0.75rem;
        }

        .duration-display {
            font-variant-numeric: tabular-nums;
        }

        #emp-grid::-webkit-scrollbar,
        #jo-list::-webkit-scrollbar {
            width: 4px;
        }

        #emp-grid::-webkit-scrollbar-thumb,
        #jo-list::-webkit-scrollbar-thumb {
            background: #0891b2;
            border-radius: 2px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4" id="timing-cross-page">

        {{-- ── HEADER ── --}}
        <div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
            <div>
                <h2 class="mb-0 fw-semibold" style="font-size:1.4rem;">
                    <i class="bi bi-shuffle me-2" style="color:#0f766e;"></i>
                    <span
                        style="background:linear-gradient(90deg,#0f766e,#0891b2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Cross
                        Dept Timing</span>
                </h2>
                <small class="text-muted">Universal timing — semua karyawan &amp; semua job order, lintas departemen</small>
            </div>
            @if ($bypassAttendance)
                <span class="badge bg-warning text-dark ms-auto">
                    <i class="bi bi-exclamation-triangle me-1"></i>Dev Mode (Absensi Bypass)
                </span>
            @endif
        </div>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button"
                    class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">

            {{-- ─── LEFT: Start New Session ─── --}}
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-gradient-cross text-white py-2 d-flex align-items-center gap-2">
                        <h5 class="mb-0"><i class="bi bi-play-circle-fill me-2"></i>Start New Session</h5>
                    </div>
                    <div class="card-body pb-3">

                        {{-- STEP 1: Job Order --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                <span class="badge rounded-circle me-1" style="background:#0f766e;">1</span>Select Job Order
                            </label>
                            <input type="text" id="jo-search" class="form-control form-control-sm mb-2"
                                placeholder="Search Job Order Or Project name">
                            <div id="jo-list" class="row g-2" style="max-height:280px;overflow-y:auto;padding:2px;">
                                @forelse($jobOrders as $jo)
                                    @php
                                        $deliveryDate = $jo->delivery_date
                                            ? \Carbon\Carbon::parse($jo->delivery_date)
                                            : null;
                                        $daysLeft = $deliveryDate
                                            ? (int) now()
                                                ->startOfDay()
                                                ->diffInDays($deliveryDate->copy()->startOfDay(), false)
                                            : null;
                                    @endphp
                                    <div class="col-md-4 col-sm-6 jo-card-wrapper"
                                        data-jo-name="{{ strtolower($jo->name) }}"
                                        data-jo-project="{{ strtolower($jo->project->name ?? '') }}">
                                        <div class="card jo-card border-2 h-100" data-jo-id="{{ $jo->id }}"
                                            data-jo-name="{{ strtolower($jo->name) }}"
                                            data-jo-project="{{ strtolower($jo->project->name ?? '') }}"
                                            style="cursor:pointer; transition: all 0.3s;">
                                            <div class="card-body p-2">
                                                <h6 class="mb-1 fw-bold lh-sm" style="font-size:0.78rem;">
                                                    {{ $jo->name }}</h6>
                                                <div class="text-muted mb-1" style="font-size:0.68rem;">
                                                    <i class="bi bi-folder2 me-1"></i>{{ $jo->project->name ?? 'N/A' }}
                                                </div>
                                                @if ($deliveryDate)
                                                    @if ($daysLeft < 0)
                                                        <span class="badge bg-danger" style="font-size:0.6rem;"><i
                                                                class="bi bi-exclamation-triangle-fill me-1"></i>OVERDUE
                                                            {{ abs($daysLeft) }}d</span>
                                                    @elseif($daysLeft === 0)
                                                        <span class="badge bg-danger" style="font-size:0.6rem;"><i
                                                                class="bi bi-alarm-fill me-1"></i>DUE TODAY</span>
                                                    @elseif($daysLeft <= 3)
                                                        <span class="badge bg-warning text-dark"
                                                            style="font-size:0.6rem;"><i
                                                                class="bi bi-clock-fill me-1"></i>{{ $daysLeft }}d
                                                            left</span>
                                                    @else
                                                        <span class="badge bg-info text-dark" style="font-size:0.6rem;"><i
                                                                class="bi bi-calendar-check me-1"></i>{{ $daysLeft }}d
                                                            left</span>
                                                    @endif
                                                    <div class="text-muted mt-1" style="font-size:0.62rem;">
                                                        {{ $deliveryDate->format('d M Y') }}</div>
                                                @else
                                                    <span class="badge bg-secondary" style="font-size:0.6rem;">No
                                                        deadline</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted py-3 small">Tidak ada job order aktif.</div>
                                @endforelse
                            </div>
                            <div id="jo-selected-display" class="mt-2 d-none">
                                <span class="badge py-1 px-2" style="background:#0f766e;font-size:.78rem;">
                                    <i class="bi bi-check-circle me-1"></i><span id="jo-selected-label"></span>
                                </span>
                            </div>
                        </div>

                        {{-- STEP 2: Employees --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                <span class="badge rounded-circle me-1" style="background:#0f766e;">2</span>Select Employees
                                <span class="text-muted fw-normal ms-1">({{ $employees->count() }} total)</span>
                            </label>

                            <div class="d-flex gap-1 mb-2">
                                <input type="text" id="emp-search" class="form-control form-control-sm flex-grow-1"
                                    placeholder="Search by name, position, or department">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-2" id="select-all-btn"
                                    title="Pilih Semua Terlihat"><i class="bi bi-check-all"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm px-2" id="deselect-all-btn"
                                    title="Batal Semua"><i class="bi bi-x-lg"></i></button>
                            </div>

                            {{-- Dept filter chips --}}
                            <div class="d-flex flex-wrap gap-1 mb-1" id="dept-filter-chips">
                                <button type="button" class="btn btn-sm dept-chip active" data-dept="all">Semua
                                    Dept</button>
                                @foreach ($departments as $dept)
                                    <button type="button" class="btn btn-sm btn-outline-secondary dept-chip"
                                        data-dept="{{ $dept->id }}">{{ $dept->name }}</button>
                                @endforeach
                            </div>



                            {{-- Employee Grid --}}
                            <div id="emp-grid" class="row g-2"
                                style="max-height:480px;overflow-y:auto;padding:2px;border:1px solid #e9ecef;border-radius:6px;">
                                @foreach ($employees as $emp)
                                    @php $hasActive = in_array($emp->id, $employeesWithActiveSessions); @endphp
                                    <div class="col-md-4 col-sm-6 emp-card-wrapper" data-emp-id="{{ $emp->id }}"
                                        data-emp-name="{{ strtolower($emp->name) }}"
                                        data-emp-position="{{ strtolower($emp->position ?? '') }}"
                                        data-emp-dept="{{ $emp->department_id }}"
                                        data-emp-dept-name="{{ strtolower($emp->department->name ?? '') }}"
                                        data-has-active="{{ $hasActive ? 'true' : 'false' }}">
                                        <div class="emp-card p-2 text-center h-100 position-relative {{ $hasActive ? 'emp-disabled' : '' }}"
                                            style="min-height:72px;">
                                            <div class="form-check position-absolute top-0 end-0 m-1">
                                                <input class="form-check-input emp-checkbox" type="checkbox"
                                                    id="emp-{{ $emp->id }}" value="{{ $emp->id }}"
                                                    {{ $hasActive ? 'disabled' : '' }}>
                                            </div>
                                            @if ($hasActive)
                                                <span
                                                    class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark"
                                                    style="font-size:.55rem;">
                                                    <i class="bi bi-circle-fill me-1"
                                                        style="font-size:.45rem;"></i>Running
                                                </span>
                                            @endif
                                            @if ($emp->photo)
                                                <img src="{{ asset('storage/' . $emp->photo) }}"
                                                    class="rounded-circle mb-1 border" width="38" height="38"
                                                    style="object-fit:cover;">
                                            @else
                                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-1"
                                                    style="width:38px;height:38px;">
                                                    <i class="bi bi-person text-white" style="font-size:.9rem;"></i>
                                                </div>
                                            @endif
                                            <div class="fw-semibold lh-sm text-truncate" style="font-size:.72rem;">
                                                {{ $emp->name }}</div>
                                            <div class="text-muted text-truncate" style="font-size:.62rem;">
                                                {{ $emp->position ?? '-' }}</div>
                                            @if ($emp->department)
                                                <div class="text-truncate" style="font-size:.58rem;color:#0891b2;">
                                                    {{ $emp->department->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                                <small class="text-muted"><span id="selected-count">0</span> karyawan dipilih <span
                                        id="filtered-count" class="ms-1"></span></small>
                                <small class="text-muted ms-auto"><i class="bi bi-people-fill me-1"></i>Total:
                                    <strong>{{ $employees->count() }}</strong> Employee Active
                                    <span class="text-info ms-1"><i class="bi bi-arrow-down-circle"></i> Scroll for
                                        view all</span>
                                </small>
                                <span id="active-warn" class="text-warning d-none small"><i
                                        class="bi bi-exclamation-triangle-fill me-1"></i>Some are already running</span>
                            </div>
                        </div>

                        {{-- STEP 3: Parts (REQUIRED) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                <span class="badge rounded-circle me-1" style="background:#0f766e;">3</span>Parts
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select id="parts-select" class="form-select form-select-sm select2-parts" required>
                                <option value="">— Select Parts —</option>
                                @foreach ($timingParts as $part)
                                    <option value="{{ $part->name }}">{{ $part->name }}</option>
                                @endforeach
                            </select>
                            <small id="parts-error" class="text-danger d-none">Parts wajib dipilih sebelum start.</small>
                        </div>

                        {{-- STEP 4: Task & Session Type --}}
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <span class="badge rounded-circle me-1" style="background:#0f766e;">4</span>Task <span
                                        class="text-danger">*</span>
                                </label>
                                <input type="text" id="task-input" class="form-control form-control-sm"
                                    placeholder="e.g., Jahit, Airbrush..." required>
                                <small id="task-error" class="text-danger d-none">Task wajib diisi.</small>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Session Type</label>
                                <select id="session-type-select" class="form-select form-select-sm" required>
                                    <option value="">— Select type —</option>
                                    <option value="mass_production">Mass Production</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                        </div>

                        <button type="button" class="btn w-100 fw-bold text-white" id="start-btn"
                            style="background:linear-gradient(135deg,#0f766e,#0891b2);" disabled>
                            <i class="bi bi-play-circle-fill me-2"></i><span id="start-btn-text">START WORK</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ─── RIGHT: Active Sessions ─── --}}
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div
                        class="card-header bg-gradient-cross text-white d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Active Sessions
                            <span class="badge bg-white text-dark ms-2"
                                id="active-count">{{ $activeSessions->count() }}</span>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light" id="refresh-sessions-btn" title="Refresh"><i
                                    class="bi bi-arrow-clockwise"></i></button>
                            <button class="btn btn-sm btn-light text-danger fw-bold" id="bulk-stop-btn" disabled><i
                                    class="bi bi-stop-circle me-1"></i>Bulk Stop</button>
                        </div>
                    </div>

                    <div id="bulk-stop-panel" class="d-none px-3 pt-2 pb-2 border-bottom bg-danger bg-opacity-10">
                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <label class="form-label small mb-1 fw-semibold">Output Qty</label>
                                <input type="number" id="bulk-output-qty" class="form-control form-control-sm"
                                    value="1" min="0" step="0.01">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1 fw-semibold">Tipe Ukur</label>
                                <select id="bulk-measurement-type" class="form-select form-select-sm">
                                    @forelse($units as $unit)
                                        <option value="{{ strtolower($unit->name) }}"
                                            {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>{{ $unit->name }}
                                        </option>
                                    @empty
                                        <option value="pcs" selected>Pcs</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-danger w-100 btn-sm fw-bold" id="confirm-bulk-stop-btn">
                                    <i class="bi bi-stop-fill me-1"></i>Stop (<span id="bulk-selected-count">0</span>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="sessions-container" style="max-height:calc(100vh - 220px);overflow-y:auto;">
                        @if ($activeSessions->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-shuffle"
                                    style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:.5rem;"></i>
                                Belum ada sesi aktif hari ini.
                            </div>
                        @else
                            @foreach ($activeSessions as $session)
                                @php
                                    $isFrozen = $session->status === 'frozen';
                                    $isRepair = $session->session_type === 'repair';
                                    $deptData = $session->department_specific_data ?? [];
                                    $prevProgress =
                                        $deptData['current_progress'] ?? ($deptData['previous_progress'] ?? 0);
                                    $cardBorderStyle = $isFrozen
                                        ? 'border-color:#f59e0b!important;'
                                        : ($isRepair
                                            ? 'border-color:#fd7e14!important;'
                                            : 'border-color:#0f766e!important;');
                                    $durationStyle = $isFrozen
                                        ? 'color:#f59e0b;'
                                        : ($isRepair
                                            ? 'color:#fd7e14;'
                                            : 'color:#0f766e;');
                                @endphp
                                <div class="card session-card mb-3 border-2" id="session-card-{{ $session->id }}"
                                    data-timing-id="{{ $session->id }}" style="{{ $cardBorderStyle }}">
                                    <div class="card-body p-3">
                                        {{-- Header: checkbox + avatar + badge + name + timer --}}
                                        <div class="d-flex align-items-center mb-2 gap-2">
                                            <input type="checkbox" class="form-check-input bulk-session-cb flex-shrink-0"
                                                value="{{ $session->id }}">
                                            @if ($session->employee->photo ?? false)
                                                <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                    class="rounded-circle border flex-shrink-0" width="40"
                                                    height="40" style="object-fit:cover;">
                                            @else
                                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width:40px;height:40px;">
                                                    <i class="bi bi-person text-white"></i>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <h6 class="mb-0">
                                                    @if ($isFrozen)
                                                        <span class="badge bg-warning text-dark me-1"><i
                                                                class="bi bi-pause-circle"></i> PAUSED</span>
                                                    @elseif ($isRepair)
                                                        <span class="badge me-1" style="background-color:#fd7e14;"><i
                                                                class="bi bi-tools"></i> REPAIR</span>
                                                    @else
                                                        <span class="badge me-1" style="background-color:#0f766e;"><i
                                                                class="bi bi-grid-3x3-gap-fill"></i> MASS PROD</span>
                                                    @endif
                                                    {{ $session->employee->name ?? '-' }}
                                                </h6>
                                                <small
                                                    class="text-muted">{{ $session->employee->position ?? 'N/A' }}</small>
                                            </div>
                                            @if ($isFrozen)
                                                <span
                                                    class="fs-5 fw-bold text-warning flex-shrink-0">{{ $deptData['frozen_duration'] ?? '00:00:00' }}</span>
                                            @else
                                                <span class="duration-display fs-5 fw-bold flex-shrink-0"
                                                    style="{{ $durationStyle }}"
                                                    data-start-time="{{ $session->start_time }}">00:00:00</span>
                                            @endif
                                        </div>
                                        {{-- Details --}}
                                        <div class="border-top pt-2 mb-2 small">
                                            <div class="mb-1"><strong>Job Order:</strong>
                                                {{ $session->jobOrder->name ?? '-' }}<br>
                                                <strong>Project:</strong> {{ $session->project->name ?? '-' }}
                                            </div>
                                            <div class="row g-1">
                                                <div class="col-6"><strong>Step:</strong> {{ $session->step ?: '—' }}
                                                </div>
                                                @if ($session->parts)
                                                    <div class="col-6"><strong>Part:</strong> {{ $session->parts }}</div>
                                                @endif
                                            </div>
                                            <div class="text-muted mt-1"><i class="bi bi-clock me-1"></i>Started:
                                                {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i:s') : '-' }}
                                            </div>
                                        </div>
                                        {{-- Action buttons --}}
                                        <div class="d-flex gap-2">
                                            @if ($isFrozen)
                                                <button class="btn btn-success btn-sm unfreeze-btn flex-grow-1"
                                                    data-timing-id="{{ $session->id }}"
                                                    data-employee-name="{{ $session->employee->name ?? '' }}">
                                                    <i class="bi bi-play-circle me-1"></i>Continue
                                                </button>
                                            @else
                                                <button class="btn btn-warning btn-sm freeze-btn flex-shrink-0"
                                                    data-timing-id="{{ $session->id }}"
                                                    data-employee-name="{{ $session->employee->name ?? '' }}">
                                                    <i class="bi bi-pause-circle me-1"></i>Pause
                                                </button>
                                                <button class="btn btn-danger btn-sm stop-btn flex-grow-1"
                                                    data-timing-id="{{ $session->id }}"
                                                    data-employee-name="{{ $session->employee->name ?? '' }}"
                                                    data-job-order="{{ $session->jobOrder->name ?? '' }}"
                                                    data-job-order-id="{{ $session->job_order_id }}"
                                                    data-previous-progress="{{ $prevProgress }}">
                                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER QTY
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── STOP MODAL ── --}}
    <div class="modal fade" id="stopModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#0f766e,#0891b2);">
                    <h5 class="modal-title text-white"><i class="bi bi-stop-circle me-2"></i>Complete Work Session</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="stop-work-form">
                    <div class="modal-body">
                        <input type="hidden" id="stop-timing-id">
                        <input type="hidden" id="stop-job-order-id">
                        <div id="stop-session-info" class="alert alert-info mb-3 small"></div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Stage Completed <span
                                    class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="stop-stage" name="stage" required>
                                <option value="">Choose stage...</option>
                                <option value="1">1 — Design &amp; Prototyping</option>
                                <option value="2">2 — Structure Approval</option>
                                <option value="3">3 — Structure &amp; Sample</option>
                                <option value="4">4 — Visual Review &amp; Paint Prep</option>
                                <option value="5">5 — Adjustment &amp; Finishing (Structure)</option>
                                <option value="6">6 — Final Structure Approval</option>
                                <option value="7">7 — Wrapping &amp; Painting</option>
                                <option value="8">8 — Wrapping Approval</option>
                                <option value="9">9 — Finishing &amp; Approval</option>
                                <option value="10">10 — Final QC &amp; Shipping</option>
                            </select>
                            <small class="text-muted">Each stage = 10% progress.</small>
                        </div>
                        <div class="mb-3">
                            <div class="alert alert-success mb-0 py-2 small">
                                <strong>Previous Progress:</strong> <span id="previous-progress-display">0</span>%<br>
                                <strong>Will be updated to:</strong> <span id="current-progress-display"
                                    class="fw-bold text-primary">0</span>%
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Output Qty <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="stop-output-qty"
                                    min="0" step="0.1" value="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small">Measurement Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="stop-measurement-type" required>
                                    @forelse($units as $unit)
                                        <option value="{{ strtolower($unit->name) }}"
                                            {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>{{ $unit->name }}
                                        </option>
                                    @empty
                                        <option value="pcs" selected>Pcs</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn fw-bold text-white" id="stop-submit-btn"
                            style="background:linear-gradient(135deg,#0f766e,#0891b2);">
                            <i class="bi bi-stop-circle me-1"></i>Stop &amp; Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const CSRF = '{{ csrf_token() }}';
            let selectedJoId = null;
            let selectedEmployees = new Set();

            /* ══ SELECT2 INIT ══ */
            $('#parts-select').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih parts —',
                allowClear: true,
                width: '100%',
            }).on('select2:select select2:clear', function() {
                $('#parts-error').toggleClass('d-none', !!$(this).val());
                updateStartBtn();
            });
            let selectedBulkIds = new Set();
            let activeDept = 'all';

            /* ══ JO SEARCH ══ */
            $('#jo-search').on('input', function() {
                const q = $(this).val().toLowerCase().trim();
                $('#jo-list .jo-card-wrapper').each(function() {
                    const card = $(this).find('.jo-card');
                    const show = !q || (card.data('jo-name') || '').includes(q) || (card.data(
                        'jo-project') || '').includes(q);
                    $(this).toggle(show);
                });
            });

            /* ══ JO SELECT ══ */
            $(document).on('click', '.jo-card', function() {
                const joId = String($(this).data('jo-id'));
                const joName = $(this).find('h6').first().text().trim();
                if (selectedJoId === joId) {
                    selectedJoId = null;
                    $(this).removeClass('jo-selected');
                    $('#jo-selected-display').addClass('d-none');
                } else {
                    $('.jo-card').removeClass('jo-selected');
                    $(this).addClass('jo-selected');
                    selectedJoId = joId;
                    $('#jo-selected-label').text(joName);
                    $('#jo-selected-display').removeClass('d-none');
                }
                updateStartBtn();
            });

            /* ══ DEPT FILTER ══ */
            $(document).on('click', '.dept-chip', function() {
                $('.dept-chip').removeClass('active').addClass('btn-outline-secondary').css({
                    'background': '',
                    'border-color': '',
                    'color': ''
                });
                $(this).removeClass('btn-outline-secondary').addClass('active').css({
                    'background': '#0f766e',
                    'border-color': '#0f766e',
                    'color': '#fff'
                });
                activeDept = String($(this).data('dept'));
                applyEmployeeFilters();
            });

            /* ══ EMP SEARCH ══ */
            $('#emp-search').on('input', applyEmployeeFilters);

            function applyEmployeeFilters() {
                const q = ($('#emp-search').val() || '').toLowerCase().trim();
                let visible = 0;
                $('.emp-card-wrapper').each(function() {
                    const nameOk = !q ||
                        ($(this).data('emp-name') || '').includes(q) ||
                        ($(this).data('emp-position') || '').includes(q) ||
                        ($(this).data('emp-dept-name') || '').includes(q);
                    const deptOk = activeDept === 'all' || String($(this).data('emp-dept')) === activeDept;
                    const show = nameOk && deptOk;
                    $(this).toggle(show);
                    if (show) visible++;
                });
                const isFiltered = activeDept !== 'all' || q;
                $('#filtered-count').html(isFiltered ? '<span class="badge" style="background:#0891b2;">' +
                    visible + ' tampil</span>' : '');
            }

            /* ══ EMP SELECT (click card, but not the checkbox itself) ══ */
            $(document).on('click', '.emp-card-wrapper .emp-card:not(.emp-disabled)', function(e) {
                if ($(e.target).hasClass('emp-checkbox') || $(e.target).closest('.form-check').length)
                    return;
                const cb = $(this).find('.emp-checkbox');
                cb.prop('checked', !cb.prop('checked')).trigger('change');
            });

            $(document).on('change', '.emp-checkbox', function() {
                const empId = String($(this).val());
                const card = $(this).closest('.emp-card-wrapper').find('.emp-card');
                if ($(this).is(':checked')) {
                    selectedEmployees.add(empId);
                    card.addClass('emp-selected');
                } else {
                    selectedEmployees.delete(empId);
                    card.removeClass('emp-selected');
                }
                updateSelectedCount();
                updateStartBtn();
            });

            $('#select-all-btn').on('click', function() {
                $('.emp-card-wrapper:visible .emp-checkbox:not(:disabled)').prop('checked', true).trigger(
                    'change');
            });
            $('#deselect-all-btn').on('click', function() {
                $('.emp-checkbox:checked').prop('checked', false).trigger('change');
            });

            function updateSelectedCount() {
                $('#selected-count').text(selectedEmployees.size);
                let hasActive = false;
                selectedEmployees.forEach(id => {
                    if ($('.emp-card-wrapper[data-emp-id="' + id + '"]').attr('data-has-active') === 'true')
                        hasActive = true;
                });
                $('#active-warn').toggleClass('d-none', !hasActive);
            }

            function updateStartBtn() {
                const parts = ($('#parts-select').val() || '').trim();
                const sessionType = ($('#session-type-select').val() || '').trim();
                const task = ($('#task-input').val() || '').trim();
                const ok = selectedJoId && selectedEmployees.size > 0 && parts !== '' && sessionType !== '' &&
                    task !== '';
                $('#start-btn').prop('disabled', !ok);
                $('#start-btn-text').text(ok ? 'START (' + selectedEmployees.size + ' karyawan)' : 'START WORK');
            }

            /* Parts change → update button */
            $('#parts-select').on('change', function() {
                $('#parts-error').toggleClass('d-none', $(this).val() !== '');
                updateStartBtn();
            });

            /* Task change → update button */
            $('#task-input').on('input', function() {
                $('#task-error').toggleClass('d-none', $(this).val().trim() !== '');
                updateStartBtn();
            });

            /* Session type change → update button */
            $('#session-type-select').on('change', function() {
                updateStartBtn();
            });

            /* ══ START ══ */
            $('#start-btn').on('click', function() {
                if (!selectedJoId || selectedEmployees.size === 0) return;
                const task = ($('#task-input').val() || '').trim();
                if (!task) {
                    $('#task-error').removeClass('d-none');
                    $('#task-input').focus();
                    return;
                }
                const parts = ($('#parts-select').val() || '').trim();
                if (!parts) {
                    $('#parts-error').removeClass('d-none');
                    $('#parts-select').trigger('focus');
                    return;
                }
                const sessionType = ($('#session-type-select').val() || '').trim();
                if (!sessionType) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Session Type wajib',
                        text: 'Pilih Session Type sebelum start.'
                    });
                    $('#session-type-select').focus();
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Starting...');

                const employeeArr = Array.from(selectedEmployees);
                const tasksPayload = {},
                    sessionTypesPayload = {};
                employeeArr.forEach(id => {
                    tasksPayload[id] = task;
                    sessionTypesPayload[id] = sessionType;
                });

                $.ajax({
                    url: '{{ route('timing-cross.start') }}',
                    method: 'POST',
                    data: {
                        _token: CSRF,
                        employees: employeeArr,
                        job_order_id: selectedJoId,
                        task,
                        tasks: tasksPayload,
                        session_type: sessionType,
                        session_types: sessionTypesPayload,
                        parts
                    },
                    success(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Started!',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadActiveSessions();
                            selectedEmployees.clear();
                            $('.emp-checkbox:checked').prop('checked', false);
                            $('.emp-card').removeClass('emp-selected');
                            $('#task-input').val('');
                            $('#parts-select').val('');
                            $('#session-type-select').val('');
                            updateSelectedCount();
                            updateStartBtn();
                        }
                    },
                    error(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to start.'
                        });
                    },
                    complete() {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-play-circle-fill me-2"></i><span id="start-btn-text">START WORK</span>'
                        );
                        updateStartBtn();
                    },
                });
            });

            /* ══ STOP MODAL ══ */
            let currentPrevProgress = 0;
            $(document).on('click', '.stop-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                const joName = $(this).data('job-order');
                const joId = $(this).data('job-order-id');
                const prevProg = parseInt($(this).data('previous-progress') || 0);
                currentPrevProgress = prevProg;

                $('#stop-timing-id').val(timingId);
                $('#stop-job-order-id').val(joId);
                $('#stop-session-info').html('<strong>' + empName + '</strong> &bull; ' + joName);
                $('#previous-progress-display').text(prevProg);
                $('#current-progress-display').text(prevProg);

                const currentStage = Math.floor(prevProg / 10);
                $('#stop-stage option').each(function() {
                    const v = parseInt($(this).val());
                    if (v && v < currentStage) {
                        $(this).prop('disabled', true).text($(this).text().replace(' ✓', '') +
                            ' ✓');
                    } else {
                        $(this).prop('disabled', false).text($(this).text().replace(' ✓', ''));
                    }
                });
                $('#stop-stage').val(currentStage > 0 ? currentStage : '').trigger('change');
                $('#stop-output-qty').val(1);
                $('#stopModal').modal('show');
            });

            $('#stop-stage').on('change', function() {
                const stage = parseInt($(this).val());
                $('#current-progress-display').text(stage >= 1 && stage <= 10 ? stage * 10 :
                    currentPrevProgress);
            });

            /* ══ STOP SUBMIT ══ */
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();
                const stage = parseInt($('#stop-stage').val());
                if (!stage) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stage required',
                        text: 'Pilih stage terlebih dahulu.'
                    });
                    return;
                }
                const btn = $('#stop-submit-btn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Stopping...');
                $.ajax({
                    url: '{{ route('timing-cross.stop') }}',
                    method: 'POST',
                    data: {
                        _token: CSRF,
                        timing_id: $('#stop-timing-id').val(),
                        stage,
                        output_qty: $('#stop-output-qty').val(),
                        measurement_type: $('#stop-measurement-type').val()
                    },
                    success(res) {
                        if (res.success) {
                            $('#stopModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Done!',
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            loadActiveSessions();
                        }
                    },
                    error(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to stop.'
                        });
                    },
                    complete() {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-stop-circle me-1"></i>Stop &amp; Save');
                    },
                });
            });

            /* ══ FREEZE ══ */
            $(document).on('click', '.freeze-btn', function() {
                const timingId = $(this).data('timing-id'),
                    name = $(this).data('employee-name');
                Swal.fire({
                    icon: 'info',
                    title: 'Pause Session?',
                    html: 'Timer <strong>' + name + '</strong> akan di-pause.',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: '<i class="bi bi-pause-circle"></i> Pause',
                    cancelButtonText: 'Batal'
                }).then(r => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('timing-cross.freeze') }}',
                        method: 'POST',
                        data: {
                            _token: CSRF,
                            timing_id: timingId
                        },
                        success(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Paused!',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadActiveSessions();
                            } else Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                            });
                        },
                        error(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Failed.'
                            });
                        }
                    });
                });
            });

            /* ══ UNFREEZE ══ */
            $(document).on('click', '.unfreeze-btn', function() {
                const timingId = $(this).data('timing-id'),
                    name = $(this).data('employee-name');
                Swal.fire({
                    icon: 'question',
                    title: 'Lanjutkan Session?',
                    html: 'Timer <strong>' + name + '</strong> akan dilanjutkan.',
                    showCancelButton: true,
                    confirmButtonColor: '#0f766e',
                    confirmButtonText: '<i class="bi bi-play-circle"></i> Lanjut',
                    cancelButtonText: 'Batal'
                }).then(r => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('timing-cross.unfreeze') }}',
                        method: 'POST',
                        data: {
                            _token: CSRF,
                            timing_id: timingId
                        },
                        success(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Dilanjutkan!',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadActiveSessions();
                            } else Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                            });
                        },
                        error(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Failed.'
                            });
                        }
                    });
                });
            });

            /* ══ LOAD ACTIVE SESSIONS ══ */
            function loadActiveSessions() {
                $.ajax({
                    url: '{{ route('timing-cross.active-sessions') }}',
                    method: 'GET',
                    success(res) {
                        if (!res.success) return;
                        $('#active-count').text(res.sessions.length);
                        const container = $('#sessions-container');
                        if (res.sessions.length === 0) {
                            container.html(
                                '<div class="text-center py-5 text-muted"><i class="bi bi-shuffle" style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:.5rem;"></i>Belum ada sesi aktif hari ini.</div>'
                            );
                            startDurationTimers();
                            return;
                        }
                        container.html(res.sessions.map(s => buildSessionCard(s)).join(''));
                        startDurationTimers();
                    },
                });
            }

            function buildSessionCard(s) {
                const isFrozen = s.status === 'frozen',
                    isRepair = s.session_type === 'repair';
                const borderStyle = isFrozen ? 'border-color:#f59e0b!important;' : (isRepair ?
                    'border-color:#fd7e14!important;' : 'border-color:#0f766e!important;');
                const durStyle = isFrozen ? 'color:#f59e0b;' : (isRepair ? 'color:#fd7e14;' : 'color:#0f766e;');

                const badge = isFrozen ?
                    '<span class="badge bg-warning text-dark me-1"><i class="bi bi-pause-circle"></i> PAUSED</span>' :
                    (isRepair ?
                        '<span class="badge me-1" style="background-color:#fd7e14;"><i class="bi bi-tools"></i> REPAIR</span>' :
                        '<span class="badge me-1" style="background-color:#0f766e;"><i class="bi bi-grid-3x3-gap-fill"></i> MASS PROD</span>'
                    );

                const timer = isFrozen ?
                    '<span class="fs-5 fw-bold text-warning flex-shrink-0">' + (s.frozen_duration || '00:00:00') +
                    '</span>' :
                    '<span class="duration-display fs-5 fw-bold flex-shrink-0" style="' + durStyle +
                    '" data-start-time="' + s.start_time + '">00:00:00</span>';

                const avatar =
                    '<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;"><i class="bi bi-person text-white"></i></div>';

                const partsRow = s.parts ? '<div class="col-6"><strong>Part:</strong> ' + s.parts + '</div>' : '';

                const actionBtns = isFrozen ?
                    '<button class="btn btn-success btn-sm unfreeze-btn flex-grow-1" data-timing-id="' + s.id +
                    '" data-employee-name="' + s.employee_name +
                    '"><i class="bi bi-play-circle me-1"></i>Continue</button>' :
                    '<button class="btn btn-warning btn-sm freeze-btn flex-shrink-0" data-timing-id="' + s.id +
                    '" data-employee-name="' + s.employee_name +
                    '"><i class="bi bi-pause-circle me-1"></i>Pause</button>' +
                    '<button class="btn btn-danger btn-sm stop-btn flex-grow-1" data-timing-id="' + s.id +
                    '" data-employee-name="' + s.employee_name + '" data-job-order="' + s.job_order_name +
                    '" data-job-order-id="' + s.job_order_id + '" data-previous-progress="' + (s
                        .previous_progress || 0) +
                    '"><i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER QTY</button>';

                return '<div class="card session-card mb-3 border-2" id="session-card-' + s.id +
                    '" data-timing-id="' + s.id + '" style="' + borderStyle + '">' +
                    '<div class="card-body p-3">' +
                    '<div class="d-flex align-items-center mb-2 gap-2">' +
                    '<input type="checkbox" class="form-check-input bulk-session-cb flex-shrink-0" value="' + s.id +
                    '">' +
                    avatar +
                    '<div class="flex-grow-1" style="min-width:0;"><h6 class="mb-0">' + badge + s.employee_name +
                    '</h6>' +
                    '<small class="text-muted">' + (s.employee_position || 'N/A') + '</small></div>' +
                    timer +
                    '</div>' +
                    '<div class="border-top pt-2 mb-2 small">' +
                    '<div class="mb-1"><strong>Job Order:</strong> ' + s.job_order_name +
                    '<br><strong>Project:</strong> ' + s.project_name + '</div>' +
                    '<div class="row g-1"><div class="col-6"><strong>Step:</strong> ' + (s.task || '—') + '</div>' +
                    partsRow + '</div>' +
                    '<div class="text-muted mt-1"><i class="bi bi-clock me-1"></i>Started: ' + (s.start_time ||
                        '-') + '</div>' +
                    '</div>' +
                    '<div class="d-flex gap-2">' + actionBtns + '</div>' +
                    '</div></div>';
            }

            /* ══ DURATION TIMERS ══ */
            let durationInterval;

            function startDurationTimers() {
                clearInterval(durationInterval);
                durationInterval = setInterval(() => {
                    $('.duration-display').each(function() {
                        const st = $(this).data('start-time');
                        if (!st) return;
                        const now = new Date();
                        const start = new Date(now.toISOString().split('T')[0] + 'T' + st);
                        const diff = Math.max(0, Math.floor((now - start) / 1000));
                        const h = Math.floor(diff / 3600),
                            m = Math.floor((diff % 3600) / 60),
                            s = diff % 60;
                        $(this).text(String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') +
                            ':' + String(s).padStart(2, '0'));
                    });
                }, 1000);
            }
            startDurationTimers();

            /* ══ REFRESH ══ */
            $('#refresh-sessions-btn').on('click', loadActiveSessions);

            /* ══ BULK STOP ══ */
            $(document).on('change', '.bulk-session-cb', function() {
                const id = String($(this).val());
                if ($(this).is(':checked')) {
                    selectedBulkIds.add(id);
                    $(this).closest('.session-card').addClass('selected-for-bulk');
                } else {
                    selectedBulkIds.delete(id);
                    $(this).closest('.session-card').removeClass('selected-for-bulk');
                }
                const has = selectedBulkIds.size > 0;
                $('#bulk-stop-btn').prop('disabled', !has);
                $('#bulk-stop-panel').toggleClass('d-none', !has);
                $('#bulk-selected-count').text(selectedBulkIds.size);
            });

            $('#confirm-bulk-stop-btn').on('click', function() {
                if (selectedBulkIds.size === 0) return;
                const btn = $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Stopping...');
                $.ajax({
                    url: '{{ route('timing-cross.bulk-stop') }}',
                    method: 'POST',
                    data: {
                        _token: CSRF,
                        timing_ids: Array.from(selectedBulkIds),
                        output_qty: $('#bulk-output-qty').val(),
                        measurement_type: $('#bulk-measurement-type').val()
                    },
                    success(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Done!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            selectedBulkIds.clear();
                            $('#bulk-stop-panel').addClass('d-none');
                            $('#bulk-stop-btn').prop('disabled', true);
                            loadActiveSessions();
                        }
                    },
                    error(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed.'
                        });
                    },
                    complete() {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-stop-fill me-1"></i>Stop (<span id="bulk-selected-count">0</span>)'
                        );
                    }
                });
            });

            /* ══ Auto-refresh every 30s ══ */
            setInterval(loadActiveSessions, 30000);
        });
    </script>
@endpush
