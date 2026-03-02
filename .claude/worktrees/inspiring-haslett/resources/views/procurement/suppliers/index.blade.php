@extends('layouts.app')

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Filter Form Styling - sesuai Goods Out */
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
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-4">
        <!-- Card Wrapper -->
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="bi bi-truck gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Suppliers List</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Add Supplier
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
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {!! session('warning') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filter Form -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="location_filter" class="form-select form-select-sm select2">
                                <option value="">All Locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="status_filter" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blacklisted">Blacklisted</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Search by name, code, or contact person...">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm w-100"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DataTable dengan Server-Side Processing -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle" id="datatable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>#</th>
                                <th>Supplier Code</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Contact Person</th>
                                <th>Lead Time</th>
                                <th>Status</th>
                                <th>Remark</th>
                                <th>Actions</th>
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
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable dengan server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    url: "{{ route('suppliers.index') }}",
                    data: function(d) {
                        d.location_filter = $('#location_filter').val();
                        d.status_filter = $('#status_filter').val();
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
                        data: 'supplier_code',
                        name: 'supplier_code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },
                    {
                        data: 'contact_person',
                        name: 'contact_person'
                    },
                    {
                        data: 'lead_time_days',
                        name: 'lead_time_days',
                        className: 'text-center'
                    },
                    {
                        data: 'status_badge',
                        name: 'status'
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
                ],
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No suppliers data available</div>',
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
            $('#location_filter, #status_filter').on('change', function() {
                table.ajax.reload();
            });

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#location_filter, #status_filter').val('').trigger('change');
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

            // Delete functionality dengan AJAX
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete supplier "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const deleteUrl = `/suppliers/${id}`;

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
                                Swal.fire('Deleted!',
                                    `Supplier <b>${name}</b> has been deleted.`,
                                    'success');
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
            }).on('select2:open', function() {
                // Auto-focus search field
                setTimeout(() => {
                    const searchField = document.querySelector('.select2-search__field');
                    if (searchField) searchField.focus();
                }, 100);
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Auto-dismiss alerts
            setTimeout(() => {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endpush
