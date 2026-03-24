@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-mask gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">🎭 Mascot Timing - Stage Progress Tracking</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('mascot-timing.monitor') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-tv me-1"></i> Mascot Monitor
                </a>
                <a href="{{ route('timings.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-table me-1"></i> View All Timings
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left Column: Start New Work -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-mascot text-white">
                        <h5 class="mb-0"><i class="bi bi-play-circle me-2"></i>Start New Work Session</h5>
                    </div>
                    <div class="card-body">
                        <form id="mascot-timer-form">
                            @csrf

                            <!-- STEP 1: Select Employees -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-warning text-dark me-2">1</span>Select Employees (Multiple)
                                </label>

                                <!-- Employee Search -->
                                <div class="mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="employee-search" class="form-control form-control-sm"
                                            placeholder="Search by name or position...">
                                        <button type="button" id="select-all-btn" class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-check-all"></i> All Visible
                                        </button>
                                        <button type="button" id="deselect-all-btn"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-x-lg"></i> Clear
                                        </button>
                                    </div>
                                </div>

                                {{-- Skillset Filter Buttons --}}
                                <div class="mb-2 d-flex gap-1 flex-wrap">
                                    <button type="button" class="btn btn-warning btn-xs skillset-filter active" data-skillset="all">All</button>
                                    @foreach($employeesBySkillset as $group)
                                        <button type="button" class="btn btn-outline-warning btn-xs skillset-filter"
                                            data-skillset="{{ $group['skillset_id'] ?? 'none' }}">
                                            {{ $group['label'] }}
                                        </button>
                                    @endforeach
                                </div>

                                <div id="employee-cards">
                                    @if($employees->isEmpty())
                                        <div class="alert alert-warning">
                                            No active mascot employees found. Please add employees to mascot department first.
                                        </div>
                                    @else
                                        <div class="row g-2">
                                            @foreach($employees as $employee)
                                                <div class="col-md-4 col-sm-6 employee-card-wrapper"
                                                    data-skillset-ids=",{{ $employee->skillsets->pluck('id')->implode(',') }},"
                                                    data-department-id="{{ $employee->department_id }}"
                                                    data-position="{{ $employee->position }}"
                                                    data-name="{{ strtolower($employee->name) }}">
                                                    <div class="card employee-card h-100 border-2"
                                                        data-employee-id="{{ $employee->id }}"
                                                        style="cursor: pointer; transition: all 0.3s;">
                                                        <div class="card-body text-center p-2">
                                                            <div class="form-check position-absolute top-0 end-0 m-1">
                                                                <input class="form-check-input employee-checkbox" type="checkbox"
                                                                    name="employees[]" value="{{ $employee->id }}"
                                                                    id="emp-{{ $employee->id }}">
                                                            </div>
                                                            @if ($employee->photo)
                                                                <img src="{{ asset('storage/' . $employee->photo) }}"
                                                                    class="rounded-circle mb-1 border" width="44" height="44"
                                                                    style="object-fit: cover;">
                                                            @else
                                                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-1"
                                                                    style="width: 44px; height: 44px;">
                                                                    <i class="bi bi-person text-white"></i>
                                                                </div>
                                                            @endif
                                                            <h6 class="mb-0 small lh-sm">{{ $employee->name }}</h6>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <span id="selected-count">0 employee(s) selected</span>
                                        <span id="filtered-count" class="ms-2"></span>
                                    </small>
                                </div>
                            </div>

                            <!-- STEP 2: Select Job Order -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-warning text-dark me-2">2</span>Select Job Order
                                </label>
                                <select class="form-select select2" id="job-order-select" name="job_order_id" required>
                                    <option value="">Choose Job Order...</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}"
                                            data-project-name="{{ $jo->project->name ?? 'N/A' }}"
                                            data-job-order-name="{{ $jo->name }}">
                                            {{ $jo->name }} ({{ $jo->project->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Job Order Progress Info -->
                                <div id="job-order-info" class="mt-3 p-3 bg-light rounded d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Project:</small>
                                            <strong id="project-name-display">-</strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Current Progress:</small>
                                            <strong id="current-progress-display" class="text-success">0%</strong>
                                            <span class="badge bg-info ms-2">Stage <span
                                                    id="current-stage-display">0</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: Task Description -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-warning text-dark me-2">3</span>Task Description
                                </label>
                                <input type="text" class="form-control" id="task-input" name="task"
                                    placeholder="e.g., Sculpting, Painting, Assembly, etc." required>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100" id="start-work-btn">
                                <i class="bi bi-play-circle-fill me-2"></i>
                                <span id="btn-text">START WORK</span>
                                <span id="btn-info" class="small">(Select employees, job order & task first)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Active Sessions -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div
                        class="card-header bg-gradient-mascot text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Active Sessions

                        </h5>
                        <button class="btn btn-sm btn-light" id="refresh-sessions-btn">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="card-body" id="active-sessions-container" style="max-height: 70vh; overflow-y: auto;">
                        @include('timing.mascot.partials.active-sessions', [
                            'activeSessions' => $activeSessions,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stop Work Modal – Qty & Measurement (same as Costume) -->
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
                        <div id="stop-session-info" class="alert alert-info mb-3"></div>

                        <!-- Measurement Type -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Measurement Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="stop-measurement-type" name="measurement_type" required>
                                @forelse($units as $unit)
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

                        <!-- Output Qty -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Output Quantity <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control form-control-lg" id="stop-output-qty"
                                name="output_qty" min="1" step="0.1" value="1" required>
                            <small class="text-muted">Minimum 1 — enter the quantity produced in this session</small>
                        </div>

                        <input type="hidden" id="stop-timing-id" name="timing_id">
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
@endsection

@section('styles')
    <style>
        .btn-xs { padding: 0.15rem 0.5rem; font-size: 0.72rem; }

        /* Gradient icon */
        .gradient-icon {
            background: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Mascot gradient - Yellow to Orange */
        .bg-gradient-mascot {
            background: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);
        }

        .employee-card.selected {
            border-color: #ff9800 !important;
            background: linear-gradient(135deg, rgba(249, 212, 35, 0.1) 0%, rgba(255, 78, 80, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(249, 212, 35, 0.3);
        }

        .employee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .session-card {
            border-left: 4px solid #ff9800;
            transition: all 0.3s;
        }

        .session-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .duration-display {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }

        .btn-check:checked+.btn-outline-primary {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let selectedEmployees = [];
            let selectedJobOrder = null;

            // Initialize Select2
            $('#job-order-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose Job Order...',
                allowClear: true,
                width: '100%'
            });

            // Employee text search
            $('#employee-search').on('input', function() {
                filterEmployees();
            });

            // Select all visible employees
            $('#select-all-btn').on('click', function() {
                $('.employee-card-wrapper:visible').each(function() {
                    const cb = $(this).find('.employee-checkbox');
                    if (!cb.prop('checked')) {
                        cb.prop('checked', true).trigger('change');
                    }
                });
            });

            // Deselect all
            $('#deselect-all-btn').on('click', function() {
                $('.employee-checkbox:checked').each(function() {
                    $(this).prop('checked', false).trigger('change');
                });
            });

            // Skillset filter buttons
            let activeSkillset = 'all';
            $('.skillset-filter').on('click', function() {
                $('.skillset-filter').removeClass('active btn-warning').addClass('btn-outline-warning');
                $(this).removeClass('btn-outline-warning').addClass('active btn-warning');
                activeSkillset = $(this).attr('data-skillset');
                filterEmployees();
            });

            // Filter employees
            function filterEmployees() {
                const searchTerm = ($('#employee-search').val() || '').toLowerCase().trim();
                let visibleCount = 0;

                $('.employee-card-wrapper').each(function() {
                    const skillsetIds = $(this).attr('data-skillset-ids') || ',';
                    const empName     = ($(this).attr('data-name') || '');

                    let show = true;
                    if (activeSkillset !== 'all' && !skillsetIds.includes(',' + activeSkillset + ',')) show = false;
                    if (searchTerm && !empName.includes(searchTerm))                                   show = false;

                    if (show) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                        $(this).find('.employee-checkbox').prop('checked', false).trigger('change');
                    }
                });

                $('#filtered-count').html(
                    (activeSkillset !== 'all' || searchTerm)
                        ? `<span class="badge bg-info">${visibleCount} shown</span>` : ''
                );
            }

            // Employee card click handler
            $(document).on('click', '.employee-card', function(e) {
                if (!$(e.target).hasClass('employee-checkbox') && !$(e.target).is('input')) {
                    const checkbox = $(this).find('.employee-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Employee checkbox change handler
            $(document).on('change', '.employee-checkbox', function() {
                const card = $(this).closest('.employee-card');
                const employeeId = $(this).val();

                if ($(this).is(':checked')) {
                    card.addClass('selected');
                    if (!selectedEmployees.includes(employeeId)) {
                        selectedEmployees.push(employeeId);
                    }
                } else {
                    card.removeClass('selected');
                    selectedEmployees = selectedEmployees.filter(id => id !== employeeId);
                }

                updateSelectedCount();
                updateStartButton();
            });

            // Job order selection handler
            $('#job-order-select').on('change', function() {
                const selected = $(this).find(':selected');
                selectedJobOrder = $(this).val();

                if (selectedJobOrder) {
                    const projectName = selected.data('project-name');
                    $('#project-name-display').text(projectName);

                    // Load job order progress info
                    loadJobOrderInfo(selectedJobOrder);
                    $('#job-order-info').removeClass('d-none');
                } else {
                    $('#job-order-info').addClass('d-none');
                }

                updateStartButton();
            });

            // Load job order current progress
            function loadJobOrderInfo(jobOrderId) {
                $.ajax({
                    url: `/mascot-timing/job-order/${jobOrderId}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const jo = response.job_order;
                            $('#current-stage-display').text(jo.current_stage);
                            $('#current-progress-display').text(jo.current_progress + '%');
                        }
                    }
                });
            }

            // Update selected count
            function updateSelectedCount() {
                $('#selected-count').text(selectedEmployees.length + ' employee(s) selected');
            }

            // Update start button state
            function updateStartButton() {
                const hasEmployees = selectedEmployees.length > 0;
                const hasJobOrder = selectedJobOrder !== null && selectedJobOrder !== '';
                const hasTask = $('#task-input').val().trim() !== '';

                const btn = $('#start-work-btn');

                if (hasEmployees && hasJobOrder && hasTask) {
                    btn.prop('disabled', false);
                    $('#btn-info').text(`(${selectedEmployees.length} employee(s) ready)`);
                } else {
                    btn.prop('disabled', true);
                    $('#btn-info').text('(Select employees, job order & task first)');
                }
            }

            // Task input change
            $('#task-input').on('input', updateStartButton);

            // Start work form submission
            $('#mascot-timer-form').on('submit', function(e) {
                e.preventDefault();

                const task = $('#task-input').val().trim();

                if (selectedEmployees.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Employees Selected',
                        text: 'Please select at least one employee'
                    });
                    return;
                }

                if (!selectedJobOrder) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Job Order Selected',
                        text: 'Please select a job order'
                    });
                    return;
                }

                if (!task) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Task Entered',
                        text: 'Please enter a task description'
                    });
                    return;
                }

                const btn = $('#start-work-btn');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Starting...');

                $.ajax({
                    url: '{{ route('mascot-timing.start') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        employees: selectedEmployees,
                        job_order_id: selectedJobOrder,
                        task: task
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Work Started!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Reset form
                            $('#mascot-timer-form')[0].reset();
                            $('.employee-card').removeClass('selected');
                            $('.employee-checkbox').prop('checked', false);
                            $('#job-order-info').addClass('d-none');
                            $('#job-order-select').val('').trigger('change');
                            selectedEmployees = [];
                            selectedJobOrder = null;

                            // Reload active sessions
                            loadActiveSessions();
                            updateStartButton();
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to start work session.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-play-circle-fill me-2"></i><span id="btn-text">START WORK</span> <span id="btn-info" class="small">(Select employees, job order & task first)</span>'
                        );
                        updateStartButton();
                    }
                });
            });

            // Stop work button click handler
            // Stop work button click handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId    = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder    = $(this).data('job-order');

                $('#stop-timing-id').val(timingId);
                $('#stop-session-info').html(
                    `<strong>Employee:</strong> ${employeeName}<br>
                     <strong>Job Order:</strong> ${jobOrder}`
                );

                // Reset fields to defaults
                $('#stop-measurement-type').val('pcs');
                $('#stop-output-qty').val(1);

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId       = $('#stop-timing-id').val();
                const outputQty      = parseFloat($('#stop-output-qty').val());
                const measureType    = $('#stop-measurement-type').val();

                if (!outputQty || outputQty < 1) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Quantity must be at least 1' });
                    return;
                }
                if (!measureType) {
                    Swal.fire({ icon: 'warning', title: 'Measurement Required', text: 'Please select a measurement type' });
                    return;
                }

                const submitBtn = $('#stop-submit-btn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('mascot-timing.stop') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_id:        timingId,
                        output_qty:       outputQty,
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

                            $(`#session-card-${timingId}`).fadeOut(300, function() {
                                $(this).remove();
                                if ($('.session-card').length === 0) {
                                    $('#active-sessions-container').html(`
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-mask" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">No active mascot sessions</p>
                                            <small>Start a new session to track mascot production</small>
                                        </div>
                                    `);
                                }
                            });

                            setTimeout(() => location.reload(), 2000);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to complete work session.';
                        Swal.fire({ icon: 'error', title: 'Error', text: message });
                        submitBtn.prop('disabled', false).html('<i class="bi bi-stop-circle me-1"></i>Stop & Save');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="bi bi-stop-circle me-1"></i>Stop & Save');
                    }
                });
            });

            // Duration timer for active sessions
            let durationInterval;

            function startDurationTimers() {
                clearInterval(durationInterval);
                durationInterval = setInterval(() => {
                    $('.duration-display').each(function() {
                        const startTime = $(this).data('start-time');
                        if (!startTime) return;

                        const now = new Date();
                        const today = now.toISOString().split('T')[0];
                        const start = new Date(`${today} ${startTime}`);
                        const diff = Math.floor((now - start) / 1000);

                        const hours = Math.floor(diff / 3600);
                        const minutes = Math.floor((diff % 3600) / 60);
                        const seconds = diff % 60;

                        const display =
                            `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                        $(this).text(display);
                    });
                }, 1000);
            }

            // Refresh button - reload page
            $('#refresh-sessions-btn').on('click', function() {
                location.reload();
            });

            // Start duration timers on page load
            startDurationTimers();
        });
    </script>
@endsection
