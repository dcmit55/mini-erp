@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-2">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-clock gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Timing Data</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    @if (auth()->user()->canModifyData())
                        <div class="ms-lg-auto d-flex flex-wrap gap-2">
                            <a href="{{ route('timings.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="bi bi-plus-circle me-1"></i> Input Timing
                            </a>
                        </div>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
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
                <table class="table table-striped table-hover table-bordered align-middle rounded shadow-sm"
                    id="timing-table">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Department</th>
                            <th>Step</th>
                            <th>Parts</th>
                            <th>Employee</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="timing-rows">
                        @include('timings.timing_table', ['timings' => $timings])
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
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

            // Inisialisasi DataTables
            dt = $('#timing-table').DataTable(dtConfig);

            // AJAX search & filter dengan debounce dan update URL
            let debounceTimer;
            $('input[name="search"], select[name="project_id"], select[name="department"], select[name="employee_id"]')
                .on('input change', function() {
                    updateQueryString(); // update URL setiap filter berubah
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        let state = dt.state ? dt.state.loaded() : null;

                        let search = $('input[name="search"]').val();
                        let project_id = $('select[name="project_id"]').val();
                        let department = $('select[name="department"]').val();
                        let employee_id = $('select[name="employee_id"]').val();

                        $.ajax({
                            url: "{{ route('timings.ajax_search') }}",
                            method: 'POST',
                            data: {
                                search: search,
                                project_id: project_id,
                                department: department,
                                employee_id: employee_id,
                            },
                            success: function(res) {
                                $('#timing-error-alert').addClass('d-none').text('');
                                try {
                                    if (dt && typeof dt.destroy === 'function') {
                                        dt.destroy();
                                    }
                                    $('#timing-rows').html(res.html);
                                    dt = $('#timing-table').DataTable(dtConfig);

                                    if (state) {
                                        if (state.start) dt.page(state.start / dt.page
                                            .len()).draw('page');
                                        if (state.order) dt.order(state.order).draw();
                                    }
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
                });
        });
    </script>
@endpush
