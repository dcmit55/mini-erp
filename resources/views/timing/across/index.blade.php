@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" id="timing-across-page">

    {{-- ── HEADER ── --}}
    <div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
        <div>
            <h4 class="mb-0 fw-bold"
                style="background:linear-gradient(90deg,#7c3aed,#2563eb);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                <i class="fas fa-layer-group me-2" style="-webkit-text-fill-color:#7c3aed;"></i>Timing Across
            </h4>
            <small class="text-muted">Universal timing — semua karyawan &amp; semua job order</small>
        </div>
        @if ($bypassAttendance)
            <span class="badge bg-warning text-dark ms-auto">
                <i class="bi bi-exclamation-triangle me-1"></i>Dev Mode (Absensi Bypass)
            </span>
        @endif
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">

        {{-- ─────────── LEFT: Start New Session ─────────── --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-play-circle text-success me-2"></i>Start New Session
                    </h6>
                </div>
                <div class="card-body pb-3">

                    {{-- STEP 1: Job Order --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                            <span class="badge bg-primary rounded-circle me-1">1</span>Pilih Job Order
                        </label>
                        <input type="text" id="jo-search" class="form-control form-control-sm mb-2"
                            placeholder="���  Cari job order atau project...">

                        <div id="jo-cards"
                            style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
                            @forelse($jobOrders as $jo)
                                @php
                                    $daysLeft = null;
                                    if ($jo->delivery_date) {
                                        $d = \Carbon\Carbon::parse($jo->delivery_date);
                                        $daysLeft = (int) now()->startOfDay()->diffInDays($d->startOfDay(), false);
                                    }
                                @endphp
                                <div class="jo-item d-flex align-items-center gap-2 px-3 py-2"
                                    style="cursor:pointer;border-bottom:1px solid #f1f1f1;transition:.15s;"
                                    data-jo-id="{{ $jo->id }}"
                                    data-jo-name="{{ strtolower($jo->name) }}"
                                    data-jo-project="{{ strtolower($jo->project->name ?? '') }}">
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="fw-semibold text-truncate" style="font-size:.82rem;line-height:1.3;">
                                            {{ $jo->name }}</div>
                                        <div class="text-muted text-truncate" style="font-size:.7rem;">
                                            <i class="bi bi-folder2 me-1"></i>{{ $jo->project->name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if ($daysLeft !== null)
                                            @if ($daysLeft < 0)
                                                <span class="badge bg-danger" style="font-size:.62rem;">OVERDUE</span>
                                            @elseif($daysLeft === 0)
                                                <span class="badge bg-danger" style="font-size:.62rem;">DUE TODAY</span>
                                            @elseif($daysLeft <= 3)
                                                <span class="badge bg-warning text-dark" style="font-size:.62rem;">{{ $daysLeft }}d</span>
                                            @else
                                                <span class="badge bg-info text-dark" style="font-size:.62rem;">{{ $daysLeft }}d</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.62rem;">No date</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">Tidak ada job order aktif.</div>
                            @endforelse
                        </div>

                        <div id="jo-selected-display" class="mt-2 d-none">
                            <span class="badge bg-success py-1 px-2" style="font-size:.78rem;">
                                <i class="bi bi-check-circle me-1"></i><span id="jo-selected-label"></span>
                            </span>
                        </div>
                    </div>

                    {{-- STEP 2: Employees --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                            <span class="badge bg-primary rounded-circle me-1">2</span>Pilih Karyawan
                            <span class="text-muted fw-normal ms-1">({{ $employees->count() }} total)</span>
                        </label>

                        <div class="d-flex gap-1 mb-2">
                            <input type="text" id="emp-search" class="form-control form-control-sm flex-grow-1"
                                placeholder="���  Cari nama / posisi / dept...">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                                id="select-all-btn" title="Pilih Semua Terlihat">
                                <i class="bi bi-check-all"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                                id="deselect-all-btn" title="Batal Semua">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        {{-- Dept filter chips --}}
                        <div class="d-flex flex-wrap gap-1 mb-2" id="dept-filters">
                            <button type="button" class="btn btn-secondary dept-filter active" data-dept="all">All</button>
                            @foreach ($departments as $dept)
                                <button type="button" class="btn btn-outline-secondary dept-filter" data-dept="{{ $dept->id }}">
                                    {{ $dept->name }}
                                </button>
                            @endforeach
                        </div>

                        <div id="emp-list"
                            style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
                            @foreach ($employees as $emp)
                                @php $hasActive = in_array($emp->id, $employeesWithActiveSessions); @endphp
                                <label class="emp-item d-flex align-items-center gap-2 px-3 py-2 mb-0"
                                    style="cursor:pointer;border-bottom:1px solid #f1f1f1;transition:.15s;"
                                    data-emp-id="{{ $emp->id }}"
                                    data-emp-name="{{ strtolower($emp->name) }}"
                                    data-emp-position="{{ strtolower($emp->position ?? '') }}"
                                    data-emp-dept="{{ $emp->department_id }}"
                                    data-emp-dept-name="{{ strtolower($emp->department->name ?? '') }}"
                                    data-has-active="{{ $hasActive ? 'true' : 'false' }}">
                                    <input type="checkbox" class="form-check-input emp-checkbox flex-shrink-0 mt-0"
                                        id="emp-{{ $emp->id }}" value="{{ $emp->id }}">
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="fw-semibold text-truncate" style="font-size:.8rem;line-height:1.3;">
                                            {{ $emp->name }}</div>
                                        <div class="text-muted text-truncate" style="font-size:.68rem;">
                                            {{ $emp->position ?? '-' }}
                                            @if($emp->department) &bull; {{ $emp->department->name }} @endif
                                        </div>
                                    </div>
                                    @if ($hasActive)
                                        <span class="badge bg-warning text-dark flex-shrink-0" style="font-size:.6rem;" title="Sedang ada sesi aktif">
                                            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Running
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <small class="text-muted mt-1 d-block">
                            <span id="selected-count">0</span> karyawan dipilih
                            <span id="active-warn" class="text-warning d-none ms-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Beberapa sudah running — akan di-pause otomatis
                            </span>
                        </small>
                    </div>

                    {{-- STEP 3: Task & Session Type --}}
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                <span class="badge bg-primary rounded-circle me-1">3</span>Task
                                <span class="fw-normal">(opsional)</span>
                            </label>
                            <input type="text" id="task-input" class="form-control form-control-sm"
                                placeholder="e.g., Sewing, Airbrush...">
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold text-muted mb-1">Session Type</label>
                            <select id="session-type-select" class="form-select form-select-sm">
                                <option value="mass_production">Mass Production</option>
                                <option value="repair">Repair</option>
                            </select>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success w-100 fw-bold" id="start-btn" disabled>
                        <i class="fas fa-play me-2"></i><span id="start-btn-text">START WORK</span>
                    </button>

                </div>
            </div>
        </div>

        {{-- ─────────── RIGHT: Active Sessions ─────────── --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-bolt text-warning me-2"></i>Sesi Aktif
                        <span class="badge bg-secondary ms-1" id="active-count">{{ $activeSessions->count() }}</span>
                    </h6>
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-sm btn-outline-secondary" id="refresh-sessions-btn" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="bulk-stop-btn" disabled>
                            <i class="bi bi-stop-circle me-1"></i>Bulk Stop
                        </button>
                    </div>
                </div>

                {{-- Bulk Stop Panel --}}
                <div id="bulk-stop-panel" class="d-none px-3 pt-2 pb-1 border-bottom bg-danger bg-opacity-10">
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <label class="form-label small mb-1 fw-semibold">Output Qty</label>
                            <input type="number" id="bulk-output-qty" class="form-control form-control-sm" value="1" min="0" step="0.01">
                        </div>
                        <div class="col-4">
                            <label class="form-label small mb-1 fw-semibold">Tipe Ukur</label>
                            <select id="bulk-measurement-type" class="form-select form-select-sm">
                                @forelse($units as $unit)
                                    <option value="{{ strtolower($unit->name) }}" {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>{{ $unit->name }}</option>
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

                <div id="sessions-container" style="max-height:calc(100vh - 240px);overflow-y:auto;">
                    @if ($activeSessions->isEmpty())
                        <div class="text-center py-5 text-muted" id="no-sessions-msg">
                            <i class="fas fa-hourglass-half fa-2x mb-2 d-block opacity-25"></i>
                            Belum ada sesi aktif.
                        </div>
                    @else
                        @foreach ($activeSessions as $session)
                            @php
                                $isFrozen = $session->status === 'frozen';
                                $isRepair = $session->session_type === 'repair';
                                $deptData = $session->department_specific_data ?? [];
                                $prevProgress = $deptData['current_progress'] ?? $deptData['previous_progress'] ?? 0;
                                $cardColor = $isFrozen ? '#f59e0b' : ($isRepair ? '#fd7e14' : '#198754');
                            @endphp
                            <div class="session-card p-3 border-bottom"
                                id="session-card-{{ $session->id }}"
                                data-timing-id="{{ $session->id }}"
                                style="border-left:4px solid {{ $cardColor }};">
                                <div class="d-flex align-items-start gap-2">
                                    <input type="checkbox" class="form-check-input bulk-session-cb flex-shrink-0 mt-1" value="{{ $session->id }}">
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                            <span class="fw-semibold" style="font-size:.88rem;">{{ $session->employee->name ?? '-' }}</span>
                                            @if ($isFrozen)
                                                <span class="badge bg-warning text-dark" style="font-size:.62rem;"><i class="bi bi-pause-circle me-1"></i>PAUSED</span>
                                            @elseif ($isRepair)
                                                <span class="badge" style="background:#fd7e14;font-size:.62rem;"><i class="bi bi-tools me-1"></i>REPAIR</span>
                                            @else
                                                <span class="badge bg-success" style="font-size:.62rem;"><i class="bi bi-grid-3x3-gap-fill me-1"></i>MASS PROD</span>
                                            @endif
                                        </div>
                                        <div class="text-muted text-truncate" style="font-size:.75rem;">
                                            <i class="bi bi-briefcase me-1"></i>{{ $session->jobOrder->name ?? '-' }} &bull; {{ $session->project->name ?? '-' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem;">
                                            <i class="bi bi-tools me-1"></i>{{ $session->step ?: '—' }} &bull;
                                            <i class="bi bi-clock me-1"></i>{{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i') : '-' }}
                                        </div>
                                        <div class="mt-1">
                                            @if ($isFrozen)
                                                <span class="fw-bold text-warning" style="font-size:.9rem;">{{ $deptData['frozen_duration'] ?? '00:00:00' }}</span>
                                            @else
                                                <span class="duration-display fw-bold" style="font-size:.9rem;color:{{ $cardColor }};" data-start-time="{{ $session->start_time }}">00:00:00</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-1 flex-shrink-0">
                                        @if ($isFrozen)
                                            <button class="btn btn-success btn-sm unfreeze-btn" data-timing-id="{{ $session->id }}" data-employee-name="{{ $session->employee->name ?? '' }}" style="font-size:.72rem;padding:3px 10px;">
                                                <i class="bi bi-play-fill me-1"></i>Lanjut
                                            </button>
                                        @else
                                            <button class="btn btn-warning btn-sm freeze-btn" data-timing-id="{{ $session->id }}" data-employee-name="{{ $session->employee->name ?? '' }}" style="font-size:.72rem;padding:3px 10px;">
                                                <i class="bi bi-pause-fill me-1"></i>Pause
                                            </button>
                                        @endif
                                        <button class="btn btn-danger btn-sm stop-btn"
                                            data-timing-id="{{ $session->id }}"
                                            data-employee-name="{{ $session->employee->name ?? '' }}"
                                            data-job-order="{{ $session->jobOrder->name ?? '' }}"
                                            data-job-order-id="{{ $session->job_order_id }}"
                                            data-previous-progress="{{ $prevProgress }}"
                                            style="font-size:.72rem;padding:3px 10px;">
                                            <i class="bi bi-stop-fill me-1"></i>Stop
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- end .row --}}
</div>

{{-- ── STOP MODAL ── --}}
<div class="modal fade" id="stopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-stop-circle me-2"></i>Complete Work Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stop-work-form">
                <div class="modal-body">
                    <input type="hidden" id="stop-timing-id">
                    <input type="hidden" id="stop-job-order-id">

                    <div id="stop-session-info" class="alert alert-info mb-3 small"></div>

                    {{-- Stage --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Select Stage Completed <span class="text-danger">*</span>
                        </label>
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

                    {{-- Progress preview --}}
                    <div class="mb-3">
                        <div class="alert alert-success mb-0 py-2 small">
                            <strong>Previous Progress:</strong> <span id="previous-progress-display">0</span>%<br>
                            <strong>Will be updated to:</strong> <span id="current-progress-display" class="fw-bold text-primary">0</span>%
                        </div>
                    </div>

                    {{-- Output + Measurement --}}
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Output Qty <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="stop-output-qty" min="0" step="0.1" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Measurement Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="stop-measurement-type" required>
                                @forelse($units as $unit)
                                    <option value="{{ strtolower($unit->name) }}" {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>{{ $unit->name }}</option>
                                @empty
                                    <option value="pcs" selected>Pcs</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold" id="stop-submit-btn">
                        <i class="bi bi-stop-circle me-1"></i>Stop &amp; Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .jo-item:hover, .jo-item.selected { background:#f0f4ff; }
    .jo-item.selected { box-shadow: inset 3px 0 0 #6366f1; }
    .emp-item:hover { background:#f9fafb; }
    .emp-item.selected { background:#ecfdf5; }
    .session-card { transition:.15s; }
    .session-card:hover { background:#fafafa; }
    .session-card.selected-for-bulk { background:#fef2f2; }
    .dept-filter { border-radius:20px !important; font-size:.68rem !important; padding:2px 10px !important; }
    #emp-list .emp-item, #jo-cards .jo-item { border-bottom:1px solid #f1f1f1; }
    #emp-list .emp-item:last-child, #jo-cards .jo-item:last-child { border-bottom:none; }
</style>
@endsection

@push('scripts')
<script>
$(function() {
    const CSRF = '{{ csrf_token() }}';
    let selectedJoId = null;
    let selectedEmployees = new Set();
    let selectedBulkIds = new Set();
    let activeDept = 'all';

    /* ─── JO SEARCH ─── */
    $('#jo-search').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        $('#jo-cards .jo-item').each(function() {
            const show = !q || ($(this).data('jo-name')||'').includes(q) || ($(this).data('jo-project')||'').includes(q);
            $(this).toggle(show);
        });
    });

    /* ─── JO SELECT ─── */
    $(document).on('click', '#jo-cards .jo-item', function() {
        const joId = String($(this).data('jo-id'));
        const joName = $(this).find('.fw-semibold').first().text().trim();
        if (selectedJoId === joId) {
            selectedJoId = null;
            $(this).removeClass('selected');
            $('#jo-selected-display').addClass('d-none');
        } else {
            $('#jo-cards .jo-item').removeClass('selected');
            $(this).addClass('selected');
            selectedJoId = joId;
            $('#jo-selected-label').text(joName);
            $('#jo-selected-display').removeClass('d-none');
        }
        updateStartBtn();
    });

    /* ─── DEPT FILTER ─── */
    $(document).on('click', '.dept-filter', function() {
        $('.dept-filter').removeClass('btn-secondary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-secondary active');
        activeDept = String($(this).data('dept'));
        applyEmployeeFilters();
    });

    /* ─── EMP SEARCH ─── */
    $('#emp-search').on('input', applyEmployeeFilters);

    function applyEmployeeFilters() {
        const q = $('#emp-search').val().toLowerCase().trim();
        $('#emp-list .emp-item').each(function() {
            const nameOk = !q
                || ($(this).data('emp-name')||'').includes(q)
                || ($(this).data('emp-position')||'').includes(q)
                || ($(this).data('emp-dept-name')||'').includes(q);
            const deptOk = activeDept === 'all' || String($(this).data('emp-dept')) === activeDept;
            $(this).toggle(nameOk && deptOk);
        });
    }

    /* ─── EMP SELECT ─── */
    $(document).on('change', '.emp-checkbox', function() {
        const empId = String($(this).val());
        const $item = $(this).closest('.emp-item');
        if ($(this).is(':checked')) {
            selectedEmployees.add(empId);
            $item.addClass('selected');
        } else {
            selectedEmployees.delete(empId);
            $item.removeClass('selected');
        }
        updateSelectedCount();
        updateStartBtn();
    });

    $('#select-all-btn').on('click', function() {
        $('#emp-list .emp-item:visible .emp-checkbox').prop('checked', true).trigger('change');
    });
    $('#deselect-all-btn').on('click', function() {
        $('#emp-list .emp-checkbox:checked').prop('checked', false).trigger('change');
    });

    function updateSelectedCount() {
        const n = selectedEmployees.size;
        $('#selected-count').text(n);
        let hasActive = false;
        selectedEmployees.forEach(id => {
            if ($('[data-emp-id="' + id + '"]').data('has-active') === true || $('[data-emp-id="' + id + '"]').attr('data-has-active') === 'true') hasActive = true;
        });
        $('#active-warn').toggleClass('d-none', !hasActive);
    }

    function updateStartBtn() {
        const ok = selectedJoId && selectedEmployees.size > 0;
        $('#start-btn').prop('disabled', !ok);
        $('#start-btn-text').text(ok ? 'START (' + selectedEmployees.size + ' karyawan)' : 'START WORK');
    }

    /* ─── START ─── */
    $('#start-btn').on('click', function() {
        if (!selectedJoId || selectedEmployees.size === 0) return;
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Starting...');

        const employeeArr = Array.from(selectedEmployees);
        const task = $('#task-input').val().trim();
        const sessionType = $('#session-type-select').val();
        const tasksPayload = {}, sessionTypesPayload = {};
        employeeArr.forEach(id => { tasksPayload[id] = task; sessionTypesPayload[id] = sessionType; });

        $.ajax({
            url: '{{ route('timing-across.start') }}',
            method: 'POST',
            data: { _token: CSRF, employees: employeeArr, job_order_id: selectedJoId,
                    task, tasks: tasksPayload, session_type: sessionType, session_types: sessionTypesPayload },
            success(res) {
                if (res.success) {
                    Swal.fire({ icon:'success', title:'Started!', text:res.message, timer:2000, showConfirmButton:false });
                    loadActiveSessions();
                    selectedEmployees.clear();
                    $('#emp-list .emp-checkbox:checked').prop('checked', false);
                    $('#emp-list .emp-item').removeClass('selected');
                    $('#task-input').val('');
                    updateSelectedCount(); updateStartBtn();
                }
            },
            error(xhr) {
                Swal.fire({ icon:'error', title:'Error', text: xhr.responseJSON?.message || 'Failed to start.' });
            },
            complete() {
                btn.prop('disabled', false).html('<i class="fas fa-play me-2"></i><span id="start-btn-text">START WORK</span>');
                updateStartBtn();
            },
        });
    });

    /* ─── STOP MODAL OPEN ─── */
    let currentPrevProgress = 0;

    $(document).on('click', '.stop-btn', function() {
        const timingId  = $(this).data('timing-id');
        const empName   = $(this).data('employee-name');
        const joName    = $(this).data('job-order');
        const joId      = $(this).data('job-order-id');
        const prevProg  = parseInt($(this).data('previous-progress') || 0);
        currentPrevProgress = prevProg;

        $('#stop-timing-id').val(timingId);
        $('#stop-job-order-id').val(joId);
        $('#stop-session-info').html('<strong>' + empName + '</strong> &bull; ' + joName);
        $('#previous-progress-display').text(prevProg);
        $('#current-progress-display').text(prevProg);
        $('#stop-stage').val('');
        $('#stop-output-qty').val(1);
        $('#stopModal').modal('show');
    });

    /* ─── Stage preview ─── */
    $('#stop-stage').on('change', function() {
        const stage = parseInt($(this).val());
        if (stage >= 1 && stage <= 10) {
            $('#current-progress-display').text(stage * 10);
        } else {
            $('#current-progress-display').text(currentPrevProgress);
        }
    });

    /* ─── STOP SUBMIT ─── */
    $('#stop-work-form').on('submit', function(e) {
        e.preventDefault();
        const stage = parseInt($('#stop-stage').val());
        if (!stage) { Swal.fire({ icon:'warning', title:'Stage required', text:'Pilih stage terlebih dahulu.' }); return; }

        const btn = $('#stop-submit-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Stopping...');

        $.ajax({
            url: '{{ route('timing-across.stop') }}',
            method: 'POST',
            data: {
                _token: CSRF,
                timing_id: $('#stop-timing-id').val(),
                stage,
                output_qty: $('#stop-output-qty').val(),
                measurement_type: $('#stop-measurement-type').val(),
            },
            success(res) {
                if (res.success) {
                    $('#stopModal').modal('hide');
                    Swal.fire({ icon:'success', title:'Done!', text:res.message, timer:1800, showConfirmButton:false });
                    loadActiveSessions();
                }
            },
            error(xhr) {
                Swal.fire({ icon:'error', title:'Error', text: xhr.responseJSON?.message || 'Failed to stop.' });
            },
            complete() {
                btn.prop('disabled', false).html('<i class="bi bi-stop-circle me-1"></i>Stop &amp; Save');
            },
        });
    });

    /* ─── FREEZE ─── */
    $(document).on('click', '.freeze-btn', function() {
        const timingId = $(this).data('timing-id');
        const name = $(this).data('employee-name');
        Swal.fire({ icon:'info', title:'Pause Session?',
            html:'Timer <strong>' + name + '</strong> akan di-pause.',
            showCancelButton:true, confirmButtonColor:'#ffc107',
            confirmButtonText:'<i class="bi bi-pause-circle"></i> Pause', cancelButtonText:'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ route('timing-across.freeze') }}', method:'POST',
                data: { _token:CSRF, timing_id:timingId },
                success(res) {
                    if (res.success) { Swal.fire({ icon:'success', title:'Paused!', text:res.message, timer:1500, showConfirmButton:false }); loadActiveSessions(); }
                    else Swal.fire({ icon:'error', title:'Error', text:res.message });
                },
                error(xhr) { Swal.fire({ icon:'error', title:'Error', text:xhr.responseJSON?.message||'Failed.' }); },
            });
        });
    });

    /* ─── UNFREEZE ─── */
    $(document).on('click', '.unfreeze-btn', function() {
        const timingId = $(this).data('timing-id');
        const name = $(this).data('employee-name');
        Swal.fire({ icon:'question', title:'Lanjutkan Session?',
            html:'Timer <strong>' + name + '</strong> akan dilanjutkan.',
            showCancelButton:true, confirmButtonColor:'#198754',
            confirmButtonText:'<i class="bi bi-play-circle"></i> Lanjut', cancelButtonText:'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ route('timing-across.unfreeze') }}', method:'POST',
                data: { _token:CSRF, timing_id:timingId },
                success(res) {
                    if (res.success) { Swal.fire({ icon:'success', title:'Dilanjutkan!', text:res.message, timer:1500, showConfirmButton:false }); loadActiveSessions(); }
                    else Swal.fire({ icon:'error', title:'Error', text:res.message });
                },
                error(xhr) { Swal.fire({ icon:'error', title:'Error', text:xhr.responseJSON?.message||'Failed.' }); },
            });
        });
    });

    /* ─── LOAD ACTIVE SESSIONS ─── */
    function loadActiveSessions() {
        $.ajax({
            url: '{{ route('timing-across.active-sessions') }}',
            method: 'GET',
            success(res) {
                if (!res.success) return;
                const container = $('#sessions-container');
                $('#active-count').text(res.sessions.length);
                if (res.sessions.length === 0) {
                    container.html('<div class="text-center py-5 text-muted"><i class="fas fa-hourglass-half fa-2x mb-2 d-block opacity-25"></i>Belum ada sesi aktif.</div>');
                    startDurationTimers(); return;
                }
                container.html(res.sessions.map(s => buildSessionCard(s)).join(''));
                startDurationTimers();
            },
        });
    }

    function buildSessionCard(s) {
        const isFrozen = s.status === 'frozen';
        const isRepair = s.session_type === 'repair';
        const cardColor = isFrozen ? '#f59e0b' : (isRepair ? '#fd7e14' : '#198754');
        const statusBadge = isFrozen
            ? '<span class="badge bg-warning text-dark" style="font-size:.62rem;"><i class="bi bi-pause-circle me-1"></i>PAUSED</span>'
            : (isRepair
                ? '<span class="badge" style="background:#fd7e14;font-size:.62rem;"><i class="bi bi-tools me-1"></i>REPAIR</span>'
                : '<span class="badge bg-success" style="font-size:.62rem;"><i class="bi bi-grid-3x3-gap-fill me-1"></i>MASS PROD</span>');
        const timerHtml = isFrozen
            ? '<span class="fw-bold text-warning" style="font-size:.9rem;">' + (s.frozen_duration || '00:00:00') + '</span>'
            : '<span class="duration-display fw-bold" style="font-size:.9rem;color:' + cardColor + ';" data-start-time="' + s.start_time + '">00:00:00</span>';
        const actionBtns = isFrozen
            ? '<button class="btn btn-success btn-sm unfreeze-btn" data-timing-id="' + s.id + '" data-employee-name="' + s.employee_name + '" style="font-size:.72rem;padding:3px 10px;"><i class="bi bi-play-fill me-1"></i>Lanjut</button>'
            : '<button class="btn btn-warning btn-sm freeze-btn" data-timing-id="' + s.id + '" data-employee-name="' + s.employee_name + '" style="font-size:.72rem;padding:3px 10px;"><i class="bi bi-pause-fill me-1"></i>Pause</button>';

        return '<div class="session-card p-3 border-bottom" id="session-card-' + s.id + '" data-timing-id="' + s.id + '" style="border-left:4px solid ' + cardColor + ';">'
            + '<div class="d-flex align-items-start gap-2">'
            + '<input type="checkbox" class="form-check-input bulk-session-cb flex-shrink-0 mt-1" value="' + s.id + '">'
            + '<div class="flex-grow-1" style="min-width:0;">'
            + '<div class="d-flex align-items-center gap-1 flex-wrap mb-1"><span class="fw-semibold" style="font-size:.88rem;">' + s.employee_name + '</span>' + statusBadge + '</div>'
            + '<div class="text-muted text-truncate" style="font-size:.75rem;"><i class="bi bi-briefcase me-1"></i>' + s.job_order_name + ' &bull; ' + s.project_name + '</div>'
            + '<div class="text-muted" style="font-size:.72rem;"><i class="bi bi-tools me-1"></i>' + (s.task || '—') + ' &bull; <i class="bi bi-clock me-1"></i>' + (s.start_time ? s.start_time.slice(0,5) : '-') + '</div>'
            + '<div class="mt-1">' + timerHtml + '</div>'
            + '</div>'
            + '<div class="d-flex flex-column gap-1 flex-shrink-0">'
            + actionBtns
            + '<button class="btn btn-danger btn-sm stop-btn" data-timing-id="' + s.id + '" data-employee-name="' + s.employee_name + '" data-job-order="' + s.job_order_name + '" data-job-order-id="' + s.job_order_id + '" data-previous-progress="' + (s.previous_progress || 0) + '" style="font-size:.72rem;padding:3px 10px;"><i class="bi bi-stop-fill me-1"></i>Stop</button>'
            + '</div></div></div>';
    }

    /* ─── DURATION TIMERS ─── */
    let durationInterval;
    function startDurationTimers() {
        clearInterval(durationInterval);
        durationInterval = setInterval(() => {
            $('.duration-display').each(function() {
                const startTime = $(this).data('start-time');
                if (!startTime) return;
                const now = new Date();
                const today = now.toISOString().split('T')[0];
                const start = new Date(today + ' ' + startTime);
                const diff = Math.max(0, Math.floor((now - start) / 1000));
                const h = Math.floor(diff / 3600);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;
                $(this).text(String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0'));
            });
        }, 1000);
    }
    startDurationTimers();

    /* ─── REFRESH ─── */
    $('#refresh-sessions-btn').on('click', loadActiveSessions);

    /* ─── BULK STOP ─── */
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
        const btn = $(this).prop('disabled', true).text('Stopping...');
        $.ajax({
            url: '{{ route('timing-across.bulk-stop') }}', method: 'POST',
            data: { _token:CSRF, timing_ids:Array.from(selectedBulkIds),
                    output_qty:$('#bulk-output-qty').val(),
                    measurement_type:$('#bulk-measurement-type').val() },
            success(res) {
                if (res.success) {
                    Swal.fire({ icon:'success', title:'Done!', text:res.message, timer:1500, showConfirmButton:false });
                    selectedBulkIds.clear();
                    $('#bulk-stop-panel').addClass('d-none');
                    $('#bulk-stop-btn').prop('disabled', true);
                    loadActiveSessions();
                }
            },
            error(xhr) { Swal.fire({ icon:'error', title:'Error', text:xhr.responseJSON?.message||'Failed.' }); },
            complete() { btn.prop('disabled', false).html('<i class="bi bi-stop-fill me-1"></i>Stop (<span id="bulk-selected-count">0</span>)'); },
        });
    });

    /* ─── Auto-refresh every 30s ─── */
    setInterval(loadActiveSessions, 30000);
});
</script>
@endpush
