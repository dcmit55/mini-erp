@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-robot gradient-icon me-2" style="font-size: 1.8rem;"></i>
                <h2 class="mb-0" style="font-size:1.5rem;">Animatronics Timing - Production Tracking</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2">
                <a href="{{ route('animatronics-timing.monitor') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-tv me-1"></i> Animatronics Monitor
                </a>
                <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-cut me-1"></i> Costume Timing
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
                    <div class="card-header bg-gradient-animatronics text-white">
                        <h5 class="mb-0"><i class="bi bi-play-circle me-2"></i>Start Animatronics Work Session</h5>
                    </div>
                    <div class="card-body">
                        <form id="animatronics-timing-form">
                            @csrf

                            <!-- STEP 1: Select Tracking Mode -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-danger me-2">1</span>Select Tracking Mode
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-check form-check-inline mode-card p-3 border rounded w-100"
                                            style="cursor: pointer;">
                                            <input class="form-check-input" type="radio" name="tracking_mode"
                                                id="mode-timer" value="timer" checked>
                                            <label class="form-check-label w-100" for="mode-timer" style="cursor: pointer;">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-stopwatch fs-3 me-2 text-primary"></i>
                                                    <div>
                                                        <strong class="d-block">Timer Mode</strong>
                                                        <small class="text-muted">For support & small tasks<br>Track by
                                                            quantity/units</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-inline mode-card p-3 border rounded w-100"
                                            style="cursor: pointer;">
                                            <input class="form-check-input" type="radio" name="tracking_mode"
                                                id="mode-progress" value="progress">
                                            <label class="form-check-label w-100" for="mode-progress"
                                                style="cursor: pointer;">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-graph-up fs-3 me-2 text-success"></i>
                                                    <div>
                                                        <strong class="d-block">Progress Mode</strong>
                                                        <small class="text-muted">For big projects<br>Track by percentage
                                                            progress</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: Select Employees -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-danger me-2">2</span>Select Animatronics Employees
                                </label>

                                <!-- Filter by Position -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-10">
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
                                                            class="rounded-circle mb-2 border" width="50"
                                                            height="50" style="object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-2"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="bi bi-person text-white fs-4"></i>
                                                        </div>
                                                    @endif
                                                    <h6 class="mb-1 small">{{ $employee->name }}</h6>
                                                    <small class="text-muted d-block">{{ $employee->position }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                No active animatronics employees found.
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

                            <!-- STEP 3: Select Job Order -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-danger me-2">3</span>Select Job Order
                                </label>
                                <select class="form-select select2" id="job-order-select" name="job_order_id" required>
                                    <option value="">Choose Animatronics Job Order...</option>
                                    @foreach ($jobOrders as $jo)
                                        <option value="{{ $jo->id }}" data-project-id="{{ $jo->project_id }}"
                                            data-project-name="{{ $jo->project->name ?? 'N/A' }}"
                                            data-job-order-name="{{ $jo->name }}">
                                            {{ $jo->name }} ({{ $jo->project->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Auto-filled Project Info -->
                                <div id="project-info" class="mt-3 p-3 bg-light rounded d-none">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <small class="text-muted d-block">Project:</small>
                                            <strong id="project-name-display">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 4: Work Details -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-danger me-2">4</span>Work Details
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Step/Process <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="step-input" name="step"
                                            placeholder="e.g., Sculpting, Assembly, Programming" required>
                                        <small class="text-muted">Enter the current work step/process</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Part/Component</label>
                                        <input type="text" class="form-control" id="parts-input" name="parts"
                                            placeholder="e.g., Head, Arms, Full Figure">
                                        <small class="text-muted">Enter the part being worked on (optional)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 5: Animatronics-Specific Fields -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-danger me-2">5</span>Animatronics Details (Optional)
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small">Remark / Additional Notes</label>
                                        <textarea class="form-control" rows="3" name="department_specific_data[remark]"
                                            placeholder="Enter any additional notes, specifications, or details about this work session..."></textarea>
                                        <small class="text-muted">Optional: Add any relevant information (motor types,
                                            voltage, control systems, materials used, etc.)</small>
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
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Active Animatronics Sessions</h5>
                    </div>
                    <div class="card-body" id="active-sessions-container" style="max-height: 600px; overflow-y: auto;">
                        @include('timing.animatronics.partials.active-sessions', [
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
                <form id="stop-work-form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div id="stop-session-info" class="alert alert-info mb-3"></div>

                        <!-- Photo Upload (OPTIONAL - tidak required) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Upload Result Photo
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="file" class="form-control" id="stop-photo" name="photo"
                                accept="image/jpeg,image/jpg,image/png">
                            <small class="text-muted">Optional: Upload photo of completed work (JPG, PNG, max 5MB)</small>

                            <!-- Photo Preview -->
                            <div id="photo-preview" class="mt-2 d-none">
                                <img id="preview-image" src="" alt="Preview" class="img-thumbnail"
                                    style="max-height: 200px;">
                            </div>
                        </div>

                        <!-- Measurement Type Selection (untuk timer mode ONLY) -->
                        <div class="mb-3" id="measurement-type-container">
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

                        <!-- Stage Selection (ONLY for progress mode) -->
                        <div class="mb-3 d-none" id="stage-selection-container">
                            <label class="form-label fw-bold">
                                Select Stage Completed
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" id="stop-stage" name="stage">
                                <option value="">Choose stage...</option>
                                <option value="1">Stage 1 (10% Progress)</option>
                                <option value="2">Stage 2 (20% Progress)</option>
                                <option value="3">Stage 3 (30% Progress)</option>
                                <option value="4">Stage 4 (40% Progress)</option>
                                <option value="5">Stage 5 (50% Progress)</option>
                                <option value="6">Stage 6 (60% Progress)</option>
                                <option value="7">Stage 7 (70% Progress)</option>
                                <option value="8">Stage 8 (80% Progress)</option>
                                <option value="9">Stage 9 (90% Progress)</option>
                                <option value="10">Stage 10 (100% Complete)</option>
                            </select>
                            <small class="text-muted">Each stage represents 10% progress increment. Select the stage you've
                                just completed.</small>
                        </div>

                        <!-- Output Quantity (for timer mode ONLY) -->
                        <div class="mb-3" id="output-qty-container">
                            <label class="form-label fw-bold">
                                <span id="output-label">Output Quantity</span>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control form-control-lg" id="stop-output-qty"
                                name="output_qty" min="0" step="0.1" value="1" required>
                            <small class="text-muted" id="output-help">Enter the total quantity produced during this
                                session</small>
                        </div>

                        <!-- Progress tracking info (for progress mode) -->
                        <div class="mb-3 d-none" id="progress-info">
                            <div class="alert alert-success mb-0">
                                <strong>Previous Progress:</strong> <span id="previous-progress">0</span>%<br>
                                <strong>Will be updated to:</strong> <span id="current-progress"
                                    class="text-primary fw-bold">0</span>%
                            </div>
                        </div>

                        <input type="hidden" id="stop-timing-id" name="timing_id">
                        <input type="hidden" id="stop-tracking-mode" name="tracking_mode" value="timer">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="stop-submit-btn">
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

        .bg-gradient-animatronics {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            border-color: #ff6b6b !important;
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(238, 90, 111, 0.1) 100%);
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.3);
        }

        .mode-card {
            transition: all 0.3s ease;
        }

        .mode-card:has(input:checked) {
            border-color: #ff6b6b !important;
            background: rgba(255, 107, 107, 0.1);
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

        .progress-badge {
            font-size: 0.9rem;
            padding: 0.3rem 0.6rem;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let selectedEmployees = [];
            let selectedJobOrder = null;
            let trackingMode = 'timer';

            // Tracking mode change handler
            $('input[name="tracking_mode"]').on('change', function() {
                trackingMode = $(this).val();
            });

            // Initialize Select2 for job order
            $('#job-order-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose Animatronics Job Order...',
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for position filter
            $('#filter-position').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            });

            // Filter employees by position
            $('#filter-position').on('change', function() {
                filterEmployees();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-position').val('').trigger('change');
                filterEmployees();
            });

            // Filter function
            function filterEmployees() {
                const posFilter = $('#filter-position').val();
                let visibleCount = 0;

                $('.employee-card-wrapper').each(function() {
                    const position = $(this).data('position');

                    let showCard = true;

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
                if (posFilter) {
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

            // Initialize Select2 for filters
            $('#filter-position').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%',
                placeholder: 'All Positions'
            });

            // Initialize Select2 for step and parts
            $('#step-select, #parts-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });

            // Tracking mode selection
            $('input[name="tracking_mode"]').on('change', function() {
                trackingMode = $(this).val();
                updateModeUI();
            });

            function updateModeUI() {
                if (trackingMode === 'progress') {
                    $('#btn-info').text('(Progress tracking mode selected)');
                } else {
                    $('#btn-info').text('(Timer mode selected)');
                }
            }

            // Filter employees by position
            $('#filter-position').on('change', function() {
                filterEmployees();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-position').val('').trigger('change');
                filterEmployees();
            });

            // Filter function
            function filterEmployees() {
                const posFilter = $('#filter-position').val();
                let visibleCount = 0;

                $('.employee-card-wrapper').each(function() {
                    const position = $(this).data('position');
                    let showCard = true;

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
                if (posFilter) {
                    $('#filtered-count').html(`<span class="badge bg-info">${visibleCount} shown</span>`);
                } else {
                    $('#filtered-count').html('');
                }
            }

            // Job order selection handler
            $('#job-order-select').on('change', function() {
                selectedJobOrder = $(this).val();

                if (selectedJobOrder) {
                    const selectedOption = $(this).find('option:selected');
                    const projectName = selectedOption.data('project-name');

                    $('#project-name-display').text(projectName);
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
            $('#animatronics-timing-form').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    _token: $('input[name="_token"]').val(),
                    employees: selectedEmployees,
                    job_order_id: selectedJobOrder,
                    tracking_mode: trackingMode,
                    step: $('#step-input').val(), // Changed from step-select to step-input
                    parts: $('#parts-input').val(), // Changed from parts-select to parts-input
                    department_specific_data: {
                        remark: $('textarea[name="department_specific_data[remark]"]')
                        .val() // Changed to remark only
                    }
                };

                // Disable button and show loading
                const btn = $('#start-btn');
                btn.prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-2"></i>Starting...');

                $.ajax({
                    url: '{{ route('animatronics-timing.start') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Work Started!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });

                            // Reset form
                            $('#animatronics-timing-form')[0].reset();
                            $('.employee-card').removeClass('selected');
                            $('.employee-checkbox').prop('checked', false);
                            $('#project-info').addClass('d-none');
                            $('#job-order-select').val('').trigger('change');
                            $('#step-input').val(''); // Changed from step-select
                            $('#parts-input').val(''); // Changed from parts-select
                            selectedEmployees = [];
                            selectedJobOrder = null;
                            trackingMode = 'timer';
                            $('#mode-timer').prop('checked', true);

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

            // Photo preview handler
            $('#stop-photo').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) { // 5MB
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Photo size must be less than 5MB'
                        });
                        $(this).val('');
                        $('#photo-preview').addClass('d-none');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview-image').attr('src', e.target.result);
                        $('#photo-preview').removeClass('d-none');
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#photo-preview').addClass('d-none');
                }
            });

            // Stop work handler (delegated event) - INDIVIDUAL STOP
            $(document).on('click', '.stop-work-btn', function() {
                const $btn = $(this);

                // DEBOUNCE PROTECTION: Prevent double-click
                if ($btn.prop('disabled') || $btn.hasClass('processing')) {
                    console.warn('Stop button already processing, ignoring click');
                    return;
                }

                const timingId = $btn.data('timing-id');
                const employeeName = $btn.data('employee-name') || 'Unknown';
                const jobOrder = $btn.data('job-order') || 'Unknown';
                const mode = $btn.data('tracking-mode') || 'timer';
                const previousProgress = parseFloat($btn.data('previous-progress')) || 0;

                console.log('Stop button clicked:', {
                    timingId,
                    employeeName,
                    jobOrder,
                    mode,
                    previousProgress
                });

                // Validate timing ID
                if (!timingId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Invalid session data. Please refresh the page.',
                        footer: '<small>Debug: timing-id attribute is missing from button</small>'
                    });
                    console.error('Missing timing-id. Button data:', $btn.data());
                    return;
                }

                // Mark button as processing
                $btn.addClass('processing').prop('disabled', true);

                // Enable button again after modal closes (cleanup)
                $('#stopWorkModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    $btn.removeClass('processing').prop('disabled', false);
                });

                // Reset form and preview
                $('#stop-work-form')[0].reset();
                $('#photo-preview').addClass('d-none');
                $('#stop-submit-btn').prop('disabled', false).html(
                    '<i class="bi bi-stop-circle me-1"></i>Stop & Save');

                $('#stop-timing-id').val(timingId);
                $('#stop-tracking-mode').val(mode);
                $('#stop-session-info').html(`
                    <strong>Employee:</strong> ${employeeName}<br>
                    <strong>Job Order:</strong> ${jobOrder}<br>
                    <strong>Mode:</strong> ${mode.toUpperCase()}
                `);
                $('#stop-output-qty').val(1).focus();

                // Update labels based on mode
                if (mode === 'progress') {
                    // Progress mode: show stage selection, hide measurement type and output qty
                    $('#stage-selection-container').removeClass('d-none');
                    $('#output-qty-container').addClass('d-none');
                    $('#measurement-type-container').addClass('d-none');
                    $('#progress-info').removeClass('d-none');

                    $('#previous-progress').text(previousProgress);
                    $('#current-progress').text(previousProgress);

                    // Update progress preview when stage changes
                    $('#stop-stage').off('change').on('change', function() {
                        const stage = parseInt($(this).val()) || 0;
                        const stageProgress = stage * 10; // Each stage = 10%
                        const newProgress = Math.min(100, previousProgress + stageProgress);
                        $('#current-progress').text(newProgress);
                    });
                } else {
                    // Timer mode: show output qty and measurement type, hide stage
                    $('#stage-selection-container').addClass('d-none');
                    $('#output-qty-container').removeClass('d-none');
                    $('#measurement-type-container').removeClass('d-none');
                    $('#progress-info').addClass('d-none');

                    $('#output-label').text('Output Quantity');
                    $('#output-help').text('Enter the total quantity produced');
                    $('#stop-output-qty').removeAttr('max').attr('step', 1);
                }

                $('#stopWorkModal').modal('show');
            });

            // Stop work form submission - INDIVIDUAL
            $('#stop-work-form').on('submit', function(e) {
                e.preventDefault();

                const timingId = $('#stop-timing-id').val();
                const trackingMode = $('#stop-tracking-mode').val();
                const photoFile = $('#stop-photo')[0].files[0];

                let outputQty;
                let measurementType;
                let stage = null;

                // Get values based on tracking mode
                if (trackingMode === 'progress') {
                    stage = parseInt($('#stop-stage').val());
                    if (!stage || stage < 1 || stage > 10) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Stage Required',
                            text: 'Please select a stage (1-10)'
                        });
                        return;
                    }
                    outputQty = stage * 10; // Convert stage to percentage
                    measurementType = 'percentage';
                } else {
                    outputQty = parseFloat($('#stop-output-qty').val());
                    measurementType = $('#stop-measurement-type').val();

                    if (!outputQty || outputQty < 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Quantity',
                            text: 'Please enter a valid output quantity'
                        });
                        return;
                    }
                }

                // Create FormData for file upload
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('timing_id', timingId);
                formData.append('output_qty', outputQty);
                formData.append('measurement_type', measurementType);

                // Add stage if progress mode
                if (stage) {
                    formData.append('stage', stage);
                }

                // Append photo hanya jika ada
                if (photoFile) {
                    formData.append('photo', photoFile);
                }

                // Disable submit button
                const submitBtn = $('#stop-submit-btn');
                submitBtn.prop('disabled', true).html(
                    '<i class="spinner-border spinner-border-sm me-2"></i>Saving...');

                $.ajax({
                    url: '{{ route('animatronics-timing.stop') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-stop-circle me-1"></i>Stop & Save');

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
                    url: '{{ route('animatronics-timing.active-sessions') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            updateActiveSessionsDisplay(response.sessions);
                        }
                    }
                });
            }

            // Update active sessions display (INDIVIDUAL cards, NOT grouped)
            function updateActiveSessionsDisplay(sessions) {
                const container = $('#active-sessions-container');

                if (sessions.length === 0) {
                    container.html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">No active animatronics sessions</p>
                    <small>Start a new session to track production time</small>
                </div>
            `);
                    return;
                }

                // Render INDIVIDUAL session cards (NOT grouped)
                let html = '';
                sessions.forEach(session => {
                    const photoHtml = session.employee_photo ?
                        `<img src="/storage/${session.employee_photo}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">` :
                        `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                             <i class="bi bi-person text-white"></i>
                           </div>`;

                    const trackingMode = session.tracking_mode || 'timer';
                    const previousProgress = session.previous_progress || 0;

                    const modeBadge = trackingMode === 'progress' ?
                        '<span class="badge bg-warning text-dark ms-1">PROGRESS</span>' :
                        '<span class="badge bg-info ms-1">TIMER</span>';

                    html += `
                        <div class="card session-card mb-3" id="session-card-${session.id}" data-session-id="${session.id}">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    ${photoHtml}
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <span class="badge bg-success me-1">RUNNING</span>
                                            ${session.employee_name || 'Unknown'}
                                            ${modeBadge}
                                        </h6>
                                        <small class="text-muted">${session.employee_position || 'N/A'}</small>
                                    </div>
                                    <span class="duration-display fs-5 fw-bold text-success"
                                          data-start-time="${session.start_time}"
                                          data-session-id="${session.id}">
                                        ${session.duration || '00:00:00'}
                                    </span>
                                </div>
                                <div class="border-top pt-2 mb-2">
                                    <div class="row g-2 small">
                                        <div class="col-12">
                                            <strong>Job Order:</strong> ${session.job_order_name || 'N/A'}<br>
                                            <strong>Project:</strong> ${session.project_name || 'N/A'}
                                        </div>
                                        <div class="col-6">
                                            <strong>Step:</strong> ${session.step || 'N/A'}
                                        </div>
                                        <div class="col-6">
                                            <strong>Part:</strong> ${session.parts || 'N/A'}
                                        </div>
                                        ${trackingMode === 'progress' ? `
                                                    <div class="col-12">
                                                        <strong>Previous Progress:</strong> ${previousProgress}%
                                                    </div>
                                                    ` : ''}
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> Started: ${session.start_time}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-danger btn-sm stop-work-btn"
                                            data-timing-id="${session.id}"
                                            data-employee-name="${session.employee_name || 'Unknown'}"
                                            data-job-order="${session.job_order_name || 'Unknown'}"
                                            data-tracking-mode="${trackingMode}"
                                            data-previous-progress="${previousProgress}">
                                        <i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER ${trackingMode === 'progress' ? 'PROGRESS' : 'QTY'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.html(html);
                startDurationTimers();
            }

            // Add individual session card (Real-time display for Animatronics)
            function addSessionCard(timing) {
                const photoHtml = timing.employee_photo ?
                    `<img src="/storage/${timing.employee_photo}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">` :
                    `<div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                         <i class="bi bi-person text-white"></i>
                       </div>`;

                const departmentData = timing.department_data || {};
                const trackingMode = departmentData.tracking_mode || 'timer';
                const previousProgress = departmentData.previous_progress || 0;

                const modeBadge = trackingMode === 'progress' ?
                    '<span class="badge bg-warning text-dark ms-1">PROGRESS MODE</span>' :
                    '<span class="badge bg-info ms-1">TIMER MODE</span>';

                const cardHtml = `
                    <div class="card session-card mb-3" id="session-card-${timing.id}" data-session-id="${timing.id}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                ${photoHtml}
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <span class="badge bg-success me-1">RUNNING</span>
                                        ${timing.employee_name || 'Unknown'}
                                        ${modeBadge}
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
                                        <strong>Job Order:</strong> ${timing.job_order_name || 'N/A'}<br>
                                        <strong>Project:</strong> ${timing.project_name || 'N/A'}
                                    </div>
                                    <div class="col-6">
                                        <strong>Step:</strong> ${timing.step || 'N/A'}
                                    </div>
                                    <div class="col-6">
                                        <strong>Part:</strong> ${timing.parts || 'N/A'}
                                    </div>
                                    ${trackingMode === 'progress' ? `
                                                <div class="col-12">
                                                    <strong>Previous Progress:</strong> ${previousProgress}%
                                                </div>
                                                ` : ''}
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
                                        data-employee-name="${timing.employee_name || 'Unknown'}"
                                        data-job-order="${timing.job_order_name || 'Unknown'}"
                                        data-tracking-mode="${trackingMode}"
                                        data-previous-progress="${previousProgress}">
                                    <i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER ${trackingMode === 'progress' ? 'PROGRESS' : 'QTY'}
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

            // Calculate duration
            function calculateDuration(startTime) {
                try {
                    const today = new Date();
                    const [hours, minutes, seconds] = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes,
                        seconds);
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

            // Auto-refresh active sessions every 30 seconds
            setInterval(loadActiveSessions, 30000);

            // Start duration timers on page load
            startDurationTimers();
        });
    </script>
@endsection
