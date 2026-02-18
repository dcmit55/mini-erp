@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        @guest
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Self-Service Leave Request</strong><br>
                You can submit a leave request without login. Your request will be sent to HR for approval.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endguest
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">
                    Create Leave Request
                </h2>
                <hr>
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('leave_requests.store') }}" id="leaveRequestForm">
                    @csrf
                    <div class="row">
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select select2" required>
                                <option value="">Select</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" data-department="{{ $emp->department->name ?? '' }}"
                                        data-position="{{ $emp->position ?? '' }}"
                                        data-hiredate="{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d-m-Y') : '' }}">
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" id="department" class="form-control" readonly>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" id="position" class="form-control" readonly>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">Hire Date</label>
                            <input type="text" id="hire_date" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label d-flex align-items-center">
                                Leave Duration
                                <span class="ms-2" data-bs-toggle="tooltip" title="Can use 0.5 for half day"
                                    style="cursor: pointer;">
                                    <i class="bi bi-info-circle text-muted"></i>
                                </span>
                            </label>
                            <div class="input-group">
                                <input type="number" name="duration" id="duration" class="form-control" min="0.5"
                                    max="999.99" step="0.5" required
                                    value="{{ old('duration', isset($leave) ? $leave->duration : '') }}"
                                    placeholder="1 or 0.5">
                                <span class="input-group-text">days</span>
                            </div>
                            <small class="text-muted" id="leave-balance-info"></small>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                            <div class="row row-cols-2 row-cols-md-2 g-2">
                                @foreach ($leaveTypes as $type)
                                    <div class="col">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type"
                                                id="type_{{ $type }}" value="{{ $type }}" required>
                                            <label class="form-check-label" for="type_{{ $type }}">
                                                {{ $leaveTypeLabels[$type] ?? $type }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="row ">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="4"></textarea>
                        </div>
                    </div>

                    @guest
                        <!-- reCAPTCHA v2 - Only for unauthenticated users -->
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Security Verification <span class="text-danger">*</span></label>
                                <div class="g-recaptcha"
                                    data-sitekey="{{ config('services.recaptcha.site_key', '6LfD2WgsAAAAAKM7FHahZOxYuFvtRHDIVt_uhkPX') }}">
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-shield-check"></i> Please complete the reCAPTCHA to prevent spam
                                </small>
                                @error('g-recaptcha-response')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endguest

                    <div class="d-flex gap-2 mt-2 justify-content-end">
                        @auth
                            <a href="{{ route('leave_requests.index') }}" class="btn btn-secondary">Cancel</a>
                        @else
                            <button type="button" class="btn btn-secondary"
                                onclick="window.location.reload()">Reset</button>
                        @endauth
                        <button type="submit" class="btn btn-success">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .g-recaptcha {
            display: inline-block;
        }
    </style>
@endpush

@push('scripts')
    <!-- Google reCAPTCHA v2 Script -->
    @guest
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endguest

    <script>
        $(document).ready(function() {
            // Check for guest success message
            @if (session('guest_success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted!',
                    html: `
                        <p class="mb-3">{{ session('guest_success') }}</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Your request will be reviewed by HR department.
                        </div>
                    `,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745',
                    allowOutsideClick: false
                }).then((result) => {
                        if (result.isConfirmed) {
                            // Reset form after success
                            $('#leaveRequestForm')[0].reset();
                            $('#employee_id').val(null).trigger('change');
                            $('#department, #position, #hire_date').val('');
                            $('#leave-balance-info').html('');

                            @guest
                            // Reset reCAPTCHA for guest users
                            if (typeof grecaptcha !== 'undefined') {
                                grecaptcha.reset();
                            }
                        @endguest
                    }
                });
        @endif

        $('#employee_id').select2({
            width: '100%',
            placeholder: 'Select',
            allowClear: true,
            theme: 'bootstrap-5'
        });

        $('#employee_id').on('change', function() {
            const selected = this.options[this.selectedIndex];
            $('#department').val(selected.getAttribute('data-department') || '');
            $('#position').val(selected.getAttribute('data-position') || '');
            $('#hire_date').val(selected.getAttribute('data-hiredate') || '');
        });

        // Leave type change handler - Auto calculate dates for fixed-day types
        $('input[name="type"]').on('change', function() {
            const leaveType = $(this).val();
            const startDate = $('#start_date');
            const endDate = $('#end_date');
            const duration = $('#duration');

            // Define fixed-day leave types with their durations
            const fixedDayTypes = {
                'EMP_SELF_WEDDING': 3,
                'BIRTH_CHILD_MISCARRIAGE': 2,
                'DEATH_FAMILY_SAME_HOUSE': 1,
                'CHILD_CIRCUMCISION_BAPTISM': 2,
                'SON_DAUGHTER_WEDDING': 2,
                'DEATH_SPOUSE_CHILD_PARENT_IN_LAW': 2
            };

            if (fixedDayTypes[leaveType]) {
                // Fixed-day leave: disable to_date field and auto-calculate
                endDate.prop('readonly', true)
                    .prop('disabled', true)
                    .prop('required', false)
                    .css('background-color', '#e9ecef')
                    .attr('title', 'Auto-calculated based on leave type');

                duration.prop('readonly', true)
                    .css('background-color', '#e9ecef')
                    .val(fixedDayTypes[leaveType]);

                // Auto-calculate end_date when start_date is selected
                startDate.off('change').on('change', function() {
                    if ($(this).val()) {
                        const start = new Date($(this).val());
                        const days = fixedDayTypes[leaveType];
                        const end = new Date(start);
                        end.setDate(end.getDate() + days - 1);

                        endDate.val(end.toISOString().split('T')[0]);
                        duration.val(days);
                    }
                });

                // Trigger if start_date already has value
                if (startDate.val()) {
                    startDate.trigger('change');
                }
            } else {
                // Annual Leave and Unpaid Leave: allow manual date selection
                endDate.prop('readonly', false)
                    .prop('disabled', false)
                    .prop('required', true)
                    .css('background-color', '')
                    .removeAttr('title');

                duration.prop('readonly', false)
                    .css('background-color', '');

                // Re-attach manual date calculation
                startDate.off('change').on('change', calculateDuration);
                endDate.off('change').on('change', calculateDuration);
            }
        });

        // Manual date calculation function
        function calculateDuration() {
            let start = $('#start_date').val();
            let end = $('#end_date').val();

            if (start && end) {
                let d1 = new Date(start);
                let d2 = new Date(end);
                let diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;

                if (diff > 0) {
                    $('#duration').val(diff);
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
        }

        // Initial setup for manual date calculation
        $('#start_date, #end_date').on('change', calculateDuration);

        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        $('#duration').on('input', function() {
            let value = parseFloat($(this).val());
            if ($(this).val().includes('.')) {
                let parts = $(this).val().split('.');
                if (parts[1].length > 2) {
                    $(this).val(parseFloat($(this).val()).toFixed(2));
                }
            }
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

        $('#employee_id, input[name="type"]').on('change', function() {
            const employeeId = $('#employee_id').val();
            const leaveType = $('input[name="type"]:checked').val();
            const infoElement = $('#leave-balance-info');

            if (employeeId && leaveType === 'ANNUAL') {
                const selectedOption = $('#employee_id option:selected');
                const employeeName = selectedOption.text();
                $.ajax({
                    url: `/employees/${employeeId}/leave-balance`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            infoElement.html(
                                `<i class="bi bi-info-circle"></i> Available balance: <strong>${response.balance} days</strong>`
                            );
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

        // Prevent multiple submit & show loading spinner
        $('#leaveRequestForm').on('submit', function(e) {
                var $btn = $(this).find('button[type="submit"]');

                @guest
                // Validate reCAPTCHA for guest users
                var recaptchaResponse = grecaptcha.getResponse();
                if (!recaptchaResponse) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'reCAPTCHA Required',
                        text: 'Please complete the reCAPTCHA verification before submitting.',
                        confirmButtonColor: '#ffc107'
                    });
                    return false;
                }
            @endguest

            if ($btn.prop('disabled')) {
                e.preventDefault();
                return false;
            }
            $btn.prop('disabled', true); $btn.html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...');
        });
        });
    </script>
@endpush
