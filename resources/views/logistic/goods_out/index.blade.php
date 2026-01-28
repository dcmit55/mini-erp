@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="bi bi-box-arrow-up gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Goods Out</h2>
                    </div>

                    <!-- Spacer to push buttons to the right -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        @if (in_array(Auth::user()->role, ['admin_logistic', 'super_admin', 'admin']))
                            <a href="{{ route('goods_out.create_independent') }}" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Create
                            </a>
                        @endif
                        <a href="{{ route('goods_out.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm">
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

                <!-- Filters -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="material_filter" class="form-select form-select-sm select2">
                                <option value="">All Materials</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="project_filter" class="form-select form-select-sm select2">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="requested_by_filter" class="form-select form-select-sm select2">
                                <option value="">All Requesters</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}">{{ ucfirst($user->username) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="proceeded_at_filter" class="form-control form-control-sm"
                                placeholder="Proceeded At Date" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Search...">
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
                                <th>Material</th>
                                <th>Goods Out Qty</th>
                                <th><i class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Remaining Qty column serves as an indicator to monitor the quantity of goods that have not been returned (Goods In) to inventory after the Goods Out process."
                                        style="font-size: 0.8rem; cursor: pointer;"></i> Remaining Qty to Goods In
                                </th>
                                <th>Project</th>
                                <th>Requested By</th>
                                <th>Proceeded At</th>
                                <th>Remark</th>
                                <th>Actions</th>
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

        .form-control {
            border: 1px solid #ced4da !important;
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
        #datatable .quantity-column,
        #datatable .quantity-column-header {
            text-align: left !important;
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
                stateSave: true,
                ajax: {
                    url: "{{ route('goods_out.index') }}",
                    data: function(d) {
                        d.material_filter = $('#material_filter').val();
                        d.project_filter = $('#project_filter').val();
                        d.requested_by_filter = $('#requested_by_filter').val();
                        d.proceeded_at_filter = $('#proceeded_at_filter').val();
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
                        data: 'material',
                        name: 'material'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'quantity-column',
                        orderable: false
                    },
                    {
                        data: 'remaining_quantity',
                        name: 'remaining_quantity',
                        className: 'quantity-column',
                        orderable: false
                    },
                    {
                        data: 'project',
                        name: 'project'
                    },
                    {
                        data: 'requested_by',
                        name: 'requested_by'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                    },
                    {
                        data: 'remark',
                        name: 'remark'
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
                ], // Sort by proceeded_at (created_at) by default
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No goods out data available</div>',
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
                stateSave: false, // We handle filters manually
                drawCallback: function() {
                    // Reinitialize tooltips after table redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter functionality
            $('#material_filter, #project_filter, #requested_by_filter, #proceeded_at_filter').on('change',
                function() {
                    table.ajax.reload();
                });

            // Initialize flatpickr for proceeded at date filter
            if (window.flatpickr) {
                flatpickr("#proceeded_at_filter", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    locale: "default"
                });
            } else {
                // Fallback: enhance UX for native input
                const dateInput = document.getElementById('proceeded_at_filter');
                if (dateInput) {
                    dateInput.onfocus = function() {
                        this.type = 'date';
                    };
                    dateInput.onblur = function() {
                        if (!this.value) this.type = 'text';
                    };
                    if (!dateInput.value) dateInput.type = 'text';
                }
            }

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filter
            $('#reset-filters').on('click', function() {
                $('#material_filter, #project_filter, #requested_by_filter').val('').trigger('change');
                $('#proceeded_at_filter').val('');
                $('#custom-search').val('');
                table.ajax.reload();
            });

            // Delete functionality with AJAX
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const material = $(this).data('material');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete Goods Out for "${material}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const deleteUrl = `/goods_out/${id}`;

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
                                    `<b>${material}</b> has been deleted.`,
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

                                // Handle permission errors differently
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
