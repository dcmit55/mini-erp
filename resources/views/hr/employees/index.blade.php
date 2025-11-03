@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Employees Management</h2>
                    </div>

                    <div class="ms-lg-auto">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-lg-end">
                            @if (auth()->user()->canModifyData())
                                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span class="d-none d-sm-inline">Add Employee</span>
                                    <span class="d-sm-none">Add</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="departmentFilter" class="form-select form-select-sm select2">
                                <option value="">All Departments</option>
                                @foreach ($employees->pluck('department.name')->unique()->filter() as $dept)
                                    <option value="{{ $dept }}">{{ ucfirst($dept) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="employmentTypeFilter" class="form-select form-select-sm select2">
                                <option value="">All Employment Types</option>
                                <option value="PKWT">PKWT</option>
                                <option value="PKWTT">PKWTT</option>
                                <option value="Daily Worker">Daily Worker</option>
                                <option value="Probation">Probation</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="statusFilter" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="positionFilter" class="form-select form-select-sm select2">
                                <option value="">All Positions</option>
                                @foreach ($employees->pluck('position')->unique()->filter() as $position)
                                    <option value="{{ $position }}">{{ $position }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Search employees...">
                        </div>
                        <div class="col-md-1">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="employees-table">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th width="60" class="text-center">Photo</th>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Position</th>
                                <th class="d-none d-lg-table-cell">Department</th>
                                <th class="d-none d-lg-table-cell">Contact</th>
                                <th class="d-none d-xl-table-cell">Hire Date</th>
                                <th class="d-none d-xl-table-cell">Contract End</th>
                                {{-- <th class="text-center">Documents</th> --}}
                                <th class="d-none d-sm-table-cell">Type & Status</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                <tr data-employee-id="{{ $employee->id }}">
                                    <td class="text-center">
                                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}"
                                            class="rounded-circle employee-photo"
                                            style="width: 35px; height: 35px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark border small">{{ $employee->employee_no }}</span>
                                    </td>
                                    <td class="clickable-name" style="cursor: pointer;" title="Click to view details">
                                        <div class="fw-medium">{{ $employee->name }}</div>
                                        @if ($employee->email)
                                            <small class="text-muted d-md-none">
                                                <i class="bi bi-envelope"></i> {{ Str::limit($employee->email, 15) }}
                                            </small>
                                        @endif
                                        <div class="d-md-none mt-1">
                                            <small class="badge bg-info">{{ $employee->position }}</small>
                                            @if ($employee->department)
                                                <small
                                                    class="badge bg-primary ms-1">{{ ucfirst($employee->department->name) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-info">{{ $employee->position }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        @if ($employee->department)
                                            <span
                                                class="badge bg-primary">{{ ucfirst($employee->department->name) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        @if ($employee->phone)
                                            <div class="small">
                                                <i class="bi bi-telephone text-success"></i> {{ $employee->phone }}
                                            </div>
                                        @endif
                                        @if ($employee->email)
                                            <div class="small">
                                                <i class="bi bi-envelope text-primary"></i>
                                                {{ Str::limit($employee->email, 20) }}
                                            </div>
                                        @endif
                                        @if (!$employee->phone && !$employee->email)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        @if ($employee->hire_date)
                                            <div class="small">{{ $employee->hire_date->format('d M Y') }}</div>
                                            <small class="text-muted">{{ $employee->hire_date->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        @if ($employee->contract_end_date)
                                            <div class="small">{{ $employee->contract_end_date->format('d M Y') }}</div>
                                            @php
                                                $now = \Carbon\Carbon::now();
                                                $daysRemaining = $now->diffInDays($employee->contract_end_date, false);
                                            @endphp

                                            @if ($daysRemaining < 0)
                                                <small class="badge bg-danger">Expired</small>
                                            @elseif ($daysRemaining <= 30)
                                                <small class="badge bg-warning text-dark">{{ $daysRemaining }}d
                                                    left</small>
                                            @else
                                                <small
                                                    class="text-muted">{{ $employee->contract_end_date->diffForHumans() }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    {{-- <td class="text-center">
                                        @if ($employee->documents->count() > 0)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-success view-documents-btn"
                                                data-employee-id="{{ $employee->id }}"
                                                data-employee-name="{{ $employee->name }}" title="View Documents">
                                                <i class="bi bi-file-earmark-text"></i>
                                                <span
                                                    class="badge bg-success ms-1">{{ $employee->documents->count() }}</span>
                                            </button>
                                        @else
                                            <span class="badge bg-secondary small">No docs</span>
                                        @endif
                                    </td> --}}
                                    <td>
                                        <span class="badge bg-{{ $employee->employment_type_badge['color'] }} mb-1">
                                            {{ $employee->employment_type }}
                                        </span>
                                        <br>
                                        <span class="badge bg-{{ $employee->status_badge['color'] }}">
                                            {{ $employee->status_badge['text'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical btn-group-sm d-lg-none" role="group">
                                            <a href="{{ route('employees.show', $employee) }}"
                                                class="btn btn-primary btn-sm" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.edit', $employee) }}"
                                                class="btn btn-warning btn-sm" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="{{ route('employees.timing', $employee) }}"
                                                class="btn btn-info btn-sm" title="Timing">
                                                <i class="bi bi-clock"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-employee-btn"
                                                data-employee-id="{{ $employee->id }}"
                                                data-employee-name="{{ $employee->name }}" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <div class="btn-group d-none d-lg-flex" role="group">
                                            <a href="{{ route('employees.show', $employee) }}"
                                                class="btn btn-primary btn-sm" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.timing', $employee) }}"
                                                class="btn btn-info btn-sm" title="View Timings">
                                                <i class="bi bi-clock"></i>
                                            </a>
                                            @if (auth()->user()->canModifyData())
                                                <a href="{{ route('employees.edit', $employee) }}"
                                                    class="btn btn-warning btn-sm" title="Edit Employee">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm delete-employee-btn"
                                                    data-employee-id="{{ $employee->id }}"
                                                    data-employee-name="{{ $employee->name }}" title="Delete Employee">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Modal -->
    {{-- <div class="modal fade" id="documentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-text"></i>
                        Documents for <span id="modalEmployeeName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="documentsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> --}}
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

        /* Table styling */
        #employees-table tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .clickable-name {
            transition: all 0.2s ease;
            border-radius: 4px;
            padding: 4px 8px;
            margin: -4px -8px;
        }

        .clickable-name:hover {
            background-color: #e3f2fd;
            transform: scale(1.02);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .employee-photo {
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .employee-photo:hover {
            transform: scale(1.1);
        }

        .badge {
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 4px;
        }

        .btn-group .btn {
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .view-documents-btn {
            transition: all 0.2s ease;
        }

        .view-documents-btn:hover {
            transform: scale(1.05);
        }

        .document-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }

        .document-item:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

            #employees-table thead th {
                font-size: 0.8rem;
                padding: 8px 4px;
            }

            #employees-table tbody td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }

            .employee-photo {
                width: 30px !important;
                height: 30px !important;
            }
        }

        .alert-success {
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
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
            // Initialize DataTable dengan konfigurasi sama seperti Goods Out
            const table = $('#employees-table').DataTable({
                processing: false,
                searching: true,
                paging: true,
                info: true,
                ordering: true,
                lengthChange: true,
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search in table...",
                    emptyTable: '<div class="text-muted py-2">No employee data available</div>',
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
                columnDefs: [{
                        targets: [0], // Photo
                        orderable: false,
                        searchable: false,
                        className: "text-center"
                    },
                    // {
                    //     targets: [7], // Documents
                    //     orderable: false,
                    //     className: "text-center"
                    // },
                    {
                        targets: [9], // Actions
                        orderable: false,
                        searchable: false,
                        className: "text-center"
                    }
                ],
                order: [
                    []
                ], // Sort by name
                responsive: true,
                stateSave: false,
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter functionality
            $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').on('change',
                function() {
                    applyFilters();
                });

            $('#custom-search').on('input', debounce(function() {
                table.search($(this).val()).draw();
            }, 500));

            function applyFilters() {
                const dept = $('#departmentFilter').val();
                const empType = $('#employmentTypeFilter').val();
                const status = $('#statusFilter').val();
                const position = $('#positionFilter').val();

                // Filter department (column 4)
                table.column(4).search(dept).draw();

                // Filter employment type & status (column 8)
                let typeStatusFilter = '';
                if (empType && status) {
                    typeStatusFilter = empType + '.*' + status;
                } else if (empType) {
                    typeStatusFilter = empType;
                } else if (status) {
                    typeStatusFilter = status;
                }
                table.column(8).search(typeStatusFilter, true, false).draw();

                // Filter position (column 3)
                table.column(3).search(position).draw();
            }

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').val('')
                    .trigger(
                        'change');
                $('#custom-search').val('');
                table.search('').columns().search('').draw();
            });

            // Handle name click
            $(document).on('click', '.clickable-name', function(e) {
                e.stopPropagation();
                const employeeId = $(this).closest('tr').data('employee-id');
                if (employeeId) {
                    const originalContent = $(this).html();
                    $(this).html('<i class="spinner-border spinner-border-sm"></i> Loading...');
                    window.location.href = `/employees/${employeeId}`;
                }
            });

            // Handle view documents
            $(document).on('click', '.view-documents-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');
                const originalHtml = $(this).html();
                $(this).prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');

                loadEmployeeDocuments(employeeId, employeeName, $(this), originalHtml);
            });

            function loadEmployeeDocuments(employeeId, employeeName, button, originalHtml) {
                $.ajax({
                    url: `/employees/${employeeId}/documents`,
                    method: 'GET',
                    success: function(response) {
                        $('#modalEmployeeName').text(employeeName);
                        let documentsHtml = '';

                        if (response.documents && response.documents.length > 0) {
                            response.documents.forEach(function(doc) {
                                const fileSize = formatFileSize(doc.file_size);
                                const uploadDate = new Date(doc.created_at).toLocaleDateString(
                                    'id-ID', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric'
                                    });

                                documentsHtml += `
                                    <div class="document-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-${getFileIcon(doc.mime_type)} text-primary me-3 fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-1">${doc.document_name}</h6>
                                                        <div class="small text-muted">
                                                            <span class="badge bg-info me-2">${doc.document_type_label}</span>
                                                            <span>${fileSize}</span>
                                                            <span class="mx-1">•</span>
                                                            <span>Uploaded ${uploadDate}</span>
                                                        </div>
                                                        ${doc.description ? `<p class="mb-0 mt-1 small text-secondary">${doc.description}</p>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="${doc.file_url}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="/employee-documents/${doc.id}/download" class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            documentsHtml = `
                                <div class="text-center py-4">
                                    <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                                    <div class="mt-3 text-muted">No documents found</div>
                                </div>
                            `;
                        }

                        $('#documentsContent').html(documentsHtml);
                        $('#documentsModal').modal('show');
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to load documents.', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            }

            // Delete employee
            $(document).on('click', '.delete-employee-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');

                Swal.fire({
                    title: 'Delete Employee',
                    html: `Are you sure you want to delete <strong>"${employeeName}"</strong>?<br><br>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This will delete all related data.
                        </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $(`
                            <form action="/employees/${employeeId}" method="POST" style="display: none;">
                                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                <input type="hidden" name="_method" value="DELETE">
                            </form>
                        `);
                        $('body').append(form);
                        form.submit();
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

            // Helper functions
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function getFileIcon(mimeType) {
                if (mimeType.includes('pdf')) return 'pdf';
                if (mimeType.includes('word') || mimeType.includes('document')) return 'word';
                if (mimeType.includes('image')) return 'image';
                return 'text';
            }

            function debounce(func, wait) {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, arguments), wait);
                };
            }

            // Auto-dismiss alerts
            setTimeout(() => $('.alert').fadeOut('slow'), 5000);

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endpush
