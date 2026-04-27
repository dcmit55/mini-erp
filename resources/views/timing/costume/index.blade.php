@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 fw-semibold" style="font-size:1.4rem;">Costume Timing</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('costume-timing.monitor') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-tv me-1"></i> Costume Monitor
                </a>
                {{-- <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Animatronics
                </a>
                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-mask me-1"></i> Mascot Timing
                </a> --}}
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
                    <div class="card-header bg-gradient-costume text-white">
                        <h5 class="mb-0"><i class="bi bi-play-circle me-2"></i>Start New Work Session</h5>
                    </div>
                    <div class="card-body">
                        <form id="costume-timer-form">
                            @csrf

                            <!-- STEP 1: Select Employees -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-secondary me-2">1</span>Select Employees (Multiple)
                                </label>

                                <!-- Employee Search -->
                                <div class="mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="employee-search" class="form-control form-control-sm"
                                            placeholder="Search by name, position, or department...">
                                        <button type="button" id="select-all-btn" class="btn btn-outline-secondary btn-sm">
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
                                    <button type="button" class="btn btn-secondary btn-xs skillset-filter active"
                                        data-skillset="all">All</button>
                                    @foreach ($employeesBySkillset as $group)
                                        <button type="button" class="btn btn-outline-secondary btn-xs skillset-filter"
                                            data-skillset="{{ $group['skillset_id'] ?? 'none' }}">
                                            {{ $group['label'] }}
                                        </button>
                                    @endforeach
                                </div>

                                <div id="employee-cards">
                                    @if ($employees->isEmpty())
                                        <div class="alert alert-warning">
                                            No active employees found. Please add employees first.
                                        </div>
                                    @else
                                        <div class="row g-2">
                                            @foreach ($employees as $employee)
                                                @php
                                                    $frozenInfo = $frozenSessionsByEmployee[$employee->id] ?? null;
                                                @endphp
                                                <div class="col-md-4 col-sm-6 employee-card-wrapper"
                                                    data-skillset-ids=",{{ $employee->skillsets->pluck('id')->implode(',') }},"
                                                    data-department-id="{{ $employee->department_id }}"
                                                    data-position="{{ $employee->position }}"
                                                    data-name="{{ strtolower($employee->name) }}"
                                                    @if ($frozenInfo) data-has-paused="true"
                                                        data-paused-job-order="{{ $frozenInfo['job_order_name'] }}"
                                                        data-paused-duration="{{ $frozenInfo['frozen_duration'] }}"
                                                        data-paused-timing-id="{{ $frozenInfo['timing_id'] }}" @endif>
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
                                                                <span
                                                                    class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark"
                                                                    style="font-size:0.6rem;">
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
                                                            <div class="emp-plan-task text-success d-none"
                                                                style="font-size:0.6rem; line-height:1.2; margin-top:2px;">
                                                                <i class="bi bi-clipboard2-check me-1"></i><span
                                                                    class="emp-plan-task-text"></span></div>
                                                            <div class="emp-plan-session d-none"
                                                                style="font-size:0.6rem; line-height:1.2; margin-top:1px;">
                                                                <span class="emp-plan-session-badge badge"></span></div>
                                                            @if ($frozenInfo)
                                                                <div class="text-warning" style="font-size:0.65rem;">
                                                                    <i class="bi bi-clock-history"></i>
                                                                    {{ $frozenInfo['frozen_duration'] }}
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
                                    <span class="badge bg-secondary me-2">2</span>Select Job Order
                                </label>
                                <select class="form-select select2" id="job-order-select" name="job_order_id" required>
                                    <option value="">Choose Job Order...</option>
                                    @foreach ($jobOrders as $jo)
                                        @php $joplan = $plannedDataPerJo[$jo->id] ?? null; @endphp
                                        <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                            data-project-name="{{ $jo->project->name ?? 'N/A' }}"
                                            data-department="{{ $jo->department->name ?? 'N/A' }}"
                                            data-job-order-name="{{ $jo->name }}"
                                            data-planned-task="{{ $joplan['task'] ?? '' }}"
                                            data-planned-tasks-by-emp='@json($joplan['task_per_emp'] ?? [])'
                                            data-planned-session-type="{{ $joplan['session_type'] ?? '' }}"
                                            data-planned-session-types-by-emp='@json($joplan['session_type_per_emp'] ?? [])'
                                            data-planned-employees='@json($joplan['employee_ids'] ?? [])'>
                                            {{ $jo->name }}
                                            ({{ $jo->project->name ?? 'N/A' }}){{ $joplan ? ' 📅' : '' }}
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
                                    <span class="badge bg-secondary me-2">3</span>Work Details
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Step/Process <span class="text-danger">*</span>
                                            <span id="plan-task-badge" class="badge bg-success ms-1 d-none"><i
                                                    class="bi bi-calendar2-check-fill me-1"></i>From Plan</span>
                                        </label>
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

                            <!-- STEP 4: Session Type -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-secondary me-2">4</span>Session Type <span
                                        class="text-danger">*</span>
                                    <span id="plan-session-badge" class="badge bg-success ms-1 d-none"><i
                                            class="bi bi-calendar2-check-fill me-1"></i>From Plan</span>
                                </label>
                                <div class="d-flex gap-3">
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="session_type"
                                            id="type-mass-production" value="mass_production" checked>
                                        <label
                                            class="form-check-label d-flex align-items-center gap-2 p-2 border rounded w-100 cursor-pointer session-type-label"
                                            for="type-mass-production" style="cursor:pointer;">
                                            <span class="badge bg-success fs-6"><i
                                                    class="bi bi-grid-3x3-gap-fill"></i></span>
                                            <div>
                                                <strong class="d-block">Mass Production</strong>
                                                <small class="text-muted">Produksi massal / normal</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="session_type"
                                            id="type-repair" value="repair">
                                        <label
                                            class="form-check-label d-flex align-items-center gap-2 p-2 border rounded w-100 cursor-pointer session-type-label"
                                            for="type-repair" style="cursor:pointer;">
                                            <span class="badge bg-warning text-dark fs-6"><i
                                                    class="bi bi-tools"></i></span>
                                            <div>
                                                <strong class="d-block">Repair</strong>
                                                <small class="text-muted">Perbaikan / rework unit</small>
                                            </div>
                                        </label>
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
                    <div
                        class="card-header bg-gradient-costume text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Active Sessions</h5>
                        <button class="btn btn-sm btn-light" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="card-body" id="active-sessions-container" style="max-height: 70vh; overflow-y: auto;">
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
        .btn-xs {
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
        }

        /* Session Type Radio Visual Feedback */
        .form-check-input[name="session_type"]:checked+.session-type-label {
            border-color: #0d6efd !important;
            background-color: #f0f4ff;
        }

        .form-check-input[value="mass_production"]:checked+.session-type-label {
            border-color: #198754 !important;
            background-color: #f0fff4;
        }

        .form-check-input[value="repair"]:checked+.session-type-label {
            border-color: #fd7e14 !important;
            background-color: #fff8f0;
        }

        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-gradient-costume {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .employee-card {
            transition: all 0.3s ease;
        }

        .employee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .employee-card.selected {
            border-color: #667eea !important;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .session-card {
            border-left: 4px solid #667eea;
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
    </style>
@endsection

@section('scripts')
    <script>
        // Units from DB — used in dynamic grouped stop modal
        const UNITS_OPTIONS = @json($units->map(fn($u) => ['value' => strtolower($u->name), 'label' => $u->name]));

        // Frozen sessions by employee_id — used for paused-session warnings
        let frozenSessionsByEmployee = @json($frozenSessionsByEmployee);

        $(document).ready(function() {
            let selectedEmployees = [];
            let selectedJobOrder = null;
            let currentTasksByEmp = {}; // Per-employee planned tasks from plan
            let currentSessionTypesByEmp = {}; // Per-employee planned session_type from plan

            // Initialize Select2 for job order
            $('#job-order-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose Job Order...',
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for stop modal measurement type
            $('#stop-measurement-type').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#stopWorkModal'),
                minimumResultsForSearch: Infinity,
                width: '100%',
            });

            // Employee text search
            $('#employee-search').on('input', function() {
                filterEmployees();
            });

            // Select all visible
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

            // Skillset filter buttons
            let activeSkillset = 'all';
            $('.skillset-filter').on('click', function() {
                $('.skillset-filter').removeClass('active btn-secondary').addClass('btn-outline-secondary');
                $(this).removeClass('btn-outline-secondary').addClass('active btn-secondary');
                activeSkillset = $(this).attr('data-skillset');
                filterEmployees();
            });

            // Filter function
            function filterEmployees() {
                const deptFilter = $('#filter-department').val();
                const posFilter = $('#filter-position').val();
                const searchTerm = ($('#employee-search').val() || '').toLowerCase().trim();
                let visibleCount = 0;

                $('.employee-card-wrapper').each(function() {
                    const skillsetIds = $(this).attr('data-skillset-ids') || ',';
                    const deptId = $(this).data('department-id');
                    const position = $(this).data('position');
                    const empName = ($(this).data('name') || '');

                    let show = true;
                    if (activeSkillset !== 'all' && !skillsetIds.includes(',' + activeSkillset + ','))
                        show = false;
                    if (deptFilter && deptId != deptFilter) show = false;
                    if (posFilter && position != posFilter) show = false;
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
                    (deptFilter || posFilter || activeSkillset !== 'all' || searchTerm) ?
                    `<span class="badge bg-info">${visibleCount} shown</span>` : ''
                );
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

                    // Auto-fill from plan
                    const plannedTask = selectedOption.data('planned-task') || '';
                    const plannedTasksByEmp = selectedOption.data('planned-tasks-by-emp') || {};
                    const plannedSessionType = selectedOption.data('planned-session-type') || '';
                    const plannedSessionTypesByEmp = selectedOption.data('planned-session-types-by-emp') ||
                    {};
                    const plannedEmpIds = selectedOption.data('planned-employees') || [];

                    // Store globally for form submission
                    currentTasksByEmp = plannedTasksByEmp;
                    currentSessionTypesByEmp = plannedSessionTypesByEmp;

                    if (plannedTask) {
                        $('#step-input').val(plannedTask);
                        $('#plan-task-badge').removeClass('d-none');
                    } else {
                        $('#plan-task-badge').addClass('d-none');
                    }

                    // Show per-employee planned tasks AND session types on employee cards
                    $('.employee-card-wrapper').each(function() {
                        const empId = $(this).find('.employee-checkbox').val();
                        const empTask = plannedTasksByEmp[empId];
                        const $label = $(this).find('.emp-plan-task');
                        if (empTask) {
                            $label.find('.emp-plan-task-text').text(empTask);
                            $label.removeClass('d-none');
                        } else {
                            $label.addClass('d-none');
                        }
                        const empSession = plannedSessionTypesByEmp[empId];
                        const $sessionDiv = $(this).find('.emp-plan-session');
                        if (empSession) {
                            const isMass = empSession === 'mass_production';
                            $sessionDiv.find('.emp-plan-session-badge')
                                .removeClass('bg-success bg-warning text-dark')
                                .addClass(isMass ? 'bg-success' : 'bg-warning text-dark')
                                .text(isMass ? 'Mass Prod' : 'Repair');
                            $sessionDiv.removeClass('d-none');
                        } else {
                            $sessionDiv.addClass('d-none');
                        }
                    });

                    if (plannedSessionType) {
                        $('input[name="session_type"]').prop('checked', false); // uncheck all first
                        $(`input[name="session_type"][value="${plannedSessionType}"]`)
                            .prop('checked', true)
                            .trigger('change');
                        $('#plan-session-badge').removeClass('d-none');
                    } else {
                        $('#plan-session-badge').addClass('d-none');
                    }

                    // Auto-select planned employees
                    if (Array.isArray(plannedEmpIds) && plannedEmpIds.length > 0) {
                        $('.employee-checkbox:checked').prop('checked', false).trigger('change');
                        selectedEmployees = [];
                        let autoSelected = 0;
                        plannedEmpIds.forEach(function(empId) {
                            const $cb = $(`.employee-checkbox[value="${empId}"]`);
                            if ($cb.length && !$cb.prop('disabled')) {
                                $cb.prop('checked', true).trigger('change');
                                autoSelected++;
                            }
                        });
                        if (autoSelected > 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Karyawan Otomatis Dipilih',
                                html: `<strong>${autoSelected}</strong> karyawan dipilih dari 📅 <strong>Timing Plan</strong>.<br><small class="text-muted">Bisa diubah manual.</small>`,
                                timer: 2500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                    }
                } else {
                    $('#project-info').addClass('d-none');
                    $('#plan-task-badge').addClass('d-none');
                    $('#plan-session-badge').addClass('d-none');
                    currentTasksByEmp = {};
                    currentSessionTypesByEmp = {};
                    $('.emp-plan-task').addClass('d-none');
                    $('.emp-plan-session').addClass('d-none');
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

                // Check if any selected employees have a paused session
                const pausedWarnings = [];
                selectedEmployees.forEach(empId => {
                    const wrapper = $(`.employee-card-wrapper[data-has-paused="true"]`).filter(
                        function() {
                            return $(this).find('.employee-checkbox').val() == empId;
                        });
                    if (wrapper.length) {
                        const jobOrder = wrapper.data('paused-job-order');
                        const duration = wrapper.data('paused-duration');
                        const empName = wrapper.find('h6').text().trim();
                        pausedWarnings.push(
                            `<li><strong>${empName}</strong> — masih ada sesi PAUSED: <em>${jobOrder}</em> (${duration})</li>`
                        );
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
                        if (result.isConfirmed) {
                            doStartSession();
                        }
                    });
                    return;
                }

                doStartSession();
            });

            function doStartSession() {
                const stepVal = $('#step-input').val();
                const globalSessionType = $('input[name="session_type"]:checked').val();

                // Build tasks and session_types per employee from plan (fallback to global)
                const tasksPayload = {};
                const sessionTypesPayload = {};
                selectedEmployees.forEach(empId => {
                    tasksPayload[empId] = currentTasksByEmp[empId] || stepVal;
                    sessionTypesPayload[empId] = currentSessionTypesByEmp[empId] || globalSessionType;
                });

                const formData = {
                    _token: $('input[name="_token"]').val(),
                    employees: selectedEmployees,
                    job_order_id: selectedJobOrder,
                    step: stepVal,
                    tasks: tasksPayload,
                    parts: $('#parts-input').val(),
                    output_qty: $('#output-qty-input').val(),
                    session_type: globalSessionType,
                    session_types: sessionTypesPayload
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
                            '<i class="bi bi-play-circle-fill me-2"></i><span id="btn-text">START WORK</span> <span id="btn-info" class="small">(Select employees & job order first)</span>'
                        );
                        updateStartButton();
                    }
                });
            }

            // Stop work handler (delegated event) - INDIVIDUAL STOP
            $(document).on('click', '.stop-work-btn', function() {
                // Check if this is a grouped session (multiple employees)
                const timingIds = $(this).data('timing-ids');
                const timingId = $(this).data('timing-id');

                // GROUPED SESSION: Show employee selection modal
                if (timingIds && Array.isArray(timingIds)) {
                    showGroupedStopModal(timingIds);
                    return;
                }

                // INDIVIDUAL SESSION: Show simple stop modal
                if (timingId) {
                    const employeeName = $(this).data('employee-name');
                    const jobOrder = $(this).data('job-order');
                    const employeeId = $(this).data('employee-id');

                    $('#stop-timing-id').val(timingId);
                    $('#stop-work-form').data('current-employee-id', employeeId);

                    // Build session info — include paused-session warning if applicable
                    let sessionInfoHtml = `
                        <div class="alert alert-info mb-0">
                            <strong>Employee:</strong> ${employeeName}<br>
                            <strong>Job Order:</strong> ${jobOrder}
                        </div>
                    `;
                    if (employeeId && frozenSessionsByEmployee[employeeId]) {
                        const frozen = frozenSessionsByEmployee[employeeId];
                        sessionInfoHtml += `
                            <div class="alert alert-warning mt-2 mb-0 py-2">
                                <i class="bi bi-pause-circle me-1"></i>
                                <strong>Perhatian:</strong> ${employeeName} masih memiliki sesi yang sedang <strong>PAUSE</strong>:<br>
                                <span class="small">Job Order: <em>${frozen.job_order_name}</em> &mdash; durasi tersimpan: ${frozen.frozen_duration}</span>
                            </div>
                        `;
                    }
                    $('#stop-session-info').html(sessionInfoHtml);

                    $('#stop-output-qty').val(1);
                    // Reset Select2 measurement to pcs default
                    const pcVal = $('#stop-measurement-type option').filter(function() {
                            return $(this).val() === 'pcs';
                        }).val() ||
                        $('#stop-measurement-type option:first').val();
                    $('#stop-measurement-type').val(pcVal).trigger('change');
                    $('#stopWorkModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Cannot identify timing session. Please refresh the page.'
                    });
                }
            });

            // Stop work form submission - INDIVIDUAL
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const stoppingEmployeeId = $('#stop-work-form').data('current-employee-id');
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

                            // Check if stopping employee has a paused session
                            const frozen = stoppingEmployeeId ? frozenSessionsByEmployee[
                                stoppingEmployeeId] : null;

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

                            // Remove the specific card and refresh both panels
                            const cardId = response.timing_id || timingId;
                            $(`#session-card-${cardId}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                            loadActiveSessions();
                            loadAvailableEmployees();
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
                        url: '{{ route('costume-timing.freeze') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: timingId
                        },
                        success: function(r) {
                            if (r.success) {
                                // Track this employee as having a frozen session
                                const card = $(
                                    `.session-card[data-session-id="${timingId}"]`);
                                const empId = card.find('.stop-work-btn').data(
                                    'employee-id');
                                const joName = card.find('.stop-work-btn').data(
                                    'job-order');
                                if (empId) {
                                    frozenSessionsByEmployee[empId] = {
                                        timing_id: timingId,
                                        job_order_name: joName || 'N/A',
                                        frozen_duration: r.frozen_duration ||
                                            '00:00:00',
                                    };
                                }
                                loadActiveSessions();
                                loadAvailableEmployees();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Paused!',
                                    text: r.message,
                                    timer: 1800,
                                    showConfirmButton: false
                                });
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
                        url: '{{ route('costume-timing.unfreeze') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: timingId
                        },
                        success: function(r) {
                            if (r.success) {
                                // Remove from frozen tracking
                                const card = $(
                                    `.session-card[data-session-id="${timingId}"]`);
                                const empId = card.find('.stop-work-btn').data(
                                    'employee-id');
                                if (empId) delete frozenSessionsByEmployee[empId];
                                loadActiveSessions();
                                loadAvailableEmployees();
                                Swal.fire({
                                    icon: 'success',
                                    title: r.auto_froze ? 'Switched!' :
                                        'Continued!',
                                    text: r.message,
                                    timer: 2500,
                                    showConfirmButton: false
                                });
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
                                    'Failed to continue.'
                            });
                        }
                    });
                });
            });
            // ─────────────────────────────────────────────────────────────────

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

            // Show modal for grouped session with employee selection
            function showGroupedStopModal(timingIds) {
                // Fetch session details for all timing IDs
                $.ajax({
                    url: '{{ route('costume-timing.get-sessions-info') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        timing_ids: timingIds
                    },
                    success: function(response) {
                        if (response.success && response.sessions) {
                            buildGroupedStopModal(response.sessions);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load employee session details.'
                        });
                    }
                });
            }

            // Build and show grouped stop modal with employee checkboxes
            function buildGroupedStopModal(sessions) {
                const firstSession = sessions[0];
                let employeeListHtml = '';

                sessions.forEach(session => {
                    employeeListHtml += `
                        <div class="form-check mb-3 p-3 border rounded">
                            <input class="form-check-input grouped-employee-check"
                                   type="checkbox"
                                   value="${session.id}"
                                   id="employee-${session.id}"
                                   checked>
                            <label class="form-check-label w-100" for="employee-${session.id}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${session.employee_name}</strong>
                                        <br><small class="text-muted">${session.employee_position || 'N/A'}</small>
                                    </div>
                                    <div class="text-end">
                                        <input type="number"
                                               class="form-control form-control-sm qty-input-${session.id}"
                                               placeholder="Qty"
                                               value="1"
                                               min="0"
                                               step="0.01"
                                               style="width: 100px;"
                                               data-timing-id="${session.id}">
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                });

                const unitOptionsHtml = UNITS_OPTIONS.map(u =>
                    `<option value="${u.value}"${u.value === 'pcs' ? ' selected' : ''}>${u.label}</option>`
                ).join('');

                const modalHtml = `
                    <div class="modal fade" id="groupedStopModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Stop Work - Select Employees</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info mb-3">
                                        <strong>Job Order:</strong> ${firstSession.job_order_name || 'N/A'}<br>
                                        <strong>Project:</strong> ${firstSession.project_name || 'N/A'}
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Measurement Type</label>
                                        <select class="form-select" id="grouped-measurement-type">
                                            ${unitOptionsHtml}
                                        </select>
                                    </div>

                                    <p class="mb-2"><strong>Select employees to stop and enter their output quantity:</strong></p>
                                    ${employeeListHtml}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="confirm-grouped-stop">
                                        <i class="bi bi-stop-circle me-1"></i>Stop Selected Sessions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if present
                $('#groupedStopModal').remove();

                // Append and show new modal
                $('body').append(modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('groupedStopModal'));
                modal.show();

                // Init Select2 on the dynamically-inserted measurement select
                $('#grouped-measurement-type').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#groupedStopModal'),
                    minimumResultsForSearch: Infinity,
                    width: '100%',
                });

                // Handle grouped stop confirmation
                $('#confirm-grouped-stop').off('click').on('click', function() {
                    const selectedSessions = [];
                    const measurementType = $('#grouped-measurement-type').val();

                    $('.grouped-employee-check:checked').each(function() {
                        const timingId = $(this).val();
                        const qty = parseFloat($(`.qty-input-${timingId}`).val()) || 0;

                        if (qty > 0) {
                            selectedSessions.push({
                                timing_id: timingId,
                                output_qty: qty,
                                measurement_type: measurementType
                            });
                        }
                    });

                    if (selectedSessions.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Selection',
                            text: 'Please select at least one employee with valid quantity.'
                        });
                        return;
                    }

                    // Submit multiple stop requests
                    stopMultipleSessions(selectedSessions, modal);
                });
            }

            // Stop multiple sessions individually
            function stopMultipleSessions(sessions, modal) {
                const totalSessions = sessions.length;
                let completedCount = 0;
                let failedCount = 0;

                Swal.fire({
                    title: 'Processing...',
                    text: `Stopping ${totalSessions} session(s)...`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Process each session stop request
                const promises = sessions.map(session => {
                    return $.ajax({
                        url: '{{ route('costume-timing.stop') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            timing_id: session.timing_id,
                            output_qty: session.output_qty,
                            measurement_type: session.measurement_type
                        }
                    }).then(
                        response => {
                            completedCount++;
                            return {
                                success: true,
                                timing_id: session.timing_id
                            };
                        },
                        xhr => {
                            failedCount++;
                            return {
                                success: false,
                                timing_id: session.timing_id
                            };
                        }
                    );
                });

                // Wait for all requests to complete
                Promise.all(promises).then(results => {
                    modal.hide();
                    $('#groupedStopModal').remove();

                    Swal.fire({
                        icon: completedCount > 0 ? 'success' : 'error',
                        title: 'Batch Stop Complete',
                        html: `
                            <p>Completed: <strong class="text-success">${completedCount}</strong></p>
                            ${failedCount > 0 ? `<p>Failed: <strong class="text-danger">${failedCount}</strong></p>` : ''}
                        `,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload page after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
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

                // Group sessions by job_order_id AND start_time (same batch = same start time)
                // This ensures only sessions started together in ONE batch are grouped
                const grouped = sessions.reduce((acc, session) => {
                    const groupKey = `${session.job_order_id}_${session.start_time}`; // BATCH KEY
                    if (!acc[groupKey]) {
                        acc[groupKey] = [];
                    }
                    acc[groupKey].push(session);
                    return acc;
                }, {});

                let html = '';
                for (const [groupKey, sessionsGroup] of Object.entries(grouped)) {
                    // If only 1 employee in group, show INDIVIDUAL card
                    if (sessionsGroup.length === 1) {
                        const timing = sessionsGroup[0];
                        const photoHtml = timing.employee_photo ?
                            `<img src="/storage/${timing.employee_photo}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">` :
                            `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                 <i class="bi bi-person text-white"></i>
                               </div>`;

                        const isFrozen = timing.is_frozen || false;
                        const isAutoBreak = timing.auto_break_paused || false;
                        const cardBorder = isFrozen ? 'border-warning border-2' : '';
                        const statusBadge = isFrozen ?
                            `<span class="badge bg-warning text-dark me-1"><i class="bi bi-pause-circle"></i> PAUSED${isAutoBreak ? ' (BREAK)' : ''}</span>` :
                            `<span class="badge bg-success me-1">RUNNING</span>`;
                        const durationHtml = isFrozen ?
                            `<span class="fs-5 fw-bold text-warning">${timing.frozen_duration || '00:00:00'}</span>` :
                            `<span class="duration-display fs-5 fw-bold text-success" data-start-time="${timing.start_time}" data-session-id="${timing.id}">${timing.duration || '00:00:00'}</span>`;
                        const actionBtns = isFrozen ?
                            `<div class="d-flex gap-2">
                                   <button class="btn btn-success btn-sm flex-grow-1 unfreeze-work-btn"
                                       data-timing-id="${timing.id}"
                                       data-employee-name="${timing.employee_name}">
                                       <i class="bi bi-play-circle me-1"></i>Continue
                                   </button>
                                   <button class="btn btn-danger btn-sm stop-work-btn"
                                       data-timing-id="${timing.id}"
                                       data-employee-id="${timing.employee_id}"
                                       data-employee-name="${timing.employee_name}"
                                       data-job-order="${timing.job_order_name}">
                                       <i class="bi bi-stop-circle me-1"></i>Stop
                                   </button>
                               </div>` :
                            `<div class="d-flex gap-2">
                                   <button class="btn btn-warning btn-sm freeze-work-btn flex-shrink-0"
                                       data-timing-id="${timing.id}"
                                       data-employee-name="${timing.employee_name}">
                                       <i class="bi bi-pause-circle me-1"></i>Pause
                                   </button>
                                   <button class="btn btn-danger btn-sm flex-grow-1 stop-work-btn"
                                       data-timing-id="${timing.id}"
                                       data-employee-id="${timing.employee_id}"
                                       data-employee-name="${timing.employee_name}"
                                       data-job-order="${timing.job_order_name}">
                                       <i class="bi bi-stop-circle me-1"></i>STOP & ENTER QTY
                                   </button>
                               </div>`;

                        html += `
                            <div class="card session-card mb-3 ${cardBorder}" id="session-card-${timing.id}" data-session-id="${timing.id}">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        ${photoHtml}
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                ${statusBadge}
                                                ${timing.employee_name}
                                            </h6>
                                            <small class="text-muted">${timing.employee_position || 'N/A'}</small>
                                        </div>
                                        ${durationHtml}
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
                                                <small class="text-muted d-flex justify-content-between">
                                                    <span><i class="bi bi-clock"></i> Started: ${timing.start_time}</span>
                                                    <span><i class="bi bi-calendar-x"></i> Deadline: ${timing.job_order_deadline || '—'}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    ${actionBtns}
                                </div>
                            </div>
                        `;
                    } else {
                        // Multiple employees in SAME BATCH - show GROUPED card
                        const firstSession = sessionsGroup[0];
                        const timingIds = sessionsGroup.map(s => s.id);
                        const employeeNames = sessionsGroup.map(s => s.employee_name).join(', ');
                        const jobOrderName = firstSession.job_order_name || firstSession.job_order_id;
                        const projectName = firstSession.project_name || 'N/A';

                        html += `
                    <div class="card session-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <span class="badge bg-success">RUNNING (BATCH)</span>
                                        ${jobOrderName}
                                    </h6>
                                    <small class="text-muted">${projectName}</small>
                                    <div class="mt-1">
                                        <span class="badge bg-danger"><i class="bi bi-calendar-x"></i> Deadline: 30 Mar 2026</span>
                                    </div>
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
                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK (${sessionsGroup.length} employees)
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                    }
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
                                        <small class="text-muted d-flex justify-content-between">
                                            <span><i class="bi bi-clock"></i> Started: ${timing.start_time}</span>
                                            <span><i class="bi bi-calendar-x"></i> Deadline: ${timing.job_order_deadline || '—'}</span>
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

            // ── Available-employees helpers ───────────────────────────────────
            function loadAvailableEmployees() {
                $.ajax({
                    url: '{{ route('costume-timing.available-employees') }}',
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
                    $('#employee-cards').html(
                        '<div class="alert alert-info">No available employees at this time.</div>');
                    return;
                }

                let html = '<div class="row g-2">';
                employees.forEach(function(emp) {
                    const frozen = emp.frozen_info;
                    const skillsetIds = emp.skillset_ids && emp.skillset_ids.length ? ',' + emp.skillset_ids
                        .join(',') + ',' : ',';
                    const borderClass = frozen ? 'border-warning' : '';
                    const pausedAttrs = frozen ?
                        `data-has-paused="true" data-paused-job-order="${frozen.job_order_name}" data-paused-duration="${frozen.frozen_duration}" data-paused-timing-id="${frozen.timing_id}"` :
                        '';
                    const pausedBadge = frozen ?
                        `<span class="position-absolute top-0 start-0 m-1 badge bg-warning text-dark" style="font-size:0.6rem;"><i class="bi bi-pause-circle"></i> PAUSED</span>` :
                        '';
                    const pausedDur = frozen ?
                        `<div class="text-warning" style="font-size:0.65rem;"><i class="bi bi-clock-history"></i> ${frozen.frozen_duration}</div>` :
                        '';
                    const photoHtml = emp.photo ?
                        `<img src="/storage/${emp.photo}" class="rounded-circle mb-1 border" width="44" height="44" style="object-fit:cover;">` :
                        `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-1" style="width:44px;height:44px;"><i class="bi bi-person text-white"></i></div>`;

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
