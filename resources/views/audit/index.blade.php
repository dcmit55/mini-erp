@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Audit Log</h2>
                    </div>

                    <div class="ms-lg-auto">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-lg-end">
                            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                                <i class="bi bi-trash3 me-1"></i> Bulk Delete
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#deleteByDateModal">
                                <i class="bi bi-calendar-x me-1"></i> Delete by Date
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#purgeOldModal">
                                <i class="bi bi-hourglass-split me-1"></i> Purge Old Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form - sesuai Employee -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="eventFilter" class="form-select form-select-sm select2">
                                <option value="">All Events</option>
                                <option value="created">Created</option>
                                <option value="updated">Updated</option>
                                <option value="deleted">Deleted</option>
                                <option value="restored">Restored</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="modelFilter" class="form-select form-select-sm select2">
                                <option value="">All Models</option>
                                <option value="App\Models\Logistic\Inventory">Inventory</option>
                                <option value="App\Models\Logistic\MaterialRequest">Material Request</option>
                                <option value="App\Models\Logistic\GoodsOut">Goods Out</option>
                                <option value="App\Models\Logistic\GoodsIn">Goods In</option>
                                <option value="App\Models\Production\Project">Project</option>
                                <option value="App\Models\Production\ProjectPart">Project Part</option>
                                <option value="App\Models\Admin\User">User</option>
                                <option value="App\Models\Hr\Employee">Employee</option>
                                <option value="App\Models\Finance\Currency">Currency</option>
                                <option value="App\Models\Procurement\PurchaseRequest">Purchase Request</option>
                                <option value="App\Models\Production\MaterialPlanning">Material Planning</option>
                                <option value="App\Models\Procurement\Supplier">Supplier</option>
                                <option value="App\Models\Hr\LeaveRequest">Leave Request</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" id="dateFrom" class="form-control form-control-sm"
                                placeholder="From Date">
                        </div>
                        <div class="col-md-2">
                            <input type="date" id="dateTo" class="form-control form-control-sm" placeholder="To Date">
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Search...">
                        </div>
                        <div class="col-md-1">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm w-100"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="auditTable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Model</th>
                                <th>Event</th>
                                <th>Changes</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Changes Modal -->
    <div class="modal fade" id="changesModal" tabindex="-1" aria-labelledby="changesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changesModalLabel">Change Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="changesContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete by Date Range Modal -->
    <div class="modal fade" id="deleteByDateModal" tabindex="-1" aria-labelledby="deleteByDateLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="deleteByDateLabel">Delete Audit Logs by Date Range</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteByDateForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" id="deleteDateFrom" name="date_from" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" id="deleteDateTo" name="date_to" required>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            All audit logs within the specified date range will be permanently deleted and cannot be
                            recovered.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteByDateBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Purge Old Logs Modal -->
    <div class="modal fade" id="purgeOldModal" tabindex="-1" aria-labelledby="purgeOldLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="purgeOldLabel">Purge Old Audit Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="purgeOldForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Delete logs older than (days)</label>
                            <input type="number" class="form-control" id="purgeDays" name="days" min="1"
                                max="365" value="30" required>
                            <small class="text-muted">Enter number of days. Logs older than this will be deleted.</small>
                        </div>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <strong>Warning:</strong> All audit logs older than the specified days will be permanently
                            deleted and cannot be recovered.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmPurgeBtn">Purge</button>
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

        /* Filter Form Styling - sesuai Employee */
        #filter-form {
            background: #f8f9fa;
            padding: .75rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }

        /* Pagination styling */
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

        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

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

        .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Table styling */
        #auditTable tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

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

            #auditTable thead th {
                font-size: 0.8rem;
                padding: 8px 4px;
            }

            #auditTable tbody td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }
        }

        #changesModal table {
            table-layout: fixed;
            width: 100%;
        }

        #changesModal td {
            word-break: break-all;
            white-space: pre-line;
            max-width: 250px;
            vertical-align: top;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable dengan server-side processing - sesuai Employee
            const table = $('#auditTable').DataTable({
                processing: false,
                serverSide: true,
                searching: false, // Disable default search, gunakan custom
                ajax: {
                    url: "{{ route('audit.index') }}",
                    data: function(d) {
                        // Add filter parameters
                        d.event = $('#eventFilter').val();
                        d.auditable_type = $('#modelFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                        d.custom_search = $('#custom-search').val();
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'formatted_date',
                        name: 'created_at'
                    },
                    {
                        data: 'user_name',
                        name: 'user.username'
                    },
                    {
                        data: 'model_name',
                        name: 'auditable_type'
                    },
                    {
                        data: 'event_badge',
                        name: 'event'
                    },
                    {
                        data: 'changes',
                        name: 'changes',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'ip_address',
                        name: 'ip_address'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    []
                ],
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No audit logs available</div>',
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
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter functionality
            $('#eventFilter, #modelFilter').on('change', function() {
                table.ajax.reload();
            });

            $('#dateFrom, #dateTo').on('change', function() {
                table.ajax.reload();
            });

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#eventFilter, #modelFilter').val('').trigger('change');
                $('#dateFrom, #dateTo').val('');
                $('#custom-search').val('');
                table.ajax.reload();
            });

            // Debounce function
            function debounce(func, wait) {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, arguments), wait);
                };
            }

            // Select All Checkbox
            $(document).on('click', '#selectAllCheckbox', function() {
                const isChecked = $(this).is(':checked');
                $('.select-audit').prop('checked', isChecked);
                updateBulkDeleteBtn();
            });

            // Individual Checkbox
            $(document).on('change', '.select-audit', function() {
                updateBulkDeleteBtn();
            });

            function updateBulkDeleteBtn() {
                const selectedCount = $('.select-audit:checked').length;
                if (selectedCount > 0) {
                    $('#bulkDeleteBtn').prop('disabled', false).html(
                        `<i class="bi bi-trash3 me-1"></i> Bulk Delete (${selectedCount})`
                    );
                } else {
                    $('#bulkDeleteBtn').prop('disabled', true).html(
                        '<i class="bi bi-trash3 me-1"></i> Bulk Delete'
                    );
                }
            }

            // Bulk Delete
            $('#bulkDeleteBtn').on('click', function() {
                const selectedIds = $('.select-audit:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    Swal.fire('Warning', 'Please select at least one audit log.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Delete Selected Audit Logs?',
                    html: `You are about to permanently delete <strong>${selectedIds.length}</strong> audit log(s).`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('audit.bulkDelete') }}",
                            method: 'POST',
                            data: {
                                ids: selectedIds,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                table.ajax.reload();
                                $('#selectAllCheckbox').prop('checked', false);
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to delete audit logs.', 'error');
                            }
                        });
                    }
                });
            });

            // Individual Delete
            $(document).on('click', '.delete-audit-btn', function() {
                const auditId = $(this).data('id');

                Swal.fire({
                    title: 'Delete Audit Log?',
                    text: 'This audit log will be permanently deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('audit') }}/" + auditId,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to delete audit log.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete by Date Range
            $('#confirmDeleteByDateBtn').on('click', function() {
                const formData = $('#deleteByDateForm').serialize();

                if (!$('#deleteDateFrom').val() || !$('#deleteDateTo').val()) {
                    Swal.fire('Error', 'Please fill in both date fields.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Delete Audit Logs by Date Range?',
                    html: `<strong>${$('#deleteDateFrom').val()}</strong> to <strong>${$('#deleteDateTo').val()}</strong><br>All matching audit logs will be permanently deleted.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('audit.deleteByDateRange') }}",
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                $('#deleteByDateModal').modal('hide');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to delete audit logs.', 'error');
                            }
                        });
                    }
                });
            });

            // Purge Old Logs
            $('#confirmPurgeBtn').on('click', function() {
                const days = $('#purgeDays').val();

                Swal.fire({
                    title: 'Purge Old Audit Logs?',
                    html: `Logs older than <strong>${days} days</strong> will be permanently deleted. This cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, purge them!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('audit.purgeOldLogs') }}",
                            method: 'POST',
                            data: {
                                days: days,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success', response.message, 'success');
                                $('#purgeOldModal').modal('hide');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Failed to purge audit logs.', 'error');
                            }
                        });
                    }
                });
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });
        });

        function showChanges(auditId) {
            $('#changesContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $.get(`{{ url('audit/changes') }}/${auditId}`)
                .done(function(data) {
                    const eventBadgeClasses = {
                        created: 'bg-success',
                        updated: 'bg-warning',
                        deleted: 'bg-danger',
                        restored: 'bg-info'
                    };
                    const badgeClass = eventBadgeClasses[data.event] || 'bg-secondary';

                    let html = `
                        <div class="mb-3">
                            <strong>Model:</strong> ${data.model}<br>
                            <strong>Event:</strong> <span class="badge ${badgeClass}">${data.event}</span><br>
                            <strong>Date:</strong> ${data.created_at}
                        </div>
                    `;

                    if (data.event === 'created') {
                        html += '<h6>New Values:</h6>';
                        html += formatValues(data.new_values);
                    } else if (data.event === 'updated') {
                        html += '<div class="row">';
                        html += '<div class="col-md-6"><h6>Old Values:</h6>' + formatValues(data.old_values) + '</div>';
                        html += '<div class="col-md-6"><h6>New Values:</h6>' + formatValues(data.new_values) + '</div>';
                        html += '</div>';
                    } else if (data.event === 'deleted') {
                        html += '<h6>Deleted Values:</h6>';
                        html += formatValues(data.old_values);
                    } else if (data.event === 'restored') {
                        html += '<h6>Restored Values:</h6>';
                        html += formatValues(data.new_values);
                    }

                    $('#changesContent').html(html);
                })
                .fail(function() {
                    $('#changesContent').html('<div class="alert alert-danger">Failed to load changes.</div>');
                });
        }

        function formatValues(values) {
            if (!values || Object.keys(values).length === 0) {
                return '<p class="text-muted">No data</p>';
            }

            let html = '<table class="table table-sm table-bordered">';
            Object.keys(values).forEach(key => {
                html += `<tr><td><strong>${key}:</strong></td><td>${values[key] || '<em>null</em>'}</td></tr>`;
            });
            html += '</table>';
            return html;
        }
    </script>
@endpush
