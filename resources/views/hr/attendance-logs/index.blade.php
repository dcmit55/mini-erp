@extends('layouts.app')

@section('title', 'Attendance Daily')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Attendance Daily</h5>
                    <p class="text-muted small mb-0">Daily attendance records</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                    <a href="{{ route('attendance-logs.export') }}?{{ http_build_query(request()->except('page')) }}" class="btn btn-sm btn-outline-success px-3">
                        <i class="fas fa-download me-1"></i> Export
                    </a>
                </div>
            </div>

            <!-- Filter + Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('attendance-logs.index') }}">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">Date / Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:0.75rem;color:#6b7280;">End Date <span class="text-muted">(optional)</span></label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="employee_id" class="form-select form-select-sm">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_no }} - {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Search name or NIK..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(request()->filled('search'))
                                        <a href="{{ route('attendance-logs.index', array_merge(request()->except(['search', 'page']))) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary px-3">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                @if(request()->anyFilled(['start_date', 'end_date', 'employee_id', 'search']))
                                    <a href="{{ route('attendance-logs.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                @endif
                                <a href="{{ route('attendance-logs.index', ['all' => 1]) }}" class="btn btn-sm btn-outline-info px-3 {{ request()->has('all') ? 'active' : '' }}" title="Show all data">
                                    <i class="fas fa-list"></i> All
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Bar -->
            @if(request()->has('all'))
                <div class="alert alert-secondary py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-eye me-2"></i> Showing all data. 
                    <a href="{{ route('attendance-logs.index') }}" class="alert-link ms-2">Back to today</a>.
                </div>
            @elseif(request()->filled('start_date') && request()->filled('end_date'))
                <div class="alert alert-info py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-calendar-alt me-2"></i> Filtering from {{ request('start_date') }} to {{ request('end_date') }}.
                </div>
            @elseif($latestImportSource)
                <div class="alert alert-info py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-clock me-2"></i> Showing latest import: <strong>{{ $latestImportSource }}</strong>. 
                    <a href="{{ route('attendance-logs.index', ['all' => 1]) }}" class="alert-link ms-2">View all</a>.
                </div>
            @else
                <div class="alert alert-warning py-2 small d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> No data yet. Please import first.
                </div>
            @endif

            <!-- Tabel -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="border-0 ps-4" style="width: 60px;">No</th>
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
                                        <td>{{ $item->clock_in ? $item->clock_in->format('H:i') : '-' }}</td>
                                        <td>{{ $item->clock_out ? $item->clock_out->format('H:i') : '-' }}</td>
                                        <td>{{ $item->total_hours ? number_format($item->total_hours, 2) : '-' }}</td>
                                        <td>
                                            @php
                                                $badgeClass = [
                                                    'Present' => 'bg-success',
                                                    'Late' => 'bg-warning text-dark',
                                                    'Excused' => 'bg-info',
                                                    'Sick Leave' => 'bg-info',
                                                    'Annual Leave' => 'bg-primary',
                                                    'Alpha' => 'bg-danger',
                                                ][$item->status] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-3 py-1">{{ $item->status }}</span>
                                        </td>
                                        <td>{{ $item->remarks ? \Illuminate\Support\Str::limit($item->remarks, 20) : '-' }}</td>
                                        <td class="pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 px-3 py-1"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-employee-id="{{ $item->employee->id }}"
                                                data-date="{{ $item->date->format('Y-m-d') }}"
                                                data-clock-in="{{ $item->clock_in ? $item->clock_in->format('H:i') : '' }}"
                                                data-clock-out="{{ $item->clock_out ? $item->clock_out->format('H:i') : '' }}"
                                                data-status="{{ $item->status }}"
                                                data-remarks="{{ $item->remarks }}">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $attendancesPaginated->firstItem() }} to {{ $attendancesPaginated->lastItem() }} of {{ $attendancesPaginated->total() }} entries
                        </div>
                        <div>
                            {{ $attendancesPaginated->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Error</th>
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
                        <div class="col-md-6 mb-2">
                            <label for="edit_clock_in" class="form-label small">Clock In</label>
                            <input type="time" name="clock_in" id="edit_clock_in" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="edit_clock_out" class="form-label small">Clock Out</label>
                            <input type="time" name="clock_out" id="edit_clock_out" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="edit_status" class="form-label small">Status</label>
                        <select name="status" id="edit_status" class="form-select form-select-sm">
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
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

<style>
    .table-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.2s;
    }
    
    tr:hover .table-number {
        background-color: #4f46e5;
        color: white;
    }

    .table th {
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 0.75rem 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .table td {
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .btn-sm {
        font-size: 0.75rem;
    }

    .badge {
        font-weight: 500;
        font-size: 0.7rem;
    }

    .form-control-sm, .form-select-sm {
        font-size: 0.75rem;
    }

    .alert {
        border-radius: 0.25rem;
    }
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Import form handler
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#importBtn');
        var $progress = $('#importProgress');
        var $result = $('#importResult');
        var $failedContainer = $('#failedRowsContainer');
        var $failedBody = $('#failedRowsBody');

        $btn.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.html('');
        $failedContainer.addClass('d-none');
        $failedBody.empty();

        $.ajax({
            url: "{{ route('attendance-logs.import.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $progress.addClass('d-none');
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">' + response.message + '</div>');
                setTimeout(function() {
                    $('#importModal').modal('hide');
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        location.reload();
                    }
                }, 1500);
            },
            error: function(xhr) {
                $progress.addClass('d-none');
                if (xhr.responseJSON && xhr.responseJSON.failed_rows && xhr.responseJSON.failed_rows.length > 0) {
                    var failedRows = xhr.responseJSON.failed_rows;
                    $.each(failedRows, function(index, item) {
                        var row = item.row;
                        $failedBody.append('<tr>' +
                            '<td>' + (row.name || '-') + '</td>' +
                            '<td>' + (row.date || '-') + '</td>' +
                            '<td>' + (row.clock_in || '-') + '</td>' +
                            '<td>' + (row.clock_out || '-') + '</td>' +
                            '<td class="text-danger">' + item.error + '</td>' +
                            '</tr>');
                    });
                    $failedContainer.removeClass('d-none');
                    var message = xhr.responseJSON.message || 'Import completed with errors.';
                    $result.html('<div class="alert alert-warning py-1 px-2 mb-0">' + message + '</div>');
                } else {
                    var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload failed.';
                    $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + errorMsg + '</div>');
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

    // Edit modal handler
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var employeeId = button.data('employee-id');
        var date = button.data('date');
        var clockIn = button.data('clock-in');
        var clockOut = button.data('clock-out');
        var status = button.data('status');
        var remarks = button.data('remarks');

        var modal = $(this);
        modal.find('#edit_employee_id').val(employeeId);
        modal.find('#edit_date').val(date);
        var row = $('#row-' + employeeId + '-' + date);
        var empName = row.find('td:eq(3)').text();
        modal.find('#edit_employee_name').val(empName);
        modal.find('#edit_date_display').val(date);
        modal.find('#edit_clock_in').val(clockIn);
        modal.find('#edit_clock_out').val(clockOut);
        modal.find('#edit_status').val(status);
        modal.find('#edit_remarks').val(remarks);
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var employeeId = $('#edit_employee_id').val();
        var date = $('#edit_date').val();
        var formData = $(this).serialize();
        var $btn = $('#editBtn');
        var $result = $('#editResult');

        $btn.prop('disabled', true);
        $result.html('<div class="text-info small">Saving...</div>');

        $.ajax({
            url: "{{ route('attendance-logs.update', ['employeeId' => ':employeeId', 'date' => ':date']) }}"
                .replace(':employeeId', employeeId)
                .replace(':date', date),
            type: "POST",
            data: formData,
            success: function(response) {
                $result.html('<div class="alert alert-success py-1 px-2 mb-0">Data saved successfully.</div>');
                setTimeout(function() {
                    $('#editModal').modal('hide');
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to save.';
                $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' + errorMsg + '</div>');
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
@endpush