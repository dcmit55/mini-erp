@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.5rem;"></i>
                <h2 class="mb-0" style="font-size:1.2rem;">🤖 Animatronics Running Monitor</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2 flex-wrap">
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Start New Session
                </a>
                <a href="{{ route('live-workstation.index', ['type' => 'animatronics']) }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-desktop me-1"></i> Live Workstation
                </a>
                <a href="{{ route('timings.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-table me-1"></i> All Timings
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 small">Running</h6>
                                <h2 class="mb-0 fw-bold" id="total-running">{{ $totalRunning ?? 0 }}</h2>
                                <small>{{ $animatronicsDept->name ?? 'Animatronics Department' }}</small>
                            </div>
                            <i class="fas fa-play-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-danger text-white">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 small">Paused / Break</h6>
                                <h2 class="mb-0 fw-bold" id="total-frozen">{{ $totalFrozen ?? 0 }}</h2>
                                <small>Timer paused</small>
                            </div>
                            <i class="bi bi-pause-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 small">Active Employees</h6>
                                <h2 class="mb-0 fw-bold" id="total-employees">{{ $totalEmployees ?? 0 }}</h2>
                                <small>Working on Animatronics</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Type Summary -->
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">📦 Mass Production</div>
                            <small class="text-muted" style="font-size: 12px;">Produksi massal</small>
                        </div>
                        <h3 class="mb-0 text-secondary fw-bold">{{ $totalMassProduction ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm" style="background-color:#fff3e0;">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">🔧 Repair / Rework</div>
                            <small class="text-muted" style="font-size: 10px;">Perbaikan</small>
                        </div>
                        <h3 class="mb-0 fw-bold" style="color:#fd7e14;">{{ $totalRepair ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clocked-In Employees -->
        <div id="clocked-in-panel" class="card shadow-sm border-0 mb-3" style="display:none; border-left:4px solid #fda085 !important;">
            <div class="card-header d-flex align-items-center justify-content-between py-2" style="background:linear-gradient(135deg,#f6d365 0%,#fda085 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-fingerprint text-white"></i>
                    <span class="fw-bold text-white" style="font-size:.88rem;">Hadir — Belum Ada Sesi</span>
                    <span class="badge bg-white text-dark ms-1" id="clocked-in-count" style="font-size:.73rem;">0</span>
                </div>
                <small class="text-white opacity-75" style="font-size:.7rem;">Sudah clock-in via fingerprint · belum start timing</small>
            </div>
            <div class="card-body py-2 px-3">
                <div id="clocked-in-list" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>

        <!-- Running Sessions Grouped by Job Order -->
        @if(isset($groupedSessions) && $groupedSessions->count() > 0)
            @foreach($groupedSessions as $jobOrderName => $sessions)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-gradient-animatronics text-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>{{ $jobOrderName }}
                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Employee(s)</span>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input group-select-all" type="checkbox"
                                    data-group="{{ Str::slug($jobOrderName) }}"
                                    id="grp-all-{{ Str::slug($jobOrderName) }}">
                                <label class="form-check-label text-white small fw-normal" for="grp-all-{{ Str::slug($jobOrderName) }}">Select All</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger bulk-stop-btn"
                                data-group="{{ Str::slug($jobOrderName) }}" style="display:none;">
                                <i class="bi bi-stop-circle me-1"></i>Bulk Stop (<span class="bulk-count">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            @foreach($sessions as $session)
                                @php
                                    $deptData = $session->department_specific_data ?? [];
                                    $isFrozen = $session->status === 'frozen';
                                    $isAutoBreak = !empty($deptData['auto_break_paused']);
                                    $sessionType = $session->session_type ?? 'mass_production';
                                    $isRepair = $sessionType === 'repair';
                                    
                                    if ($isFrozen) {
                                        $cardBg = '#FEE2E2';
                                        $borderColor = '#DC2626';
                                        $badgeText = '⏸ PAUSED' . ($isAutoBreak ? ' (BREAK)' : '');
                                        $badgeBg = '#DC2626';
                                    } elseif ($isRepair) {
                                        $cardBg = '#FFF3E0';
                                        $borderColor = '#E65100';
                                        $badgeText = '🔧 REPAIR';
                                        $badgeBg = '#E65100';
                                    } else {
                                        $cardBg = '#E8F5E9';
                                        $borderColor = '#4CAF50';
                                        $badgeText = '📦 PRODUCTION';
                                        $badgeBg = '#4CAF50';
                                    }
                                @endphp
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 d-flex" id="session-{{ $session->id }}">
                                    <div class="card w-100 shadow-sm" style="background: {{ $cardBg }}; border-top: 3px solid {{ $borderColor }}; border-radius: 8px;">
                                        <div class="card-body p-2 d-flex flex-column" style="min-height: 340px;">
                                            <!-- Checkbox -->
                                            <div class="form-check mb-2">
                                                <input class="form-check-input session-checkbox" type="checkbox"
                                                    value="{{ $session->id }}"
                                                    data-group="{{ Str::slug($jobOrderName) }}"
                                                    id="chk-{{ $session->id }}">
                                                <label class="form-check-label small text-muted" for="chk-{{ $session->id }}">Select</label>
                                            </div>

                                            <!-- Badge Status -->
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge px-2 py-1" style="background: {{ $badgeBg }}; color: white; font-size: 9px;">{{ $badgeText }}</span>
                                                <span class="text-muted" style="font-size: 14px;"><i class="bi bi-clock"></i> {{ $session->start_time }}</span>
                                            </div>
                                            
                                            <!-- Employee Info: Foto di kiri, Nama & Posisi di kanan -->
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="flex-shrink-0">
                                                    @if ($session->employee && $session->employee->photo)
                                                        <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                            class="rounded-circle" width="60" height="60"
                                                            style="object-fit: cover; border: 2px solid {{ $borderColor }};">
                                                    @else
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 60px; height: 60px; background: {{ $borderColor }}20;">
                                                            <i class="bi bi-person text-secondary fs-3"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-semibold small text-truncate">{{ $session->employee->name ?? 'Unknown' }}</div>
                                                    <div class="text-muted text-truncate" style="font-size: 11px;">{{ $session->employee->position ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Timer -->
                                            <div class="text-center mb-2 py-1 bg-white bg-opacity-60 rounded">
                                                @if ($isFrozen)
                                                    <span class="fw-bold font-monospace" style="font-size: 15px; color: {{ $borderColor }};">
                                                        {{ $deptData['frozen_duration'] ?? '00:00:00' }}
                                                    </span>
                                                @else
                                                    <span class="duration-display fw-bold font-monospace"
                                                        style="font-size: 16px; color: {{ $borderColor }};"
                                                        data-start-time="{{ $session->start_time }}">
                                                        {{ $session->duration ?? '00:00:00' }}
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <!-- Job Info dengan JO dan Project -->
                                            <div class="border-top pt-2 small flex-grow-1">
                                                <!-- JO (Job Order) - BOLD -->
                                                <div class="mb-1 text-truncate" title="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                    <span class="text-muted">JO:</span> 
                                                    <strong>{{ $session->jobOrder->name ?? 'N/A' }}</strong>
                                                </div>
                                                <!-- Project - TIDAK BOLD -->
                                                <div class="mb-1 text-truncate" title="{{ $session->jobOrder->project->name ?? 'N/A' }}">
                                                    <span class="text-muted">Project:</span> 
                                                    {{ $session->jobOrder->project->name ?? 'N/A' }}
                                                </div>
                                                <div class="row g-0 mb-1">
                                                    <div class="col-6 text-truncate">
                                                        <span class="text-muted">Step:</span> {{ $session->step ?? '-' }}
                                                    </div>
                                                    <div class="col-6 text-truncate">
                                                        <span class="text-muted">Part:</span> {{ $session->parts ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="mt-2 pt-1">
                                                @if ($isFrozen)
                                                    <button class="btn btn-success btn-sm w-100 unfreeze-btn"
                                                        data-timing-id="{{ $session->id }}"
                                                        data-employee-name="{{ $session->employee->name ?? 'Unknown' }}">
                                                        <i class="bi bi-play-circle me-1"></i>RESUME
                                                    </button>
                                                @else
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-info btn-sm flex-grow-1 freeze-btn"
                                                            data-timing-id="{{ $session->id }}"
                                                            data-employee-name="{{ $session->employee->name ?? 'Unknown' }}">
                                                            <i class="bi bi-pause-circle me-1"></i>PAUSE
                                                        </button>
                                                        <button class="btn btn-danger btn-sm flex-grow-1 stop-work-btn"
                                                            data-timing-id="{{ $session->id }}"
                                                            data-employee-name="{{ $session->employee->name ?? 'Unknown' }}"
                                                            data-job-order="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                            <i class="bi bi-stop-circle me-1"></i>STOP
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-robot text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-2">No Running Animatronics Sessions</h5>
                    <p class="text-muted small">Start a new timing session from Animatronics Timing</p>
                    <a href="{{ route('animatronics-timing.index') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="fas fa-robot me-1"></i> Go to Animatronics Timing
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Bulk Stop Modal -->
    <div class="modal fade" id="bulkStopModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title mb-0"><i class="bi bi-stop-circle me-1"></i>Bulk Stop Sessions</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-stop-form">
                    <div class="modal-body py-2 px-3">
                        @csrf
                        <div id="bulk-stop-info" class="alert alert-warning py-1 px-2 mb-2 small"></div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Measurement Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="bulk-measurement-type" name="measurement_type" required>
                                @forelse($units ?? [] as $unit)
                                    <option value="{{ strtolower($unit->name) }}"
                                        {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @empty
                                    <option value="pcs" selected>Pcs</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Output Qty (per session) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="bulk-output-qty"
                                name="output_qty" min="0" step="0.1" value="1" required>
                            <small class="text-muted" style="font-size:.68rem;">Applied to all selected sessions</small>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm" id="bulk-stop-submit-btn">
                            <i class="bi bi-stop-circle me-1"></i>Stop All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stop Work Modal -->
    <div class="modal fade" id="stopWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-stop-circle me-2"></i>Stop Work Session</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="stop-work-form">
                    <div class="modal-body">
                        <div id="stop-session-info" class="alert alert-info mb-3"></div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Measurement Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="stop-measurement-type" name="measurement_type" required>
                                @forelse($units ?? [] as $unit)
                                    <option value="{{ strtolower($unit->name) }}"
                                        {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @empty
                                    <option value="pcs" selected>Pcs</option>
                                @endforelse
                            </select>
                            <small class="text-muted">Select measurement unit for output quantity</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Output Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="stop-output-qty"
                                name="output_qty" min="0" step="0.1" value="1" required>
                            <small class="text-muted">Enter the total quantity produced during this session</small>
                        </div>

                        <input type="hidden" id="stop-timing-id" name="timing_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-stop-circle me-1"></i>Stop & Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-gradient-animatronics {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .card {
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1) !important;
        }
        .duration-display {
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .d-flex.flex-column {
            display: flex !important;
            flex-direction: column !important;
        }
        .flex-grow-1 {
            flex-grow: 1 !important;
        }
        .w-100 {
            width: 100% !important;
        }
        .flex-shrink-0 {
            flex-shrink: 0 !important;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Duration timer
            function startDurationTimers() {
                setInterval(function() {
                    $('.duration-display').each(function() {
                        const startTime = $(this).data('start-time');
                        if (startTime) {
                            const duration = calculateDuration(startTime);
                            $(this).text(duration);
                        }
                    });
                }, 1000);
            }

            function calculateDuration(startTime) {
                try {
                    const today = new Date();
                    const [hours, minutes, seconds] = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                        hours, minutes, seconds);
                    const now = new Date();

                    let diffInSeconds = Math.floor((now - start) / 1000);
                    if (diffInSeconds < 0) diffInSeconds = 0;

                    const h = Math.floor(diffInSeconds / 3600);
                    const m = Math.floor((diffInSeconds % 3600) / 60);
                    const s = diffInSeconds % 60;

                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                } catch (error) {
                    return '00:00:00';
                }
            }

            function refreshData() {
                $.ajax({
                    url: '{{ route('animatronics-timing.monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $('#total-running').text(response.statistics.total_running || 0);
                            $('#total-frozen').text(response.statistics.total_frozen || 0);
                            if (response.statistics.total_employees !== undefined) {
                                $('#total-employees').text(response.statistics.total_employees);
                            }
                        }
                    }
                });
            }

            $('#refresh-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Refreshing...');
                setTimeout(() => location.reload(), 500);
            });

            function loadClockedIn() {
                $.ajax({
                    url: '{{ route('animatronics-timing.monitor.clocked-in') }}',
                    method: 'GET',
                    success: function(r) {
                        if (!r.success) return;
                        $('#clocked-in-count').text(r.count || 0);
                        const panel = $('#clocked-in-panel');
                        const list = $('#clocked-in-list');
                        if (r.count > 0) {
                            let html = '';
                            r.employees.forEach(function(emp) {
                                const av = emp.photo ?
                                    `<img src="/storage/${emp.photo}" class="rounded-circle" width="32" height="32" style="object-fit:cover;">` :
                                    `<div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0" style="width:32px;height:32px;background:linear-gradient(135deg,#f6d365,#fda085);font-size:.72rem;">${emp.initials}</div>`;
                                html += `<div class="d-flex align-items-center gap-2 p-2 rounded" style="background:rgba(0,0,0,.03);border:1px solid rgba(0,0,0,.07);">
                                    ${av}
                                    <div>
                                        <div class="fw-semibold" style="font-size:.78rem;line-height:1.2;">${emp.name}</div>
                                        <div class="text-muted" style="font-size:.67rem;"><i class="fas fa-fingerprint me-1" style="color:#fda085;"></i>${emp.clock_in} · ${emp.position}</div>
                                    </div>
                                </div>`;
                            });
                            list.html(html);
                            panel.show();
                        } else {
                            panel.hide();
                        }
                    }
                });
            }

            // Bulk stop handlers
            $(document).on('change', '.session-checkbox', function() {
                const group = $(this).data('group');
                const total = $(`.session-checkbox[data-group="${group}"]`).length;
                const checkedCount = $(`.session-checkbox[data-group="${group}"]:checked`).length;
                const btn = $(`.bulk-stop-btn[data-group="${group}"]`);
                btn.find('.bulk-count').text(checkedCount);
                btn.toggle(checkedCount > 0);
                const groupAll = $(`.group-select-all[data-group="${group}"]`);
                groupAll.prop('indeterminate', checkedCount > 0 && checkedCount < total);
                groupAll.prop('checked', checkedCount === total && total > 0);
            });

            $(document).on('change', '.group-select-all', function() {
                const group = $(this).data('group');
                const checked = $(this).prop('checked');
                $(`.session-checkbox[data-group="${group}"]`).prop('checked', checked);
                const count = checked ? $(`.session-checkbox[data-group="${group}"]`).length : 0;
                const btn = $(`.bulk-stop-btn[data-group="${group}"]`);
                btn.find('.bulk-count').text(count);
                btn.toggle(count > 0);
            });

            $(document).on('click', '.bulk-stop-btn', function() {
                const group = $(this).data('group');
                const ids = [];
                $(`.session-checkbox[data-group="${group}"]:checked`).each(function() {
                    ids.push($(this).val());
                });
                if (ids.length === 0) return;

                $('#bulk-stop-form').data('timing-ids', ids);
                $('#bulk-stop-info').html(`Stopping <strong>${ids.length}</strong> session(s). Choose measurement for all:`);
                const defaultUnit = $('#bulk-measurement-type option').filter(function() {
                    return $(this).val() === 'pcs';
                }).val() || $('#bulk-measurement-type option:first').val();
                $('#bulk-measurement-type').val(defaultUnit).trigger('change');
                $('#bulk-output-qty').val(1);
                $('#bulkStopModal').modal('show');
            });

            $('#bulk-stop-form').on('submit', function(e) {
                e.preventDefault();
                const ids = $(this).data('timing-ids') || [];
                if (ids.length === 0) return;

                const measurementType = $('#bulk-measurement-type').val();
                const outputQty = parseFloat($('#bulk-output-qty').val());

                if (!outputQty && outputQty !== 0) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid output quantity' });
                    return;
                }

                const submitBtn = $('#bulk-stop-submit-btn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Stopping...');

                $.ajax({
                    url: '{{ route('animatronics-timing.bulk-stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_ids: ids,
                        measurement_type: measurementType,
                        output_qty: outputQty,
                    },
                    success: function(r) {
                        $('#bulkStopModal').modal('hide');
                        if (r.success) {
                            Swal.fire({ icon: 'success', title: 'Done!', text: r.message, timer: 2000, showConfirmButton: false });
                            setTimeout(() => location.reload(), 2100);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                        }
                    },
                    error: function(xhr) {
                        $('#bulkStopModal').modal('hide');
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Bulk stop failed.' });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="bi bi-stop-circle me-1"></i>Stop All');
                    }
                });
            });

            // Freeze handler
            $(document).on('click', '.freeze-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'info',
                    title: 'Pause Session?',
                    html: 'Timer for <strong>' + empName + '</strong> will be paused.',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    confirmButtonText: '<i class="bi bi-pause-circle"></i> Pause',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('animatronics-timing.freeze') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', timing_id: timingId },
                        success: function(r) {
                            if (r.success) {
                                Swal.fire({ icon: 'success', title: 'Paused!', timer: 1500, showConfirmButton: false });
                                setTimeout(() => location.reload(), 1600);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to pause.' });
                        }
                    });
                });
            });

            // Unfreeze handler
            $(document).on('click', '.unfreeze-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'question',
                    title: 'Resume Session?',
                    html: 'Timer for <strong>' + empName + '</strong> will resume.',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: '<i class="bi bi-play-circle"></i> Resume',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('animatronics-timing.unfreeze') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', timing_id: timingId },
                        success: function(r) {
                            if (r.success) {
                                Swal.fire({ icon: 'success', title: 'Resumed!', timer: 1500, showConfirmButton: false });
                                setTimeout(() => location.reload(), 1600);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to resume.' });
                        }
                    });
                });
            });

            // Stop work handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');

                if (timingId) {
                    $('#stop-timing-id').val(timingId);
                    $('#stop-session-info').html(`<strong>Employee:</strong> ${employeeName}<br><strong>Job Order:</strong> ${jobOrder}`);
                    $('#stop-output-qty').val(1);
                    const defaultUnit = $('#stop-measurement-type option').filter(function() {
                        return $(this).val() === 'pcs';
                    }).val() || $('#stop-measurement-type option:first').val();
                    $('#stop-measurement-type').val(defaultUnit).trigger('change');
                    $('#stopWorkModal').modal('show');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Cannot identify timing session.' });
                }
            });

            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();
                const timingId = $('#stop-timing-id').val();
                const outputQty = parseFloat($('#stop-output-qty').val());
                const measurementType = $('#stop-measurement-type').val();

                if (!outputQty || outputQty < 0) {
                    Swal.fire({ icon: 'error', title: 'Invalid Quantity', text: 'Please enter a valid output quantity' });
                    return;
                }

                $.ajax({
                    url: '{{ route('animatronics-timing.stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_id: timingId,
                        output_qty: outputQty,
                        measurement_type: measurementType
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#stopWorkModal').modal('hide');
                            Swal.fire({ icon: 'success', title: 'Work Completed!', text: response.message, timer: 1500, showConfirmButton: false });
                            $(`#session-${timingId}`).fadeOut(300, function() { $(this).remove(); });
                            setTimeout(() => location.reload(), 2000);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to stop work session.' });
                    }
                });
            });

            // Initialize Select2
            if ($.fn.select2) {
                $('#bulk-measurement-type, #stop-measurement-type').select2({
                    theme: 'bootstrap-5',
                    minimumResultsForSearch: Infinity,
                    dropdownParent: $('#bulkStopModal, #stopWorkModal')
                });
            }

            startDurationTimers();
            setInterval(refreshData, 30000);
            setInterval(loadClockedIn, 30000);
            loadClockedIn();
        });
    </script>
    @include('timing.partials.detail-modal')
    @include('timing.partials.break-heartbeat')
@endsection