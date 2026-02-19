@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-cut gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">👔 Costume Timing - Production Tracking</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('costume-timing.monitor') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-tv me-1"></i> Costume Monitor
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
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-play-circle me-2"></i>Start New Work Session</h5>
                    </div>
                    <div class="card-body">
                        <form id="costume-timer-form">
                            @csrf

                            <!-- STEP 1: Select Employees -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-primary me-2">1</span>Select Employees (Multiple)
                                </label>

                                <!-- Filter Controls -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-5">
                                        <select class="form-select form-select-sm" id="filter-department"
                                            data-placeholder="All Departments">
                                            <option value="">All Departments</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <select class="form-select form-select-sm" id="filter-position"
                                            data-placeholder="All Positions">
                                            <option value="">All Positions</option>
                                            @foreach ($positions as $pos)
                                                <option value="{{ $pos }}">{{ $pos }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                                            id="reset-filters">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3" id="employee-cards">
                                    @forelse($employees as $employee)
                                        <div class="col-md-4 col-sm-6 employee-card-wrapper"
                                            data-department-id="{{ $employee->department_id }}"
                                            data-position="{{ $employee->position }}">
                                            <div class="card employee-card h-100 border-2"
                                                data-employee-id="{{ $employee->id }}"
                                                style="cursor: pointer; transition: all 0.3s;">
                                                <div class="card-body text-center p-3">
                                                    <div class="form-check position-absolute top-0 end-0 m-2">
                                                        <input class="form-check-input employee-checkbox" type="checkbox"
                                                            name="employees[]" value="{{ $employee->id }}"
                                                            id="emp-{{ $employee->id }}">
                                                    </div>
                                                    @if ($employee->photo)
                                                        <img src="{{ asset('storage/' . $employee->photo) }}"
                                                            class="rounded-circle mb-2 border" width="50" height="50"
                                                            style="object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-2"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="bi bi-person text-white fs-4"></i>
                                                        </div>
                                                    @endif
                                                    <h6 class="mb-1 small">{{ $employee->name }}</h6>
                                                    <small class="text-muted d-block">{{ $employee->position }}</small>
                                                    <small
                                                        class="text-muted">{{ $employee->department->name ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                No active employees found. Please add employees first.
                                            </div>
                                        </div>
                                    @endforelse
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
                                    <span class="badge bg-primary me-2">2</span>Select Job Order
                                </label>
                                <select class="form-select select2" id="job-order-select" name="job_order_id" required>
                                    <option value="">Choose Job Order...</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                            data-project-name="{{ $jo->project->name ?? 'N/A' }}"
                                            data-department="{{ $jo->department->name ?? 'N/A' }}"
                                            data-job-order-name="{{ $jo->name }}">
                                            {{ $jo->name }} ({{ $jo->project->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Auto-filled Project Info -->
                                <div id="project-info" class="mt-3 p-3 bg-light rounded d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Project:</small>
                                            <strong id="project-name-display">-</strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Department:</small>
                                            <strong id="department-name-display">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: Work Details -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-primary me-2">3</span>Work Details
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Step/Process <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="step-input" name="step"
                                            placeholder="e.g., Cutting, Sewing" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Part/Component</label>
                                        <input type="text" class="form-control" id="parts-input" name="parts"
                                            placeholder="e.g., Body, Head (optional)">
                                    </div>
                                </div>
                            </div>

                            <!-- Start Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg" id="start-btn" disabled>
                                    <i class="bi bi-play-circle-fill me-2"></i>
                                    <span id="btn-text">START WORK</span>
                                    <span id="btn-info" class="small">(Select employees & job order first)</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Active Sessions -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Active Work Sessions</h5>
                    </div>
                    <div class="card-body" id="active-sessions-container" style="max-height: 600px; overflow-y: auto;">
                        @include('timing.costume.partials.active-sessions', [
                            'activeSessions' => $activeSessions,
                        ])
                    </div>
                </div>
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

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .employee-card {
            transition: all 0.3s ease;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .employee-card.selected {
            border-color: #667eea !important;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            box-shadow: 0 0 15px rgba(102, 126, 234, 0.3);
        }

        .session-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }

        .session-card:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .duration-display {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let selectedEmployees = [];
            let selectedJobOrder = null;

            // Initialize Select2 for job order
            $('#job-order-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose Job Order...',
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for filters
            $('#filter-department, #filter-position').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            });

            // Filter employees by department
            $('#filter-department').on('change', function() {
                filterEmployees();
            });

            // Filter employees by position
            $('#filter-position').on('change', function() {
                filterEmployees();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-department').val('').trigger('change');
                $('#filter-position').val('').trigger('change');
                filterEmployees();
            });

            // Filter function
            function filterEmployees() {
                const deptFilter = $('#filter-department').val();
                const posFilter = $('#filter-position').val();
                let visibleCount = 0;

                $('.employee-card-wrapper').each(function() {
                    const deptId = $(this).data('department-id');
                    const position = $(this).data('position');

                    let showCard = true;

                    if (deptFilter && deptId != deptFilter) {
                        showCard = false;
                    }

                    if (posFilter && position != posFilter) {
                        showCard = false;
                    }

                    if (showCard) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                        // Uncheck if hidden
                        $(this).find('.employee-checkbox').prop('checked', false).trigger('change');
                    }
                });

                // Update filtered count
                if (deptFilter || posFilter) {
                    $('#filtered-count').html(`<span class="badge bg-info">${visibleCount} shown</span>`);
                } else {
                    $('#filtered-count').html('');
                }
            }

            // Employee card click handler (delegated event)
            $(document).on('click', '.employee-card', function(e) {
                if (!$(e.target).hasClass('employee-checkbox') && !$(e.target).is('input')) {
                    const checkbox = $(this).find('.employee-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });

            // Employee checkbox change handler (delegated event)
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

                updateStartButton();
                updateSelectedCount();
            });

            // Update selected count
            function updateSelectedCount() {
                const count = selectedEmployees.length;
                $('#selected-count').text(count + ' employee(s) selected');
            }

            // Job order selection handler
            $('#job-order-select').on('change', function() {
                selectedJobOrder = $(this).val();

                if (selectedJobOrder) {
                    const selectedOption = $(this).find('option:selected');
                    const projectName = selectedOption.data('project-name');
                    const departmentName = selectedOption.data('department');

                    $('#project-name-display').text(projectName);
                    $('#department-name-display').text(departmentName);
                    $('#project-info').removeClass('d-none');
                } else {
                    $('#project-info').addClass('d-none');
                }

                updateStartButton();
            });

            // Update start button state
            function updateStartButton() {
                const btn = $('#start-btn');
                const btnText = $('#btn-text');
                const btnInfo = $('#btn-info');

                if (selectedEmployees.length > 0 && selectedJobOrder) {
                    btn.prop('disabled', false)
                        .removeClass('btn-secondary')
                        .addClass('btn-success');

                    btnText.text('START WORK');
                    btnInfo.text(
                        `(${selectedEmployees.length} employee${selectedEmployees.length > 1 ? 's' : ''} selected)`
                    );
                } else {
                    btn.prop('disabled', true)
                        .removeClass('btn-success')
                        .addClass('btn-secondary');

                    btnText.text('START WORK');

                    if (selectedEmployees.length === 0 && !selectedJobOrder) {
                        btnInfo.text('(Select employees & job order first)');
                    } else if (selectedEmployees.length === 0) {
                        btnInfo.text('(Select at least one employee)');
                    } else {
                        btnInfo.text('(Select a job order)');
                    }
                }

                $('#selected-count').text(`${selectedEmployees.length} employee(s) selected`);
            }

            // Form submission
            $('#costume-timer-form').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    _token: $('input[name="_token"]').val(),
                    employees: selectedEmployees,
                    job_order_id: selectedJobOrder,
                    step: $('#step-input').val(),
                    parts: $('#parts-input').val(),
                    output_qty: $('#output-qty-input').val()
                };

                // Disable button and show loading
                const btn = $('#start-btn');
                btn.prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-2"></i>Starting...');

                $.ajax({
                    url: '{{ route('costume-timing.start') }}',
                    method: 'POST',
                    data: formData,
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
                            $('#costume-timer-form')[0].reset();
                            $('.employee-card').removeClass('selected');
                            $('.employee-checkbox').prop('checked', false);
                            $('#project-info').addClass('d-none');
                            $('#job-order-select').val('').trigger('change');
                            selectedEmployees = [];
                            selectedJobOrder = null;

                            // REAL-TIME DISPLAY: Add new session cards immediately
                            if (response.timings && response.timings.length > 0) {
                                response.timings.forEach(timing => {
                                    addSessionCard(timing);
                                });
                                // Start duration timers for new cards
                                startDurationTimers();
                            } else {
                                // Fallback: reload active sessions
                                loadActiveSessions();
                            }

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
                            '<i class="bi bi-play-circle-fill me-2"></i><span id="btn-text">START WORK</span> <span id="btn-info" class="small">(Select employees & job order first)</span>'
                        );
                        updateStartButton();
                    }
                });
            });

            // Stop work handler (delegated event) - INDIVIDUAL STOP
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');

                $('#stop-timing-id').val(timingId);
                $('#stop-session-info').html(`
                    <div class="alert alert-info mb-0">
                        <strong>Employee:</strong> ${employeeName}<br>
                        <strong>Job Order:</strong> ${jobOrder}
                    </div>
                `);
                $('#stop-output-qty').val(1).focus();

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission - INDIVIDUAL
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

                            // Remove the specific card (fade out animation)
                            const cardId = response.timing_id || timingId;
                            $(`#session-card-${cardId}`).fadeOut(300, function() {
                                $(this).remove();

                                // Check if any sessions left
                                if ($('.session-card').length === 0) {
                                    $('#active-sessions-container').html(`
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-clock-history" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">No active work sessions</p>
                                            <small>Start a new session to track production time</small>
                                        </div>
                                    `);
                                }
                            });

                            // Reload page after delay to refresh employee list
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

            // Load active sessions
            function loadActiveSessions() {
                $.ajax({
                    url: '{{ route('costume-timing.active-sessions') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            updateActiveSessionsDisplay(response.sessions);
                        }
                    }
                });
            }

            // Update active sessions display
            function updateActiveSessionsDisplay(sessions) {
                const container = $('#active-sessions-container');

                if (sessions.length === 0) {
                    container.html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">No active work sessions</p>
                    <small>Start a new session to track production time</small>
                </div>
            `);
                    return;
                }

                // Group sessions by job_order_id
                const grouped = sessions.reduce((acc, session) => {
                    if (!acc[session.job_order_id]) {
                        acc[session.job_order_id] = [];
                    }
                    acc[session.job_order_id].push(session);
                    return acc;
                }, {});

                let html = '';
                for (const [jobOrderId, sessionsGroup] of Object.entries(grouped)) {
                    const firstSession = sessionsGroup[0];
                    const timingIds = sessionsGroup.map(s => s.id);
                    const employeeNames = sessionsGroup.map(s => s.employee_name).join(', ');
                    const jobOrderName = firstSession.job_order_name || jobOrderId;
                    const projectName = firstSession.project_name || 'N/A';

                    html += `
                <div class="card session-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <span class="badge bg-success">RUNNING</span>
                                    ${jobOrderName}
                                </h6>
                                <small class="text-muted">${projectName}</small>
                            </div>
                            <span class="duration-display" data-start-time="${firstSession.start_time}">
                                ${firstSession.duration}
                            </span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Employees (${sessionsGroup.length}):</small>
                            <strong class="small">${employeeNames}</strong>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Step:</small>
                                <strong class="small">${firstSession.step}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Part:</small>
                                <strong class="small">${firstSession.parts}</strong>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-danger btn-sm stop-work-btn"
                                    data-timing-ids='${JSON.stringify(timingIds)}'
                                    data-session-info="<strong>Job Order:</strong> ${jobOrderName}<br><strong>Project:</strong> ${projectName}<br><strong>Employees:</strong> ${employeeNames}">
                                <i class="bi bi-stop-circle me-1"></i>STOP WORK
                            </button>
                        </div>
                    </div>
                </div>
            `;
                }

                container.html(html);
                startDurationTimers();
            }

            // Add individual session card (Real-time display)
            function addSessionCard(timing) {
                const photoHtml = timing.employee_photo ?
                    `<img src="/storage/${timing.employee_photo}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">` :
                    `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                         <i class="bi bi-person text-white"></i>
                       </div>`;

                const cardHtml = `
                    <div class="card session-card mb-3" id="session-card-${timing.id}" data-session-id="${timing.id}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                ${photoHtml}
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <span class="badge bg-success me-1">RUNNING</span>
                                        ${timing.employee_name}
                                    </h6>
                                    <small class="text-muted">${timing.employee_position || 'N/A'}</small>
                                </div>
                                <span class="duration-display fs-5 fw-bold text-success"
                                      data-start-time="${timing.start_time}"
                                      data-session-id="${timing.id}">
                                    00:00:00
                                </span>
                            </div>
                            <div class="border-top pt-2 mb-2">
                                <div class="row g-2 small">
                                    <div class="col-12">
                                        <strong>Job Order:</strong> ${timing.job_order_name}<br>
                                        <strong>Project:</strong> ${timing.project_name}
                                    </div>
                                    <div class="col-6">
                                        <strong>Step:</strong> ${timing.step}
                                    </div>
                                    <div class="col-6">
                                        <strong>Part:</strong> ${timing.parts}
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> Started: ${timing.start_time}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-danger btn-sm stop-work-btn"
                                        data-timing-id="${timing.id}"
                                        data-employee-name="${timing.employee_name}"
                                        data-job-order="${timing.job_order_name}">
                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER QTY
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                // Check if container has empty message
                const container = $('#active-sessions-container');
                if (container.find('.text-center.text-muted').length > 0) {
                    container.html(cardHtml);
                } else {
                    // Prepend to top (newest first)
                    container.prepend(cardHtml);
                }
            }

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

            // Calculate duration - FIX BUG
            function calculateDuration(startTime) {
                try {
                    // Parse today's date with start time
                    const today = new Date();
                    const [hours, minutes, seconds] = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes,
                        seconds);
                    const now = new Date();

                    // Calculate difference in seconds
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

            // Auto-refresh active sessions every 30 seconds
            setInterval(loadActiveSessions, 30000);

            // Start duration timers on page load
            startDurationTimers();
        });
    </script>
@endsection
