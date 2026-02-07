@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                {{-- Header dengan icon dan title --}}
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-chart-line gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Material Usage</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="#" id="export-btn" class="btn btn-outline-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Filter form --}}
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-3">
                            <select id="filter-material" name="material" class="form-select form-select-sm select2"
                                data-placeholder="All Materials">
                                <option value=""></option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filter-project" name="project" class="form-select form-select-sm select2"
                                data-placeholder="All Projects">
                                <option value=""></option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" id="custom-search" name="search" class="form-control form-control-sm"
                                placeholder="Search...">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm w-100"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                {{-- DataTable dengan server-side processing --}}
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="datatable" data-material-usage-table="1">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th width="50">#</th>
                                <th>Material</th>
                                <th>Project</th>
                                <th>Job Order</th>
                                <th>Goods Out Qty</th>
                                <th>Goods In Qty</th>
                                <th>Used Qty</th>
                                <th>Unit</th>
                                <th>Updated At</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data akan dimuat via AJAX --}}
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

        /* Vertical divider */
        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        /* DataTables footer styling - sesuai Leave Request */
        .datatables-footer-row {
            border-top: 1px solid #eee;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            text-align: left;
        }

        /* Center align checkbox and actions */
        #datatable thead th:nth-child(1),
        #datatable tbody td:nth-child(1),
        #datatable thead th:nth-child(10),
        #datatable tbody td:nth-child(10) {
            text-align: center !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%'
            }).on('select2:open', function() {
                setTimeout(() => {
                    document.querySelector('.select2-search__field')?.focus();
                }, 100);
            });

            // Auto-dismiss alerts
            setTimeout(() => $('.alert').fadeOut('slow'), 5000);

            // Initialize DataTable dengan server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: '{{ route('material_usage.index') }}',
                    data: function(d) {
                        d.material = $('#filter-material').val();
                        d.project = $('#filter-project').val();
                        d.search = $('#custom-search').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'material_name'
                    },
                    {
                        data: 'project_name'
                    },
                    {
                        data: 'job_order'
                    },
                    {
                        data: 'goods_out_qty',
                    },
                    {
                        data: 'goods_in_qty',
                    },
                    {
                        data: 'used_qty',
                    },
                    {
                        data: 'unit',
                    },
                    {
                        data: 'updated_at'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                pageLength: 15,
                lengthMenu: [
                    [5, 10, 15, 25, 50],
                    [5, 10, 15, 25, 50]
                ],
                order: [
                    []
                ],
                language: {
                    paginate: {
                        first: '<i class="bi bi-skip-start"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-skip-end"></i>'
                    },
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

            // Filter triggers
            $('#filter-material, #filter-project').on('change', function() {
                table.ajax.reload();
            });

            // Debounced search
            let searchTimeout;
            $('#custom-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    table.ajax.reload();
                }, 500);
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-form')[0].reset();
                $('.select2').val(null).trigger('change');
                $('#custom-search').val('');
                table.ajax.reload();
            });

            // Select all checkbox
            $('#select-all').on('click', function() {
                $('.row-checkbox').prop('checked', this.checked);
            });

            $(document).on('change', '.row-checkbox', function() {
                const allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
                $('#select-all').prop('checked', allChecked);
            });

            $('#export-btn').on('click', function(e) {
                e.preventDefault();
                const params = {
                    material: $('#filter-material').val(),
                    project: $('#filter-project').val(),
                    search: $('#custom-search').val()
                };
                const query = $.param(params);
                window.location.href = '{{ route('material_usage.export') }}' + '?' + query;
            });

            // Delete button handler
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const form = $(this).closest('.delete-form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This material usage record will be permanently deleted!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#8F12FE',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
