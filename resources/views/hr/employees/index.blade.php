@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                @php
                    $activeCount = $employees->where('status', 'active')->count();
                    $inactiveCount = $employees->where('status', 'inactive')->count();
                    $terminatedCount = $employees->where('status', 'terminated')->count();
                    $allCount = $employees->count();
                @endphp

                <!-- Header -->
                <div class="position-relative d-flex align-items-center mb-3" style="min-height:40px;">
                    <!-- Left: status tab buttons -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-sm rounded-2 px-3 emp-status-tab active-tab btn-purple"
                            data-status="" id="tab-all">
                            <i class="fas fa-users me-1"></i> All
                            <span class="tab-badge ms-1"
                                style="font-size:0.65rem;font-weight:600;">{{ $allCount }}</span>
                        </button>
                        <button type="button" class="btn btn-sm rounded-2 px-3 emp-status-tab btn-outline-purple"
                            data-status="active" id="tab-active">
                            <i class="fas fa-user-check me-1"></i> Active
                            <span class="tab-badge ms-1"
                                style="font-size:0.65rem;font-weight:600;">{{ $activeCount }}</span>
                        </button>
                        <button type="button" class="btn btn-sm rounded-2 px-3 emp-status-tab btn-outline-purple"
                            data-status="inactive" id="tab-inactive">
                            <i class="fas fa-user-clock me-1"></i> Inactive
                            <span class="tab-badge ms-1"
                                style="font-size:0.65rem;font-weight:600;">{{ $inactiveCount }}</span>
                        </button>
                        @if ($terminatedCount > 0)
                            <button type="button" class="btn btn-sm rounded-2 px-3 emp-status-tab btn-outline-purple"
                                data-status="terminated" id="tab-terminated">
                                <i class="fas fa-user-times me-1"></i> Terminated
                                <span class="tab-badge ms-1"
                                    style="font-size:0.65rem;font-weight:600;">{{ $terminatedCount }}</span>
                            </button>
                        @endif
                    </div>

                    <!-- Center: Title -->
                    <div class="position-absolute start-50 translate-middle-x text-center d-none d-lg-block"
                        style="pointer-events:none;">
                        <h5 class="fw-semibold mb-0"
                            style="background:linear-gradient(135deg,#8F12FE,#4A25AA);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                            Employees Management
                        </h5>
                    </div>

                    <!-- Right: action buttons -->
                    @if (auth()->user()->canModifyData())
                        <div class="ms-auto d-flex gap-2 flex-shrink-0">
                            <a href="{{ route('employees.export', ['status' => request('status', 'all')]) }}"
                                class="btn btn-sm btn-outline-success rounded-2 px-3">
                                <i class="bi bi-file-earmark-excel me-1"></i>
                                <span class="d-none d-sm-inline">Export Excel</span>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-purple rounded-2 px-3"
                                data-bs-toggle="modal" data-bs-target="#importEmployeeModal">
                                <i class="bi bi-upload me-1"></i>
                                <span class="d-none d-sm-inline">Import Excel</span>
                            </button>
                            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-purple rounded-2 px-3">
                                <i class="bi bi-plus-circle me-1"></i>
                                <span class="d-none d-sm-inline">Add Employee</span>
                                <span class="d-sm-none">Add</span>
                            </a>
                        </div>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('import_results'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-1"></i>{{ session('import_results') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Contract Expiry Info -->
                @php
                    $expiringCount = $employees
                        ->filter(function ($emp) {
                            return $emp->contract_end_date &&
                                $emp->status === 'active' &&
                                $emp->contract_end_date->lte(now()->addDays(30)) &&
                                $emp->contract_end_date->gte(now());
                        })
                        ->count();
                @endphp

                @if ($expiringCount > 0)
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Contract Expiry Alert:</strong> {{ $expiringCount }} employee(s) have contracts expiring
                        within 30 days.
                        <small class="d-block mt-1">
                            <i class="bi bi-info-circle"></i> Employee status will automatically change to "Inactive" when
                            contract expires.
                        </small>
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
                                <th class="d-none d-sm-table-cell">Type & Status</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                <tr data-employee-id="{{ $employee->id }}" data-status="{{ $employee->status }}">
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
                                        @if ($employee->username)
                                            <small class="text-muted">
                                                <i class="bi bi-person-badge"></i> {{ $employee->username }}
                                            </small>
                                        @endif
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

    <!-- Modal Import Employee - TANPA LINK TEMPLATE -->
    <div class="modal fade" id="importEmployeeModal" tabindex="-1" aria-labelledby="importEmployeeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="importEmployeeModalLabel">
                        <i class="bi bi-upload me-1"></i>
                        Import Employees from Excel
                    </h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importEmployeeForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body py-3">
                        <!-- Info Alert - TANPA REFERENSI TEMPLATE -->
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Informasi Import:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                <li>Upload file Excel dengan format yang sesuai</li>
                                <li>Kolom wajib: <strong>employee_no, name, position, status</strong></li>
                                <li>Kolom opsional baru: <strong>username</strong> (nama di mesin attendance)</li>
                                <li>Format tanggal: <strong>YYYY-MM-DD</strong> (contoh: 2024-01-01)</li>
                                <li>Data dengan employee_no yang sama akan diupdate otomatis</li>
                                <li>Maksimal file: 10MB (format: .xlsx, .xls, .csv)</li>
                                <li>Pastikan kolom tanggal tidak mengandung waktu (00:00:00)</li>
                            </ul>
                        </div>

                        <!-- File Input -->
                        <div class="mb-3">
                            <label for="import_file" class="form-label small fw-bold">File Excel</label>
                            <input type="file" name="file" id="import_file" class="form-control form-control-sm"
                                required accept=".xlsx,.xls,.csv">
                            <div class="form-text small">
                                Supported formats: .xlsx, .xls, .csv
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div id="importProgress" class="progress d-none mb-2" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                style="width: 100%">Processing...</div>
                        </div>

                        <!-- Result Message -->
                        <div id="importResult" class="mt-2 small"></div>

                        <!-- Failed Rows Container -->
                        <div id="failedRowsContainer" class="mt-3 d-none">
                            <h6 class="small fw-bold text-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Baris yang Gagal:
                            </h6>
                            <div class="table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-bordered small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Baris</th>
                                            <th>Nama</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody id="failedRowsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="importBtn">
                            <i class="bi bi-upload me-1"></i>Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Purple theme buttons */
        .btn-purple {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            color: #fff;
        }

        .btn-purple:hover,
        .btn-purple:focus {
            background: linear-gradient(135deg, #7a0fe0 0%, #3b1e8e 100%);
            border-color: #7a0fe0;
            color: #fff;
        }

        .btn-outline-purple {
            border: 1px solid #8F12FE;
            color: #8F12FE;
            background: transparent;
        }

        .btn-outline-purple:hover,
        .btn-outline-purple:focus {
            background: rgba(143, 18, 254, 0.08);
            color: #4A25AA;
            border-color: #4A25AA;
        }

        .emp-status-tab.active-tab {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            border-color: #8F12FE;
            color: #fff;
        }

        .emp-status-tab.active-tab .tab-badge {
            color: rgba(255, 255, 255, 0.85);
        }

        .emp-status-tab:not(.active-tab) .tab-badge {
            color: #8F12FE;
        }

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
            transition: color 0.2s;
        }

        .clickable-name:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        /* Modal styling */
        .modal-lg {
            max-width: 800px;
        }

        .progress {
            border-radius: 4px;
        }

        #failedRowsContainer .table {
            font-size: 0.75rem;
        }

        #failedRowsContainer .table th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 1;
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
            // Initialize DataTable
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
                        targets: [0],
                        orderable: false,
                        searchable: false,
                        className: "text-center"
                    },
                    {
                        targets: [9],
                        orderable: false,
                        searchable: false,
                        className: "text-center"
                    }
                ],
                order: [
                    []
                ],
                responsive: true,
                stateSave: false,
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Restore filter state from sessionStorage
            function restoreFilters() {
                const savedFilters = sessionStorage.getItem('employeeFilters');
                if (savedFilters) {
                    const filters = JSON.parse(savedFilters);
                    if (filters.department) $('#departmentFilter').val(filters.department);
                    if (filters.employmentType) $('#employmentTypeFilter').val(filters.employmentType);
                    if (filters.status) $('#statusFilter').val(filters.status);
                    if (filters.position) $('#positionFilter').val(filters.position);
                    if (filters.search) $('#custom-search').val(filters.search);

                    // Trigger Select2 update
                    $('.select2').trigger('change.select2');

                    // Restore active tab
                    if (filters.status) {
                        $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
                        $('.emp-status-tab[data-status="' + filters.status + '"]')
                            .removeClass('btn-outline-purple').addClass('active-tab btn-purple');
                    }

                    // Apply the restored filters
                    applyFilters();
                    if (filters.search) {
                        table.search(filters.search).draw();
                    }
                }
            }

            // Save filter state to sessionStorage
            function saveFilters() {
                const filters = {
                    department: $('#departmentFilter').val(),
                    employmentType: $('#employmentTypeFilter').val(),
                    status: $('#statusFilter').val(),
                    position: $('#positionFilter').val(),
                    search: $('#custom-search').val()
                };
                sessionStorage.setItem('employeeFilters', JSON.stringify(filters));
            }

            // Filter functionality
            $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').on('change',
                function() {
                    applyFilters();
                    saveFilters();
                });

            $('#custom-search').on('input', debounce(function() {
                table.search($(this).val()).draw();
                saveFilters();
            }, 500));

            // Restore filters on page load
            restoreFilters();

            function applyFilters() {
                const dept = $('#departmentFilter').val();
                const empType = $('#employmentTypeFilter').val();
                const status = $('#statusFilter').val();
                const position = $('#positionFilter').val();

                if (dept) {
                    table.column(4).search(dept, false, false).draw(false);
                } else {
                    table.column(4).search('').draw(false);
                }

                // Bangun regex gabungan empType + status dengan word-boundary
                let patterns = [];
                if (empType) patterns.push('\\b' + empType + '\\b');
                if (status) patterns.push('\\b' + status + '\\b');

                if (patterns.length > 0) {
                    table.column(8).search(patterns.join('(?=.*?)'), true, false, true).draw(false);
                } else {
                    table.column(8).search('').draw(false);
                }

                if (position) {
                    table.column(3).search('^' + position + '$', true, false).draw();
                } else {
                    table.column(3).search('').draw();
                }
            }

            // Status tab buttons
            $('.emp-status-tab').on('click', function() {
                const status = $(this).data('status');

                // Toggle active style
                $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
                $(this).removeClass('btn-outline-purple').addClass('active-tab btn-purple');

                // Sync status dropdown (visual only)
                $('#statusFilter').val(status).trigger('change.select2');

                // Word-boundary regex: \bactive\b tidak akan match "inactive"
                if (status) {
                    table.column(8).search('\\b' + status + '\\b', true, false, true).draw();
                } else {
                    table.column(8).search('').draw();
                }

                saveFilters();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').val('')
                    .trigger('change');
                $('#custom-search').val('');
                table.search('').columns().search('').draw();
                // Reset tab to "All"
                $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
                $('#tab-all').removeClass('btn-outline-purple').addClass('active-tab btn-purple');
                // Clear saved filters from sessionStorage
                sessionStorage.removeItem('employeeFilters');
            });

            // Handle Import Form Submit
            $('#importEmployeeForm').on('submit', function(e) {
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
                    url: "{{ route('employees.import') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $progress.addClass('d-none');
                        $result.html('<div class="alert alert-success py-1 px-2 mb-0">' +
                            response.message + '</div>');

                        setTimeout(function() {
                            $('#importEmployeeModal').modal('hide');
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        $progress.addClass('d-none');

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.failed_rows && xhr.responseJSON.failed_rows
                                .length > 0) {
                                var failedRows = xhr.responseJSON.failed_rows;
                                $.each(failedRows, function(index, item) {
                                    $failedBody.append('<tr>' +
                                        '<td>' + (item.row || '-') + '</td>' +
                                        '<td>' + (item.name || '-') + '</td>' +
                                        '<td class="text-danger">' + (item.error ||
                                            '-') + '</td>' +
                                        '</tr>');
                                });
                                $failedContainer.removeClass('d-none');

                                $result.html(
                                    '<div class="alert alert-warning py-1 px-2 mb-0">' +
                                    (xhr.responseJSON.message ||
                                        'Import completed with errors.') + '</div>');
                            } else {
                                $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' +
                                    (xhr.responseJSON.message || 'Import failed.') +
                                    '</div>');
                            }
                        } else {
                            $result.html('<div class="alert alert-danger py-1 px-2 mb-0">' +
                                'Terjadi kesalahan saat mengupload file.</div>');
                        }

                        $btn.prop('disabled', false);
                    }
                });
            });

            // Reset modal ketika ditutup
            $('#importEmployeeModal').on('hidden.bs.modal', function() {
                $('#importEmployeeForm')[0].reset();
                $('#importResult').empty();
                $('#importProgress').addClass('d-none');
                $('#failedRowsContainer').addClass('d-none');
                $('#failedRowsBody').empty();
                $('#importBtn').prop('disabled', false);
            });

            // Handle name click
            $(document).on('click', '.clickable-name', function(e) {
                e.stopPropagation();
                const employeeId = $(this).closest('tr').data('employee-id');
                if (employeeId) {
                    const originalContent = $(this).html();
                    $(this).html('<i class="spinner-border spinner-border-sm"></i> Loading...');
                    saveFilters(); // Save filters before navigation
                    window.location.href = `/employees/${employeeId}`;
                }
            });

            // Save filter state before navigating to edit page
            $(document).on('click', 'a[href*="/employees/"][href$="/edit"]', function(e) {
                saveFilters();
            });

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
