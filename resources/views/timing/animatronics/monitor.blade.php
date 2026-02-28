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
                                <h6 class="mb-1">Total Running</h6>
                                <h2 class="mb-0" id="total-running">{{ $totalRunning }}</h2>
                                <small>{{ $animatronicsDept->name ?? 'Animatronics' }}</small>
                            </div>
                            <i class="fas fa-play-circle fa-3x opacity-50"></i>
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
                <div class="card shadow-sm border-0 bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Timer Mode</h6>
                                <h2 class="mb-0" id="timer-mode">{{ $timerModeSessions->count() }}</h2>
                                <small>Quantity Tracking</small>
                            </div>
                            <i class="fas fa-stopwatch fa-3x opacity-50"></i>
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

        <!-- Running Sessions -->
        @if ($runningSessions->count() > 0)
            @foreach ($groupedSessions as $jobOrderName => $sessions)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-animatronics text-white">
                        <h5 class="mb-0">
                            <i
                                class="fas fa-tasks me-2"></i>{{ $projectName = $sessions->first()->jobOrder->project->name ?? 'Unknown Project' }}

                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Employee(s)</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($sessions as $session)
                                @php
                                    $departmentData = $session->department_data ?? [];
                                    $trackingMode = $departmentData['tracking_mode'] ?? 'timer';
                                    $modeBadge =
                                        $trackingMode === 'progress'
                                            ? '<span class="badge bg-warning text-dark ms-1">PROGRESS MODE</span>'
                                            : '<span class="badge bg-info ms-1">TIMER MODE</span>';
                                @endphp
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="card border session-card h-100" id="session-{{ $session->id }}">
                                        <div class="card-body p-3">
                                            <!-- Employee Info -->
                                            <div class="d-flex align-items-center mb-2">
                                                @if ($session->employee->photo)
                                                    <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                        class="rounded-circle me-2" width="50" height="50"
                                                        style="object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2"
                                                        style="width: 50px; height: 50px;">
                                                        <i class="bi bi-person text-white fs-4"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">
                                                        <span class="badge bg-success me-1">RUNNING</span>
                                                        {!! $modeBadge !!}
                                                    </h6>
                                                    <div class="fw-bold">{{ $session->employee->name ?? 'Unknown' }}</div>
                                                    <small
                                                        class="text-muted">{{ $session->employee->position ?? 'N/A' }}</small>
                                                </div>
                                            </div>

                                            <!-- Duration -->
                                            <div class="text-center mb-2 py-2 bg-light rounded">
                                                <span class="duration-display fs-4 fw-bold text-success"
                                                    data-start-time="{{ $session->start_time }}">
                                                    {{ $session->duration }}
                                                </span>
                                            </div>

                                            <!-- Job Info -->
                                            <div class="border-top pt-2">
                                                <div class="small">
                                                    <div class="mb-1">
                                                        <strong>Job Order:</strong><br>
                                                        {{ $session->jobOrder->name ?? 'N/A' }}
                                                    </div>
                                                    <div class="row g-1">
                                                        <div class="col-6">
                                                            <strong>Step:</strong> {{ $session->step }}
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Part:</strong> {{ $session->parts }}
                                                        </div>
                                                    </div>
                                                    @if ($trackingMode === 'progress')
                                                        <div class="mt-2">
                                                            <strong>Previous Progress:</strong>
                                                            {{ $session->previous_progress ?? 0 }}%
                                                        </div>
                                                    @endif
                                                    <div class="mt-2 text-muted">
                                                        <i class="bi bi-clock"></i> Started:
                                                        {{ $session->start_time }}
                                                    </div>
                                                    @php
                                                        $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
                                                        $deadlineTime = null;
                                                        $deadlineWarning = null;
                                                        if ($totalMinutes > 0 && $session->start_time) {
                                                            try {
                                                                $startDateTime = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $session->start_time);
                                                                $deadlineTime = $startDateTime->addMinutes($totalMinutes)->format('H:i');
                                                                
                                                                $now = \Carbon\Carbon::now();
                                                                $deadline = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $deadlineTime);
                                                                $minutesRemaining = $now->diffInMinutes($deadline, false);
                                                                
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
                                                    @if ($deadlineTime)
                                                        <div class="mt-2">
                                                            <i class="bi bi-calendar-x"></i> Deadline:
                                                            <strong class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : 'text-danger') }}">{{ $deadlineTime }}</strong>
                                                            <span class="badge bg-info badge-sm ms-1">{{ $totalMinutes }} min</span>
                                                            @if ($deadlineWarning === 'exceeded')
                                                                <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i> OVERDUE</span>
                                                            @elseif ($deadlineWarning === 'critical')
                                                                <span class="badge bg-warning text-dark ms-1"><i class="bi bi-clock-history"></i> URGENT</span>
                                                            @endif
                                                        </div>
                                                    @endif
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
                    url: '{{ route('animatronics-timing.monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update statistics
                            $('#total-running').text(response.statistics.total_running);
                            $('#total-employees').text(response.statistics.total_employees);
                            $('#timer-mode').text(response.statistics.timer_mode || 0);
                            $('#progress-mode').text(response.statistics.progress_mode || 0);

                            // Reload if session count changes significantly
                            if (response.statistics.total_running === 0 && $('.session-card').length >
                                0) {
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

            // Start timers
            startDurationTimers();

            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        });
    </script>
@endsection
