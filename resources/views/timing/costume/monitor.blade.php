@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">👔 Costume Running Monitor</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-cut me-1"></i> Start New Session
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
                                <small>{{ $costumeDept->name ?? 'Costume Department' }}</small>
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
                                <small>Working on Costume</small>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Running Sessions -->
        @if ($runningSessions->count() > 0)
            @foreach ($groupedSessions as $jobOrderName => $sessions)
                <div class="card shadow-sm border-0 mb-4">
                    <div
                        class="card-header bg-gradient-costume text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>{{ $jobOrderName }}
                            <span class="badge bg-light text-dark ms-2">{{ $sessions->count() }} Employee(s)</span>
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check mb-0">
                                <input class="form-check-input group-select-all" type="checkbox"
                                    data-group="{{ Str::slug($jobOrderName) }}" id="grp-all-{{ Str::slug($jobOrderName) }}"
                                    title="Select all employees in this project">
                                <label class="form-check-label text-white small fw-normal"
                                    for="grp-all-{{ Str::slug($jobOrderName) }}">Select All</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger bulk-stop-btn"
                                data-group="{{ Str::slug($jobOrderName) }}" style="display:none;">
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
                                                    value="{{ $session->id }}"
                                                    data-group="{{ Str::slug($jobOrderName) }}"
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
                                                    <div class="mt-2 text-muted">
                                                        <i class="bi bi-clock"></i> Started:
                                                        {{ $session->start_time }}
                                                    </div>
                                                    @php
                                                        $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
                                                        $standardTimePerUnit =
                                                            $session->jobOrder->standard_time_per_unit ?? 0;
                                                        $deadlineTime = null;
                                                        $deadlineWarning = null;

                                                        // Only show deadline if total_standard_minutes exists
                                                        if ($totalMinutes > 0 && $session->start_time) {
                                                            try {
                                                                $startDateTime = \Carbon\Carbon::parse(
                                                                    date('Y-m-d') . ' ' . $session->start_time,
                                                                );
                                                                $deadlineTime = $startDateTime
                                                                    ->addMinutes($totalMinutes)
                                                                    ->format('H:i');

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
                                                        <div class="mt-2">
                                                            <i class="bi bi-calendar-x"></i> Deadline:
                                                            <strong
                                                                class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : 'text-danger') }}">{{ $deadlineTime }}</strong>
                                                            <span class="badge bg-info badge-sm ms-1">{{ $totalMinutes }}
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

                                            <!-- Stop Work Button -->
                                            <div class="border-top pt-2 mt-2">
                                                <button class="btn btn-danger btn-sm w-5 stop-work-btn"
                                                    data-timing-id="{{ $session->id }}"
                                                    data-employee-name="{{ $session->employee->name }}"
                                                    data-job-order="{{ $session->jobOrder->name ?? 'N/A' }}">
                                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK
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
                    <i class="fas fa-cut text-muted" style="font-size: 5rem;"></i>
                    <h4 class="text-muted mt-3">No Running Costume Sessions</h4>
                    <p class="text-muted">Start a new timing session from Costume Timing</p>
                    <a href="{{ route('costume-timing.index') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-cut me-1"></i> Go to Costume Timing
                    </a>
                </div>
            </div>
        @endif
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

                        <!-- Measurement Type Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Measurement Type
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="stop-measurement-type" name="measurement_type" required>
                                <option value="qty">Qty</option>
                                <option value="pcs" selected>Pcs</option>
                                <option value="unit">Unit</option>
                                <option value="piece">Piece</option>
                                <option value="item">Item</option>
                                <option value="set">Set</option>
                                <option value="meter">Meter</option>
                                <option value="cm">Cm</option>
                                <option value="kg">Kg</option>
                                <option value="gram">Gram</option>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-costume {
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
                    url: '{{ route('costume-timing.monitor.running') }}',
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
                    text: 'All selected sessions will be stopped with output qty = 1.',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="bi bi-stop-circle"></i> Stop All',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('costume-timing.bulk-stop') }}',
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

            // Stop work handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');

                if (timingId) {
                    $('#stop-timing-id').val(timingId);
                    $('#stop-session-info').html(`
                        <strong>Employee:</strong> ${employeeName}<br>
                        <strong>Job Order:</strong> ${jobOrder}
                    `);
                    $('#stop-output-qty').val(1).focus();
                    $('#stopWorkModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Cannot identify timing session. Please refresh the page.'
                    });
                }
            });

            // Stop work form submission
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const outputQty = parseFloat($('#stop-output-qty').val());
                const measurementType = $('#stop-measurement-type').val();

                if (!outputQty || outputQty < 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Quantity',
                        text: 'Please enter a valid output quantity'
                    });
                    return;
                }

                $.ajax({
                    url: '{{ route('costume-timing.stop') }}',
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

                            Swal.fire({
                                icon: 'success',
                                title: 'Work Completed!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Remove the specific card
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
                            'Failed to stop work session.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            });
        });
    </script>
@endsection
