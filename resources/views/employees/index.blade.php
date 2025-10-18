@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <!-- Header - Improved responsive layout -->
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users gradient-icon me-2" style="font-size: 1.3rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Employees Management</h2>
                    </div>

                    <!-- Stats Cards - Better mobile layout -->
                    @if (auth()->user()->canModifyData())
                        <div class="ms-lg-auto">
                            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-lg-end">
                                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span class="d-none d-sm-inline">Add Employee</span>
                                    <span class="d-sm-none">Add</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters - Improved mobile layout -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6 col-lg-3">
                        <select id="departmentFilter" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            @foreach ($employees->pluck('department.name')->unique()->filter() as $dept)
                                <option value="{{ $dept }}">{{ ucfirst($dept) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <select id="positionFilter" class="form-select form-select-sm">
                            <option value="">All Positions</option>
                            @foreach ($employees->pluck('position')->unique()->filter() as $position)
                                <option value="{{ $position }}">{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearFilters">
                            <i class="bi bi-arrow-clockwise"></i> Clear Filters
                        </button>
                    </div>
                </div>

                <!-- DataTable - Improved responsive table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="employees-table">
                        <thead class="table-light">
                            <tr>
                                <th width="60" class="text-center">Photo</th>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Position</th>
                                <th class="d-none d-lg-table-cell">Department</th>
                                <th class="d-none d-lg-table-cell">Contact</th>
                                <th class="d-none d-xl-table-cell">Hire Date</th>
                                <th class="text-center">Documents</th>
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
                                        <!-- Mobile-only info -->
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
                                    <td class="text-center">
                                        @if ($employee->documents->count() > 0)
                                            <button type="button" class="btn btn-sm btn-outline-success view-documents-btn"
                                                data-employee-id="{{ $employee->id }}"
                                                data-employee-name="{{ $employee->name }}" title="View Documents">
                                                <i class="bi bi-file-earmark-text"></i>
                                                <span
                                                    class="badge bg-success ms-1">{{ $employee->documents->count() }}</span>
                                            </button>
                                        @else
                                            <span class="badge bg-secondary small">No docs</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Employment Type Badge --}}
                                        <span class="badge bg-{{ $employee->employment_type_badge['color'] }} mb-1">
                                            {{ $employee->employment_type }}
                                        </span>
                                        <br>
                                        {{-- Employment Status Badge --}}
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
    <div class="modal fade" id="documentsModal" tabindex="-1">
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
                    <div id="documentsContent">
                        <!-- Documents will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Enhanced DataTables Styling */
        .dataTables_wrapper {
            margin-top: 1rem;
        }

        /* Table styling improvements */
        #employees-table {
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        #employees-table tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        /* Remove row hover and click behavior - only name is clickable */
        #employees-table tbody tr {
            transition: none;
            cursor: default;
        }

        /* Make only name column clickable */
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

        /* Employee photo styling */
        .employee-photo {
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .employee-photo:hover {
            transform: scale(1.1);
        }

        /* Badge improvements */
        .badge {
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 4px;
        }

        /* Button group improvements */
        .btn-group .btn {
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* View documents button styling */
        .view-documents-btn {
            transition: all 0.2s ease;
        }

        .view-documents-btn:hover {
            transform: scale(1.05);
        }

        /* Filter styling */
        .form-select-sm {
            border-radius: 6px;
            border: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }

        .form-select-sm:focus {
            border-color: #8116ed;
            box-shadow: 0 0 0 0.2rem rgba(129, 22, 237, 0.25);
        }

        /* Enhanced pagination buttons */
        .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.875rem;
            margin: 0 0.125rem;
            border: 1px solid #e3e6f0;
            border-radius: 6px;
            background: white;
            color: #495057;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-color: #adb5bd;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #employees-table_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #8116ed 0%, #6f42c1 100%) !important;
            border-color: #8116ed !important;
            color: #fff !important;
            box-shadow: 0 4px 8px rgba(129, 22, 237, 0.3) !important;
        }

        /* Enhanced search input */
        .dataTables_filter input {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border: 1px solid #e3e6f0;
            border-radius: 25px;
            margin-left: 0.5rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            min-width: 250px;
        }

        .dataTables_filter input:focus {
            border-color: #8116ed;
            background: white;
            box-shadow: 0 0 0 0.2rem rgba(129, 22, 237, 0.25);
            outline: none;
        }

        /* Documents modal styling */
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

        .document-actions .btn {
            margin: 0 2px;
        }

        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            #employees-table thead th {
                font-size: 0.8rem;
                padding: 8px 4px;
            }

            #employees-table tbody td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }

            .btn-group-vertical .btn {
                margin: 1px 0;
                font-size: 0.8rem;
                padding: 4px 8px;
            }

            .employee-photo {
                width: 30px !important;
                height: 30px !important;
            }

            .badge {
                font-size: 0.6rem;
                padding: 2px 4px;
            }

            .dataTables_filter input {
                min-width: 180px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            .card-body {
                padding: 15px;
            }

            .dataTables_wrapper .row {
                margin: 0;
            }

            .dataTables_wrapper .col-md-6 {
                padding: 5px;
            }

            /* Stack elements vertically on mobile */
            .dataTables_length,
            .dataTables_filter {
                text-align: center !important;
                margin-bottom: 10px;
            }

            .dataTables_info,
            .dataTables_paginate {
                text-align: center !important;
            }
        }

        /* Loading state */
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            color: #495057;
            font-weight: 500;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced alert styling */
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
            // Enhanced DataTables configuration
            const dtConfig = {
                processing: true,
                searching: true,
                paging: true,
                info: true,
                ordering: true,
                lengthChange: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                language: {
                    search: "Search employees:",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees",
                    infoEmpty: "No employees available",
                    infoFiltered: "(filtered from _MAX_ total employees)",
                    emptyTable: "No employees found",
                    zeroRecords: "No employees match your search criteria",
                    processing: '<i class="fas fa-spinner fa-spin"></i> Loading employees...',
                    lengthMenu: "Show _MENU_ entries per page",
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>'
                    }
                },
                columnDefs: [{
                        targets: [0], // Photo column
                        orderable: false,
                        searchable: false,
                        responsivePriority: 1
                    },
                    {
                        targets: [9], // Actions column
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2
                    },
                    {
                        targets: [7], // Documents column
                        orderable: false,
                        className: "text-center",
                        responsivePriority: 3
                    },
                    {
                        targets: [2], // Name column
                        responsivePriority: 1
                    }
                ],
                order: [
                    [2, 'asc']
                ], // Default sort by name
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                scrollX: false, // Disable horizontal scroll for better mobile experience
                autoWidth: false,
                stateSave: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            };

            // Initialize DataTable
            const table = $('#employees-table').DataTable(dtConfig);

            // Custom filtering
            $('#departmentFilter').on('change', function() {
                const value = this.value;
                table.column(4).search(value).draw();
            });

            $('#statusFilter').on('change', function() {
                const value = this.value;
                table.column(8).search(value).draw();
            });

            $('#positionFilter').on('change', function() {
                const value = this.value;
                table.column(3).search(value).draw();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#departmentFilter, #statusFilter, #positionFilter').val('');
                table.search('').columns().search('').draw();
            });

            // Handle name click to view details (only name column)
            $(document).on('click', '.clickable-name', function(e) {
                e.stopPropagation();
                const row = $(this).closest('tr');
                const employeeId = row.data('employee-id');
                if (employeeId) {
                    // Add loading state
                    const originalContent = $(this).html();
                    $(this).html('<i class="spinner-border spinner-border-sm"></i> Loading...');

                    // Navigate to employee details
                    window.location.href = `/employees/${employeeId}`;
                }
            });

            // Handle view documents button click
            $(document).on('click', '.view-documents-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');

                // Show loading state
                const originalHtml = $(this).html();
                $(this).prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');

                loadEmployeeDocuments(employeeId, employeeName, $(this), originalHtml);
            });

            // Function to load employee documents
            function loadEmployeeDocuments(employeeId, employeeName, button, originalHtml) {
                $.ajax({
                    url: `/employees/${employeeId}/documents`,
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
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
                                        <div class="document-actions">
                                            <a href="${doc.file_url}" target="_blank"
                                               class="btn btn-outline-primary btn-sm" title="View Document">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="/employee-documents/${doc.id}/download"
                                               class="btn btn-outline-success btn-sm" title="Download Document">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                            });
                        } else {
                            documentsHtml = `
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                            <div class="mt-3 text-muted">No documents found for this employee</div>
                        </div>
                    `;
                        }

                        $('#documentsContent').html(documentsHtml);
                        $('#documentsModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading documents:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to load employee documents. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            }

            // Helper function to format file size
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Helper function to get file icon
            function getFileIcon(mimeType) {
                if (mimeType.includes('pdf')) return 'pdf';
                if (mimeType.includes('word') || mimeType.includes('document')) return 'word';
                if (mimeType.includes('image')) return 'image';
                return 'text';
            }

            // Enhanced delete confirmation with SweetAlert
            $(document).on('click', '.delete-employee-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');
                const button = $(this);

                Swal.fire({
                    title: 'Delete Employee',
                    html: `Are you sure you want to delete employee <strong>"${employeeName}"</strong>?<br><br>
                   <div class="alert alert-warning">
                       <i class="bi bi-exclamation-triangle"></i> This will also delete:
                       <ul class="mb-0 mt-2">
                           <li>Employee photo</li>
                           <li>All documents</li>
                           <li>Timing records</li>
                       </ul>
                   </div>
                   <small class="text-muted">This action cannot be undone!</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash"></i> Yes, Delete!',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancel',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        confirmButton: 'btn btn-danger ms-2',
                        cancelButton: 'btn btn-secondary me-2',
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        button.prop('disabled', true);
                        button.html('<i class="spinner-border spinner-border-sm"></i>');

                        // Create and submit form
                        const form = $(`
                    <form action="/employees/${employeeId}" method="POST" style="display: none;">
                        <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                        <input type="hidden" name="_method" value="DELETE">
                    </form>
                `);

                        $('body').append(form);

                        // Show deleting progress
                        Swal.fire({
                            title: 'Deleting Employee...',
                            html: 'Please wait while we delete the employee and all related data.',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        form.submit();
                    }
                });
            });

            // Auto-dismiss alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Add loading state to action buttons
            $('.btn-primary, .btn-warning, .btn-info').on('click', function() {
                const btn = $(this);
                const originalHtml = btn.html();

                btn.prop('disabled', true);
                btn.html('<i class="spinner-border spinner-border-sm"></i>');

                // Re-enable after navigation (fallback)
                setTimeout(() => {
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }, 3000);
            });
        });
    </script>
@endpush
