@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">🎭 Mascot Running Monitor</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-theater-masks me-1"></i> Start New Session
                </a>
                <a href="{{ route('timings.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-table me-1"></i> All Timings
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Running</h6>
                                <h2 class="mb-0" id="total-running">{{ $totalRunning }}</h2>
                                <small>{{ $mascotDept->name ?? 'Mascot Department' }}</small>
                            </div>
                            <i class="fas fa-play-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-info text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Paused</h6>
                                <h2 class="mb-0" id="total-frozen">{{ $totalFrozen }}</h2>
                                <small>Timer paused</small>
                            </div>
                            <i class="bi bi-pause-circle fa-3x opacity-50" style="font-size:2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Active Employees</h6>
                                <h2 class="mb-0" id="total-employees">{{ $totalEmployees }}</h2>
                                <small>Working on Mascot</small>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Type Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm session-mass-production">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:.85rem;"><i
                                    class="bi bi-grid-3x3-gap-fill me-2 text-secondary"></i>Mass Production</div>
                            <small class="text-muted">Sesi running produksi massal</small>
                        </div>
                        <h2 class="mb-0 text-secondary fw-bold">{{ $totalMassProduction }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 text-white" style="background-color:#fd7e14;">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:.85rem;"><i class="bi bi-tools me-2"></i>Repair</div>
                            <small class="opacity-75">Sesi running perbaikan / rework</small>
                        </div>
                        <h2 class="mb-0 fw-bold">{{ $totalRepair }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clocked-In Employees (fingerprint scan today, no active session) -->
        <div id="clocked-in-panel" class="card shadow-sm border-0 mb-4"
            style="display:none; border-left:4px solid #fda085 !important;">
            <div class="card-header d-flex align-items-center justify-content-between py-2"
                style="background:linear-gradient(135deg,#f6d365 0%,#fda085 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-fingerprint text-white"></i>
                    <span class="fw-bold text-white" style="font-size:.88rem;">Hadir — Belum Ada Sesi</span>
                    <span class="badge bg-white text-dark ms-1" id="clocked-in-count" style="font-size:.73rem;">0</span>
                </div>
                <small class="text-white opacity-75" style="font-size:.7rem;">Sudah clock-in via fingerprint · belum start
                    timing</small>
            </div>
            <div class="card-body py-2 px-3">
                <div id="clocked-in-list" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>

        <!-- Running Sessions -->
        @if ($runningSessions->count() > 0)
            @foreach ($groupedSessions as $projectName => $sessions)
                <div class="card shadow-sm border-0 mb-4">
                    <div
                        class="card-header bg-gradient-mascot text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-project-diagram me-2"></i>{{ $projectName }}
                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Employee(s)</span>
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input group-select-all" type="checkbox"
                                    data-group="{{ Str::slug($projectName) }}" id="grp-all-{{ Str::slug($projectName) }}"
                                    title="Select all employees in this project">
                                <label class="form-check-label text-white small fw-normal"
                                    for="grp-all-{{ Str::slug($projectName) }}">Select All</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger bulk-stop-btn"
                                data-group="{{ Str::slug($projectName) }}" style="display:none;">
                                <i class="bi bi-stop-circle me-1"></i>Bulk Stop (<span class="bulk-count">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach ($sessions as $session)
                                @php
                                    $deptData = $session->department_specific_data ?? [];
                                    $isFrozen = $session->isFrozen();
                                    $isAutoBreak = !empty($deptData['auto_break_paused']);
                                    $sessionType = $session->session_type ?? 'mass_production';
                                    $isRepair = $sessionType === 'repair';
                                    if ($isFrozen) {
                                        $sessionClass = 'session-frozen';
                                    } elseif ($isRepair) {
                                        $sessionClass = 'session-repair';
                                    } else {
                                        $sessionClass = 'session-mass-production';
                                    }
                                @endphp
                                <div class="col-4 col-md-2 session-col">
                                    <div class="card {{ $sessionClass }} session-card h-100"
                                        id="session-{{ $session->id }}">
                                        <div class="card-body p-1 d-flex flex-column">
                                            <!-- Checkbox -->
                                            <div class="form-check mb-2">
                                                <input class="form-check-input session-checkbox" type="checkbox"
                                                    value="{{ $session->id }}"
                                                    data-group="{{ Str::slug($projectName) }}"
                                                    id="chk-{{ $session->id }}">
                                                <label class="form-check-label small text-muted"
                                                    for="chk-{{ $session->id }}">Select</label>
                                            </div>

                                            <!-- Employee (centered) -->
                                            <div class="text-center mb-1">
                                                @if ($session->employee->photo)
                                                    <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                        class="rounded-circle mb-1" width="20" height="20"
                                                        style="object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-1"
                                                        style="width: 20px; height: 20px;">
                                                        <i class="bi bi-person text-white" style="font-size:0.55rem;"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    @if ($isFrozen)
                                                        <span class="badge bg-info text-dark" style="font-size:0.57rem;">
                                                            <i
                                                                class="bi bi-pause-circle me-1"></i>PAUSED{{ $isAutoBreak ? ' (BREAK)' : '' }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success"
                                                            style="font-size:0.57rem;">RUNNING</span>
                                                    @endif
                                                </div>
                                                <div class="fw-semibold mt-1 lh-sm" style="font-size:0.65rem;">
                                                    {{ $session->employee->name ?? 'Unknown' }}</div>
                                                <div class="text-muted" style="font-size:0.58rem;">
                                                    {{ $session->employee->position ?? 'N/A' }}</div>
                                            </div>

                                            <!-- Timer -->
                                            <div class="text-center mb-1 py-1 bg-light rounded">
                                                @if ($isFrozen)
                                                    <span class="fw-bold text-info"
                                                        style="font-family:'Courier New',monospace;font-size:0.76rem;">
                                                        {{ $deptData['frozen_duration'] ?? '00:00:00' }}
                                                    </span>
                                                    <br><small class="text-muted" style="font-size:0.55rem;">&#9208;
                                                        Paused</small>
                                                @else
                                                    <span class="duration-display fw-bold text-success"
                                                        style="font-family:'Courier New',monospace;font-size:0.76rem;"
                                                        data-start-time="{{ $session->start_time }}">
                                                        {{ $session->duration }}
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Info -->
                                            <div class="border-top pt-2 small flex-grow-1">
                                                @php
                                                    $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
                                                    $deadlineTime = null;
                                                    $deadlineWarning = null;
                                                    if ($totalMinutes > 0 && $session->start_time) {
                                                        try {
                                                            $startDateTime = \Carbon\Carbon::parse(
                                                                date('Y-m-d') . ' ' . $session->start_time,
                                                            );
                                                            $deadlineTime = $startDateTime
                                                                ->addMinutes($totalMinutes)
                                                                ->format('H:i');
                                                            $minutesRemaining = \Carbon\Carbon::now()->diffInMinutes(
                                                                \Carbon\Carbon::parse(
                                                                    date('Y-m-d') . ' ' . $deadlineTime,
                                                                ),
                                                                false,
                                                            );
                                                            if ($minutesRemaining < 0) {
                                                                $deadlineWarning = 'exceeded';
                                                            } elseif ($minutesRemaining <= 15) {
                                                                $deadlineWarning = 'critical';
                                                            } elseif ($minutesRemaining <= 30) {
                                                                $deadlineWarning = 'warning';
                                                            }
                                                        } catch (\Exception $e) {
                                                            $deadlineTime = null;
                                                        }
                                                    }
                                                @endphp
                                                <div class="mb-1 text-truncate"
                                                    title="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                    <strong>{{ $session->jobOrder->name ?? 'N/A' }}</strong>
                                                </div>
                                                <div class="mb-1 text-truncate">
                                                    <span class="text-muted">Task:</span> {{ $session->step }}
                                                </div>
                                                <div class="text-muted"><i class="bi bi-clock"></i>
                                                    {{ $session->start_time }}</div>
                                                @if ($deadlineTime)
                                                    <div class="mt-1">
                                                        <i class="bi bi-calendar-x"></i>
                                                        <strong
                                                            class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : '') }}">{{ $deadlineTime }}</strong>
                                                        @if ($deadlineWarning === 'exceeded')
                                                            <span class="badge bg-danger ms-1">OVERDUE</span>
                                                        @elseif ($deadlineWarning === 'critical')
                                                            <span class="badge bg-warning text-dark ms-1">URGENT</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Action Buttons (always at bottom) -->
                                            <div class="mt-auto pt-2">
                                                @if ($isFrozen)
                                                    <button class="btn btn-success btn-sm w-100 unfreeze-btn"
                                                        data-timing-id="{{ $session->id }}"
                                                        data-employee-name="{{ $session->employee->name }}">
                                                        <i class="bi bi-play-circle me-1"></i>RESUME
                                                    </button>
                                                @else
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-info btn-sm flex-grow-1 freeze-btn"
                                                            data-timing-id="{{ $session->id }}"
                                                            data-employee-name="{{ $session->employee->name }}">
                                                            <i class="bi bi-pause-circle me-1"></i>PAUSE
                                                        </button>
                                                        <button class="btn btn-warning btn-sm flex-grow-1 stop-work-btn"
                                                            data-timing-id="{{ $session->id }}"
                                                            data-employee-name="{{ $session->employee->name }}"
                                                            data-job-order="{{ $session->jobOrder->name ?? 'N/A' }}"
                                                            data-job-order-id="{{ $session->job_order_id }}"
                                                            data-previous-progress="{{ $session->jobOrder->current_progress ?? 0 }}">
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
                    <i class="fas fa-theater-masks text-muted" style="font-size: 5rem;"></i>
                    <h4 class="text-muted mt-3">No Running Mascot Sessions</h4>
                    <p class="text-muted">Start a new timing session from Mascot Timing</p>
                    <a href="{{ route('mascot-timing.index') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-theater-masks me-1"></i> Go to Mascot Timing
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
                            <label class="form-label small fw-bold mb-1">Measurement Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="bulk-measurement-type" name="measurement_type"
                                required>
                                @forelse($units as $unit)
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
                            <label class="form-label small fw-bold mb-1">Output Qty (per session) <span
                                    class="text-danger">*</span></label>
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

    <!-- Stop Work Modal with Stage Selection -->
    <div class="modal fade" id="stopWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-stop-circle me-2"></i>Stop Work Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="stop-work-form">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="stop-timing-id" name="timing_id">
                        <input type="hidden" id="stop-job-order-id" name="job_order_id">

                        <div id="stop-session-info" class="alert alert-info mb-3"></div>

                        <!-- Stage Selection Dropdown (1-10) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Select Stage Completed <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg select2-stage" id="stop-stage" name="stage"
                                required>
                                <option value="">Choose stage...</option>
                                <option value="1">Design &amp; Prototyping</option>
                                <option value="2">Structure Approval</option>
                                <option value="3">Structure &amp; Sample</option>
                                <option value="4">Visual Review &amp; Paint Prep</option>
                                <option value="5">Adjustment &amp; Finishing (Structure)</option>
                                <option value="6">Final Structure Approval</option>
                                <option value="7">Wrapping &amp; Painting</option>
                                <option value="8">Wrapping Approval</option>
                                <option value="9">Finishing &amp; Approval</option>
                                <option value="10">Final QC &amp; Shipping</option>
                            </select>
                            <small class="text-muted">Each stage = 10% progress. Select the stage just completed.</small>
                        </div>

                        <!-- Progress Info -->
                        <div class="mb-3">
                            <div class="alert alert-success mb-0">
                                <strong>Previous Progress:</strong> <span id="previous-progress-display">0</span>%<br>
                                <strong>Will be updated to:</strong> <span id="current-progress-display"
                                    class="text-primary fw-bold">0</span>%
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Measurement Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm select2-unit" id="stop-measurement-type"
                                    name="measurement_type" required>
                                    @forelse($units as $unit)
                                        <option value="{{ strtolower($unit->name) }}"
                                            {{ strtolower($unit->name) === 'pcs' ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @empty
                                        <option value="pcs" selected>Pcs</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small">Output Quantity <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="stop-output-qty"
                                    name="output_qty" min="0" step="0.1" value="1" required>
                                <small class="text-muted" style="font-size:.68rem;">Minimum 0</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="stop-submit-btn">
                            <i class="bi bi-stop-circle me-1"></i>Stop & Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-mascot {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .session-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .session-card.session-mass-production {
            background-color: #fff;
            border-left: 5px solid #aaa !important;
        }

        .session-card.session-repair {
            background-color: #fd7e14;
            border-left: 5px solid #c96100 !important;
            color: #fff;
        }

        .session-card.session-repair .text-muted {
            color: rgba(255,255,255,0.75) !important;
        }

        .session-card.session-repair .bg-light {
            background-color: rgba(255,255,255,0.2) !important;
        }

        .session-card.session-repair .duration-display,
        .session-card.session-repair .text-success {
            color: #fff !important;
        }

        .session-card.session-repair .border-top {
            border-color: rgba(255,255,255,0.3) !important;
        }

        .session-card.session-frozen {
            background-color: #e3f2fd;
            border-left: 5px solid #0277bd !important;
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (min-width: 768px) {
            .session-col {
                flex: 0 0 14.28%;
                max-width: 14.28%;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Init Select2 for stage in stop modal
            $('#stop-stage').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#stopWorkModal'),
                placeholder: 'Choose stage...',
                allowClear: true,
                width: '100%',
            });

            // Init Select2 for measurement type in stop modal
            $('#stop-measurement-type').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#stopWorkModal'),
                minimumResultsForSearch: Infinity,
                width: '100%',
            });

            // Init Select2 for measurement type in bulk-stop modal
            $('#bulk-measurement-type').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#bulkStopModal'),
                minimumResultsForSearch: Infinity,
            });

            // Duration timer update
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

            // Calculate duration
            function calculateDuration(startTime) {
                try {
                    const today = new Date();
                    const [hours, minutes, seconds] = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                        hours, minutes, seconds);
                    const now = new Date();

                    const diffInSeconds = Math.floor((now - start) / 1000);

                    if (diffInSeconds < 0) return '00:00:00';

                    const h = Math.floor(diffInSeconds / 3600);
                    const m = Math.floor((diffInSeconds % 3600) / 60);
                    const s = diffInSeconds % 60;

                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                } catch (error) {
                    console.error('Duration calculation error:', error);
                    return '00:00:00';
                }
            }

            // Auto-refresh data every 30 seconds
            function refreshData() {
                $.ajax({
                    url: '{{ route('mascot-timing.monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update statistics
                            $('#total-running').text(response.statistics.total_running || 0);
                            $('#total-frozen').text(response.statistics.total_frozen || 0);
                            $('#total-employees').text(response.statistics.total_employees || 0);

                            // Reload if session count changes
                            const cardCount = $('.session-card').length;
                            const newCount = (response.statistics.total_running || 0) + (response
                                .statistics.total_frozen || 0);
                            if (cardCount !== newCount) {
                                location.reload();
                            }
                        }
                    }
                });
            }

            // Manual refresh button
            $('#refresh-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-1"></i> Refreshing...');

                setTimeout(() => {
                    location.reload();
                }, 500);
            });

            // Clocked-in feed: employees who scanned fingerprint today but have no active session
            function loadClockedIn() {
                $.ajax({
                    url: '{{ route('mascot-timing.monitor.clocked-in') }}',
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

            // Start timers
            loadClockedIn();
            startDurationTimers();

            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
            setInterval(loadClockedIn, 30000);

            // Bulk stop handler
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

                // Store ids on form data
                $('#bulk-stop-form').data('timing-ids', ids);
                $('#bulk-stop-info').html(
                    `Stopping <strong>${ids.length}</strong> session(s). Choose measurement for all:`);
                // Reset defaults
                const defaultUnit = $('#bulk-measurement-type option').filter(function() {
                    return $(this).val() === 'pcs';
                }).val() || $('#bulk-measurement-type option:first').val();
                $('#bulk-measurement-type').val(defaultUnit).trigger('change');
                $('#bulk-output-qty').val(1);
                $('#bulkStopModal').modal('show');
            });

            // Bulk stop form submit
            $('#bulk-stop-form').on('submit', function(e) {
                e.preventDefault();
                const ids = $(this).data('timing-ids') || [];
                if (ids.length === 0) return;

                const measurementType = $('#bulk-measurement-type').val();
                const outputQty = parseFloat($('#bulk-output-qty').val());

                const submitBtn = $('#bulk-stop-submit-btn');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Stopping...');

                $.ajax({
                    url: '{{ route('mascot-timing.bulk-stop') }}',
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Done!',
                                text: r.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 2100);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: r.message
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#bulkStopModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Bulk stop failed.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-stop-circle me-1"></i>Stop All');
                    }
                });
            });

            // Freeze (pause) handler
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
                        url: '{{ route('mascot-timing.freeze') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: timingId
                        },
                        success: function(r) {
                            if (r.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Paused!',
                                    text: r.message,
                                    timer: 1800,
                                    showConfirmButton: false
                                });
                                setTimeout(function() {
                                    location.reload();
                                }, 1900);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: r.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    'Failed to pause.'
                            });
                        }
                    });
                });
            });

            // Unfreeze (resume) handler
            $(document).on('click', '.unfreeze-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'question',
                    title: 'Resume Session?',
                    html: 'Timer for <strong>' + empName +
                        '</strong> will resume from where it was paused.',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: '<i class="bi bi-play-circle"></i> Resume',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('mascot-timing.unfreeze') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: timingId
                        },
                        success: function(r) {
                            if (r.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Resumed!',
                                    text: r.message,
                                    timer: 1800,
                                    showConfirmButton: false
                                });
                                setTimeout(function() {
                                    location.reload();
                                }, 1900);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: r.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    'Failed to resume.'
                            });
                        }
                    });
                });
            });

            // Stop work button click handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');
                const jobOrderId = $(this).data('job-order-id');
                const previousProgress = parseInt($(this).data('previous-progress')) || 0;

                $('#stop-timing-id').val(timingId);
                $('#stop-job-order-id').val(jobOrderId);
                $('#stop-session-info').html(
                    `<strong>Employee:</strong> ${employeeName}<br>
                     <strong>Job Order:</strong> ${jobOrder}`
                );

                // Show previous progress
                $('#previous-progress-display').text(previousProgress);
                $('#current-progress-display').text(previousProgress);

                // Current stage derived from saved progress
                const currentStage = Math.floor(previousProgress / 10);

                // Reset stage select, then disable stages already passed
                $('#stop-stage').val('').trigger('change');
                $('#stop-stage option').each(function() {
                    const optionValue = parseInt($(this).val());
                    if (optionValue && optionValue < currentStage) {
                        $(this).prop('disabled', true);
                        $(this).text($(this).text().replace(' (Completed)', '') + ' (Completed)');
                    } else {
                        $(this).prop('disabled', false);
                        $(this).text($(this).text().replace(' (Completed)', ''));
                    }
                });

                // Pre-select current stage if any
                if (currentStage > 0) {
                    $('#stop-stage').val(currentStage).trigger('change');
                }

                // Live-update progress preview when stage changes
                $('#stop-stage').off('change.preview').on('change.preview', function() {
                    const stage = parseInt($(this).val()) || 0;
                    $('#current-progress-display').text(stage * 10);
                });

                // Reset to defaults
                $('#stop-measurement-type').val('pcs').trigger('change');
                const pcVal = $('#stop-measurement-type option').filter(function() {
                    return $(this).val() === 'pcs';
                }).val() || $('#stop-measurement-type option:first').val();
                $('#stop-measurement-type').val(pcVal).trigger('change');
                $('#stop-output-qty').val(1);

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const stage = parseInt($('#stop-stage').val());
                const outputQty = parseFloat($('#stop-output-qty').val());
                const measureType = $('#stop-measurement-type').val();

                if (!stage || stage < 1 || stage > 10) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stage Required',
                        text: 'Please select a stage (1–10)'
                    });
                    return;
                }

                if (isNaN(outputQty) || outputQty < 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Quantity',
                        text: 'Please enter a valid output quantity'
                    });
                    return;
                }

                const submitBtn = $('#stop-submit-btn');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('mascot-timing.stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_id: timingId,
                        stage: stage,
                        output_qty: outputQty,
                        measurement_type: measureType,
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#stopWorkModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Work Completed!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $(`#session-${timingId}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                            setTimeout(() => location.reload(), 2100);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to complete work session.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-stop-circle me-1"></i>Stop & Save');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-stop-circle me-1"></i>Stop & Save');
                    }
                });
            });
        });
    </script>
    @include('timing.partials.detail-modal')
    @include('timing.partials.break-heartbeat')
@endsection
