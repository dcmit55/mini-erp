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
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-cut me-1"></i> Costume Timing
                </a>
                <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Animatronics
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
                                <h6 class="mb-1">Costume Running</h6>
                                <h2 class="mb-0" id="costume-running">{{ $costumeRunning }}</h2>
                            </div>
                            <i class="fas fa-cut fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Animatronics Running</h6>
                                <h2 class="mb-0" id="animatronics-running">{{ $animatronicsRunning }}</h2>
                            </div>
                            <i class="fas fa-robot fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Running Sessions by Department -->
        @if ($runningSessions->count() > 0)
            @foreach ($runningSessions as $departmentName => $sessions)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>{{ $departmentName }}
                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Running</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($sessions as $session)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border session-card" id="session-{{ $session->id }}">
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
                                                        {{ $session->employee->name ?? 'Unknown' }}
                                                    </h6>
                                                    <small
                                                        class="text-muted">{{ $session->employee->position ?? 'N/A' }}</small>
                                                </div>
                                            </div>

                                            <!-- Duration -->
                                            <div class="text-center mb-2">
                                                <span class="duration-display fs-3 fw-bold text-success"
                                                    data-start-time="{{ $session->start_time }}">
                                                    {{ $session->duration }}
                                                </span>
                                            </div>

                                            <!-- Job Info -->
                                            <div class="border-top pt-2">
                                                <div class="row g-2 small">
                                                    <div class="col-12">
                                                        <strong>Job Order:</strong>
                                                        {{ $session->jobOrder->name ?? 'N/A' }}<br>
                                                        <strong>Project:</strong>
                                                        {{ $session->jobOrder->project->name ?? 'N/A' }}
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Step:</strong> {{ $session->step }}
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Part:</strong> {{ $session->parts }}
                                                    </div>
                                                    <div class="col-12">
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock"></i> Started:
                                                            {{ $session->start_time }}
                                                        </small>
                                                    </div>
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

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    url: '{{ route('timing-monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update statistics
                            $('#total-running').text(response.statistics.total_running);
                            $('#total-employees').text(response.statistics.total_employees);
                            $('#costume-running').text(response.statistics.costume_running);
                            $('#animatronics-running').text(response.statistics.animatronics_running);

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

            // Start timers
            startDurationTimers();

            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        });
    </script>
@endsection
