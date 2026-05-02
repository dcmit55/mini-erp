@extends('layouts.app')

@section('title', 'Attendance Daily')

@section('content')
<div class="container-fluid py-3 py-md-4">
    <div class="row justify-content-center">
        <div class="col-12">

            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <a href="{{ route('attendance-logs.summary') }}" class="btn btn-sm btn-outline-primary px-3">
                    <i class="fas fa-chart-bar me-1"></i>
                    <span class="d-none d-sm-inline">Attendance Summary</span>
                    <span class="d-sm-none">Summary</span>
                </a>
                <div class="d-flex gap-2">
                    @can('hr.attendance.edit')
                    <button type="button" class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload"></i>
                        <span class="d-none d-sm-inline ms-1">Import</span>
                    </button>
                    @endcan
                    @can('hr.attendance.export')
                    <a href="{{ route('attendance-logs.export') }}?{{ http_build_query(request()->except('page')) }}" class="btn btn-sm btn-outline-success px-3">
                        <i class="fas fa-download"></i>
                        <span class="d-none d-sm-inline ms-1">Export</span>
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Filter -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('attendance-logs.index') }}" id="attlog-filter-form">
                        <input type="hidden" id="input-start-date" name="start_date" value="{{ request('start_date') }}">
                        <input type="hidden" id="input-end-date"   name="end_date"   value="{{ request('end_date') }}">

                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">
                                    <i class="fas fa-calendar-alt me-1 opacity-50"></i>Date Range
                                </label>
                                <input type="text" id="attlog-date-range"
                                    class="form-control form-control-sm"
                                    placeholder="All dates"
                                    readonly style="cursor:pointer;background:#fff;">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">Department</label>
                                <select name="department_id" id="filter-dept" class="form-select form-select-sm auto-submit">
                                    <option value="">All Dept.</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">Employee</label>
                                <select name="employee_id" id="filter-emp" class="form-select form-select-sm auto-submit">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_no }} - {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">Search</label>
                                <div class="position-relative">
                                    <input type="text" name="search" id="filter-search"
                                        class="form-control form-control-sm pe-4"
                                        placeholder="Name or NIK…"
                                        value="{{ request('search') }}"
                                        autocomplete="off">
                                    <span id="search-spinner"
                                        class="position-absolute top-50 end-0 translate-middle-y pe-2 d-none"
                                        style="pointer-events:none;">
                                        <span class="spinner-border spinner-border-sm text-secondary" style="width:.7rem;height:.7rem;border-width:1.5px;"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2 align-items-end justify-content-end">
                                @if(request()->anyFilled(['start_date', 'end_date', 'department_id', 'employee_id', 'search']))
                                    <a href="{{ route('attendance-logs.index') }}"
                                        class="btn btn-sm btn-outline-secondary px-3"
                                        title="Clear all filters">
                                        <i class="fas fa-times me-1"></i>Reset
                                    </a>
                                @endif
                                <a href="{{ route('attendance-logs.index', ['all' => 1]) }}"
                                    class="btn btn-sm btn-outline-info px-2 {{ request()->has('all') ? 'active' : '' }}"
                                    title="Show all data">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Bar -->
            @if(request()->has('all'))
                <div class="alert alert-secondary py-2 small d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-eye me-2 flex-shrink-0"></i>
                    <span>Showing all data. <a href="{{ route('attendance-logs.index') }}" class="alert-link">Back to today</a>.</span>
                </div>
            @elseif(request()->filled('start_date') && request()->filled('end_date'))
                <div class="alert alert-info py-2 small d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-calendar-alt me-2 flex-shrink-0"></i>
                    <span>{{ request('start_date') }} &rarr; {{ request('end_date') }}</span>
                </div>
            @elseif($latestImportSource)
                <div class="alert alert-info py-2 small d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-clock me-2 flex-shrink-0"></i>
                    <span>Latest import: <strong>{{ $latestImportSource }}</strong>.
                    <a href="{{ route('attendance-logs.index', ['all' => 1]) }}" class="alert-link ms-1">View all</a>.</span>
                </div>
            @else
                <div class="alert alert-warning py-2 small d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2 flex-shrink-0"></i>
                    <span>No data yet. Please import first.</span>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center py-2 small mb-3">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- ═══ DESKTOP TABLE (md+) ═══ -->
            <div class="card border-0 shadow-sm d-none d-md-block">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="border-0 ps-4" style="width:50px;">No</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">NIK</th>
                                    <th class="border-0">Name</th>
                                    <th class="border-0">Shift</th>
                                    <th class="border-0">Clock In</th>
                                    <th class="border-0">Clock Out</th>
                                    <th class="border-0">Hours</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Remarks</th>
                                    <th class="border-0 pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($attendancesPaginated as $item)
                                    @php
                                        $badgeClass = [
                                            'Present'         => 'bg-success',
                                            'Late'            => 'bg-warning text-dark',
                                            'Excused'         => 'bg-info',
                                            'Sick Leave'      => 'bg-info',
                                            'Annual Leave'    => 'bg-primary',
                                            'Less Hours'      => 'bg-orange text-white',
                                            'Late, Less Hours'=> 'bg-warning text-dark',
                                            'Early Leave'     => 'bg-indigo text-white',
                                            'Alpha'           => 'bg-danger',
                                        ][$item->status] ?? 'bg-secondary';
                                    @endphp
                                    <tr id="row-{{ $item->employee->id }}-{{ $item->date->format('Y-m-d') }}">
                                        <td class="ps-4">
                                            <span class="table-number">
                                                {{ $loop->iteration + ($attendancesPaginated->currentPage()-1)*$attendancesPaginated->perPage() }}
                                            </span>
                                        </td>
                                        <td>{{ $item->date->format('d/m/Y') }}</td>
                                        <td>{{ $item->employee->employee_no }}</td>
                                        <td>{{ $item->employee->name }}</td>
                                        <td>
                                            @if($item->session_shift)
                                                <span class="badge bg-soft-primary text-primary px-2 py-1 fw-semibold"
                                                      title="{{ $item->session_shift->start_time }} – {{ $item->session_shift->end_time }}">
                                                    {{ $item->session_shift->type_of_shift }}
                                                </span>
                                            @elseif($item->clock_in)
                                                <span class="text-muted small">Undetected</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->clock_in  ? $item->clock_in->format('H:i')  : '-' }}</td>
                                        <td>{{ $item->clock_out ? $item->clock_out->format('H:i') : '-' }}</td>
                                        <td>{{ $item->total_hours ? number_format($item->total_hours, 2) : '-' }}</td>
                                        <td><span class="badge {{ $badgeClass }} px-2 py-1">{{ $item->status }}</span></td>
                                        <td>{{ $item->remarks ? \Illuminate\Support\Str::limit($item->remarks, 20) : '-' }}</td>
                                        <td class="pe-3">
                                            @can('hr.attendance.edit')
                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 px-3 py-1"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-employee-id="{{ $item->employee->id }}"
                                                data-date="{{ $item->date->format('Y-m-d') }}"
                                                data-clock-in="{{ $item->clock_in  ? $item->clock_in->format('H:i')  : '' }}"
                                                data-clock-out="{{ $item->clock_out ? $item->clock_out->format('H:i') : '' }}"
                                                data-status="{{ $item->status }}"
                                                data-session-shift-id="{{ $item->session_shift_id ?? '' }}"
                                                data-remarks="{{ $item->remarks }}">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                                                <h6>No Attendance Data Found</h6>
                                                @if(request()->anyFilled(['start_date', 'end_date', 'employee_id', 'search']))
                                                    <p class="small">Try adjusting your filters</p>
                                                    <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-primary px-4">
                                                        <i class="fas fa-times me-1"></i>Clear Filters
                                                    </a>
                                                @else
                                                    <p class="small">Start by importing data</p>
                                                    <button type="button" class="btn btn-sm btn-outline-primary px-4" data-bs-toggle="modal" data-bs-target="#importModal">
                                                        <i class="fas fa-upload me-1"></i>Import Data
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($attendancesPaginated->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $attendancesPaginated->firstItem() }} to {{ $attendancesPaginated->lastItem() }} of {{ $attendancesPaginated->total() }} entries
                        </div>
                        {{ $attendancesPaginated->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
                @endif
            </div>

            <!-- ═══ MOBILE CARDS (< md) ═══ -->
            <div class="d-md-none">
                @forelse($attendancesPaginated as $item)
                    @php
                        $badgeClass = [
                            'Present'         => 'bg-success',
                            'Late'            => 'bg-warning text-dark',
                            'Excused'         => 'bg-info',
                            'Sick Leave'      => 'bg-info',
                            'Annual Leave'    => 'bg-primary',
                            'Less Hours'      => 'bg-orange text-white',
                            'Late, Less Hours'=> 'bg-warning text-dark',
                            'Early Leave'     => 'bg-indigo text-white',
                            'Alpha'           => 'bg-danger',
                        ][$item->status] ?? 'bg-secondary';
                    @endphp
                    <div class="attlog-mobile-card mb-2"
                         id="row-{{ $item->employee->id }}-{{ $item->date->format('Y-m-d') }}">

                        <!-- Top row: date + status + action -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted" style="font-size:0.72rem;font-weight:600;letter-spacing:.02em;">
                                <i class="fas fa-calendar-day me-1"></i>{{ $item->date->format('d M Y') }}
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size:0.68rem;">
                                    {{ $item->status }}
                                </span>
                                @can('hr.attendance.edit')
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary border-0 px-2 py-0"
                                    style="font-size:0.75rem;line-height:1.6;"
                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-employee-id="{{ $item->employee->id }}"
                                    data-date="{{ $item->date->format('Y-m-d') }}"
                                    data-clock-in="{{ $item->clock_in  ? $item->clock_in->format('H:i')  : '' }}"
                                    data-clock-out="{{ $item->clock_out ? $item->clock_out->format('H:i') : '' }}"
                                    data-status="{{ $item->status }}"
                                    data-session-shift-id="{{ $item->session_shift_id ?? '' }}"
                                    data-remarks="{{ $item->remarks }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                            </div>
                        </div>

                        <!-- Employee name + NIK -->
                        <div class="fw-semibold" style="font-size:0.88rem;color:#1e293b;">
                            {{ $item->employee->name }}
                        </div>
                        <div class="text-muted mb-2" style="font-size:0.7rem;">
                            {{ $item->employee->employee_no }}
                        </div>

                        <!-- Clock In / Out / Hours -->
                        <div class="d-flex align-items-center gap-3" style="font-size:0.78rem;">
                            <span>
                                <i class="fas fa-sign-in-alt me-1" style="color:#16a34a;font-size:0.7rem;"></i>
                                {{ $item->clock_in ? $item->clock_in->format('H:i') : '—' }}
                            </span>
                            <span>
                                <i class="fas fa-sign-out-alt me-1" style="color:#dc2626;font-size:0.7rem;"></i>
                                {{ $item->clock_out ? $item->clock_out->format('H:i') : '—' }}
                            </span>
                            @if($item->total_hours)
                            <span class="ms-auto text-muted" style="font-size:0.72rem;">
                                <i class="fas fa-clock me-1 opacity-50"></i>{{ number_format($item->total_hours, 2) }}h
                            </span>
                            @endif
                        </div>

                        <!-- Shift & Remarks -->
                        @if($item->session_shift || $item->remarks)
                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            @if($item->session_shift)
                                <span class="badge bg-soft-primary text-primary px-2 py-1 fw-semibold" style="font-size:0.65rem;"
                                      title="{{ $item->session_shift->start_time }} – {{ $item->session_shift->end_time }}">
                                    {{ $item->session_shift->type_of_shift }}
                                </span>
                            @endif
                            @if($item->remarks)
                                <span class="text-muted" style="font-size:0.7rem;">
                                    <i class="fas fa-comment-alt me-1 opacity-50"></i>{{ \Illuminate\Support\Str::limit($item->remarks, 35) }}
                                </span>
                            @endif
                        </div>
                        @endif

                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                        <h6>No Attendance Data Found</h6>
                        @if(request()->anyFilled(['start_date', 'end_date', 'employee_id', 'search']))
                            <p class="small">Try adjusting your filters</p>
                            <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-primary px-4">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        @else
                            <p class="small">Start by importing data</p>
                            <button type="button" class="btn btn-sm btn-outline-primary px-4" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-upload me-1"></i>Import Data
                            </button>
                        @endif
                    </div>
                @endforelse

                @if($attendancesPaginated->hasPages())
                <div class="py-3">
                    <div class="text-muted small text-center mb-2">
                        Showing {{ $attendancesPaginated->firstItem() }}–{{ $attendancesPaginated->lastItem() }} of {{ $attendancesPaginated->total() }}
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $attendancesPaginated->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Import Attendance Logs</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label for="file" class="form-label small">File (xlsx, xls, csv)</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
                        <div class="form-text small">
                            Columns: <strong>Name, Date, Clock In, Clock Out</strong>. Inactive employees will be ignored.<br>
                            <span class="text-warning">Note: .xls files will be automatically converted if possible.</span>
                        </div>
                    </div>
                    <div id="importProgress" class="progress d-none" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Processing...</div>
                    </div>
                    <div id="importResult" class="mt-2 small"></div>
                    <div id="failedRowsContainer" class="mt-3 d-none">
                        <h6 class="small fw-bold">Failed Rows:</h6>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Error</th>
                                    </tr>
                                </thead>
                                <tbody id="failedRowsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="importBtn">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Edit Attendance</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <input type="hidden" name="date" id="edit_date">
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <label class="form-label small">Employee</label>
                        <input type="text" class="form-control form-control-sm" id="edit_employee_name" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Date</label>
                        <input type="text" class="form-control form-control-sm" id="edit_date_display" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label for="edit_clock_in" class="form-label small">Clock In</label>
                            <input type="time" name="clock_in" id="edit_clock_in" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="edit_clock_out" class="form-label small">Clock Out</label>
                            <input type="time" name="clock_out" id="edit_clock_out" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="edit_session_shift_id" class="form-label small">Session Shift</label>
                        <select name="session_shift_id" id="edit_session_shift_id" class="form-select form-select-sm">
                            <option value="">— Auto-detect from clock in —</option>
                            @foreach($sessionShifts as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->type_of_shift }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:0.68rem;">Leave blank to auto-detect from clock in time.</div>
                    </div>
                    <div class="mb-2">
                        <label for="edit_status" class="form-label small">Status</label>
                        <select name="status" id="edit_status" class="form-select form-select-sm">
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Less Hours">Less Hours</option>
                            <option value="Early Leave">Early Leave (Approved)</option>
                            <option value="Excused">Excused</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Annual Leave">Annual Leave</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="edit_remarks" class="form-label small">Remarks</label>
                        <textarea name="remarks" id="edit_remarks" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div id="editResult" class="small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="editBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ── Flatpickr compact ── */
    .flatpickr-calendar {
        font-size: 0.72rem !important;
        width: auto !important;
    }
    .flatpickr-months { padding: 4px 0 2px; }
    .flatpickr-month  { height: 28px !important; }
    .flatpickr-current-month {
        font-size: 0.8rem !important;
        padding-top: 4px !important;
    }
    .flatpickr-current-month .numInputWrapper { width: 4.5ch !important; }
    .flatpickr-weekday  { font-size: 0.65rem !important; }
    .flatpickr-day {
        max-width: 28px !important;
        height: 26px !important;
        line-height: 26px !important;
        font-size: 0.7rem !important;
        border-radius: 4px !important;
        margin: 1px !important;
    }
    .flatpickr-days .dayContainer {
        min-width: 210px !important;
        max-width: 210px !important;
        width: 210px !important;
    }
    .flatpickr-rContainer   { display: inline-block; }
    .flatpickr-innerContainer { gap: 8px; }
    .flatpickr-day.inRange {
        background: rgba(79,70,229,.12) !important;
        border-color: transparent !important;
        color: #333 !important;
        box-shadow: -3px 0 0 rgba(79,70,229,.12), 3px 0 0 rgba(79,70,229,.12) !important;
    }
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: #4f46e5 !important;
        border-color: #4f46e5 !important;
    }
    .flatpickr-day:hover { background: rgba(79,70,229,.2) !important; }

    /* ── Desktop table ── */
    .table-number {
        display: inline-block;
        width: 28px; height: 28px; line-height: 28px;
        background: #eef2ff; color: #4f46e5;
        border-radius: 6px; font-weight: 600;
        font-size: 0.78rem; text-align: center;
        transition: all .2s;
    }
    tr:hover .table-number { background: #4f46e5; color: #fff; }
    .table th {
        font-weight: 600; font-size: 0.7rem;
        text-transform: uppercase; letter-spacing: .05em;
        color: #64748b; padding: .65rem .5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .table td { padding: .65rem .5rem; border-bottom: 1px solid #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    /* ── Mobile cards ── */
    .attlog-mobile-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }

    /* ── General ── */
    .btn-sm           { font-size: 0.75rem; }
    .badge            { font-weight: 500; font-size: 0.7rem; }
    .form-control-sm,
    .form-select-sm   { font-size: 0.75rem; }
    .alert            { border-radius: 0.25rem; }

    /* ── Mobile pagination center ── */
    @media (max-width: 767.98px) {
        .pagination { justify-content: center; flex-wrap: wrap; }
        .page-item .page-link { font-size: 0.75rem; padding: .25rem .5rem; }
    }
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Import
    $('#importForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#importBtn'), $progress = $('#importProgress'),
            $result = $('#importResult'), $failedContainer = $('#failedRowsContainer'),
            $failedBody = $('#failedRowsBody');
        $btn.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.html('');
        $failedContainer.addClass('d-none');
        $failedBody.empty();
        $.ajax({
            url: "{{ route('attendance-logs.import.store') }}",
            type: 'POST', data: new FormData(this),
            processData: false, contentType: false,
            success: function (response) {
                $progress.addClass('d-none');
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">' + response.message + '</div>');
                setTimeout(function () {
                    $('#importModal').modal('hide');
                    if (response.redirect_url) window.location.href = response.redirect_url;
                    else location.reload();
                }, 1500);
            },
            error: function (xhr) {
                $progress.addClass('d-none');
                if (xhr.responseJSON && xhr.responseJSON.failed_rows && xhr.responseJSON.failed_rows.length > 0) {
                    $.each(xhr.responseJSON.failed_rows, function (i, item) {
                        var r = item.row;
                        $failedBody.append('<tr><td>' + (r.name||'-') + '</td><td>' + (r.date||'-') + '</td><td>' +
                            (r.clock_in||'-') + '</td><td>' + (r.clock_out||'-') + '</td><td class="text-danger">' + item.error + '</td></tr>');
                    });
                    $failedContainer.removeClass('d-none');
                    $result.html('<div class="alert alert-warning py-1 px-2 mb-0">' + (xhr.responseJSON.message || 'Import completed with errors.') + '</div>');
                } else {
                    $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Upload failed.') + '</div>');
                }
                $btn.prop('disabled', false);
            }
        });
    });

    $('#importModal').on('hidden.bs.modal', function () {
        $('#importForm')[0].reset();
        $('#importResult').empty();
        $('#importProgress').addClass('d-none');
        $('#failedRowsContainer').addClass('d-none');
        $('#failedRowsBody').empty();
        $('#importBtn').prop('disabled', false);
    });

    // Auto-submit: select dropdowns
    $('.auto-submit').on('change', function () {
        $('#attlog-filter-form').submit();
    });

    // Auto-submit: search with debounce
    var searchTimer;
    $('#filter-search').on('input', function () {
        clearTimeout(searchTimer);
        var $spinner = $('#search-spinner');
        $spinner.removeClass('d-none');
        searchTimer = setTimeout(function () {
            $('#attlog-filter-form').submit();
        }, 500);
    });

    // Edit modal
    $('#editModal').on('show.bs.modal', function (event) {
        var btn = $(event.relatedTarget);
        var employeeId = btn.data('employee-id'), date = btn.data('date');
        $(this).find('#edit_employee_id').val(employeeId);
        $(this).find('#edit_date').val(date);
        var row = $('#row-' + employeeId + '-' + date);
        // desktop row: td:eq(3); mobile card: .fw-semibold text
        var empName = row.is('tr') ? row.find('td:eq(3)').text().trim()
                                   : row.find('.fw-semibold').first().text().trim();
        $(this).find('#edit_employee_name').val(empName);
        $(this).find('#edit_date_display').val(date);
        $(this).find('#edit_clock_in').val(btn.data('clock-in'));
        $(this).find('#edit_clock_out').val(btn.data('clock-out'));
        $(this).find('#edit_session_shift_id').val(btn.data('session-shift-id') || '');
        $(this).find('#edit_status').val(btn.data('status'));
        $(this).find('#edit_remarks').val(btn.data('remarks'));
    });

    $('#editForm').on('submit', function (e) {
        e.preventDefault();
        var employeeId = $('#edit_employee_id').val(), date = $('#edit_date').val();
        var $btn = $('#editBtn'), $result = $('#editResult');
        $btn.prop('disabled', true);
        $result.html('<div class="text-info small">Saving…</div>');
        $.ajax({
            url: "{{ route('attendance-logs.update', ['employeeId' => ':eid', 'date' => ':d']) }}"
                .replace(':eid', employeeId).replace(':d', date),
            type: 'POST', data: $(this).serialize(),
            success: function () {
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">Saved.</div>');
                setTimeout(function () { $('#editModal').modal('hide'); location.reload(); }, 1000);
            },
            error: function (xhr) {
                $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save.') + '</div>');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#editModal').on('hidden.bs.modal', function () {
        $('#editResult').empty();
        $('#editBtn').prop('disabled', false);
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    var startVal = document.getElementById('input-start-date').value;
    var endVal   = document.getElementById('input-end-date').value;
    var isMobile = window.innerWidth < 768;

    flatpickr('#attlog-date-range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        showMonths: isMobile ? 1 : 2,
        defaultDate: (startVal && endVal) ? [startVal, endVal]
                   : (startVal ? [startVal] : []),
        onChange: function (dates) {
            if (dates.length === 0) {
                document.getElementById('input-start-date').value = '';
                document.getElementById('input-end-date').value   = '';
            } else if (dates.length === 1) {
                document.getElementById('input-start-date').value = flatpickr.formatDate(dates[0], 'Y-m-d');
                document.getElementById('input-end-date').value   = '';
            } else {
                document.getElementById('input-start-date').value = flatpickr.formatDate(dates[0], 'Y-m-d');
                document.getElementById('input-end-date').value   = flatpickr.formatDate(dates[1], 'Y-m-d');
                document.getElementById('attlog-filter-form').submit();
            }
        },
        onClose: function (dates) {
            if (dates.length === 1) {
                document.getElementById('input-start-date').value = flatpickr.formatDate(dates[0], 'Y-m-d');
                document.getElementById('input-end-date').value   = '';
                document.getElementById('attlog-filter-form').submit();
            }
        }
    });
})();
</script>
@endpush
