@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <!-- Quick Navigation Bar -->
        <div class="mb-3">
            <a href="{{ route('efficiency.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Efficiency Dashboard
            </a>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-2">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-clock gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Timing Data</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-cut me-1"></i> Costume Timing
                        </a>
                        <a href="{{ route('timings.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Input Timing
                        </a>
                        <!-- Import Button -->
                        <button type="button" class="btn btn-success btn-sm flex-shrink-0" data-bs-toggle="modal"
                            data-bs-target="#importModal">
                            <i class="bi bi-filetype-xls me-1"></i> Import
                        </button>
                        <!-- Export Button -->
                        <button type="button" id="export-btn" class="btn btn-outline-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if (session('errors') && count(session('errors')) > 0)
                    <div class="alert alert-warning">
                        <strong>Import Errors:</strong>
                        <ul class="mb-0">
                            @foreach (session('errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('warnings') && count(session('warnings')) > 0)
                    <div class="alert alert-info">
                        <strong>Import Warnings:</strong>
                        <ul class="mb-0">
                            @foreach (session('warnings') as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="GET" class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                            placeholder="Search step, remarks...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Filter Project</label>
                        <select name="project_id" class="form-select select2" data-placeholder="All Projects">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Filter Job Order</label>
                        <select name="job_order_id" class="form-select select2" data-placeholder="All Job Orders">
                            <option value="">All Job Orders</option>
                            @foreach ($jobOrders as $jo)
                                <option value="{{ $jo->id }}"
                                    {{ request('job_order_id') == $jo->id ? 'selected' : '' }}>
                                    {{ $jo->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Department</label>
                        <select name="department" class="form-select select2" data-placeholder="All Departments">
                            <option value="">All Departments</option>
                            @foreach ($departments as $id => $deptName)
                                <option value="{{ $deptName }}"
                                    {{ request('department') == $deptName ? 'selected' : '' }}>
                                    {{ ucfirst($deptName) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Employee</label>
                        <select name="employee_id" class="form-select select2" data-placeholder="All Employees">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end gap-2">
                        <a href="{{ route('timings.index') }}" class="btn btn-secondary"
                            title="Reset All Filters">Reset</a>
                    </div>
                </form>
                <div id="timing-error-alert" class="alert alert-danger d-none" role="alert"></div>
                <table class="table table-sm table-hover align-middle rounded" id="timing-table">
                    <thead class="table-light">
                        <tr>
                            <th class="date-col">Date</th>
                            <th>Project</th>
                            <th>Job Order</th>
                            <th>Department</th>
                            <th>Step</th>
                            <th>Parts</th>
                            <th>Employee</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration (min)</th>
                            <th>Value</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="timing-rows">
                        @forelse($timings as $timing)
                            <tr>
                                {{-- Date --}}
                                <td class="date-col">
                                    {{ $timing->tanggal ? \Carbon\Carbon::parse($timing->tanggal)->format('d M Y') : '-' }}
                                </td>

                                {{-- Project (no badge) --}}
                                <td>{{ $timing->project ? $timing->project->name : '-' }}</td>

                                {{-- Job Order (no badge) --}}
                                <td>{{ $timing->jobOrder ? $timing->jobOrder->name : '-' }}</td>

                                {{-- Department --}}
                                <td>
                                    @if ($timing->employee && $timing->employee->department)
                                        {{ $timing->employee->department->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- Step --}}
                                <td>{{ $timing->step ?? '-' }}</td>

                                {{-- Parts --}}
                                <td>{{ $timing->parts ?? '-' }}</td>

                                {{-- Employee --}}
                                <td>{{ $timing->employee ? $timing->employee->name : '-' }}</td>

                                {{-- Start Time --}}
                                <td>{{ $timing->start_time ? \Carbon\Carbon::parse($timing->start_time)->format('H:i') : '-' }}
                                </td>

                                {{-- End Time --}}
                                <td>
                                    @if ($timing->end_time)
                                        {{ \Carbon\Carbon::parse($timing->end_time)->format('H:i') }}
                                    @else
                                        <span class="badge bg-warning">Running</span>
                                    @endif
                                </td>

                                {{-- Duration in Minutes --}}
                                <td>
                                    @php
                                        $minutes = 0;
                                        if ($timing->duration_minutes && $timing->duration_minutes > 0) {
                                            $minutes = $timing->duration_minutes;
                                        } elseif ($timing->start_time && $timing->end_time) {
                                            $start = \Carbon\Carbon::parse($timing->start_time);
                                            $end = \Carbon\Carbon::parse($timing->end_time);
                                            $minutes = $start->diffInMinutes($end);
                                        }
                                    @endphp
                                    {{ $minutes > 0 ? $minutes . ' min' : '-' }}
                                </td>

                                {{-- Value from measurement_value --}}
                                <td>{{ $timing->measurement_value ?? '-' }}</td>

                                {{-- Type from measurement_type --}}
                                <td>
                                    @if ($timing->measurement_type == 'qty')
                                        Qty
                                    @elseif($timing->measurement_type == 'progress')
                                        Progress
                                    @else
                                        {{ $timing->measurement_type ?? '-' }}
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if ($timing->status == 'complete')
                                        <span class="badge bg-success">Complete</span>
                                    @elseif($timing->status == 'on progress')
                                        <span class="badge bg-warning">On Progress</span>
                                    @elseif($timing->status == 'pending')
                                        <span class="badge bg-secondary">Pending</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ ucfirst($timing->status ?? '-') }}</span>
                                    @endif
                                </td>

                                {{-- Approval Status --}}
                                <td>
                                    @if ($timing->approval_status == 'approved')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </span>
                                    @elseif($timing->approval_status == 'rejected')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </span>
                                    @else
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @endif
                                </td>

                                {{-- Remarks --}}
                                <td>{{ $timing->remarks ?? '-' }}</td>

                                {{-- Actions --}}
                                <td class="text-nowrap">
                                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isLogisticAdmin() || auth()->user()->isAdminTiming() || auth()->user()->id == $timing->employee_id)
                                        <a href="{{ route('timings.edit', $timing->id) }}" class="btn btn-sm btn-primary"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        @if (auth()->user()->isSuperAdmin())
                                            <form action="{{ route('timings.destroy', $timing->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this timing record?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="16" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mt-2 text-muted">No timing data found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="bi bi-upload me-2"></i>Import Timing Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('timings.import') }}" method="POST" enctype="multipart/form-data"
                    id="import-form">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="xls_file" class="form-label">Choose Excel File</label>
                            <input type="file" class="form-control" id="xls_file" name="xls_file"
                                accept=".xlsx,.xls" required>
                            <div class="form-text">
                                Only .xlsx and .xls files are allowed. Maximum file size: 2MB
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-1"></i>Import Guidelines:</h6>
                            <ul class="mb-0 small">
                                <li>Download the template first to see the required format and detailed instructions</li>
                                <li><strong>Required Fields:</strong>
                                    <ul>
                                        <li><strong>Date:</strong> DD/MM/YYYY or DD-MM-YYYY (e.g., 15/01/2024, 15-01-2024)
                                        </li>
                                        <li><strong>Job Order OR Project:</strong> At least ONE must be filled (names must
                                            exist in system)</li>
                                        <li><strong>Department:</strong> Must match existing department name exactly</li>
                                        <li><strong>Employee:</strong> Must match existing employee name exactly</li>
                                        <li><strong>Start & End Time:</strong> HH:MM in 24-hour format (e.g., 08:00, 13:30)
                                        </li>
                                    </ul>
                                </li>
                                <li><strong>Optional Fields:</strong> step, parts, value, type, status, approval, remarks
                                </li>
                                <li><strong>Duration:</strong> Auto-calculated from start and end time (leave empty)</li>
                                <li><strong>Important Notes:</strong>
                                    <ul>
                                        <li>If both job_order and project provided, system uses job_order's project</li>
                                        <li>Date formats supported: DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD</li>
                                        <li>Time must be in 24-hour format (not AM/PM)</li>
                                        <li>Ensure date and time columns are formatted as TEXT in Excel, not DATE/TIME
                                            format</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('timings.template') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-download me-1"></i>Download Template
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="import-submit-btn">
                            <i class="bi bi-upload me-1"></i>Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Date Column Width */
        .date-col {
            min-width: 180px;
            white-space: nowrap;
        }

        /* Select2 Styling */
        .select2-container .select2-selection--single {
            height: 2.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.375rem;
        }

        .select2-selection__rendered {
            line-height: 2.2rem;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 2.375rem;
        }

        /* DataTables Styling */
        .dataTables_wrapper {
            margin-top: 1rem;
        }

        /* Spacing antara tabel dan elemen bawah */
        .dataTables_wrapper .bottom {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        /* Grid layout untuk elemen bawah */
        .dataTables_wrapper .row {
            margin: 0;
            align-items: center;
        }

        .dataTables_wrapper .row .col-md-6 {
            display: flex;
            align-items: center;
            padding: 0 0.75rem;
        }

        /* Left side: Length dan Info */
        .dataTables_wrapper .row .col-md-6:first-child {
            justify-content: flex-start;
        }

        /* Right side: Pagination */
        .dataTables_wrapper .row .col-md-6:last-child {
            justify-content: flex-end !important;
            display: flex !important;
            flex: 1 1 0%;
            /* Tambahan agar child ini ambil sisa ruang */
        }

        /* Individual element styling */
        .dataTables_length {
            margin-right: 1rem;
            margin-bottom: 0 !important;
        }

        .dataTables_info {
            margin-bottom: 0 !important;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .dataTables_paginate {
            margin-bottom: 0 !important;
            margin-left: auto !important;
        }

        /* Pagination buttons styling */
        .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 0.125rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background: white;
            color: #495057;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .dataTables_paginate .paginate_button:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }

        .dataTables_paginate .paginate_button.current {
            background: #8116ed;
            border-color: #8116ed;
            color: white;
        }

        .dataTables_paginate .paginate_button.disabled {
            color: #6c757d;
            background: #f8f9fa;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        /* Length select styling */
        .dataTables_length select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin: 0 0.5rem;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .dataTables_wrapper .row .col-md-6 {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 1rem;
            }

            .dataTables_wrapper .row .col-md-6:last-child {
                align-items: flex-end;
            }

            .dataTables_length {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }

        .no-data-row td {
            background-color: #f8f9fa;
            font-style: italic;
            color: #6c757d;
        }

        .no-data-row td i {
            margin-right: 0.5rem;
        }

        /* Custom badge colors */
        .text-bg-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Loading state for import */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let dt;
        let dtConfig = {
            responsive: true,
            stateSave: true,
            searching: false,
            paging: true,
            info: true,
            ordering: true,
            order: [
                [0, 'desc']
            ], // Sort by Date column (index 0) descending - NEWEST FIRST
            lengthChange: true,
            pageLength: 25,
            language: {
                info: "_START_ to _END_ of _TOTAL_ entries",
                emptyTable: "No timing data found",
                zeroRecords: "No timing data found"
            },
            columnDefs: [{
                targets: '_all',
                defaultContent: '-'
            }],
            createdRow: function(row, data, dataIndex) {
                if ($(row).hasClass('no-data-row')) {
                    // Hapus semua <td> setelah yang pertama agar colspan tetap
                    $(row).find('td:gt(0)').remove();
                }
            }
        };

        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Fungsi update query string di URL
            function updateQueryString() {
                const params = new URLSearchParams();
                const search = $('input[name="search"]').val();
                const project_id = $('select[name="project_id"]').val();
                const department = $('select[name="department"]').val();
                const employee_id = $('select[name="employee_id"]').val();

                if (search) params.set('search', search);
                if (project_id) params.set('project_id', project_id);
                if (department) params.set('department', department);
                if (employee_id) params.set('employee_id', employee_id);

                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }

            // Fungsi set filter dari URL saat halaman load
            function setFiltersFromUrl() {
                const params = new URLSearchParams(window.location.search);
                if (params.has('search')) $('input[name="search"]').val(params.get('search'));
                if (params.has('project_id')) $('select[name="project_id"]').val(params.get('project_id')).trigger(
                    'change');
                if (params.has('department')) $('select[name="department"]').val(params.get('department')).trigger(
                    'change');
                if (params.has('employee_id')) $('select[name="employee_id"]').val(params.get('employee_id'))
                    .trigger('change');
            }
            setFiltersFromUrl();

            // Clear old sorting state untuk enforce newest first
            if (localStorage.getItem('DataTables_timing-table_/production/timings')) {
                let savedState = JSON.parse(localStorage.getItem('DataTables_timing-table_/production/timings'));
                savedState.order = [
                    [0, 'desc']
                ]; // Force newest first
                localStorage.setItem('DataTables_timing-table_/production/timings', JSON.stringify(savedState));
            }

            // Inisialisasi DataTables
            dt = $('#timing-table').DataTable(dtConfig);

            // AJAX search & filter dengan debounce dan update URL
            let debounceTimer;
            function triggerSearch() {
                updateQueryString();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    let state = dt.state ? dt.state.loaded() : null;
                    let search      = $('input[name="search"]').val();
                    let project_id  = $('select[name="project_id"]').val();
                    let job_order_id = $('select[name="job_order_id"]').val();
                    let department  = $('select[name="department"]').val();
                    let employee_id = $('select[name="employee_id"]').val();

                    $.ajax({
                        url: "{{ route('timings.ajax_search') }}",
                        method: 'POST',
                        data: {
                            search, project_id, job_order_id, department, employee_id,
                        },
                            success: function(res) {
                                $('#timing-error-alert').addClass('d-none').text('');
                                try {
                                    if (dt && typeof dt.destroy === 'function') {
                                        dt.destroy();
                                    }
                                    $('#timing-rows').html(res.html);

                                    // Force newest first sorting before re-init
                                    dtConfig.order = [
                                        [0, 'desc']
                                    ];
                                    dt = $('#timing-table').DataTable(dtConfig);

                                    // Restore page position only, NOT sorting
                                    if (state && state.start) {
                                        dt.page(state.start / dt.page.len()).draw('page');
                                    }

                                    // Ensure sorting is DESC after draw
                                    dt.order([
                                        [0, 'desc']
                                    ]).draw();
                                } catch (error) {
                                    location.reload();
                                }
                            },
                            error: function(xhr) {
                                let msg =
                                    'Failed to load data. Please check your connection or try again in a while.';
                                if (xhr.status === 500) {
                                    msg =
                                        'An error occurred on the server. Please try again later.';
                                } else if (xhr.status === 404) {
                                    msg = 'Data not found.';
                                }
                                $('#timing-error-alert').removeClass('d-none').text(msg);
                            }
                        });
                    }, 400); // 400ms debounce
            }

            $('input[name="search"], select[name="project_id"], select[name="job_order_id"], select[name="department"], select[name="employee_id"]')
                .on('input change', function() { triggerSearch(); });

            $('#export-btn').on('click', function() {
                // Ambil filter dari form
                const params = new URLSearchParams();
                const search = $('input[name="search"]').val();
                const project_id = $('select[name="project_id"]').val();
                const job_order_id = $('select[name="job_order_id"]').val();
                const department = $('select[name="department"]').val();
                const employee_id = $('select[name="employee_id"]').val();

                if (search) params.set('search', search);
                if (project_id) params.set('project_id', project_id);
                if (job_order_id) params.set('job_order_id', job_order_id);
                if (department) params.set('department', department);
                if (employee_id) params.set('employee_id', employee_id);

                // Redirect ke route export dengan query string
                window.location.href = '{{ route('timings.export') }}' + (params.toString() ? '?' + params
                    .toString() : '');
            });
        });
    </script>
@endpush
