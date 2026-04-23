@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;"> Timing Monitor - Running Sessions</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <button id="available-employees-btn" class="btn btn-success btn-sm">
                    <i class="bi bi-people me-1"></i> Available Employees
                </button>
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-cut me-1"></i> Costume Timing
                </a>
                <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Animatronics
                </a>
                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-masks-theater me-2"></i> Mascot Timing
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Running</h6>
                                <h2 class="mb-0" id="total-running">{{ $totalRunning }}</h2>
                            </div>
                            <i class="fas fa-play-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Active Employees</h6>
                                <h2 class="mb-0" id="total-employees">{{ $totalEmployees }}</h2>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card shadow-sm border-0 bg-info text-white">
                    <div class="card-body p-2">
                        <div class="text-center">
                            <small class="d-block">Costume</small>
                            <h3 class="mb-0" id="costume-running">{{ $costumeRunning }}</h3>
                            <i class="fas fa-cut fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body p-2">
                        <div class="text-center">
                            <small class="d-block">Animatronics</small>
                            <h3 class="mb-0" id="animatronics-running">{{ $animatronicsRunning }}</h3>
                            <i class="fas fa-robot fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="card shadow-sm border-0 bg-danger text-white">
                    <div class="card-body p-2">
                        <div class="text-center">
                            <small class="d-block">Mascot</small>
                            <h3 class="mb-0" id="mascot-running">{{ $mascotRunning }}</h3>
                            <i class="fas fa-masks-theater fa-2x opacity-50"></i>
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
                <div class="card shadow-sm session-repair">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:.85rem;"><i class="bi bi-tools me-2"
                                    style="color:#e65100;"></i>Repair</div>
                            <small class="text-muted">Sesi running perbaikan / rework</small>
                        </div>
                        <h2 class="mb-0 fw-bold" style="color:#e65100;">{{ $totalRepair }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Running Sessions by Department -->
        @if ($runningSessions->count() > 0)
            @foreach ($runningSessions as $departmentName => $sessions)
                @php
                    // Determine gradient class based on department name
                    $gradientClass = 'bg-gradient-primary';
                    if (stripos($departmentName, 'Costume') !== false) {
                        $gradientClass = 'bg-gradient-costume';
                    } elseif (
                        stripos($departmentName, 'Animatronic') !== false ||
                        stripos($departmentName, 'Animation') !== false
                    ) {
                        $gradientClass = 'bg-gradient-animatronics';
                    } elseif (stripos($departmentName, 'Mascot') !== false) {
                        $gradientClass = 'bg-gradient-mascot';
                    }
                @endphp
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header {{ $gradientClass }} text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>{{ $departmentName }}
                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Running</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach ($sessions as $session)
                                @php
                                    $sessionType = $session->session_type ?? 'mass_production';
                                    $isRepair = $sessionType === 'repair';
                                    $sessionClass = $isRepair ? 'session-repair' : 'session-mass-production';
                                @endphp
                                <div class="col-md-4 col-lg-3 col-xl-2">
                                    <div class="card {{ $sessionClass }} session-card shadow-sm"
                                        id="session-{{ $session->id }}">
                                        <div class="card-body p-2">
                                            <!-- Header: Photo, Name, Status -->
                                            <div class="d-flex align-items-center mb-2">
                                                @if ($session->employee->photo)
                                                    <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                        class="rounded-circle me-2" width="36" height="36"
                                                        style="object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-3"
                                                        style="width: 36px; height: 36px;">
                                                        <i class="bi bi-person text-white fs-5"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-bold text-truncate mb-0">
                                                        {{ $session->employee->name ?? 'Unknown' }}</div>
                                                    <small
                                                        class="text-muted d-block text-truncate">{{ $session->employee->position ?? 'N/A' }}</small>
                                                </div>
                                                <span class="badge bg-success ms-2">Running</span>
                                            </div>

                                            <!-- Duration - Large centered -->
                                            <div class="text-center mb-2 py-1" style="border-bottom: 1px solid #dee2e6;">
                                                <span class="duration-display fw-bold text-success d-block"
                                                    style="font-size: 1.2rem; font-family: 'Courier New', monospace; letter-spacing: 2px;"
                                                    data-start-time="{{ $session->start_time }}">
                                                    {{ $session->duration }}
                                                </span>
                                            </div>

                                            <!-- Job Info - With proper spacing -->
                                            <div class="job-info">
                                                <div class="mb-1">
                                                    <div class="d-flex">
                                                        <strong class="me-2" style="min-width: 60px;">Job Order
                                                            :</strong>
                                                        <div class="text-truncate flex-grow-1"
                                                            title="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                            {{ $session->jobOrder->name ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                                <div class="mb-1">
                                                    <div class="d-flex">
                                                        <strong class="me-2" style="min-width: 60px;">Step :</strong>
                                                        <div class="text-truncate flex-grow-1"
                                                            title="{{ $session->step }}">{{ $session->step }}</div>
                                                    </div>
                                                </div>
                                                <div class="mb-1">
                                                    <div class="d-flex">
                                                        <strong class="me-2" style="min-width: 60px;">Project:</strong>
                                                        <div class="text-truncate flex-grow-1"
                                                            title="{{ $session->jobOrder->project->name ?? 'N/A' }}">
                                                            {{ $session->jobOrder->project->name ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex">
                                                        <strong class="me-2" style="min-width: 60px;">Part:</strong>
                                                        <div class="text-truncate flex-grow-1"
                                                            title="{{ $session->parts }}">{{ $session->parts }}</div>
                                                    </div>
                                                </div>

                                                {{-- <!-- Stop Button -->
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-danger btn-sm stop-session-btn"
                                                        data-timing-id="{{ $session->id }}"
                                                        data-employee-name="{{ $session->employee->name ?? 'Unknown' }}"
                                                        data-job-order="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                        <i class="bi bi-stop-circle me-1"></i> Stop
                                                    </button>
                                                </div> --}}

                                                <div class="mt-2 pt-2 border-top text-muted small text-center">
                                                    Started: {{ $session->start_time }}
                                                </div>
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
                    <i class="bi bi-clock-history text-muted" style="font-size: 5rem;"></i>
                    <h4 class="text-muted mt-3">No Running Sessions</h4>
                    <p class="text-muted">Start a timing session from Costume Timing or Animatronics Timing</p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <a href="{{ route('costume-timing.index') }}" class="btn btn-primary">
                            <i class="fas fa-cut me-1"></i> Go to Costume Timing
                        </a>
                        <a href="{{ route('animatronics-timing.index') }}" class="btn btn-warning">
                            <i class="fas fa-robot me-1"></i> Go to Animatronics Timing
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Available Employees Modal -->
    <div class="modal fade" id="availableEmployeesModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-people me-2"></i>Available Employees (Not Running)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="available-employees-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading available employees...</p>
                    </div>
                    <div id="available-employees-content" class="d-none">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Department-specific gradients */
        .bg-gradient-costume {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .bg-gradient-animatronics {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        }

        .bg-gradient-mascot {
            background: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);
        }

        .session-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 10px;
            height: 100%;
        }

        .session-card.session-mass-production {
            background-color: #fff;
            border-left: 5px solid #aaa !important;
        }

        .session-card.session-repair {
            background-color: #fff3e0;
            border-left: 5px solid #e65100 !important;
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .session-card .card-body {
            padding: 1rem !important;
        }

        /* Job info styling */
        .job-info {
            font-size: 0.8rem;
            line-height: 1.6;
        }

        /* Compact text styling */
        .session-card .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
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
                    url: '{{ route('timing-monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update statistics
                            $('#total-running').text(response.statistics.total_running);
                            $('#total-employees').text(response.statistics.total_employees);
                            $('#costume-running').text(response.statistics.costume_running);
                            $('#animatronics-running').text(response.statistics.animatronics_running);
                            $('#mascot-running').text(response.statistics.mascot_running);

                            // Optionally reload page if session count changes significantly
                            if (response.statistics.total_running === 0 && $(
                                    '.session-card').length > 0) {
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

            // Available Employees button
            $('#available-employees-btn').on('click', function() {
                $('#availableEmployeesModal').modal('show');
                loadAvailableEmployees();
            });

            // Function to load available employees
            function loadAvailableEmployees() {
                $('#available-employees-loading').removeClass('d-none');
                $('#available-employees-content').addClass('d-none');

                $.ajax({
                    url: '{{ route('timing-monitor.available-employees') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            displayAvailableEmployees(response.employees);
                        }
                    },
                    error: function() {
                        $('#available-employees-loading').html(
                            '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load available employees.</div>'
                        );
                    }
                });
            }

            // Function to display available employees
            function displayAvailableEmployees(employees) {
                $('#available-employees-loading').addClass('d-none');
                const content = $('#available-employees-content');
                content.removeClass('d-none').empty();

                if (employees.length === 0) {
                    content.html(
                        '<div class="text-center py-5"><i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i><p class="mt-3 text-muted">All employees are currently running!</p></div>'
                    );
                    return;
                }

                // Group by department
                const byDepartment = {};
                employees.forEach(emp => {
                    const dept = emp.department || 'Unknown';
                    if (!byDepartment[dept]) {
                        byDepartment[dept] = [];
                    }
                    byDepartment[dept].push(emp);
                });

                // Display by department
                let html = '';
                Object.keys(byDepartment).sort().forEach(dept => {
                    const emps = byDepartment[dept];

                    // Determine badge color based on department
                    let badgeClass = 'bg-secondary';
                    if (dept.toLowerCase().includes('costume')) {
                        badgeClass = 'bg-info';
                    } else if (dept.toLowerCase().includes('animatronic')) {
                        badgeClass = 'bg-warning';
                    } else if (dept.toLowerCase().includes('mascot')) {
                        badgeClass = 'bg-danger';
                    }

                    html += `
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">
                                <i class="fas fa-building me-2"></i>${dept}
                                <span class="badge ${badgeClass} ms-2">${emps.length} Available</span>
                            </h6>
                            <div class="row g-3">
                    `;

                    emps.forEach(emp => {
                        const photoHtml = emp.photo ?
                            `<img src="/storage/${emp.photo}" class="rounded-circle" width="36" height="36" style="object-fit: cover;">` :
                            `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="bi bi-person text-white fs-4"></i></div>`;

                        html += `
                            <div class="col-md-4 col-lg-3 col-xl-2">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center">
                                            ${photoHtml}
                                            <div class="ms-3 flex-grow-1">
                                                <h6 class="mb-1">${emp.name}</h6>
                                                <small class="text-muted d-block">${emp.position || 'N/A'}</small>
                                                <span class="badge bg-success mt-1"><i class="bi bi-check-circle me-1"></i>Available</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;
                });

                content.html(html);
            }

            // Stop session button with confirmation
            $(document).on('click', '.stop-session-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');

                Swal.fire({
                    title: 'Stop This Session?',
                    html: `
                        <div class="text-start">
                            <p><strong>Employee:</strong> ${employeeName}</p>
                            <p><strong>Job Order:</strong> ${jobOrder}</p>
                            <hr>
                            <p class="text-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                This will stop the session immediately. The employee will need to enter output details from their timing page.
                            </p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-stop-circle me-1"></i> Yes, Stop Session',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        stopSession(timingId);
                    }
                });
            });

            // Function to stop session via AJAX
            function stopSession(timingId) {
                $.ajax({
                    url: '{{ route('timing-monitor.stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_id: timingId
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Stopping Session...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Session Stopped!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Remove the card with animation
                            $(`#session-${timingId}`).fadeOut(400, function() {
                                $(this).remove();

                                // Reload if no more sessions
                                if ($('.session-card').length === 0) {
                                    setTimeout(() => location.reload(), 1000);
                                }
                            });

                            // Refresh statistics
                            refreshData();
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to stop session.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            }

            // Start timers
            startDurationTimers();

            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        });
    </script>
@endsection
