@extends('layouts.app')

@section('title', 'Attendance List')

@section('content')
    <div class="container-fluid py-4">
        <!-- Card Wrapper -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <!-- Header -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul gradient-icon me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Attendance List</h5>
                                <p class="text-muted mb-0 small">View and manage attendance history</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
                            <a href="{{ route('attendance.index') }}"
                                class="btn btn-outline-primary rounded-pill shadow-sm">
                                <i class="bi bi-calendar-check"></i> Input Daily Attendance
                            </a>
                            <button type="button" class="btn btn-success rounded-pill shadow-sm" id="btn-export">
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

                <!-- Filter Form -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-2">
                        <div class="col-md-2 mt-0">
                            <label for="department_filter" class="form-label small text-muted mb-1">Department</label>
                            <select id="department_filter" class="form-select form-select-sm select2">
                                <option value="">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mt-0">
                            <label for="position_filter" class="form-label small text-muted mb-1">Position</label>
                            <select id="position_filter" class="form-select form-select-sm select2">
                                <option value="">All Positions</option>
                                @foreach ($positions as $pos)
                                    <option value="{{ $pos }}">{{ $pos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mt-0">
                            <label for="status_filter" class="form-label small text-muted mb-1">Status</label>
                            <select id="status_filter" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        <div class="col-md-1 mt-0">
                            <label for="date_from_filter" class="form-label small text-muted mb-1">From Date</label>
                            <input type="date" id="date_from_filter" class="form-control form-control-sm"
                                max="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-1 mt-0">
                            <label for="date_to_filter" class="form-label small text-muted mb-1">To Date</label>
                            <input type="date" id="date_to_filter" class="form-control form-control-sm"
                                max="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 mt-0">
                            <label for="custom-search" class="form-label small text-muted mb-1">Search</label>
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Employee name or number...">
                        </div>
                        <div class="col-md-1 mt-0">
                            <label class="form-label small text-muted mb-1 invisible">Reset</label>
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm w-100 d-block"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="datatable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th width="50">#</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Arrival Time</th>
                                <th>Recorded Time</th>
                                <th>Recorded By</th>
                                <th>Notes</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables akan populate ini -->
                        </tbody>
                    </table>
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

        .form-control,
        .form-select {
            border: 1px solid #ced4da !important;
        }

        /* Pagination styling - sama seperti Employee */
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
        #datatable tbody td {
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

            #datatable thead th {
                font-size: 0.8rem;
                padding: 8px 4px;
            }

            #datatable tbody td {
                padding: 8px 4px;
                font-size: 0.85rem;
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

        .alert-success {
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable dengan server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                stateSave: false,
                ajax: {
                    url: "{{ route('attendance.list') }}",
                    data: function(d) {
                        d.department_filter = $('#department_filter').val();
                        d.position_filter = $('#position_filter').val();
                        d.status_filter = $('#status_filter').val();
                        d.date_from_filter = $('#date_from_filter').val();
                        d.date_to_filter = $('#date_to_filter').val();
                        d.custom_search = $('#custom-search').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'employee',
                        name: 'employee'
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
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'arrival_time',
                        name: 'arrival_time',
                        orderable: false
                    },
                    {
                        data: 'recorded_time',
                        name: 'recorded_time'
                    },
                    {
                        data: 'recorded_by',
                        name: 'recorded_by',
                        orderable: false
                    },
                    {
                        data: 'notes',
                        name: 'notes',
                        orderable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'desc']
                ], // Sort by date desc
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No attendance data available</div>',
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
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter functionality
            $('#department_filter, #position_filter, #status_filter, #date_from_filter, #date_to_filter').on(
                'change',
                function() {
                    table.ajax.reload();
                });

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#department_filter, #position_filter, #status_filter').val('').trigger('change');
                $('#date_from_filter, #date_to_filter, #custom-search').val('');
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

            // Delete functionality
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const date = $(this).data('date');

                Swal.fire({
                    title: 'Are you sure?',
                    html: `You want to delete attendance record for <strong>"${name}"</strong> on <strong>${date}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/attendance/${id}`,
                            method: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire('Deleted!',
                                    `Attendance record for <b>${name}</b> deleted successfully.`,
                                    'success');
                                table.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    xhr.responseJSON?.message ||
                                    'Failed to delete record.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Export functionality
            $('#btn-export').on('click', function() {
                const filters = {
                    department_id: $('#department_filter').val(),
                    position: $('#position_filter').val(),
                    status: $('#status_filter').val(),
                    date_from: $('#date_from_filter').val(),
                    date_to: $('#date_to_filter').val(),
                    search: $('#custom-search').val()
                };

                const queryString = $.param(filters);
                window.location.href = `{{ route('attendance.export') }}?${queryString}`;
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select an option';
                },
                allowClear: true,
                width: '100%'
            }).on('select2:open', function() {
                setTimeout(() => {
                    const searchField = document.querySelector('.select2-search__field');
                    if (searchField) searchField.focus();
                }, 100);
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Auto-dismiss alerts
            setTimeout(() => {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endpush
