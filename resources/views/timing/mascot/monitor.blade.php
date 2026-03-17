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
            <div class="col-md-6">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Running Sessions</h6>
                                <h2 class="mb-0" id="total-running">{{ $totalRunning }}</h2>
                                <small>{{ $mascotDept->name ?? 'Mascot Department' }}</small>
                            </div>
                            <i class="fas fa-play-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
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
                        <div class="row g-3">
                            @foreach ($sessions as $session)
                                <div class="col-md-4 col-lg-3 col-xl-2">
                                    <div class="card border session-card h-100" id="session-{{ $session->id }}">
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
                                                        <i class="bi bi-person text-white fs-4"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">
                                                        <span class="badge bg-success me-1">RUNNING</span>
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
                                                    <div class="row g-2">
                                                        <div class="col-12">
                                                            <i class="bi bi-clock"></i> Started:
                                                            {{ $session->start_time }}
                                                        </div>
                                                        @php
                                                            $totalMinutes =
                                                                $session->jobOrder->total_standard_minutes ?? 0;
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

                                                                    // Calculate time remaining
                                                                    $now = \Carbon\Carbon::now();
                                                                    $deadline = \Carbon\Carbon::parse(
                                                                        date('Y-m-d') . ' ' . $deadlineTime,
                                                                    );
                                                                    $minutesRemaining = $now->diffInMinutes(
                                                                        $deadline,
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
                                                        @if ($deadlineTime)
                                                            <div class="col-12">
                                                                <i class="bi bi-calendar-x"></i> Deadline:
                                                                <strong
                                                                    class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : 'text-danger') }}">{{ $deadlineTime }}</strong>
                                                                <span
                                                                    class="badge bg-info badge-sm ms-1">{{ $totalMinutes }}
                                                                    min</span>
                                                                @if ($deadlineWarning === 'exceeded')
                                                                    <span class="badge bg-danger ms-1"><i
                                                                            class="bi bi-exclamation-triangle"></i>
                                                                        OVERDUE</span>
                                                                @elseif ($deadlineWarning === 'critical')
                                                                    <span class="badge bg-warning text-dark ms-1"><i
                                                                            class="bi bi-clock-history"></i> URGENT</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Stop Work Button -->
                                            <div class="border-top pt-2 mt-2">
                                                <button class="btn btn-warning btn-sm w-100 stop-work-btn"
                                                    data-timing-id="{{ $session->id }}"
                                                    data-employee-name="{{ $session->employee->name }}"
                                                    data-job-order="{{ $session->jobOrder->name ?? 'N/A' }}"
                                                    data-job-order-id="{{ $session->job_order_id }}"
                                                    data-previous-progress="{{ $session->jobOrder->current_progress ?? 0 }}">
                                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK & SELECT STAGE
                                                </button>
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

    <!-- Stop Work Modal with Stage Selection -->
    <div class="modal fade" id="stopWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-stop-circle me-2"></i>Complete Work Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="stop-work-form">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="stop-timing-id" name="timing_id">
                        <input type="hidden" id="stop-job-order-id" name="job_order_id">

                        <!-- Session Info -->
                        <div id="stop-session-info" class="alert alert-info mb-3"></div>

                        <!-- Stage Selection Dropdown (1-10) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Select Stage Completed <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" id="stop-stage" name="stage" required>
                                <option value="">Choose stage...</option>
                                <option value="1">Design & Prototyping</option>
                                <option value="2">Structure Approval</option>
                                <option value="3">Structure & Sample</option>
                                <option value="4">Visual Review & Paint Prep</option>
                                <option value="5">Adjustment & Finishing (Structure)</option>
                                <option value="6">Final Structure Approval</option>
                                <option value="7">Wrapping & Painting</option>
                                <option value="8">Wrapping Approval</option>
                                <option value="9">Finishing & Approval</option>
                                <option value="10">Final QC & Shipping</option>
                            </select>
                            <small class="text-muted">Each stage represents 10% progress increment. Select the stage you've
                                just completed.</small>
                        </div>

                        <!-- Progress Info -->
                        <div class="mb-3">
                            <div class="alert alert-success mb-0">
                                <strong>Previous Progress:</strong> <span id="previous-progress-display">0</span>%<br>
                                <strong>Will be updated to:</strong> <span id="current-progress-display"
                                    class="text-primary fw-bold">0</span>%
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
                    url: '{{ route('mascot-timing.monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update statistics
                            $('#total-running').text(response.statistics.total_running);
                            $('#total-employees').text(response.statistics.total_employees);

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
                    text: 'All selected sessions will be stopped. Each session must have a stage already saved.',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="bi bi-stop-circle"></i> Stop All',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('mascot-timing.bulk-stop') }}',
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

            // Stop work button click handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');
                const jobOrderId = $(this).data('job-order-id');
                const previousProgress = $(this).data('previous-progress') || 0;

                $('#stop-timing-id').val(timingId);
                $('#stop-job-order-id').val(jobOrderId);
                $('#stop-session-info').html(
                    `<strong>Employee:</strong> ${employeeName}<br>
                     <strong>Job Order:</strong> ${jobOrder}`
                );

                // Display previous progress
                $('#previous-progress-display').text(previousProgress);
                $('#current-progress-display').text(previousProgress);

                // Calculate current stage from progress (progress / 10)
                const currentStage = Math.floor(previousProgress / 10);

                // Reset and enable/disable stage options based on current progress
                $('#stop-stage').val('').trigger('change');
                $('#stop-stage option').each(function() {
                    const optionValue = parseInt($(this).val());
                    if (optionValue && optionValue <= currentStage) {
                        // Disable stages that are already completed
                        $(this).prop('disabled', true);
                        $(this).text($(this).text().replace(' (Completed)', '') + ' (Completed)');
                    } else {
                        // Enable future stages
                        $(this).prop('disabled', false);
                        $(this).text($(this).text().replace(' (Completed)', ''));
                    }
                });

                // Add info message
                if (currentStage > 0) {
                    $('#stop-session-info').append(
                        `<div class="alert alert-warning mt-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Current progress is at stage ${currentStage} (${previousProgress}%).
                            You can only select stage ${currentStage + 1} or higher.
                        </div>`
                    );
                }

                // Update current progress when stage changes
                $('#stop-stage').off('change').on('change', function() {
                    const stage = parseInt($(this).val()) || 0;
                    const newProgress = stage * 10;
                    $('#current-progress-display').text(newProgress);
                });

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const stage = parseInt($('#stop-stage').val());

                if (!stage || stage < 1 || stage > 10) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stage Required',
                        text: 'Please select a stage (1-10)'
                    });
                    return;
                }

                // Disable submit button
                const submitBtn = $('#stop-submit-btn');
                submitBtn.prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-1"></i>Saving...');

                $.ajax({
                    url: '{{ route('mascot-timing.stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_id: timingId,
                        stage: parseInt(stage)
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

                            // Remove session card
                            $(`#session-${timingId}`).fadeOut(300, function() {
                                $(this).remove();
                            });

                            // Reload page after delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
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
                    }
                });
            });
        });
    </script>
@endsection
