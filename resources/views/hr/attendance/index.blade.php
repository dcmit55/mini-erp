@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check-fill text-primary" style="font-size: 2rem;"></i>
                    <div>
                        <h2 class="mb-0">Attendance Input</h2>
                        <p class="text-muted mb-0 small">Daily employee attendance management</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <!-- Button to List -->
                    <a href="{{ route('attendance.list') }}" class="btn btn-outline-primary px-3 py-2"
                        style="min-width: 10px;">
                        <i class="bi bi-list-ul"></i> Back To Attendance List
                    </a>
                    <!-- Current Time Card -->
                    <div class="bg-primary text-white rounded px-3 py-2 text-center" style="min-width: 160px;">
                        <h5 class="mb-0" id="current-time">{{ now()->format('H:i:s') }}</h5>
                        <small>{{ now()->format('l, M d, Y') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4 g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Employees</h6>
                        <h3 class="mb-0 fw-bold text-primary">{{ $summary['total'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Present</h6>
                        <h3 class="mb-0 fw-bold text-success">{{ $summary['present'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Absent</h6>
                        <h3 class="mb-0 fw-bold text-danger">{{ $summary['absent'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Late</h6>
                        <h3 class="mb-0 fw-bold text-warning">{{ $summary['late'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4 rounded-3">
            <div class="card-body">
                <form id="filter-form" method="GET" action="{{ route('attendance.index') }}">
                    <div class="row g-3">
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Department</label>
                            <select name="department_id" class="form-select rounded-pill">
                                <option value="">All</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Position</label>
                            <select name="position" class="form-select rounded-pill">
                                <option value="">All</option>
                                @foreach ($positions as $pos)
                                    <option value="{{ $pos }}"
                                        {{ request('position') == $pos ? 'selected' : '' }}>
                                        {{ $pos }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select rounded-pill">
                                <option value="">All</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present
                                </option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent
                                </option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="date" class="form-control rounded-pill"
                                value="{{ $date }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" name="search" class="form-control rounded-pill"
                                placeholder="Employee name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-12 col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-3 py-2" style="min-width: 120px;">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="row g-2 justify-content-center justify-content-md-start">
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-info btn-sm rounded-pill px-3" id="btn-bulk-present">
                                    <i class="bi bi-check-all"></i> Bulk: Present
                                </button>
                            </div>
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" id="btn-bulk-absent">
                                    <i class="bi bi-x-circle"></i> Bulk: Absent
                                </button>
                            </div>
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3" id="btn-bulk-late">
                                    <i class="bi bi-clock"></i> Bulk: Late
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Employee List -->
        <div class="card shadow-sm rounded-3">
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="select-all">
                    <label class="form-check-label fw-bold" for="select-all">
                        Select All (<span id="selected-count">0</span> selected)
                    </label>
                </div>
                <div id="employee-list">
                    @forelse($employees as $employee)
                        <div class="card mb-3 employee-card border-0 shadow-sm rounded-3"
                            data-employee-id="{{ $employee->id }}">
                            <div class="card-body py-3 px-2">
                                <div class="row align-items-center">
                                    <!-- Checkbox -->
                                    <div class="col-auto">
                                        <input type="checkbox" class="form-check-input employee-checkbox"
                                            value="{{ $employee->id }}">
                                    </div>
                                    <!-- Avatar -->

                                    <!-- Employee Info -->
                                    <div class="col-12 col-md-4 d-flex justify-content: flex-start align-items-center"
                                        style="gap: 16px;">
                                        <div class="d-flex flex-row flex-wrap align-items-center gap-3">
                                            <h6 class="mb-0 fw-bold">{{ $employee->name }}</h6>
                                            <small class="text-muted">{{ $employee->position ?? 'N/A' }}</small>
                                            <small class="text-muted">{{ $employee->department->name ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                    <!-- Status Buttons -->
                                    <div class="col-12 col-md-4 mt-2 mt-md-0">
                                        <div class="d-flex flex-row gap-2 justify-content-start" style="margin-top: 8px;">
                                            <button type="button"
                                                class="btn status-btn {{ $employee->attendance_status == 'present' ? 'btn-success active' : 'btn-outline-success' }} rounded-pill"
                                                data-status="present" data-employee-id="{{ $employee->id }}">
                                                <i class="bi bi-check-circle"></i> Present
                                            </button>
                                            <button type="button"
                                                class="btn status-btn {{ $employee->attendance_status == 'absent' ? 'btn-danger active' : 'btn-outline-danger' }} rounded-pill"
                                                data-status="absent" data-employee-id="{{ $employee->id }}">
                                                <i class="bi bi-x-circle"></i> Absent
                                            </button>
                                            <button type="button"
                                                class="btn status-btn {{ $employee->attendance_status == 'late' ? 'btn-warning active' : 'btn-outline-warning' }} rounded-pill"
                                                data-status="late" data-employee-id="{{ $employee->id }}"
                                                onclick="openLateModal(this)">
                                                <i class="bi bi-clock"></i> Late
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Recorded Time -->
                                    <div class="col-12 col-md-2 text-md-end mt-2 mt-md-0" style="margin-top: 12px;">
                                        @if ($employee->recorded_time)
                                            <small class="text-muted">
                                                <i class="bi bi-clock-history"></i>
                                                Recorded at:
                                                {{ \Carbon\Carbon::parse($employee->recorded_time)->format('h:i A') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info rounded-3 shadow-sm">
                            <i class="bi bi-info-circle"></i> No employees found. Try adjusting your filters.
                        </div>
                    @endforelse
                </div>
                <!-- Modal Input Late -->
                <div class="modal fade" id="lateModal" tabindex="-1" aria-labelledby="lateModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <form id="lateForm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="lateModalLabel">Set Late Time</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="late-employee-id" name="employee_id">
                                    <input type="hidden" id="late-date" name="date" value="{{ $date }}">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Arrival Time (Late)</label>
                                        <input type="time" class="form-control" id="late-time" name="late_time"
                                            required>
                                        <small class="text-muted">Enter the actual arrival time of the employee</small>
                                        <div class="invalid-feedback">Please select a valid time.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toast" class="toast hide" role="alert">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .avatar-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .status-btn {
            transition: all 0.3s;
        }

        .status-btn.active {
            transform: scale(1.07);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        }

        .employee-card {
            transition: box-shadow 0.2s;
        }

        .employee-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
        }

        @media (max-width: 767px) {
            .d-flex.flex-row.flex-wrap.gap-2 {
                gap: 8px !important;
            }

            .btn {
                font-size: 0.95rem;
                padding: 0.5rem 1rem;
            }

            .row.g-2>.col-4 {
                margin-bottom: 8px;
            }

            .avatar-circle {
                width: 40px;
                height: 40px;
                font-size: 15px;
            }

            .card-body.py-3.px-2 {
                padding: 1rem 0.5rem !important;
            }

            .d-flex.align-items-center {
                gap: 8px !important;
            }

            .d-flex.flex-row.gap-2 {
                gap: 8px !important;
            }

            .col-12.col-md-4.d-flex.align-items-center {
                margin-bottom: 8px;
            }

            .col-12.col-md-2.text-md-end {
                margin-top: 8px !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        function openLateModal(btn) {
            $('#late-employee-id').val($(btn).data('employee-id'));
            $('#late-value').val('');
            $('#late-type').val('minutes');
            $('#lateModal').modal('show');
        }

        // Submit modal Late
        $('#lateForm').on('submit', function(e) {
            e.preventDefault();
            var employeeId = $('#late-employee-id').val();
            var date = $('#late-date').val();
            var lateTime = $('#late-time').val();

            // Validasi
            if (!lateTime) {
                $('#late-time').addClass('is-invalid');
                return;
            } else {
                $('#late-time').removeClass('is-invalid');
            }

            // AJAX simpan data late
            $.ajax({
                url: '{{ route('attendance.store') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    employee_id: employeeId,
                    date: date,
                    status: 'late',
                    late_time: lateTime
                },
                beforeSend: function() {
                    $('#lateForm button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('#lateModal').modal('hide');

                    // Update status button warna
                    var rowCard = $('.employee-card[data-employee-id="' + employeeId + '"]');
                    var btnGroup = rowCard.find('.d-flex.flex-row.gap-2');

                    // Reset all buttons
                    btnGroup.find('.status-btn').removeClass(
                            'active btn-success btn-danger btn-warning btn-outline-success btn-outline-danger btn-outline-warning'
                        )
                        .each(function() {
                            var btnStatus = $(this).data('status');
                            $(this).addClass('btn-outline-' + (btnStatus === 'present' ? 'success' :
                                btnStatus === 'absent' ? 'danger' : 'warning'));
                        });

                    // Activate Late button
                    var lateBtn = btnGroup.find('[data-status="late"]');
                    lateBtn.removeClass('btn-outline-warning').addClass('btn-warning active');

                    // Update recorded time
                    if (response.data && response.data.recorded_time) {
                        rowCard.find('.col-md-2.text-md-end').html(
                            `<small class="text-muted"><i class="bi bi-clock-history"></i> Recorded at: ${response.data.recorded_time}</small>`
                        );
                    }

                    showToast(response.message || 'Late status saved successfully', 'success');
                },
                error: function(xhr) {
                    showToast('Failed to save late info', 'error');
                },
                complete: function() {
                    $('#lateForm button[type="submit"]').prop('disabled', false);
                }
            });
        });

        $(document).ready(function() {
            const currentDate = '{{ $date }}';

            // Update clock every second
            setInterval(function() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false
                });
                $('#current-time').text(timeString);
            }, 1000);

            // Select All Checkbox
            $('#select-all').on('change', function() {
                $('.employee-checkbox').prop('checked', $(this).is(':checked'));
                updateSelectedCount();
            });

            $('.employee-checkbox').on('change', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                const count = $('.employee-checkbox:checked').length;
                $('#selected-count').text(count);
            }

            // Status Button Click (except Late which uses modal)
            $('.status-btn:not([onclick])').on('click', function() {
                const button = $(this);
                const employeeId = button.data('employee-id');
                const status = button.data('status');
                updateAttendance(employeeId, status, button);
            });

            // Initialize Default (Set All Present)
            $('#btn-init-default').on('click', function() {
                if (confirm('Set all employees as Present for this date?')) {
                    $.ajax({
                        url: '{{ route('attendance.initialize') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            date: currentDate
                        },
                        beforeSend: function() {
                            $('#btn-init-default').prop('disabled', true).html(
                                '<span class="spinner-border spinner-border-sm"></span> Processing...'
                            );
                        },
                        success: function(response) {
                            showToast(response.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        },
                        error: function(xhr) {
                            showToast('Failed to initialize attendance', 'error');
                        },
                        complete: function() {
                            $('#btn-init-default').prop('disabled', false).html(
                                '<i class="bi bi-check-circle"></i> Set All Present (Default)'
                            );
                        }
                    });
                }
            });

            // Bulk Update
            $('.btn[id^="btn-bulk-"]').on('click', function() {
                const selectedIds = $('.employee-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    showToast('Please select at least one employee', 'warning');
                    return;
                }

                const status = $(this).attr('id').replace('btn-bulk-', '');

                if (confirm(`Mark ${selectedIds.length} employee(s) as ${status}?`)) {
                    bulkUpdateAttendance(selectedIds, status);
                }
            });

            // Update Single Attendance
            function updateAttendance(employeeId, status, button) {
                $.ajax({
                    url: '{{ route('attendance.store') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        employee_id: employeeId,
                        date: currentDate,
                        status: status
                    },
                    beforeSend: function() {
                        button.prop('disabled', true);
                    },
                    success: function(response) {
                        // Temukan grup tombol status (gunakan parent .d-flex.flex-row.gap-2)
                        const buttonGroup = button.closest('.d-flex.flex-row.gap-2');
                        // Reset semua tombol ke outline dan non-aktif
                        buttonGroup.find('.status-btn')
                            .removeClass(
                                'active btn-success btn-danger btn-warning btn-outline-success btn-outline-danger btn-outline-warning'
                            )
                            .each(function() {
                                const btnStatus = $(this).data('status');
                                $(this).addClass('btn-outline-' + (btnStatus === 'present' ?
                                    'success' : btnStatus === 'absent' ? 'danger' :
                                    'warning'));
                            });
                        // Aktifkan tombol yang dipilih
                        button.removeClass('btn-outline-success btn-outline-danger btn-outline-warning')
                            .addClass(
                                status === 'present' ? 'btn-success active' :
                                status === 'absent' ? 'btn-danger active' :
                                status === 'late' ? 'btn-warning active' : ''
                            );
                        showToast(response.message, 'success');
                        // Update recorded time
                        const recordedTimeText =
                            `<i class="bi bi-clock-history"></i> Recorded at: ${response.data.recorded_time}`;
                        button.closest('.row').find('.col-md-2.text-md-end').html(
                            `<small class="text-muted">${recordedTimeText}</small>`);
                    },
                    error: function(xhr) {
                        showToast('Failed to update attendance', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            }

            // Bulk Update Attendance
            function bulkUpdateAttendance(employeeIds, status) {
                $.ajax({
                    url: '{{ route('attendance.bulk-update') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        employee_ids: employeeIds,
                        date: currentDate,
                        status: status
                    },
                    success: function(response) {
                        showToast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function(xhr) {
                        showToast('Failed to bulk update', 'error');
                    }
                });
            }

            // Show Toast Notification
            function showToast(message, type) {
                const toast = $('#toast');
                const toastBody = toast.find('.toast-body');

                toastBody.text(message);

                // Set color based on type
                toast.find('.toast-header').removeClass('bg-success bg-danger bg-warning text-white text-dark');
                if (type === 'success') toast.find('.toast-header').addClass('bg-success text-white');
                if (type === 'error') toast.find('.toast-header').addClass('bg-danger text-white');
                if (type === 'warning') toast.find('.toast-header').addClass('bg-warning text-dark');

                const bsToast = new bootstrap.Toast(toast[0]);
                bsToast.show();
            }

            // Auto-submit filter on change
            $('select[name="department_id"], select[name="position"], select[name="status"], input[name="date"]')
                .on('change', function() {
                    $('#filter-form').submit();
                });
        });
    </script>
@endpush
