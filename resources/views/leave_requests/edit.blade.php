@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="mb-4 fw-bold">Edit Leave Request</h2>
                <form method="POST" action="{{ route('leave_requests.update', $leave->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Name</label>
                            <select name="employee_id" id="employee_id"
                                class="form-select select2 border border-dark bg-white" required>
                                <option value="">Select</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" data-department="{{ $emp->department->name ?? '' }}"
                                        data-position="{{ $emp->position ?? '' }}"
                                        data-hiredate="{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d-m-Y') : '' }}"
                                        {{ old('employee_id', $leave->employee_id) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Department</label>
                            <input type="text" id="department"
                                class="form-control form-control-lg border border-dark bg-white"
                                value="{{ old('department', $leave->employee->department->name ?? '') }}" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Position</label>
                            <input type="text" id="position"
                                class="form-control form-control-lg border border-dark bg-white"
                                value="{{ old('position', $leave->employee->position ?? '') }}" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">Hire Date</label>
                            <input type="text" id="hire_date"
                                class="form-control form-control-lg border border-dark bg-white"
                                value="{{ old('hire_date', isset($leave->employee->hire_date) ? \Carbon\Carbon::parse($leave->employee->hire_date)->format('d-m-Y') : '') }}"
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">From Date</label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-control form-control-lg border border-dark bg-white"
                                value="{{ old('start_date', $leave->start_date ? $leave->start_date->format('Y-m-d') : '') }}"
                                required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark">To Date</label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-control form-control-lg border border-dark bg-white"
                                value="{{ old('end_date', $leave->end_date ? $leave->end_date->format('Y-m-d') : '') }}"
                                required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-dark d-flex align-items-center">
                                Leave Duration
                                <span class="ms-1" data-bs-toggle="tooltip" title="Can use 0.5 for half day"
                                    style="cursor: pointer;">
                                    <i class="bi bi-info-circle text-muted"></i>
                                </span>
                            </label>
                            <div class="input-group">
                                <input type="number" name="duration" id="duration"
                                    class="form-control form-control-lg border border-dark bg-white" min="0.5"
                                    max="999.99" step="0.5"
                                    value="{{ old('duration', number_format($leave->duration, 2, '.', '')) }}" required
                                    placeholder="1 or 0.5">
                                <span class="input-group-text">days</span>
                            </div>
                            <small class="text-muted" id="leave-balance-info"></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-dark">Reason</label>
                            <textarea name="reason" class="form-control form-control-lg border border-dark bg-white" rows="5">{{ old('reason', $leave->reason) }}</textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-dark">Leave Type</label>
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                @foreach ($leaveTypes as $type)
                                    <div class="col">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="type"
                                                id="type_{{ $type }}" value="{{ $type }}"
                                                {{ old('type', $leave->type) == $type ? 'checked' : '' }} required>
                                            <label class="form-check-label fw-bold" for="type_{{ $type }}">
                                                {{ $leaveTypeLabels[$type] ?? $type }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('leave_requests.index') }}" class="btn px-4 py-2"
                            style="background:#ff2222;color:#fff;font-weight:bold;">Cancel</a>
                        <button type="submit" class="btn px-4 py-2"
                            style="background:#33e133;color:#fff;font-weight:bold;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .form-label {
            font-size: 1.1rem;
        }

        .form-control-lg {
            font-size: 1.1rem;
        }

        /* RADIO BUTTON CUSTOM */
        .form-check-input[type="radio"] {
            width: 1em;
            height: 1em;
            border: 0.5px solid #000000ff !important;
            background-color: #fff;
            box-shadow: 0 0 0 2px #000000ff;
        }

        .form-check-input[type="radio"]:checked {
            background-color: #000000ff !important;
            border-color: #000000ff !important;
            box-shadow: 0 0 0 3px #000000ff;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('#employee_id').select2({
                width: '100%',
                placeholder: 'Select',
                allowClear: true,
                theme: 'bootstrap-5'
            });

            // Set employee info on select
            $('#employee_id').on('change', function() {
                const selected = this.options[this.selectedIndex];
                $('#department').val(selected.getAttribute('data-department') || '');
                $('#position').val(selected.getAttribute('data-position') || '');
                $('#hire_date').val(selected.getAttribute('data-hiredate') || '');
            });

            // Initialize tooltips
            $(function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });

            // Calculate duration - Support 0.5 day increments
            $('#start_date, #end_date').on('change', function() {
                let start = $('#start_date').val();
                let end = $('#end_date').val();

                if (start && end) {
                    let d1 = new Date(start);
                    let d2 = new Date(end);
                    let diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;

                    if (diff > 0) {
                        // Set full days
                        $('#duration').val(diff);

                        // Show info about half-day option
                        const infoElement = $('#leave-balance-info');
                        if (diff === 1) {
                            infoElement.html(
                                '<i class="bi bi-lightbulb text-info"></i> Tip: You can change to 0.5 for half day'
                            );
                        }
                    } else {
                        $('#duration').val('');
                        if (diff < 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Date Range',
                                text: 'End date must be equal to or after start date',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    }
                } else {
                    $('#duration').val('');
                }
            });

            // Validate duration input - support decimal
            $('#duration').on('input', function() {
                let value = parseFloat($(this).val());

                // Validate decimal places (max 2 decimal places)
                if ($(this).val().includes('.')) {
                    let parts = $(this).val().split('.');
                    if (parts[1].length > 2) {
                        $(this).val(parseFloat($(this).val()).toFixed(2));
                    }
                }

                // Check against leave balance for Annual Leave
                const employeeId = $('#employee_id').val();
                const leaveType = $('input[name="type"]:checked').val();
                const infoElement = $('#leave-balance-info');

                if (employeeId && leaveType === 'ANNUAL' && value) {
                    $.ajax({
                        url: `/employees/${employeeId}/leave-balance`,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                const balance = parseFloat(response.balance);

                                if (value > balance) {
                                    $('#duration').addClass('is-invalid');
                                    infoElement.html(
                                        `<i class="bi bi-exclamation-triangle text-danger"></i> Insufficient balance! Available: <strong>${balance} days</strong>`
                                    );
                                } else {
                                    $('#duration').removeClass('is-invalid');
                                    infoElement.html(
                                        `<i class="bi bi-info-circle"></i> Available balance: <strong>${balance} days</strong>`
                                    );
                                }
                            }
                        }
                    });
                }
            });

            // Show available leave balance for Annual Leave
            $('#employee_id, input[name="type"]').on('change', function() {
                const employeeId = $('#employee_id').val();
                const leaveType = $('input[name="type"]:checked').val();
                const infoElement = $('#leave-balance-info');

                if (employeeId && leaveType === 'ANNUAL') {
                    // Get employee data
                    const selectedOption = $('#employee_id option:selected');
                    const employeeName = selectedOption.text();

                    // Fetch employee leave balance
                    $.ajax({
                        url: `/employees/${employeeId}/leave-balance`,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                infoElement.html(
                                    `<i class="bi bi-info-circle"></i> Available balance: <strong>${response.balance} days</strong>`
                                );

                                // Validate duration input
                                $('#duration').attr('max', response.balance);
                                $('#duration').on('input', function() {
                                    const duration = parseFloat($(this).val());
                                    if (duration > response.balance) {
                                        $(this).addClass('is-invalid');
                                        infoElement.html(
                                            `<i class="bi bi-exclamation-triangle text-danger"></i> Insufficient balance! Available: <strong>${response.balance} days</strong>`
                                        );
                                    } else {
                                        $(this).removeClass('is-invalid');
                                        infoElement.html(
                                            `<i class="bi bi-info-circle"></i> Available balance: <strong>${response.balance} days</strong>`
                                        );
                                    }
                                });
                            }
                        },
                        error: function() {
                            infoElement.html('');
                        }
                    });
                } else {
                    infoElement.html('');
                    $('#duration').removeAttr('max').removeClass('is-invalid');
                }
            });
        });
    </script>
@endpush
