@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">Animatronics Running Monitor</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Start New Session
                </a>
                <a href="{{ route('timings.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-table me-1"></i> All Timings
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Running</h6>
                                <h2 class="mb-0" id="total-running">{{ $totalRunning }}</h2>
                                <small>{{ $animatronicsDept->name ?? 'Animatronics' }}</small>
                            </div>
                            <i class="fas fa-play-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-info text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Paused</h6>
                                <h2 class="mb-0" id="total-frozen">{{ $totalFrozen }}</h2>
                                <small>Timer paused</small>
                            </div>
                            <i class="bi bi-snow opacity-50" style="font-size:2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Active Employees</h6>
                                <h2 class="mb-0" id="total-employees">{{ $totalEmployees }}</h2>
                                <small>Working on Animatronics</small>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Progress Mode</h6>
                                <h2 class="mb-0" id="progress-mode">{{ $progressModeSessions->count() }}</h2>
                                <small>Percentage Tracking</small>
                            </div>
                            <i class="fas fa-percentage fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clocked-In Employees (fingerprint scan today, no active session) -->
        <div id="clocked-in-panel" class="card shadow-sm border-0 mb-4" style="display:none; border-left:4px solid #fda085 !important;">
            <div class="card-header d-flex align-items-center justify-content-between py-2"
                style="background:linear-gradient(135deg,#f6d365 0%,#fda085 100%);">
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

        <!-- Session Cards -->
        @if ($runningSessions->count() > 0)
            @foreach ($groupedSessions as $projectName => $sessions)
                <div class="card shadow-sm border-0 mb-4">
                    <div
                        class="card-header bg-gradient-animatronics text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>{{ $projectName }}
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
                        <div class="row g-3">
                            @foreach ($sessions as $session)
                                @php
                                    $deptData = $session->department_specific_data ?? [];
                                    $isFrozen = $session->isFrozen();
                                    $trackingMode = $deptData['tracking_mode'] ?? 'timer';
                                    $modeBadge =
                                        $trackingMode === 'progress'
                                            ? '<span class="badge bg-warning text-dark ms-1">PROGRESS</span>'
                                            : '<span class="badge bg-secondary ms-1">TIMER</span>';
                                    $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
                                    $deadlineTime = null;
                                    $deadlineWarning = null;
                                    if ($totalMinutes > 0 && $session->start_time) {
                                        try {
                                            $startDT = \Carbon\Carbon::parse(
                                                date('Y-m-d') . ' ' . $session->start_time,
                                            );
                                            $deadlineTime = $startDT->addMinutes($totalMinutes)->format('H:i');
                                            $deadline = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $deadlineTime);
                                            $minsRemaining = \Carbon\Carbon::now()->diffInMinutes($deadline, false);
                                            $deadlineWarning =
                                                $minsRemaining < 0
                                                    ? 'exceeded'
                                                    : ($minsRemaining <= 15
                                                        ? 'critical'
                                                        : ($minsRemaining <= 30
                                                            ? 'warning'
                                                            : null));
                                        } catch (\Exception $e) {
                                            $deadlineTime = null;
                                        }
                                    }
                                @endphp
                                <div class="col-md-4 col-lg-3 col-xl-2">
                                    <div class="card session-card h-100 {{ $isFrozen ? 'border border-2 border-info' : 'border' }}"
                                        id="session-{{ $session->id }}">
                                        <div class="card-body p-2">

                                            <!-- Bulk Select Checkbox -->
                                            <div class="form-check mb-1">
                                                <input class="form-check-input session-checkbox" type="checkbox"
                                                    value="{{ $session->id }}" data-group="{{ Str::slug($projectName) }}"
                                                    id="chk-{{ $session->id }}">
                                                <label class="form-check-label small text-muted"
                                                    for="chk-{{ $session->id }}">Select</label>
                                            </div>

                                            <!-- Employee Info -->
                                            <div class="d-flex align-items-center mb-2">
                                                @if ($session->employee->photo)
                                                    <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                        class="rounded-circle me-2" width="36" height="36"
                                                        style="object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2"
                                                        style="width: 36px; height: 36px;">
                                                        <i class="bi bi-person text-white"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1" style="min-width:0;">
                                                    <div class="fw-bold text-truncate small">
                                                        {{ $session->employee->name ?? 'Unknown' }}</div>
                                                    <small
                                                        class="text-muted d-block text-truncate">{{ $session->employee->position ?? 'N/A' }}</small>
                                                    <div class="mt-1">
                                                        @if ($isFrozen)
                                                            <span class="badge bg-info text-dark"><i
                                                                    class="bi bi-snow me-1"></i>PAUSED</span>
                                                        @else
                                                            <span class="badge bg-success">RUNNING</span>
                                                        @endif
                                                        {!! $modeBadge !!}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Duration -->
                                            <div class="text-center mb-2 py-1 bg-light rounded">
                                                @if ($isFrozen)
                                                    <span class="fw-bold text-info d-block"
                                                        style="font-size:1.2rem; font-family:'Courier New',monospace;">
                                                        {{ $deptData['frozen_duration'] ?? '00:00:00' }}
                                                    </span>
                                                    <small class="text-muted">&#9208; Paused</small>
                                                @else
                                                    <span class="duration-display fw-bold text-success d-block"
                                                        style="font-size:1.2rem; font-family:'Courier New',monospace;"
                                                        data-start-time="{{ $session->start_time }}">
                                                        {{ $session->duration }}
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Job Info -->
                                            <div class="border-top pt-2 small">
                                                <div class="mb-1">
                                                    <strong class="text-muted">Job Order:</strong><br>
                                                    <span class="text-truncate d-block"
                                                        title="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                        {{ $session->jobOrder->name ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div class="row g-1 mb-1">
                                                    <div class="col-6"><strong>Step:</strong> {{ $session->step }}</div>
                                                    <div class="col-6"><strong>Part:</strong> {{ $session->parts }}</div>
                                                </div>
                                                @if ($trackingMode === 'progress')
                                                    <div class="mb-1"><strong>Progress:</strong>
                                                        {{ $session->previous_progress ?? 0 }}%</div>
                                                @endif
                                                <div class="text-muted"><i class="bi bi-clock"></i> Started:
                                                    {{ $session->start_time }}</div>
                                                @if ($deadlineTime)
                                                    <div class="mt-1">
                                                        <i class="bi bi-calendar-x"></i>
                                                        Deadline: <strong
                                                            class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : 'text-muted') }}">{{ $deadlineTime }}</strong>
                                                        @if ($deadlineWarning === 'exceeded')
                                                            <span class="badge bg-danger ms-1">OVERDUE</span>
                                                        @elseif ($deadlineWarning === 'critical')
                                                            <span class="badge bg-warning text-dark ms-1">URGENT</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Action Button -->
                                            <div class="mt-2">
                                                @if ($isFrozen)
                                                    <button type="button"
                                                        class="btn btn-success btn-sm w-100 unfreeze-session-btn"
                                                        data-timing-id="{{ $session->id }}"
                                                        data-employee-name="{{ $session->employee->name ?? 'Unknown' }}">
                                                        <i class="bi bi-play-circle me-1"></i>Start
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-info btn-sm w-100 freeze-session-btn"
                                                        data-timing-id="{{ $session->id }}"
                                                        data-employee-name="{{ $session->employee->name ?? 'Unknown' }}"
                                                        data-job-order="{{ $session->jobOrder->name ?? 'Unknown' }}">
                                                        <i class="bi bi-snow me-1"></i>Pause
                                                    </button>
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
                    <i class="fas fa-robot text-muted" style="font-size: 5rem;"></i>
                    <h4 class="text-muted mt-3">No Running Animatronics Sessions</h4>
                    <p class="text-muted">Start a new timing session from Animatronics Timing</p>
                    <a href="{{ route('animatronics-timing.index') }}" class="btn btn-warning mt-3">
                        <i class="fas fa-robot me-1"></i> Go to Animatronics Timing
                    </a>
                </div>
            </div>
        @endif
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

        .session-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            function startDurationTimers() {
                setInterval(function() {
                    $('.duration-display').each(function() {
                        const startTime = $(this).data('start-time');
                        if (startTime) {
                            $(this).text(calculateDuration(startTime));
                        }
                    });
                }, 1000);
            }

            function calculateDuration(startTime) {
                try {
                    const today = new Date();
                    const parts = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                        parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2] || 0));
                    const diff = Math.floor((new Date() - start) / 1000);
                    if (diff < 0) return '00:00:00';
                    const h = Math.floor(diff / 3600);
                    const m = Math.floor((diff % 3600) / 60);
                    const s = diff % 60;
                    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
                } catch (e) {
                    return '00:00:00';
                }
            }

            function refreshData() {
                $.ajax({
                    url: '{{ route('animatronics-timing.monitor.running') }}',
                    method: 'GET',
                    success: function(r) {
                        if (r.success) {
                            $('#total-running').text(r.statistics.total_running || 0);
                            $('#total-frozen').text(r.statistics.total_frozen || 0);
                            $('#total-employees').text(r.statistics.total_employees || 0);
                            $('#progress-mode').text(r.statistics.progress_mode || 0);
                            const cardCount = $('.session-card').length;
                            const newCount = (r.statistics.total_running || 0) + (r.statistics
                                .total_frozen || 0);
                            if (cardCount !== newCount) location.reload();
                        }
                    }
                });
            }

            $(document).on('click', '.freeze-session-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'info',
                    title: 'Pause Session?',
                    html: 'Timer for <strong>' + empName +
                        '</strong> will be paused. The card stays visible in the monitor.',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    confirmButtonText: '<i class="bi bi-snow"></i> Pause',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('animatronics-timing.freeze') }}',
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
                                text: (xhr.responseJSON && xhr.responseJSON
                                        .message) ? xhr.responseJSON.message :
                                    'Failed to freeze.'
                            });
                        }
                    });
                });
            });

            $(document).on('click', '.unfreeze-session-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'question',
                    title: 'Start Session?',
                    html: 'Timer for <strong>' + empName +
                        '</strong> will resume from where it was paused.',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: '<i class="bi bi-play-circle"></i> Start',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('animatronics-timing.unfreeze') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: timingId
                        },
                        success: function(r) {
                            if (r.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Started!',
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
                                text: (xhr.responseJSON && xhr.responseJSON
                                        .message) ? xhr.responseJSON.message :
                                    'Failed to unfreeze.'
                            });
                        }
                    });
                });
            });

            $('#refresh-btn').on('click', function() {
                $(this).prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-1"></i> Refreshing...');
                setTimeout(function() {
                    location.reload();
                }, 500);
            });

            // Clocked-in feed: employees who scanned fingerprint today but have no active session
            function loadClockedIn() {
                $.ajax({
                    url: '{{ route('animatronics-timing.monitor.clocked-in') }}',
                    method: 'GET',
                    success: function(r) {
                        if (!r.success) return;
                        $('#clocked-in-count').text(r.count || 0);
                        const panel = $('#clocked-in-panel');
                        const list  = $('#clocked-in-list');
                        if (r.count > 0) {
                            let html = '';
                            r.employees.forEach(function(emp) {
                                const av = emp.photo
                                    ? `<img src="/storage/${emp.photo}" class="rounded-circle" width="32" height="32" style="object-fit:cover;">`
                                    : `<div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0" style="width:32px;height:32px;background:linear-gradient(135deg,#f6d365,#fda085);font-size:.72rem;">${emp.initials}</div>`;
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

            loadClockedIn();
            startDurationTimers();
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

                Swal.fire({
                    icon: 'warning',
                    title: 'Bulk Stop ' + ids.length + ' session(s)?',
                    text: 'All selected sessions will be stopped immediately.',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="bi bi-stop-circle"></i> Stop All',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('animatronics-timing.bulk-stop') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_ids: ids
                        },
                        success: function(r) {
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    'Bulk stop failed.'
                            });
                        }
                    });
                });
            });
        });
    </script>
    @include('timing.partials.detail-modal')
    @include('timing.partials.break-heartbeat')
@endsection
