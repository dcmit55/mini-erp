@extends('layouts.app')

@section('title', 'Timing Approval')

@section('content')
    <div class="container-fluid">
        {{-- Session Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-check-circle"></i> Timing Approval
            </h1>
        </div>

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Approval
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock-history fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Paused Sessions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['paused'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-pause-circle fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['approved_today'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['rejected_today'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-x-circle fa-2x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="approval_status">Approval Status</label>
                            <select name="approval_status" id="approval_status" class="form-control">
                                <option value="">All Status</option>
                                @foreach ($availableStatuses as $s)
                                    <option value="{{ $s }}" {{ $s === 'pending' ? 'selected' : '' }}>
                                        {{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="project_id">Project</label>
                            <select name="project_id" id="project_id" class="form-control select2">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="department_id">Department</label>
                            <select name="department_id" id="department_id" class="form-control select2">
                                <option value="">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Date Range</label>
                            <div class="position-relative">
                                <input type="text" id="tanggal-range-picker" class="form-control"
                                    placeholder="All dates" readonly style="cursor:pointer;">
                                <input type="hidden" id="date_from" name="date_from">
                                <input type="hidden" id="date_to" name="date_to">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Timing Sessions</h6>
                    <div>
                        @can('production.timing.edit')
                        <button type="button" id="btnBulkApprove" class="btn btn-success btn-sm" disabled>
                            <i class="bi bi-check-circle"></i> Bulk Approve
                        </button>
                        <button type="button" id="btnBulkReject" class="btn btn-danger btn-sm" disabled>
                            <i class="bi bi-x-circle"></i> Bulk Reject
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="timingApprovalTable" width="100%"
                        cellspacing="0">
                        <thead>
                            <tr>
                                <th width="3%"><input type="checkbox" id="selectAll"></th>
                                <th width="10%">Date</th>
                                <th width="15%">Employee</th>
                                <th width="15%">Project / Job Order</th>
                                <th width="10%">Work Details</th>
                                <th width="10%">Duration</th>
                                <th width="10%">Output</th>
                                <th width="10%">Status</th>
                                <th width="12%">Approved By</th>
                                <th width="5%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Rejection Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Timing Session</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" id="reject_timing_id" name="timing_id">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="reason" id="rejection_reason" class="form-control" rows="4" required
                                placeholder="Please provide a clear reason for rejection..."></textarea>
                            <div class="form-text">Max 500 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Rejection Modal --}}
    <div class="modal fade" id="bulkRejectModal" tabindex="-1" aria-labelledby="bulkRejectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkRejectModalLabel">Bulk Reject Timing Sessions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="bulkRejectForm">
                    <div class="modal-body">
                        <p>You are about to reject <strong id="bulkRejectCount"></strong> timing session(s).</p>
                        <div class="mb-3">
                            <label for="bulk_rejection_reason" class="form-label">Rejection Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="reason" id="bulk_rejection_reason" class="form-control" rows="4" required
                                placeholder="Please provide a clear reason for rejection..."></textarea>
                            <div class="form-text">Max 500 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Reject All Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- View Reason Modal --}}
    <div class="modal fade" id="viewReasonModal" tabindex="-1" aria-labelledby="viewReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewReasonModalLabel">Rejection Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="viewReasonText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Fix Select2 with Bootstrap 5 */
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
        }

        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dee2e6;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // Auto-dismiss session alerts after 5 seconds
            setTimeout(function() {
                $('.alert').not('.alert-permanent').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'Select an option'
            });

            // --- Filter persistence via sessionStorage ---
            const FILTER_KEY = 'timingApprovalFilters';

            function saveFilters() {
                const filters = {
                    approval_status: $('#approval_status').val(),
                    project_id: $('#project_id').val(),
                    department_id: $('#department_id').val(),
                    date_from: $('#date_from').val(),
                    date_to: $('#date_to').val(),
                };
                sessionStorage.setItem(FILTER_KEY, JSON.stringify(filters));
            }

            function restoreFilters() {
                const raw = sessionStorage.getItem(FILTER_KEY);
                if (!raw) return;
                try {
                    const filters = JSON.parse(raw);
                    if (filters.approval_status !== undefined) {
                        $('#approval_status').val(filters.approval_status);
                    }
                    if (filters.project_id) {
                        $('#project_id').val(filters.project_id).trigger('change');
                    }
                    if (filters.department_id) {
                        $('#department_id').val(filters.department_id).trigger('change');
                    }
                    if (filters.date_from) $('#date_from').val(filters.date_from);
                    if (filters.date_to) $('#date_to').val(filters.date_to);
                } catch (e) {}
            }

            // Restore filters before DataTable initialises
            restoreFilters();

            // ── Flatpickr date range picker ──────────────────────────────────
            const dateFromVal = $('#date_from').val();
            const dateToVal = $('#date_to').val();

            const tanggalPicker = flatpickr('#tanggal-range-picker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: false,
                showMonths: 2,
                defaultDate: (dateFromVal && dateToVal) ? [dateFromVal, dateToVal] : (dateFromVal ? [
                    dateFromVal
                ] : []),
                onChange: function(selectedDates) {
                    if (selectedDates.length === 0) {
                        $('#date_from').val('');
                        $('#date_to').val('');
                    } else if (selectedDates.length === 1) {
                        $('#date_from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#date_to').val('');
                    } else {
                        $('#date_from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#date_to').val(flatpickr.formatDate(selectedDates[1], 'Y-m-d'));
                        saveFilters();
                        table.ajax.reload();
                    }
                },
                onClose: function(selectedDates) {
                    if (selectedDates.length === 1) {
                        $('#date_from').val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                        $('#date_to').val('');
                        saveFilters();
                        table.ajax.reload();
                    }
                }
            });
            // --- End filter persistence ---

            // Initialize DataTable
            var table = $('#timingApprovalTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('timing-approval.index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.approval_status = $('#approval_status').val();
                        d.project_id = $('#project_id').val();
                        d.department_id = $('#department_id').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    },
                    dataSrc: function(json) {
                        console.log('DataTable Response:', json);
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        alert('Error loading data: ' + error);
                    }
                },
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal_formatted',
                        searchable: true
                    },
                    {
                        data: 'employee_info',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'project_info',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'work_details',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'duration_info',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'output_info',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'approval_status_badge',
                        searchable: true
                    },
                    {
                        data: 'approver_info',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                drawCallback: function() {
                    updateBulkButtons();
                }
            });

            // Auto-filter on change — save to sessionStorage
            $('#approval_status, #project_id, #department_id, #date_from, #date_to').on('change', function() {
                saveFilters();
                table.ajax.reload();
            });

            // Reset filters button
            $('#btnResetFilters').on('click', function() {
                sessionStorage.removeItem(FILTER_KEY);
                $('#filterForm')[0].reset();
                $('#approval_status').val('pending'); // Set back to default
                $('.select2').val(null).trigger('change');
                $('#date_from').val('');
                $('#date_to').val('');
                tanggalPicker.clear();
                table.ajax.reload();
            });

            // Select all checkbox
            $('#selectAll').on('change', function() {
                $('.timing-checkbox').prop('checked', this.checked);
                updateBulkButtons();
            });

            // Individual checkbox
            $(document).on('change', '.timing-checkbox', function() {
                updateBulkButtons();
                var totalCheckboxes = $('.timing-checkbox').length;
                var checkedCheckboxes = $('.timing-checkbox:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Update bulk button states
            function updateBulkButtons() {
                var checkedCount = $('.timing-checkbox:checked').length;
                $('#btnBulkApprove, #btnBulkReject').prop('disabled', checkedCount === 0);
            }

            // Approve single
            $(document).on('click', '.btn-approve', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Approve Timing?',
                    text: 'Are you sure you want to approve this timing session?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/timing-approval/" + id + "/approve",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                var errorMsg = (xhr.responseJSON && xhr.responseJSON
                                        .message) ? xhr
                                    .responseJSON.message : 'Error approving timing';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMsg
                                });
                            }
                        });
                    }
                });
            });

            // Reject single
            $(document).on('click', '.btn-reject', function() {
                var id = $(this).data('id');
                $('#reject_timing_id').val(id);
                $('#rejection_reason').val('');
                var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
                rejectModal.show();
            });

            // Reject form submit
            $('#rejectForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#reject_timing_id').val();
                var reason = $('#rejection_reason').val();

                $.ajax({
                    url: "/timing-approval/" + id + "/reject",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        reason: reason
                    },
                    success: function(response) {
                        bootstrap.Modal.getInstance(document.getElementById('rejectModal'))
                            .hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Rejected!',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Error rejecting timing';
                        showAlert('error', errorMsg);
                    }
                });
            });

            // Bulk approve
            $('#btnBulkApprove').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one timing session'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Bulk Approve?',
                    text: 'Are you sure you want to approve ' + ids.length + ' timing session(s)?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve All!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('timing-approval.bulk-approve') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                timing_ids: ids
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    text: response.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload();
                                $('#selectAll').prop('checked', false);
                            },
                            error: function(xhr) {
                                var errorMsg = (xhr.responseJSON && xhr.responseJSON
                                        .message) ? xhr
                                    .responseJSON.message : 'Error in bulk approval';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMsg
                                });
                            }
                        });
                    }
                });
            });

            // Bulk reject
            $('#btnBulkReject').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) {
                    showAlert('warning', 'Please select at least one timing session');
                    return;
                }

                $('#bulkRejectCount').text(ids.length);
                $('#bulk_rejection_reason').val('');
                $('#bulkRejectModal').modal('show');
            });

            // Bulk reject form submit
            $('#bulkRejectForm').on('submit', function(e) {
                e.preventDefault();
                var ids = getSelectedIds();
                var reason = $('#bulk_rejection_reason').val();

                $.ajax({
                    url: "{{ route('timing-approval.bulk-reject') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        timing_ids: ids,
                        reason: reason
                    },
                    success: function(response) {
                        bootstrap.Modal.getInstance(document.getElementById('bulkRejectModal'))
                            .hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Rejected!',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                        $('#selectAll').prop('checked', false);
                    },
                    error: function(xhr) {
                        var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Error in bulk rejection';
                        showAlert('error', errorMsg);
                    }
                });
            });

            // View reason
            $(document).on('click', '.btn-view-reason', function() {
                var reason = $(this).data('reason');
                $('#viewReasonText').text(reason || 'No reason provided');
                var viewReasonModal = new bootstrap.Modal(document.getElementById('viewReasonModal'));
                viewReasonModal.show();
            });

            // Helper: Get selected IDs
            function getSelectedIds() {
                var ids = [];
                $('.timing-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                return ids;
            }

            // Helper: Show alert
            function showAlert(type, message) {
                var alertClass = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' :
                    'alert-danger';
                var alertHtml = '<div class="alert ' + alertClass +
                    ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';

                $('.container-fluid').prepend(alertHtml);

                setTimeout(function() {
                    $('.alert').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    </script>
@endpush
