@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <!-- Header -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-check gradient-icon me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Daily Attendance</h5>
                                <p class="text-muted mb-0 small">Daily employee attendance management</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
                            <!-- Button to List -->
                            <a href="{{ route('attendance.list') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                                <i class="bi bi-list-ul me-1"></i> Attendance List
                            </a>

                            <!-- Current Time Display -->
                            <div class="d-flex align-items-center bg-light border rounded px-3 py-1 shadow-sm"
                                style="min-width: 180px;">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <div class="d-flex flex-column">
                                    <small class="text-muted mb-0" style="font-size: 0.7rem; line-height: 1;">Current
                                        Time</small>
                                    <strong id="current-time"
                                        style="font-size: 0.95rem; line-height: 1.2;">{{ now()->format('H:i:s') }}</strong>
                                </div>
                            </div>

                            <!-- Current Date Display -->
                            <div class="d-flex align-items-center bg-primary text-white rounded px-3 py-1 shadow-sm"
                                style="min-width: 180px;">
                                <i class="bi bi-calendar3 me-2"></i>
                                <div class="d-flex flex-column">
                                    <small class="mb-0" style="font-size: 0.7rem; line-height: 1; opacity: 0.9;">Today's
                                        Date</small>
                                    <strong
                                        style="font-size: 0.95rem; line-height: 1.2;">{{ now()->format('d M Y') }}</strong>
                                </div>
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

                <!-- SKILL GAP NOTIFICATION (compact version) -->
                @if ($skillGapAnalysis['total_affected_employees'] > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert
                        {{ $skillGapAnalysis['has_critical_impact'] ? 'alert-danger' : 'alert-warning' }}
                        alert-dismissible fade show d-flex align-items-center"
                                role="alert">
                                <div class="me-3">
                                    <i class="bi {{ $skillGapAnalysis['has_critical_impact'] ? 'bi-exclamation-triangle-fill' : 'bi-exclamation-circle-fill' }}"
                                        style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    @if ($skillGapAnalysis['has_critical_impact'])
                                        <h5 class="alert-heading mb-1">Critical Skill Gap Detected!</h5>
                                    @else
                                        <h5 class="alert-heading mb-1">Skill Gap Alert</h5>
                                    @endif
                                    <p class="mb-2">
                                        <strong>{{ $skillGapAnalysis['total_affected_employees'] }} employee(s)</strong>
                                        are absent or late today, affecting
                                        <strong>{{ count($skillGapAnalysis['missing_skills']) }} skillset(s)</strong>
                                        @if ($skillGapAnalysis['has_critical_impact'])
                                            (including <strong
                                                class="text-danger">{{ count($skillGapAnalysis['critical_skills']) }}
                                                critical
                                                skills</strong>)
                                        @endif
                                    </p>
                                    <button type="button"
                                        class="btn btn-sm
                                {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-danger' : 'btn-warning' }}"
                                        data-bs-toggle="modal" data-bs-target="#skillGapModal">View Detailed Analysis
                                    </button>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Filters -->
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
                                value="{{ $date }}" max="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" name="search" class="form-control rounded-pill"
                                placeholder="Employee name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-12 col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-3 py-2"
                                style="min-width: 120px;">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="row g-2 justify-content-center justify-content-md-start">
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-info btn-sm rounded-pill px-3"
                                    id="btn-bulk-present">
                                    <i class="bi bi-check-all"></i> Bulk: Present
                                </button>
                            </div>
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3"
                                    id="btn-bulk-absent">
                                    <i class="bi bi-x-circle"></i> Bulk: Absent
                                </button>
                            </div>
                            <div class="col-4 col-md-auto">
                                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3"
                                    id="btn-bulk-late">
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

    <!-- Modal Skill Gap -->
    @if ($skillGapAnalysis['total_affected_employees'] > 0)
        <div class="modal fade" id="skillGapModal" tabindex="-1" aria-labelledby="skillGapModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div
                        class="modal-header {{ $skillGapAnalysis['has_critical_impact'] ? 'bg-danger text-white' : 'bg-warning' }}">
                        <h5 class="modal-title" id="skillGapModalLabel">
                            <i
                                class="bi {{ $skillGapAnalysis['has_critical_impact'] ? 'bi-exclamation-triangle-fill' : 'bi-exclamation-circle-fill' }}"></i>
                            @if ($skillGapAnalysis['has_critical_impact'])
                                Critical Skill Gap Analysis
                            @else
                                Skill Gap Analysis
                            @endif
                        </h5>
                        <button type="button"
                            class="btn-close {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-close-white' : '' }}"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('hr.attendance.skill-gap-modal', [
                            'skillGapAnalysis' => $skillGapAnalysis,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Close
                        </button>
                        <button type="button"
                            class="btn {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-danger' : 'btn-warning' }}"
                            onclick="window.print();">
                            <i class="bi bi-printer"></i> Print Analysis
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Input Bulk Late Time - Individual per Employee -->
    <div class="modal fade" id="bulk-late-modal" tabindex="-1" aria-labelledby="bulkLateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="bulkLateModalLabel">
                        <i class="bi bi-clock"></i> Mark Employees as Late - Enter Individual Times
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulk-late-form">
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            <strong><span id="bulk-late-employee-count">0</span> employee(s)</strong> will be marked as
                            late.
                            <br>
                            <small>Enter the time each employee arrived for their status to be recorded.</small>
                        </div>

                        <!-- Dynamic employee late time inputs -->
                        <div id="bulk-late-employee-inputs" class="row g-3">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Mark All as Late
                        </button>
                    </div>
                </form>
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
        /* Current Time & Date Cards */
        .bg-light.border {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
            transition: all 0.3s ease;
        }

        .bg-light.border:hover {
            background-color: #e9ecef !important;
            border-color: #adb5bd !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .bg-primary.rounded {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
            transition: all 0.3s ease;
        }

        .bg-primary.rounded:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3) !important;
        }

        /* Button consistent styling */
        .btn-outline-primary.btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .btn-outline-primary.btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .d-flex.flex-wrap.gap-2 {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .bg-light.border,
            .bg-primary.rounded,
            .btn-outline-primary.btn-sm {
                width: 100%;
                justify-content: center;
            }
        }

        /* Gradient icon */
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Avatar circle */
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

        /* Status button */
        .status-btn {
            transition: all 0.3s;
        }

        .status-btn.active {
            transform: scale(1.07);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        }

        /* Employee card */
        .employee-card {
            transition: box-shadow 0.2s;
        }

        .employee-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
        }

        /* Mobile responsive */
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

        /* Skill Gap Alert Styling */
        .alert-danger .btn-close-white {
            filter: brightness(0) invert(1);
        }

        /* Modal Skill Cards */
        .modal-xl {
            max-width: 1200px;
        }

        .card.border-danger {
            border-width: 2px;
        }

        .card.border-warning {
            border-width: 2px;
        }

        /* Print styles */
        @media print {

            .modal-header,
            .modal-footer {
                display: none !important;
            }

            .modal-body {
                padding: 0 !important;
            }

            .card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
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

            // Bulk Update dengan Individual Late Time Input per Employee
            $('.btn[id^="btn-bulk-"]').on('click', function() {
                const selectedIds = $('.employee-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    showToast('Please select at least one employee', 'warning');
                    return;
                }

                const status = $(this).attr('id').replace('btn-bulk-', '');

                // Jika status late, tampilkan modal dengan individual time inputs
                if (status === 'late') {
                    $('#bulk-late-employee-count').text(selectedIds.length);
                    generateBulkLateInputs(selectedIds);
                    $('#bulk-late-modal').modal('show');

                    // Store selected IDs di data attribute
                    $('#bulk-late-modal').data('selected-ids', selectedIds);
                    return;
                }

                // Untuk status lain (present, absent)
                if (confirm(`Mark ${selectedIds.length} employee(s) as ${status}?`)) {
                    bulkUpdateAttendance(selectedIds, status);
                }
            });

            // Generate individual time input untuk setiap employee
            function generateBulkLateInputs(employeeIds) {
                const container = $('#bulk-late-employee-inputs');
                let html = '';

                employeeIds.forEach((empId, index) => {
                    // Get employee name dari DOM
                    const empCard = $(`.employee-card[data-employee-id="${empId}"]`);
                    const empName = empCard.find('.fw-bold').text() || `Employee ${empId}`;

                    html += `
                    <div class="col-md-6">
                        <div class="card border-1 bg-light">
                            <div class="card-body p-3">
                                <label class="form-label small mb-2">
                                    <strong>${empName}</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <input type="time"
                                        class="form-control late-time-input"
                                        name="late_time[${empId}]"
                                        data-employee-id="${empId}"
                                        required>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Enter arrival time (HH:MM format)
                                </small>
                            </div>
                        </div>
                    </div>
                `;
                });

                container.html(html);
            }

            // Handle Bulk Late Submit dengan individual times
            $('#bulk-late-form').on('submit', function(e) {
                e.preventDefault();

                const selectedIds = $('#bulk-late-modal').data('selected-ids');
                const lateTimeInputs = {};
                let allFilled = true;

                // Collect all late times
                $('.late-time-input').each(function() {
                    const empId = $(this).data('employee-id');
                    const time = $(this).val();

                    if (!time) {
                        $(this).addClass('is-invalid');
                        allFilled = false;
                    } else {
                        $(this).removeClass('is-invalid');
                        lateTimeInputs[empId] = time;
                    }
                });

                if (!allFilled) {
                    showToast('Please enter late time for all employees', 'warning');
                    return;
                }

                // Build confirmation message
                const confirmMsg = buildBulkLateConfirmation(selectedIds, lateTimeInputs);

                if (confirm(confirmMsg)) {
                    bulkUpdateAttendanceWithIndividualTimes(selectedIds, lateTimeInputs);
                    $('#bulk-late-modal').modal('hide');
                }
            });

            // Build confirmation message dengan detail waktu
            function buildBulkLateConfirmation(selectedIds, lateTimeInputs) {
                let msg = `Mark ${selectedIds.length} employee(s) as late?\n\n`;

                selectedIds.forEach(empId => {
                    const empCard = $(`.employee-card[data-employee-id="${empId}"]`);
                    const empName = empCard.find('.fw-bold').text() || `Employee ${empId}`;
                    const time = lateTimeInputs[empId];

                    msg += `• ${empName}: ${time}\n`;
                });

                return msg;
            }

            // Bulk Update dengan individual late times per employee
            function bulkUpdateAttendanceWithIndividualTimes(employeeIds, lateTimeInputs) {
                const currentDate = '{{ $date }}';

                // Siapkan data untuk AJAX
                const data = {
                    _token: '{{ csrf_token() }}',
                    date: currentDate,
                    status: 'late',
                    employees_with_times: [] // Array of {employee_id, late_time}
                };

                // Convert ke format yang dikirim ke server
                employeeIds.forEach(empId => {
                    data.employees_with_times.push({
                        employee_id: empId,
                        late_time: lateTimeInputs[empId]
                    });
                });

                $.ajax({
                    url: '{{ route('attendance.bulk-update-individual') }}', // Route baru
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function(response) {
                        showToast(response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Failed to bulk update';
                        showToast(errorMsg, 'error');
                    }
                });
            }

            // Bulk Update Attendance
            function bulkUpdateAttendance(employeeIds, status, lateTime = null) {
                const data = {
                    _token: '{{ csrf_token() }}',
                    employee_ids: employeeIds,
                    date: currentDate,
                    status: status
                };

                // Tambah late_time jika ada
                if (status === 'late' && lateTime) {
                    data.bulk_late_time = lateTime;
                }

                $.ajax({
                    url: '{{ route('attendance.bulk-update') }}',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        showToast(response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function(xhr) {
                        showToast('Failed to bulk update', 'error');
                    }
                });
            }

            // Update Single Attendance - PERBAIKAN
            function updateAttendance(employeeId, status, button) {
                // Jika status late, prompt untuk input time
                let lateTime = null;
                if (status === 'late') {
                    lateTime = prompt('Enter late time (HH:MM):', '09:00');
                    if (!lateTime) {
                        showToast('Late time is required', 'warning');
                        return;
                    }
                }

                $.ajax({
                    url: '{{ route('attendance.store') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        employee_id: employeeId,
                        date: currentDate,
                        status: status,
                        late_time: lateTime
                    },
                    beforeSend: function() {
                        button.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            const rowCard = $('.employee-card[data-employee-id="' + employeeId + '"]');
                            const btnGroup = rowCard.find('.d-flex.flex-row.gap-2');

                            // Reset all buttons
                            btnGroup.find('.status-btn').removeClass(
                                'btn-primary btn-success btn-warning btn-danger active');
                            btnGroup.find('.status-btn').addClass('btn-outline-primary');

                            // Activate corresponding button dengan class yang tepat
                            const activeBtn = btnGroup.find('[data-status="' + status + '"]');
                            const btnClass = status === 'present' ? 'btn-success active' :
                                status === 'absent' ? 'btn-danger active' :
                                'btn-warning active';

                            activeBtn.removeClass('btn-outline-primary').addClass(btnClass);

                            // Update recorded time
                            if (response.data && response.data.recorded_time) {
                                rowCard.find('.recorded-time-badge').html(
                                    '<i class="bi bi-check-circle text-success"></i> ' + response
                                    .data.recorded_time
                                );
                            }

                            showToast(response.message || 'Attendance updated successfully', 'success');

                            // Update UI Components
                            updateSummaryCards();

                            // Update Skill Gap dari response
                            if (response.skillGapAnalysis) {
                                updateSkillGapUI(response.skillGapAnalysis);
                            }
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Failed to update attendance';
                        showToast(errorMsg, 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            }

            // Function untuk update Summary Cards real-time
            function updateSummaryCards() {
                // Hitung status dari semua employee cards di halaman
                const totalEmployees = $('.employee-card').length;
                const presentCount = $('.employee-card').filter(function() {
                    return $(this).find('[data-status="present"]').hasClass('btn-success') ||
                        $(this).find('[data-status="present"]').hasClass('active');
                }).length;

                const absentCount = $('.employee-card').filter(function() {
                    return $(this).find('[data-status="absent"]').hasClass('btn-danger') ||
                        $(this).find('[data-status="absent"]').hasClass('active');
                }).length;

                const lateCount = $('.employee-card').filter(function() {
                    return $(this).find('[data-status="late"]').hasClass('btn-warning') ||
                        $(this).find('[data-status="late"]').hasClass('active');
                }).length;

                // Update summary cards dengan animasi
                updateCardValue('primary', totalEmployees); // Total
                updateCardValue('success', presentCount); // Present
                updateCardValue('danger', absentCount); // Absent
                updateCardValue('warning', lateCount); // Late
            }

            // Helper function untuk update card value dengan animasi
            function updateCardValue(colorClass, newValue) {
                const selector = `h3.text-${colorClass}`;
                const cardText = document.querySelector(selector);

                if (cardText) {
                    const oldValue = parseInt(cardText.textContent.trim());

                    if (oldValue !== newValue) {
                        // Animasi: fade out, update, fade in
                        const card = cardText.closest('.card');
                        if (card) {
                            $(card).fadeOut(100, function() {
                                cardText.textContent = newValue;
                                $(card).fadeIn(100);
                            });
                        } else {
                            cardText.textContent = newValue;
                        }
                    }
                } else {
                    console.warn(`Card selector "${selector}" not found`);
                }
            }

            // Function untuk update Skill Gap Notification real-time
            function updateSkillGapNotification() {
                const currentDate = '{{ $date }}';

                $.ajax({
                    url: '{{ route('attendance.index') }}',
                    type: 'GET',
                    data: {
                        date: currentDate,
                        ajax_skill_gap: true // Flag untuk return hanya skill gap
                    },
                    success: function(response) {
                        if (response.skillGapAnalysis) {
                            updateSkillGapUI(response.skillGapAnalysis);
                        }
                    },
                    error: function() {
                        // Silent fail - skill gap tidak ter-update tapi attendance sudah terubah
                    }
                });
            }

            // Function untuk update Skill Gap UI dengan selector yang lebih akurat
            function updateSkillGapUI(skillGapAnalysis) {
                // Cari alert container dengan lebih spesifik
                const alertContainer = $('.alert').filter(function() {
                    return $(this).find('.alert-heading').text().includes('Skill Gap') ||
                        $(this).find('.alert-heading').text().includes('Critical Skill Gap');
                });

                if (skillGapAnalysis.total_affected_employees > 0) {
                    // Jika alert tidak ada (baru ada skill gap), reload page
                    if (alertContainer.length === 0) {
                        setTimeout(() => location.reload(), 1000);
                        return;
                    }

                    // Update alert dengan animasi fade
                    alertContainer.fadeOut(150, function() {
                        // Update counts
                        const strongElements = $(this).find('strong');

                        // Update employee count (first strong)
                        if (strongElements.length > 0) {
                            $(strongElements[0]).text(skillGapAnalysis.total_affected_employees);
                        }

                        // Update skillset count (second strong)
                        if (strongElements.length > 1) {
                            $(strongElements[1]).text(Object.keys(skillGapAnalysis.missing_skills).length);
                        }

                        // Update header text
                        const header = $(this).find('h5.alert-heading');
                        if (skillGapAnalysis.has_critical_impact) {
                            header.html(
                                '<i class="bi bi-exclamation-triangle-fill"></i> Critical Skill Gap Detected!'
                            );
                        } else {
                            header.html('<i class="bi bi-info-circle"></i> Skill Gap Alert');
                        }

                        // Update alert color
                        $(this).removeClass('alert-warning alert-danger');
                        $(this).addClass(skillGapAnalysis.has_critical_impact ? 'alert-danger' :
                            'alert-warning');

                        // Update modal content jika modal sudah dibuka
                        if ($('#skillGapModal').length > 0 && $('#skillGapModal').hasClass('show')) {
                            refreshSkillGapModal(skillGapAnalysis);
                        }

                        $(this).fadeIn(150);
                    });
                } else {
                    // Hide alert jika tidak ada skill gap lagi
                    if (alertContainer.length > 0) {
                        alertContainer.fadeOut(200, function() {
                            $(this).remove();
                        });
                    }
                }
            }

            // Function untuk refresh modal content
            function refreshSkillGapModal(skillGapAnalysis) {
                const modalBody = $('#skillGapModal .modal-body');
                const modalHeader = $('#skillGapModal .modal-header');

                // Update modal header color
                modalHeader.removeClass('bg-warning bg-danger');
                if (skillGapAnalysis.has_critical_impact) {
                    modalHeader.addClass('bg-danger text-white');
                } else {
                    modalHeader.addClass('bg-warning');
                }

                // Fetch updated modal content
                $.get('{{ route('attendance.index') }}', {
                    date: '{{ $date }}',
                    ajax_skill_gap_modal: true
                }, function(html) {
                    // Extract modal body content dari response
                    const tempDiv = $('<div>').html(html);
                    const newContent = tempDiv.find('.alert, .row.g-3').first().parent().html();

                    if (newContent) {
                        modalBody.fadeOut(150, function() {
                            $(this).html(html);
                            $(this).fadeIn(150);
                        });
                    }
                }).fail(function(err) {
                    console.error('Failed to refresh skill gap modal:', err);
                });
            }

            // Auto-submit filter on change
            $('select[name="department_id"], select[name="position"], select[name="status"], input[name="date"]')
                .on('change', function() {
                    $('#filter-form').submit();
                });
        });
    </script>
@endpush
