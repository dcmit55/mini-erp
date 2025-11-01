@extends('layouts.app')

@section('title', 'Attendance List')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-items-center gap-2">
                    <i class="bi bi-list-ul text-primary fs-2"></i>
                    <div>
                        <h2 class="mb-0">Attendance List</h2>
                        <p class="text-muted mb-0 small mt-1">View and manage attendance history</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-wrap justify-content-md-end gap-2">
                    <a href="{{ route('attendance.index') }}"
                        class="btn btn-outline-primary rounded-pill shadow-sm mb-2 mb-md-0 me-0 me-md-2">
                        <i class="bi bi-calendar-check"></i> Input Attendance
                    </a>
                    <button type="button" class="btn btn-success rounded-pill shadow-sm ms-0 ms-md-2" id="btn-export">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Records</h6>
                        <h3 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_records']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Today Present</h6>
                        <h3 class="mb-0 fw-bold text-success">{{ $stats['today_present'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Today Absent</h6>
                        <h3 class="mb-0 fw-bold text-danger">{{ $stats['today_absent'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Today Late</h6>
                        <h3 class="mb-0 fw-bold text-warning">{{ $stats['today_late'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form id="filter-form" method="GET" action="{{ route('attendance.list') }}">
                    <div class="row g-3">
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Date From</label>
                            <input type="date" name="date_from" class="form-control rounded-pill"
                                value="{{ $date_from }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Date To</label>
                            <input type="date" name="date_to" class="form-control rounded-pill"
                                value="{{ $date_to }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Department</label>
                            <select name="department_id" class="form-select rounded-pill">
                                <option value="">All</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $department_id == $dept->id ? 'selected' : '' }}>
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
                                    <option value="{{ $pos }}" {{ $position == $pos ? 'selected' : '' }}>
                                        {{ $pos }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select rounded-pill">
                                <option value="">All</option>
                                <option value="present" {{ $status == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ $status == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ $status == 'late' ? 'selected' : '' }}>Late</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" name="search" class="form-control rounded-pill"
                                placeholder="Name or Employee No..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill shadow-sm">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                                <a href="{{ route('attendance.list') }}" class="btn btn-secondary rounded-pill shadow-sm">
                                    <i class="bi bi-x-circle"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card shadow-sm rounded-3">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Show per page:</label>
                        <select class="form-select w-auto d-inline-block rounded-pill" id="per-page-select">
                            <option value="25" {{ $per_page == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $per_page == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $per_page == 100 ? 'selected' : '' }}>100</option>
                            <option value="500" {{ $per_page == 500 ? 'selected' : '' }}>500</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
                        <p class="mb-0 text-muted">
                            Showing {{ $attendances->firstItem() ?? 0 }} to {{ $attendances->lastItem() ?? 0 }}
                            of {{ $attendances->total() }} entries
                        </p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Arrival Time</th>
                                <th>Recorded Time</th>
                                <th>Recorded By</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                @if ($attendance->employee)
                                    <tr>
                                        <td>{{ $loop->iteration + ($attendances->currentPage() - 1) * $attendances->perPage() }}
                                        </td>
                                        <td>
                                            <strong>{{ $attendance->date->format('d M Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $attendance->date->format('l') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">

                                                <div>
                                                    <strong>{{ $attendance->employee->name }}</strong>
                                                    <br>
                                                    <small
                                                        class="text-muted">{{ $attendance->employee->employee_no ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $attendance->employee->department->name ?? 'N/A' }}</td>
                                        <td>{{ $attendance->employee->position ?? 'N/A' }}</td>
                                        <td>
                                            @if ($attendance->status == 'present')
                                                <span class="badge bg-success rounded-pill px-3 py-2 shadow-sm">
                                                    <i class="bi bi-check-circle"></i> Present
                                                </span>
                                            @elseif($attendance->status == 'absent')
                                                <span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm">
                                                    <i class="bi bi-x-circle"></i> Absent
                                                </span>
                                            @else
                                                <span class="badge bg-warning rounded-pill px-3 py-2 shadow-sm">
                                                    <i class="bi bi-clock"></i> Late
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->status == 'late' && $attendance->late_time)
                                                <span class="text-warning fw-bold">
                                                    <i class="bi bi-clock"></i>
                                                    {{ \Carbon\Carbon::parse($attendance->late_time)->format('H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td>
                                            <i class="bi bi-clock-history"></i>
                                            {{ \Carbon\Carbon::parse($attendance->recorded_time)->format('h:i A') }}
                                        </td>
                                        <td>
                                            <small>{{ $attendance->recordedBy->name ?? 'System' }}</small>
                                        </td>
                                        <td>
                                            @if ($attendance->notes)
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;"
                                                    data-bs-toggle="tooltip" title="{{ $attendance->notes }}">
                                                    {{ $attendance->notes }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-danger btn-delete rounded-pill shadow-sm"
                                                data-id="{{ $attendance->id }}"
                                                data-name="{{ $attendance->employee->name }}"
                                                data-date="{{ $attendance->date->format('Y-m-d') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @else
                                    <tr class="table-warning">
                                        <td>{{ $loop->iteration + ($attendances->currentPage() - 1) * $attendances->perPage() }}
                                        </td>
                                        <td>
                                            <strong>{{ $attendance->date->format('d M Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $attendance->date->format('l') }}</small>
                                        </td>
                                        <td colspan="6" class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <strong>Orphaned Record</strong> - Employee data not found (ID:
                                            {{ $attendance->employee_id }})
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-danger btn-delete rounded-pill shadow-sm"
                                                data-id="{{ $attendance->id }}" data-name="Unknown Employee"
                                                data-date="{{ $attendance->date->format('Y-m-d') }}">
                                                <i class="bi bi-trash"></i> Clean
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No attendance records found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        {{ $attendances->appends(request()->query())->links() }}
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
        .avatar-circle-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table-warning {
            background-color: #fff3cd;
        }

        @media (max-width: 767px) {

            .card-body.text-center {
                padding: 1rem 0.5rem !important;
            }

            .d-flex.align-items-start {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .d-flex.flex-wrap.gap-2 {
                gap: 8px !important;
            }

            .d-flex.align-items-center.gap-2 {
                gap: 8px !important;
            }
        }
    </style>
@endpush
