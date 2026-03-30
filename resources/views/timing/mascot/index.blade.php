@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 fw-semibold" style="font-size:1.4rem;">Mascot Timing</h2>
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
                                    <button type="button" class="btn btn-warning btn-xs skillset-filter active"
                                        data-skillset="all">All</button>
                                    @foreach ($employeesBySkillset as $group)
                                        <button type="button" class="btn btn-outline-warning btn-xs skillset-filter"
                                            data-skillset="{{ $group['skillset_id'] ?? 'none' }}">
                                            {{ $group['label'] }}
                                        </button>
                                    @endforeach
                                </div>

                                <div id="employee-cards">
                                    @if ($employees->isEmpty())
                                        <div class="alert alert-warning">
                                            No active mascot employees found. Please add employees to mascot department
                                            first.
                                        </div>
                                    @else
                                        <div class="row g-2">
                                            @foreach ($employees as $employee)
                                                @php $frozenInfo = $frozenSessionsByEmployee[$employee->id] ?? null; @endphp
                                                <div class="col-md-4 col-sm-6 employee-card-wrapper"
                                                    data-skillset-ids=",{{ $employee->skillsets->pluck('id')->implode(',') }},"
                                                    data-department-id="{{ $employee->department_id }}"
                                                    data-position="{{ $employee->position }}"
                                                    data-name="{{ strtolower($employee->name) }}"
                                                    @if($frozenInfo)
                                                        data-has-paused="true"
                                                        data-paused-job-order="{{ $frozenInfo['job_order_name'] }}"
                                                        data-paused-duration="{{ $frozenInfo['frozen_duration'] }}"
                                                        data-paused-timing-id="{{ $frozenInfo['timing_id'] }}"
                                                    @endif>
                                                    <div class="card employee-card h-100 border-2 {{ $frozenInfo ? 'border-warning' : '' }}"
                                                        data-employee-id="{{ $employee->id }}"
                                                        style="cursor: pointer; transition: all 0.3s;">
                                                        <div class="card-body text-center p-2">
                                                            <div class="form-check position-absolute top-0 end-0 m-1">
                                                                <input class="form-check-input employee-checkbox"
                                                                    type="checkbox" name="employees[]"
                                                                    value="{{ $employee->id }}"
                                                                    id="emp-{{ $employee->id }}">
                                                            </div>
                                                            @if ($frozenInfo)
                                                                <span class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark" style="font-size:0.6rem;">
                                                                    <i class="bi bi-pause-circle"></i> PAUSED
                                                                </span>
                                                            @endif
                                                            @if ($employee->photo)
                                                                <img src="{{ asset('storage/' . $employee->photo) }}"
                                                                    class="rounded-circle mb-1 border" width="44"
                                                                    height="44" style="object-fit: cover;">
                                                            @else
                                                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-1"
                                                                    style="width: 44px; height: 44px;">
                                                                    <i class="bi bi-person text-white"></i>
                                                                </div>
                                                            @endif
                                                            <h6 class="mb-0 small lh-sm">{{ $employee->name }}</h6>
                                                            @if ($frozenInfo)
                                                                <div class="text-warning" style="font-size:0.65rem;">
                                                                    <i class="bi bi-clock-history"></i> {{ $frozenInfo['frozen_duration'] }}
                                                                </div>
                                                            @endif
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

    <!-- Stop Work Modal – Stage Selection (1–10, each = 10% progress) -->
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

                        <!-- Output Qty + Measurement Type -->
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Output Qty <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="stop-output-qty"
                                    name="output_qty" min="0" step="0.1" value="1" required>
                            </div>
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
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="stop-submit-btn">
                            <i class="bi bi-stop-circle me-1"></i>Stop &amp; Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .btn-xs {
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
        }

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
        // Frozen sessions by employee_id — used for paused-session warnings
        let frozenSessionsByEmployee = @json($frozenSessionsByEmployee);

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

            // Initialize Select2 for Stop modal — Stage
            $('#stop-stage').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose stage...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stopWorkModal')
            });

            // Initialize Select2 for Stop modal — Unit
            $('#stop-measurement-type').select2({
                theme: 'bootstrap-5',
                minimumResultsForSearch: Infinity,
                width: '100%',
                dropdownParent: $('#stopWorkModal')
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
                    const empName = ($(this).attr('data-name') || '');

                    let show = true;
                    if (activeSkillset !== 'all' && !skillsetIds.includes(',' + activeSkillset + ','))
                        show = false;
                    if (searchTerm && !empName.includes(searchTerm)) show = false;

                    if (show) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                        $(this).find('.employee-checkbox').prop('checked', false).trigger('change');
                    }
                });

                $('#filtered-count').html(
                    (activeSkillset !== 'all' || searchTerm) ?
                    `<span class="badge bg-info">${visibleCount} shown</span>` : ''
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

                // Check if any selected employees have a paused session
                const pausedWarnings = [];
                selectedEmployees.forEach(empId => {
                    const wrapper = $(`.employee-card-wrapper[data-has-paused="true"]`).filter(function() {
                        return $(this).find('.employee-checkbox').val() == empId;
                    });
                    if (wrapper.length) {
                        const jobOrder = wrapper.data('paused-job-order');
                        const duration = wrapper.data('paused-duration');
                        const empName = wrapper.find('h6').text().trim();
                        pausedWarnings.push(`<li><strong>${empName}</strong> — masih ada sesi PAUSED: <em>${jobOrder}</em> (${duration})</li>`);
                    }
                });

                if (pausedWarnings.length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ada Sesi yang Sedang Dipause!',
                        html: `Karyawan berikut masih memiliki sesi yang dipause:<ul class="text-start mt-2">${pausedWarnings.join('')}</ul>Tetap mulai sesi baru?`,
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        confirmButtonText: 'Ya, Mulai Sesi Baru',
                        cancelButtonText: 'Batal',
                    }).then(result => {
                        if (result.isConfirmed) doMascotStartSession();
                    });
                    return;
                }

                doMascotStartSession();
            });

            function doMascotStartSession() {
                const task = $('#task-input').val().trim();
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

                            // Remove started employees from the available list immediately
                            response.timings.forEach(function(timing) {
                                $(`.employee-card[data-employee-id="${timing.employee_id}"]`)
                                    .closest('.employee-card-wrapper')
                                    .fadeOut(300, function() { $(this).remove(); });
                            });

                            // Reset form
                            $('#mascot-timer-form')[0].reset();
                            $('.employee-card').removeClass('selected');
                            $('.employee-checkbox').prop('checked', false);
                            $('#job-order-info').addClass('d-none');
                            $('#job-order-select').val('').trigger('change');
                            selectedEmployees = [];
                            selectedJobOrder = null;

                            // Reload active sessions and employee list
                            loadActiveSessions();
                            loadAvailableEmployees();
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
            }

            // Stop work button click handler
            $(document).on('click', '.stop-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const employeeName = $(this).data('employee-name');
                const jobOrder = $(this).data('job-order');
                const jobOrderId = $(this).data('job-order-id');
                const employeeId = $(this).data('employee-id');
                const previousProgress = parseInt($(this).data('previous-progress')) || 0;

                $('#stop-timing-id').val(timingId);
                $('#stop-job-order-id').val(jobOrderId);
                $('#stop-work-form').data('current-employee-id', employeeId);

                let sessionInfoHtml = `<strong>Employee:</strong> ${employeeName}<br><strong>Job Order:</strong> ${jobOrder}`;
                if (employeeId && frozenSessionsByEmployee[employeeId]) {
                    const frozen = frozenSessionsByEmployee[employeeId];
                    sessionInfoHtml += `<div class="alert alert-warning mt-2 mb-0 py-2">
                        <i class="bi bi-pause-circle me-1"></i>
                        <strong>Perhatian:</strong> ${employeeName} masih memiliki sesi yang sedang <strong>PAUSE</strong>:<br>
                        <span class="small">Job Order: <em>${frozen.job_order_name}</em> &mdash; durasi tersimpan: ${frozen.frozen_duration}</span>
                    </div>`;
                }
                $('#stop-session-info').html(sessionInfoHtml);

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

                // Pre-select current stage
                if (currentStage > 0) {
                    $('#stop-stage').val(currentStage).trigger('change');
                }

                // Add backward-navigation warning
                if (currentStage > 0) {
                    $('#stop-session-info').append(
                        `<div class="alert alert-warning mt-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Current progress is at stage ${currentStage} (${previousProgress}%).
                            You can select stage ${currentStage} (repeat) or higher. Cannot go back.
                        </div>`
                    );
                }

                // Live-update progress preview when stage changes
                $('#stop-stage').off('change.preview').on('change.preview', function() {
                    const stage = parseInt($(this).val()) || 0;
                    $('#current-progress-display').text(stage * 10);
                });

                // Reset qty + unit defaults
                $('#stop-output-qty').val(1);
                const defaultUnit = $('#stop-measurement-type option').filter(function() {
                    return $(this).val() === 'pcs';
                }).val() || $('#stop-measurement-type option:first').val();
                $('#stop-measurement-type').val(defaultUnit).trigger('change');

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const stoppingEmployeeId = $('#stop-work-form').data('current-employee-id');
                const stage = parseInt($('#stop-stage').val());
                const outputQty = parseFloat($('#stop-output-qty').val());
                const measurementType = $('#stop-measurement-type').val();

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
                        measurement_type: measurementType,
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#stopWorkModal').modal('hide');

                            const frozen = stoppingEmployeeId ? frozenSessionsByEmployee[stoppingEmployeeId] : null;
                            if (frozen) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Pekerjaan Selesai!',
                                    html: `${response.message}<br><br>
                                        <div class="alert alert-warning text-start mb-0 py-2">
                                            <i class="bi bi-pause-circle me-1"></i>
                                            <strong>Pengingat:</strong> Karyawan ini masih memiliki sesi yang sedang <strong>PAUSE</strong>:<br>
                                            <span class="small">Job Order: <em>${frozen.job_order_name}</em> &mdash; durasi tersimpan: ${frozen.frozen_duration}</span><br>
                                            <span class="small text-muted">Jangan lupa untuk melanjutkan atau menyelesaikan sesi tersebut.</span>
                                        </div>`,
                                    confirmButtonText: 'OK, Mengerti',
                                    confirmButtonColor: '#198754',
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Work Completed!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }

                            $(`#session-card-${timingId}`).fadeOut(300, function() { $(this).remove(); });
                            loadActiveSessions();
                            loadAvailableEmployees();
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

            // ── Freeze / Unfreeze handlers ────────────────────────────────────
            $(document).on('click', '.freeze-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'info',
                    title: 'Pause Session?',
                    html: `Timer for <strong>${empName}</strong> will be paused.`,
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: '<i class="bi bi-pause-circle"></i> Pause',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('mascot-timing.freeze') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', timing_id: timingId },
                        success: function(r) {
                            if (r.success) {
                                loadActiveSessions();
                                loadAvailableEmployees();
                                Swal.fire({ icon: 'success', title: 'Paused!', text: r.message, timer: 1800, showConfirmButton: false });
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

            $(document).on('click', '.unfreeze-work-btn', function() {
                const timingId = $(this).data('timing-id');
                const empName = $(this).data('employee-name');
                Swal.fire({
                    icon: 'question',
                    title: 'Continue Session?',
                    html: `Timer for <strong>${empName}</strong> will continue from where it was paused.`,
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: '<i class="bi bi-play-circle"></i> Continue',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: '{{ route('mascot-timing.unfreeze') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', timing_id: timingId },
                        success: function(r) {
                            if (r.success) {
                                loadActiveSessions();
                                loadAvailableEmployees();
                                Swal.fire({ icon: 'success', title: r.auto_froze ? 'Switched!' : 'Continued!', text: r.message, timer: 2500, showConfirmButton: false });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to continue.' });
                        }
                    });
                });
            });
            // ─────────────────────────────────────────────────────────────────

            // ── Auto-refresh active sessions (triggers break service) ──────────
            function loadActiveSessions() {
                $.ajax({
                    url: '{{ route('mascot-timing.active-sessions') }}',
                    method: 'GET',
                    success: function(response) {
                        if (!response.success) return;
                        updateActiveSessionsDisplay(response.sessions);
                    }
                });
            }

            function updateActiveSessionsDisplay(sessions) {
                const container = $('#active-sessions-container');
                if (sessions.length === 0) {
                    container.html(`
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-mask" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">No active mascot sessions</p>
                            <small>Start a new session to track mascot production</small>
                        </div>
                    `);
                    startDurationTimers();
                    return;
                }

                let html = '';
                sessions.forEach(session => {
                    const isFrozen = session.is_frozen || false;
                    const isAutoBreak = session.auto_break_paused || false;
                    const photo = session.employee_photo
                        ? `<img src="/storage/${session.employee_photo}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">`
                        : `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;"><i class="bi bi-person text-white"></i></div>`;

                    const statusBadge = isFrozen
                        ? `<span class="badge bg-warning text-dark me-1"><i class="bi bi-pause-circle"></i> PAUSED${isAutoBreak ? ' (BREAK)' : ''}</span>`
                        : `<span class="badge bg-success me-1">RUNNING</span>`;

                    const durationHtml = isFrozen
                        ? `<span class="fs-5 fw-bold text-warning">${session.frozen_duration || '00:00:00'}</span>`
                        : `<span class="duration-display fs-5 fw-bold text-success" data-start-time="${session.start_time}" data-session-id="${session.id}">00:00:00</span>`;

                    const cardBorder = isFrozen ? 'border-warning border-2' : '';

                    const actionBtns = isFrozen
                        ? `<div class="d-grid">
                               <button class="btn btn-success btn-sm unfreeze-work-btn"
                                   data-timing-id="${session.id}"
                                   data-employee-name="${session.employee_name}">
                                   <i class="bi bi-play-circle me-1"></i>Continue
                               </button>
                           </div>`
                        : `<div class="d-flex gap-2">
                               <button class="btn btn-warning btn-sm freeze-work-btn flex-shrink-0"
                                   data-timing-id="${session.id}"
                                   data-employee-name="${session.employee_name}">
                                   <i class="bi bi-pause-circle me-1"></i>Pause
                               </button>
                               <button class="btn btn-danger btn-sm stop-work-btn flex-grow-1"
                                   data-timing-id="${session.id}"
                                   data-employee-name="${session.employee_name}"
                                   data-job-order="${session.job_order_name}"
                                   data-job-order-id="${session.job_order_id}"
                                   data-previous-progress="${session.previous_progress || 0}">
                                   <i class="bi bi-stop-circle me-1"></i>STOP & SELECT STAGE
                               </button>
                           </div>`;

                    html += `
                        <div class="card session-card mb-3 ${cardBorder}" id="session-card-${session.id}" data-session-id="${session.id}">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    ${photo}
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">${statusBadge}${session.employee_name}</h6>
                                        <small class="text-muted">${session.employee_position || 'N/A'}</small>
                                    </div>
                                    ${durationHtml}
                                </div>
                                <div class="border-top pt-2 mb-2">
                                    <div class="row g-2 small">
                                        <div class="col-12">
                                            <strong>Job Order:</strong> ${session.job_order_name}<br>
                                            <strong>Project:</strong> ${session.project_name}
                                        </div>
                                        <div class="col-12"><strong>Task:</strong> ${session.task}</div>
                                        <div class="col-12"><strong>Previous Progress:</strong> ${session.previous_progress || 0}%</div>
                                        <div class="col-12"><small class="text-muted"><i class="bi bi-clock"></i> Started: ${session.start_time}</small></div>
                                    </div>
                                </div>
                                ${actionBtns}
                            </div>
                        </div>`;
                });

                container.html(html);
                startDurationTimers();
            }

            // Poll every 30 seconds — also triggers TimingBreakService on server
            setInterval(loadActiveSessions, 30000);
            // ─────────────────────────────────────────────────────────────────

            // Start duration timers on page load
            startDurationTimers();

            // ── Available-employees helpers ───────────────────────────────────
            function loadAvailableEmployees() {
                $.ajax({
                    url: '{{ route('mascot-timing.available-employees') }}',
                    method: 'GET',
                    success: function(r) {
                        if (r.success) {
                            frozenSessionsByEmployee = r.frozen_sessions_by_employee || {};
                            updateEmployeeListDisplay(r.employees);
                        }
                    }
                });
            }

            function updateEmployeeListDisplay(employees) {
                selectedEmployees = [];
                updateStartButton();

                if (!employees || employees.length === 0) {
                    $('#employee-cards').html('<div class="alert alert-info">No available employees at this time.</div>');
                    return;
                }

                let html = '<div class="row g-2">';
                employees.forEach(function(emp) {
                    const frozen = emp.frozen_info;
                    const skillsetIds = emp.skillset_ids && emp.skillset_ids.length ? ',' + emp.skillset_ids.join(',') + ',' : ',';
                    const borderClass = frozen ? 'border-warning' : '';
                    const pausedAttrs = frozen
                        ? `data-has-paused="true" data-paused-job-order="${frozen.job_order_name}" data-paused-duration="${frozen.frozen_duration}" data-paused-timing-id="${frozen.timing_id}"`
                        : '';
                    const pausedBadge = frozen
                        ? `<span class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark" style="font-size:0.6rem;"><i class="bi bi-pause-circle"></i> PAUSED</span>`
                        : '';
                    const pausedDur = frozen
                        ? `<div class="text-warning" style="font-size:0.65rem;"><i class="bi bi-clock-history"></i> ${frozen.frozen_duration}</div>`
                        : '';
                    const photoHtml = emp.photo
                        ? `<img src="/storage/${emp.photo}" class="rounded-circle mb-1 border" width="44" height="44" style="object-fit:cover;">`
                        : `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-1" style="width:44px;height:44px;"><i class="bi bi-person text-white"></i></div>`;

                    html += `
                        <div class="col-md-4 col-sm-6 employee-card-wrapper"
                            data-skillset-ids="${skillsetIds}"
                            data-department-id="${emp.department_id}"
                            data-position="${emp.position || ''}"
                            data-name="${emp.name.toLowerCase()}"
                            ${pausedAttrs}>
                            <div class="card employee-card h-100 border-2 ${borderClass}" data-employee-id="${emp.id}" style="cursor:pointer;transition:all 0.3s;">
                                <div class="card-body text-center p-2">
                                    <div class="form-check position-absolute top-0 end-0 m-1">
                                        <input class="form-check-input employee-checkbox" type="checkbox" name="employees[]" value="${emp.id}" id="emp-${emp.id}">
                                    </div>
                                    ${pausedBadge}
                                    ${photoHtml}
                                    <h6 class="mb-0 small lh-sm">${emp.name}</h6>
                                    ${pausedDur}
                                </div>
                            </div>
                        </div>`;
                });
                html += '</div>';
                $('#employee-cards').html(html);
                filterEmployees();
            }
            // ─────────────────────────────────────────────────────────────────
        });
    </script>
    @include('timing.partials.detail-modal')
    @include('timing.partials.break-heartbeat')
@endsection
