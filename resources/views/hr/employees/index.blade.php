@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    @php
        $activeCount     = $employees->where('status', 'active')->count();
        $inactiveCount   = $employees->where('status', 'inactive')->count();
        $terminatedCount = $employees->where('status', 'terminated')->count();
        $allCount        = $employees->count();
        $expiringCount   = $employees->filter(fn($e) =>
            $e->contract_end_date &&
            $e->status === 'active' &&
            $e->contract_end_date->lte(now()->addDays(30)) &&
            $e->contract_end_date->gte(now())
        )->count();
    @endphp

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-semibold" style="color:#4A25AA;">Employees</h5>
            <small class="text-muted">{{ $allCount }} total &middot; {{ $activeCount }} active</small>
        </div>
        @canany(['hr.employees.create', 'hr.employees.import', 'hr.employees.view'])
        <div class="d-flex gap-2">
            @can('hr.employees.view')
            <a href="{{ route('employees.export', ['status' => request('status', 'all')]) }}"
               class="btn btn-sm btn-outline-success rounded-2">
                <i class="bi bi-file-earmark-excel me-1"></i>
                <span class="d-none d-sm-inline">Export</span>
            </a>
            @endcan
            @can('hr.employees.import')
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2"
                data-bs-toggle="modal" data-bs-target="#importEmployeeModal">
                <i class="bi bi-upload me-1"></i>
                <span class="d-none d-sm-inline">Import</span>
            </button>
            @endcan
            @can('hr.employees.create')
            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-purple rounded-2">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">Add Employee</span>
                <span class="d-sm-none">Add</span>
            </a>
            @endcan
        </div>
        @endcanany
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 py-2 px-3 mb-3 rounded-2" role="alert" style="font-size:.875rem;">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('import_results'))
        <div class="alert alert-info alert-dismissible fade show border-0 py-2 px-3 mb-3 rounded-2" role="alert" style="font-size:.875rem;">
            <i class="bi bi-info-circle me-1"></i>{{ session('import_results') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($expiringCount > 0)
        <div class="alert alert-warning alert-dismissible fade show border-0 py-2 px-3 mb-3 rounded-2 d-flex align-items-center gap-2" role="alert" style="font-size:.875rem;">
            <i class="bi bi-clock-history flex-shrink-0"></i>
            <div>
                <strong>{{ $expiringCount }} contract(s)</strong> expiring within 30 days.
                <span class="text-muted d-none d-md-inline">Status will auto-update to Inactive on expiry.</span>
            </div>
            <button type="button" class="btn-close btn-sm ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">

            {{-- Toolbar: tabs + filters --}}
            <div class="px-3 pt-3 pb-2 border-bottom">

                {{-- Status tabs --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm emp-status-tab active-tab btn-purple rounded-pill px-3"
                            data-status="" id="tab-all">
                            All <span class="tab-badge ms-1">{{ $allCount }}</span>
                        </button>
                        <button type="button" class="btn btn-sm emp-status-tab btn-outline-purple rounded-pill px-3"
                            data-status="active" id="tab-active">
                            <span class="d-none d-sm-inline">Active</span>
                            <span class="d-sm-none"><i class="bi bi-person-check"></i></span>
                            <span class="tab-badge ms-1">{{ $activeCount }}</span>
                        </button>
                        <button type="button" class="btn btn-sm emp-status-tab btn-outline-purple rounded-pill px-3"
                            data-status="inactive" id="tab-inactive">
                            <span class="d-none d-sm-inline">Inactive</span>
                            <span class="d-sm-none"><i class="bi bi-person-dash"></i></span>
                            <span class="tab-badge ms-1">{{ $inactiveCount }}</span>
                        </button>
                        @if($terminatedCount > 0)
                        <button type="button" class="btn btn-sm emp-status-tab btn-outline-purple rounded-pill px-3"
                            data-status="terminated" id="tab-terminated">
                            Terminated
                            <span class="tab-badge ms-1">{{ $terminatedCount }}</span>
                        </button>
                        @endif
                        <a href="{{ route('employees.near-expired') }}"
                           class="btn btn-sm rounded-pill px-3 {{ isset($isNearExpired) ? 'btn-warning' : 'btn-outline-warning' }}">
                            <i class="bi bi-clock me-1"></i>Near Expired
                            @if(isset($nearExpiredIds))
                                <span class="ms-1">{{ count($nearExpiredIds) }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Filters --}}
                <form id="filter-form" class="row g-2 align-items-center">
                    <div class="col-6 col-sm-4 col-md-2">
                        <select id="departmentFilter" class="form-select form-select-sm select2">
                            <option value="">All Departments</option>
                            @foreach($employees->pluck('department.name')->unique()->filter() as $dept)
                                <option value="{{ $dept }}">{{ ucfirst($dept) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <select id="employmentTypeFilter" class="form-select form-select-sm select2">
                            <option value="">All Types</option>
                            <option value="PKWT">PKWT</option>
                            <option value="PKWTT">PKWTT</option>
                            <option value="Daily Worker">Daily Worker</option>
                            <option value="Probation">Probation</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <select id="statusFilter" class="form-select form-select-sm select2">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-6 col-md-2">
                        <select id="positionFilter" class="form-select form-select-sm select2">
                            <option value="">All Positions</option>
                            @foreach($employees->pluck('position')->unique()->filter() as $position)
                                <option value="{{ $position }}">{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-10 col-sm-5 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search" style="font-size:.75rem;"></i>
                            </span>
                            <input type="text" id="custom-search" class="form-control border-start-0 ps-0"
                                placeholder="Search employees...">
                        </div>
                    </div>
                    <div class="col-2 col-sm-1">
                        <button type="button" id="reset-filters"
                            class="btn btn-sm btn-outline-secondary w-100 rounded-2"
                            title="Reset filters">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 w-100" id="employees-table">
                    <thead>
                        <tr class="text-muted" style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; background:#fafafa;">
                            <th class="text-center ps-3 border-0" style="width:52px">Photo</th>
                            <th class="border-0">Name</th>
                            <th class="d-none d-lg-table-cell border-0" style="width:130px">No.</th>
                            <th class="d-none d-md-table-cell border-0" style="width:130px">Position</th>
                            <th class="d-none d-lg-table-cell border-0">Department</th>
                            <th class="d-none d-xl-table-cell border-0">Contact</th>
                            <th class="d-none d-xl-table-cell border-0">Hire Date</th>
                            <th class="d-none d-xxl-table-cell border-0">Contract End</th>
                            <th class="d-none d-sm-table-cell border-0" style="width:110px">Type & Status</th>
                            <th class="text-center pe-3 border-0" style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                        <tr data-employee-id="{{ $employee->id }}"
                            data-status="{{ $employee->status }}"
                            data-near-expired="{{ in_array($employee->id, $nearExpiredIds ?? []) ? '1' : '0' }}">

                            {{-- Photo --}}
                            <td class="text-center ps-3">
                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}"
                                    class="rounded-circle employee-photo"
                                    style="width:34px;height:34px;object-fit:cover;">
                            </td>

                            {{-- Name --}}
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-medium" style="font-size:.875rem; line-height:1.3;">{{ $employee->name }}</span>
                                    @if(isset($activeSpMap[$employee->id]))
                                        @php
                                            $spLvl = $activeSpMap[$employee->id];
                                            $spBadgeColor = [1=>'info', 2=>'warning', 3=>'warning', 4=>'danger'][$spLvl] ?? 'secondary';
                                            $spTextColor  = in_array($spLvl, [2,3]) ? 'text-dark' : '';
                                        @endphp
                                        <a href="{{ route('warning-letters.index', ['employee_id' => $employee->id]) }}"
                                           class="badge bg-{{ $spBadgeColor }} {{ $spTextColor }} text-decoration-none"
                                           style="font-size:.58rem;"
                                           title="Active SP{{ $spLvl }}">SP{{ $spLvl }}</a>
                                    @endif
                                </div>
                                {{-- Fallback: employee no when No. column hidden (< lg) --}}
                                <div class="d-lg-none">
                                    <span class="text-muted" style="font-size:.68rem; font-family:monospace;">{{ $employee->employee_no }}</span>
                                </div>
                            </td>

                            {{-- Employee No --}}
                            <td class="d-none d-lg-table-cell" style="white-space:nowrap">
                                <span class="text-muted" style="font-size:.78rem; font-family:monospace;">{{ $employee->employee_no }}</span>
                            </td>

                            {{-- Position --}}
                            <td class="d-none d-md-table-cell">
                                <span class="badge rounded-pill text-truncate" style="background:#e8f4fd;color:#0c7a8f;font-size:.7rem;font-weight:500;max-width:120px;display:inline-block;">
                                    {{ $employee->position ?? '—' }}
                                </span>
                            </td>

                            {{-- Department --}}
                            <td class="d-none d-lg-table-cell">
                                @if($employee->department)
                                    <span class="badge rounded-pill" style="background:#eef2ff;color:#4A25AA;font-size:.7rem;font-weight:500;">
                                        {{ ucfirst($employee->department->name) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Contact --}}
                            <td class="d-none d-xl-table-cell">
                                @if($employee->phone)
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $employee->phone }}</div>
                                @endif
                                @if($employee->email)
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i>{{ Str::limit($employee->email, 22) }}</div>
                                @endif
                                @if(!$employee->phone && !$employee->email)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Hire Date --}}
                            <td class="d-none d-xl-table-cell">
                                @if($employee->hire_date)
                                    <div class="small">{{ $employee->hire_date->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $employee->hire_date->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Contract End --}}
                            <td class="d-none d-xxl-table-cell">
                                @if($employee->contract_end_date)
                                    @php $daysLeft = now()->diffInDays($employee->contract_end_date, false); @endphp
                                    <div class="small">{{ $employee->contract_end_date->format('d M Y') }}</div>
                                    @if($daysLeft < 0)
                                        <span class="badge bg-danger rounded-pill" style="font-size:.62rem;">Expired</span>
                                    @elseif($daysLeft <= 30)
                                        <span class="badge bg-warning text-dark rounded-pill" style="font-size:.62rem;">{{ $daysLeft }}d left</span>
                                    @else
                                        <small class="text-muted">{{ $employee->contract_end_date->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Type & Status --}}
                            <td class="d-none d-sm-table-cell">
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge rounded-pill bg-{{ $employee->employment_type_badge['color'] }}" style="font-size:.67rem;width:fit-content;">
                                        {{ $employee->employment_type }}
                                    </span>
                                    <span class="badge rounded-pill bg-{{ $employee->status_badge['color'] }}" style="font-size:.67rem;width:fit-content;">
                                        {{ $employee->status_badge['text'] }}
                                    </span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="text-center pe-3">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="btn btn-icon btn-sm text-primary" title="View"
                                       data-bs-toggle="tooltip" style="width:28px;height:28px;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('production.timing.view')
                                    <a href="{{ route('employees.timing', $employee) }}"
                                       class="btn btn-icon btn-sm text-info" title="Timing"
                                       data-bs-toggle="tooltip" style="width:28px;height:28px;">
                                        <i class="bi bi-clock"></i>
                                    </a>
                                    @endcan
                                    @can('hr.employees.edit')
                                        <a href="{{ route('employees.edit', $employee) }}"
                                           class="btn btn-icon btn-sm text-warning" title="Edit"
                                           data-bs-toggle="tooltip" style="width:28px;height:28px;">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('hr.employees.delete')
                                        <button type="button"
                                            class="btn btn-icon btn-sm text-danger delete-employee-btn"
                                            data-employee-id="{{ $employee->id }}"
                                            data-employee-name="{{ $employee->name }}"
                                            title="Delete" data-bs-toggle="tooltip"
                                            style="width:28px;height:28px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
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

{{-- Import Modal --}}
<div class="modal fade" id="importEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-upload me-2"></i>Import Employees from Excel
                </h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="importEmployeeForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3 border-0 rounded-2">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Required columns:</strong> employee_no, name, position, status &nbsp;·&nbsp;
                        Date format: <code>YYYY-MM-DD</code> &nbsp;·&nbsp; Max 10MB (.xlsx, .xls, .csv)
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Optional: <strong>username</strong> (attendance machine name)</li>
                            <li>Duplicate employee_no will be updated automatically</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Excel File</label>
                        <input type="file" name="file" id="import_file" class="form-control form-control-sm"
                            required accept=".xlsx,.xls,.csv">
                    </div>
                    <div id="importProgress" class="progress d-none mb-2" style="height:6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%"></div>
                    </div>
                    <div id="importResult" class="mt-2 small"></div>
                    <div id="failedRowsContainer" class="mt-3 d-none">
                        <h6 class="small fw-semibold text-danger mb-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>Failed Rows
                        </h6>
                        <div class="table-responsive" style="max-height:250px;">
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Row</th><th>Name</th><th>Error</th>
                                    </tr>
                                </thead>
                                <tbody id="failedRowsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success d-none" id="importReloadBtn"
                        onclick="$('#importEmployeeModal').modal('hide'); location.reload();">
                        <i class="bi bi-arrow-clockwise me-1"></i>Close & Refresh
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
    /* Purple theme */
    .btn-purple {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        border-color: #8F12FE;
        color: #fff;
    }
    .btn-purple:hover, .btn-purple:focus {
        background: linear-gradient(135deg, #7a0fe0 0%, #3b1e8e 100%);
        border-color: #7a0fe0;
        color: #fff;
    }
    .btn-outline-purple {
        border: 1px solid #8F12FE;
        color: #8F12FE;
        background: transparent;
    }
    .btn-outline-purple:hover, .btn-outline-purple:focus {
        background: rgba(143,18,254,.08);
        color: #4A25AA;
        border-color: #4A25AA;
    }
    .emp-status-tab.active-tab {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        border-color: #8F12FE;
        color: #fff;
    }
    .emp-status-tab.active-tab .tab-badge { color: rgba(255,255,255,.8); }
    .emp-status-tab:not(.active-tab) .tab-badge { color: #8F12FE; }

    /* Table */
    #employees-table thead th {
        padding: 8px 10px;
        font-weight: 600;
        border-bottom: 1px solid #f0f0f0;
    }
    #employees-table tbody td {
        padding: 9px 10px;
        border-bottom: 1px solid #f8f8f8;
        vertical-align: middle;
    }
    #employees-table tbody tr:hover td { background: #fafbff; }

    /* Icon action buttons */
    .btn-icon {
        background: transparent;
        border: none;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: background .15s;
    }
    .btn-icon:hover { background: rgba(0,0,0,.06); }
    .btn-icon i { font-size: .85rem; }

    /* Employee photo */
    .employee-photo {
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
        transition: transform .2s;
    }
    .employee-photo:hover { transform: scale(1.08); }


    /* Filter form */
    #filter-form .form-select-sm, #filter-form .form-control { font-size: .8rem; }

    /* Prevent DataTables from overflowing the container */
    #employees-table { width: 100% !important; table-layout: auto; }
    .dataTables_wrapper { width: 100%; overflow: hidden; }
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    /* DataTables footer */
    .datatables-footer-row {
        border-top: 1px solid #f0f0f0;
        padding: .5rem 1rem;
    }
    .datatables-left { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .vr-divider { width:1px; height:20px; background:#dee2e6; display:inline-block; }
    .dataTables_paginate { display:flex; justify-content:flex-end; align-items:center; flex-wrap:wrap; }

    /* Pagination */
    .page-item.active .page-link {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        border-color: #8F12FE;
    }
    .page-link { transition: all .15s; font-size:.8rem; }

    @media (max-width: 575.98px) {
        .datatables-footer-row { flex-direction: column !important; align-items: flex-start !important; }
        .vr-divider { display:none; }
        .dataTables_paginate { width:100%; justify-content: center !important; }
        .dataTables_paginate .pagination { justify-content: center; flex-wrap: wrap; }
    }
</style>
@endpush

@push('scripts')
<script>
    var isNearExpiredMode = {{ isset($isNearExpired) ? 'true' : 'false' }};

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!isNearExpiredMode) return true;
        var $row = $(settings.nTable).find('tbody tr').eq(dataIndex);
        return $row.data('near-expired') == '1';
    });

    $(document).ready(function() {
        const table = $('#employees-table').DataTable({
            processing: false,
            searching: true,
            paging: true,
            info: true,
            ordering: true,
            lengthChange: true,
            pageLength: 15,
            lengthMenu: [[10,15,25,50,100],[10,15,25,50,100]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search in table...",
                emptyTable: '<div class="text-muted py-3">No employee data available</div>',
                zeroRecords: '<div class="text-muted py-3">No matching records found</div>',
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                lengthMenu: "Show _MENU_ per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
            },
            dom: 't<"datatables-footer-row d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2"<"datatables-left"l<"vr-divider mx-2">i><"dataTables_paginate"p>>',
            columnDefs: [
                { targets: [0], orderable: false, searchable: false, className: "text-center" },
                { targets: [2], orderable: false, searchable: false },
                { targets: [9], orderable: false, searchable: false, className: "text-center" }
            ],
            order: [[]],
            responsive: false,
            autoWidth: false,
            stateSave: false,
            drawCallback: function() { $('[data-bs-toggle="tooltip"]').tooltip(); }
        });

        function restoreFilters() {
            const saved = sessionStorage.getItem('employeeFilters');
            if (saved) {
                const f = JSON.parse(saved);
                if (f.department)     $('#departmentFilter').val(f.department);
                if (f.employmentType) $('#employmentTypeFilter').val(f.employmentType);
                if (f.status)         $('#statusFilter').val(f.status);
                if (f.position)       $('#positionFilter').val(f.position);
                if (f.search)         $('#custom-search').val(f.search);
                $('.select2').trigger('change.select2');
                if (f.status) {
                    $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
                    $('.emp-status-tab[data-status="' + f.status + '"]').removeClass('btn-outline-purple').addClass('active-tab btn-purple');
                }
                applyFilters();
                if (f.search) table.search(f.search).draw();
            }
        }

        function saveFilters() {
            sessionStorage.setItem('employeeFilters', JSON.stringify({
                department:     $('#departmentFilter').val(),
                employmentType: $('#employmentTypeFilter').val(),
                status:         $('#statusFilter').val(),
                position:       $('#positionFilter').val(),
                search:         $('#custom-search').val()
            }));
        }

        $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').on('change', function() {
            applyFilters(); saveFilters();
        });

        $('#custom-search').on('input', debounce(function() {
            table.search($(this).val()).draw(); saveFilters();
        }, 500));

        if (!isNearExpiredMode) restoreFilters(); else table.draw();

        function applyFilters() {
            const dept     = $('#departmentFilter').val();
            const empType  = $('#employmentTypeFilter').val();
            const status   = $('#statusFilter').val();
            const position = $('#positionFilter').val();

            table.column(4).search(dept ? dept : '', false, false).draw(false);

            let patterns = [];
            if (empType) patterns.push('\\b' + empType + '\\b');
            if (status)  patterns.push('\\b' + status  + '\\b');
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

        $('.emp-status-tab').on('click', function() {
            isNearExpiredMode = false;
            const status = $(this).data('status');
            $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
            $(this).removeClass('btn-outline-purple').addClass('active-tab btn-purple');
            $('#statusFilter').val(status).trigger('change.select2');
            if (status) {
                table.column(8).search('\\b' + status + '\\b', true, false, true).draw();
            } else {
                table.column(8).search('').draw();
            }
            saveFilters();
        });

        $('#reset-filters').on('click', function() {
            isNearExpiredMode = false;
            $('#departmentFilter, #employmentTypeFilter, #statusFilter, #positionFilter').val('').trigger('change');
            $('#custom-search').val('');
            table.search('').columns().search('').draw();
            $('.emp-status-tab').removeClass('active-tab btn-purple').addClass('btn-outline-purple');
            $('#tab-all').removeClass('btn-outline-purple').addClass('active-tab btn-purple');
            sessionStorage.removeItem('employeeFilters');
        });

        // Import form
        $('#importEmployeeForm').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#importBtn').prop('disabled', true);
            $('#importProgress').removeClass('d-none');
            $('#importResult').html('');
            $('#failedRowsContainer').addClass('d-none');
            $('#failedRowsBody').empty();

            $.ajax({
                url: "{{ route('employees.import') }}",
                type: "POST",
                data: new FormData(this),
                processData: false, contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(r) {
                    $('#importProgress').addClass('d-none');
                    $('#importResult').html('<div class="alert alert-success py-1 px-2 mb-0 small">' + r.message + '</div>');
                    $btn.addClass('d-none');
                    $('#importReloadBtn').removeClass('d-none');
                },
                error: function(xhr) {
                    $('#importProgress').addClass('d-none');
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.failed_rows && xhr.responseJSON.failed_rows.length > 0) {
                            $.each(xhr.responseJSON.failed_rows, function(i, item) {
                                $('#failedRowsBody').append('<tr><td>' + (item.row||'-') + '</td><td>' + (item.name||'-') + '</td><td class="text-danger">' + (item.error||'-') + '</td></tr>');
                            });
                            $('#failedRowsContainer').removeClass('d-none');
                            $('#importResult').html('<div class="alert alert-warning py-1 px-2 mb-0 small">' + (xhr.responseJSON.message || 'Import completed with errors.') + '</div>');
                        } else {
                            $('#importResult').html('<div class="alert alert-danger py-1 px-2 mb-0 small">' + (xhr.responseJSON.message || 'Import failed.') + '</div>');
                        }
                    } else {
                        $('#importResult').html('<div class="alert alert-danger py-1 px-2 mb-0 small">An error occurred while uploading the file.</div>');
                    }
                    $btn.prop('disabled', false);
                }
            });
        });

        $('#importEmployeeModal').on('hidden.bs.modal', function() {
            $('#importEmployeeForm')[0].reset();
            $('#importResult').empty();
            $('#importProgress').addClass('d-none');
            $('#failedRowsContainer').addClass('d-none');
            $('#failedRowsBody').empty();
            $('#importBtn').prop('disabled', false).removeClass('d-none');
            $('#importReloadBtn').addClass('d-none');
        });

        $(document).on('click', 'a[href*="/employees/"][href$="/edit"]', saveFilters);

        $(document).on('click', '.delete-employee-btn', function(e) {
            e.preventDefault(); e.stopPropagation();
            const id = $(this).data('employee-id');
            const name = $(this).data('employee-name');
            Swal.fire({
                title: 'Delete Employee',
                html: `Are you sure you want to delete <strong>"${name}"</strong>?<br><br><div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>This will delete all related data.</div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(r => {
                if (r.isConfirmed) {
                    const f = $(`<form action="/employees/${id}" method="POST" style="display:none"><input name="_token" value="${$('meta[name="csrf-token"]').attr('content')}"><input name="_method" value="DELETE"></form>`);
                    $('body').append(f); f.submit();
                }
            });
        });

        $('.select2').select2({ theme: 'bootstrap-5', allowClear: true, width: '100%' });

        function debounce(fn, wait) {
            let t; return function() { clearTimeout(t); t = setTimeout(() => fn.apply(this, arguments), wait); };
        }

        setTimeout(() => $('.alert').fadeOut('slow'), 5000);
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
@endpush
