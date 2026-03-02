@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="bi bi-calendar-plus gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Leave Request List</h2>
                    </div>

                    <!-- Spacer to push buttons to the right -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('leave_requests.create') }}" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Leave Request
                        </a>
                        {{-- <a href="{{ route('leave_requests.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </a> --}}
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="employee_filter" class="form-select form-select-sm select2">
                                <option value="">All Employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="department_filter" class="form-select form-select-sm select2">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="type_filter" class="form-select form-select-sm select2">
                                <option value="">All Leave Types</option>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type }}">{{ $leaveTypeLabels[$type] ?? $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="approval_status_filter" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="both_approved">Both Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" id="custom-search" class="form-control form-control-sm"
                                    placeholder="Search...">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="datatable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Type Leave</th>
                                <th>Reason</th>
                                <th>Approval 1 (HR)</th>
                                <th>Approval 2 (Super Admin)</th>
                                <th>Submitted On</th>
                                @if ($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr']))
                                    <th>Actions</th>
                                @endif
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
@endsection

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        #filter-form {
            background: #f8f9fa;
            padding: .75rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }

        /* Pagination styling - sama seperti Goods Out */
        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-color: #6c757d;
            --bs-pagination-bg: #fff;
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: #dee2e6;
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: #495057;
            --bs-pagination-hover-bg: #e9ecef;
            --bs-pagination-hover-border-color: #dee2e6;
            --bs-pagination-focus-color: #495057;
            --bs-pagination-focus-bg: #e9ecef;
            --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: #8F12FE;
            --bs-pagination-active-border-color: #4A25AA;
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: #fff;
            --bs-pagination-disabled-border-color: #dee2e6;
        }

        .page-link {
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            box-shadow: 0 2px 4px rgba(143, 18, 254, 0.3);
        }

        /* DataTables footer styling */
        .datatables-footer-row {
            border-top: 1px solid #eee;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .datatables-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .datatables-footer-row {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .datatables-left {
                flex-direction: column !important;
                gap: 0.5rem;
            }

            .vr-divider {
                display: none;
            }

            .dataTables_paginate {
                justify-content: center !important;
            }
        }

        /* Tooltips */
        .tooltip {
            z-index: 9999 !important;
        }

        .tooltip-inner {
            max-width: 200px;
            padding: 0.3rem 0.6rem;
            font-size: 0.775rem;
            line-height: 1.2;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable with server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('leave_requests.index') }}",
                    data: function(d) {
                        d.employee_filter = $('#employee_filter').val();
                        d.department_filter = $('#department_filter').val();
                        d.type_filter = $('#type_filter').val();
                        d.approval_status_filter = $('#approval_status_filter').val();
                        d.submitted_at_filter = $('#submitted_at_filter').val();
                        d.custom_search = $('#custom-search').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'employee_name',
                        name: 'employee_name'
                    },
                    {
                        data: 'department',
                        name: 'department'
                    },
                    {
                        data: 'position',
                        name: 'position'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date'
                    },
                    {
                        data: 'duration',
                        name: 'duration',
                        orderable: false
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'reason',
                        name: 'reason'
                    },
                    {
                        data: 'approval_1',
                        name: 'approval_1',
                        orderable: false
                    },
                    {
                        data: 'approval_2',
                        name: 'approval_2',
                        orderable: false
                    },
                    {
                        data: 'submitted_on',
                        name: 'submitted_on'
                    },
                    @if ($isAuthenticated && in_array($userRole, ['super_admin', 'admin_hr']))
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false
                        }
                    @endif
                ],
                order: [
                    []
                ], // Sort by submitted date by default
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No leave requests submitted yet</div>',
                    zeroRecords: '<div class="text-muted py-2">No matching records found</div>',
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                dom: 't<' +
                    '"row datatables-footer-row align-items-center"' +
                    '<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i>' +
                    '<"col-md-5 dataTables_paginate justify-content-end"p>' +
                    '>',
                responsive: true,
                stateSave: false,
                drawCallback: function() {
                    // Reinitialize tooltips after table redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // AJAX Approval Update - Approval 1 (HR)
            $(document).on('change', '.approval-1-select', function(e) {
                e.preventDefault();

                const $select = $(this);
                const leaveId = $select.data('id');
                const newStatus = $select.val();
                const oldStatus = $select.data('old-status');
                const employeeName = $select.data('employee');
                const leaveType = $select.data('type');
                const approval2Status = $select.data('approval2');

                // Determine confirmation message
                let confirmMsg = 'Update Approval 1 status?';
                if (newStatus === 'approved' && approval2Status === 'approved' && leaveType === 'ANNUAL') {
                    confirmMsg = 'This will deduct employee leave balance. Continue?';
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, update it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Updating...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: `/leave_requests/${leaveId}/approval`,
                            method: 'POST',
                            data: {
                                approval_1: newStatus,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close SweetAlert immediately
                                    Swal.close();

                                    // Update old status
                                    $select.data('old-status', newStatus);

                                    // Reload table to reflect changes
                                    table.ajax.reload(null, false);
                                }
                            },
                            error: function(xhr) {
                                console.error('Approval update error:', xhr);
                                let errorMsg = 'Failed to update approval status.';

                                if (xhr.responseJSON?.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMsg,
                                    confirmButtonColor: '#dc3545'
                                });

                                // Revert select to old status
                                $select.val(oldStatus);
                            }
                        });
                    } else {
                        // User cancelled, revert select
                        $select.val(oldStatus);
                    }
                });
            });

            // AJAX Approval Update - Approval 2 (Super Admin)
            $(document).on('change', '.approval-2-select', function(e) {
                e.preventDefault();

                const $select = $(this);
                const leaveId = $select.data('id');
                const newStatus = $select.val();
                const oldStatus = $select.data('old-status');
                const employeeName = $select.data('employee');
                const leaveType = $select.data('type');
                const approval1Status = $select.data('approval1');

                // Determine confirmation message
                let confirmMsg = 'Update Approval 2 status?';
                if (newStatus === 'approved' && approval1Status === 'approved' && leaveType === 'ANNUAL') {
                    confirmMsg = 'This will deduct employee leave balance. Continue?';
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, update it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Updating...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: `/leave_requests/${leaveId}/approval`,
                            method: 'POST',
                            data: {
                                approval_2: newStatus,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close SweetAlert immediately
                                    Swal.close();

                                    // Update old status
                                    $select.data('old-status', newStatus);

                                    // Reload table to reflect changes
                                    table.ajax.reload(null, false);
                                }
                            },
                            error: function(xhr) {
                                console.error('Approval update error:', xhr);
                                let errorMsg = 'Failed to update approval status.';

                                if (xhr.responseJSON?.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMsg,
                                    confirmButtonColor: '#dc3545'
                                });

                                // Revert select to old status
                                $select.val(oldStatus);
                            }
                        });
                    } else {
                        // User cancelled, revert select
                        $select.val(oldStatus);
                    }
                });
            });

            // Filter functionality
            $('#employee_filter, #department_filter, #type_filter, #approval_status_filter, #submitted_at_filter')
                .on('change',
                    function() {
                        table.ajax.reload();
                    });

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filter
            $('#reset-filters').on('click', function() {
                $('#employee_filter, #department_filter, #type_filter, #approval_status_filter').val('')
                    .trigger('change');
                $('#submitted_at_filter').val('');
                $('#custom-search').val('');
                table.ajax.reload();
            });

            // Delete functionality with AJAX
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const employeeName = $(this).data('employee');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete leave request for "${employeeName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const deleteUrl = `/leave_requests/${id}`;

                        $.ajax({
                            url: deleteUrl,
                            method: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                    'content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message ||
                                    `Leave request for <b>${employeeName}</b> has been deleted.`,
                                    'success'
                                );
                                table.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                console.error('Delete error:', xhr);
                                let errorMsg = 'Something went wrong!';
                                let iconType = 'error';

                                if (xhr.responseJSON?.message) {
                                    errorMsg = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    errorMsg = xhr.responseText;
                                }

                                if (xhr.status === 403) {
                                    iconType = 'warning';
                                    errorMsg = xhr.responseJSON?.message ||
                                        'You don\'t have permission to perform this action.';
                                }

                                Swal.fire(
                                    xhr.status === 403 ? 'Access Denied!' :
                                    'Error!',
                                    errorMsg,
                                    iconType
                                );
                            }
                        });
                    }
                });
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select an option';
                },
                allowClear: true,
                width: '100%'
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Auto-dismiss alerts
            setTimeout(() => $('.alert').fadeOut('slow'), 5000);
        });

        // Debounce function to prevent excessive API calls
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    func.apply(context, args);
                }, wait);
            };
        }
    </script>
@endpush
