@extends('layouts.app')

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-responsive {
            overflow-x: auto;
        }

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

        .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Form controls in table */
        .form-select-sm,
        .form-control-sm {
            font-size: 0.875rem;
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
                        <i class="fas fa-external-link-alt gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Purchase Requests</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('purchase_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Request
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

                <!-- Filters -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-2">
                        <div class="col-lg-2">
                            <select name="type_filter" id="type_filter" class="form-select">
                                <option value="">All Types</option>
                                <option value="new_material">New Material</option>
                                <option value="restock">Restock</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="project_filter" id="project_filter" class="form-select">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="supplier_filter" id="supplier_filter" class="form-select">
                                <option value="">All Suppliers</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="approval_filter" id="approval_filter" class="form-select">
                                <option value="">All Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Decline">Declined</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="text" id="custom-search" class="form-control" placeholder="Search requests...">
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="button" id="reset-filter" class="btn btn-secondary"
                                title="Reset All Filters">Reset</button>
                        </div>
                    </form>
                </div>

                @php
                    $canViewUnitPrice = in_array(auth()->user()->role, [
                        'super_admin',
                        'admin',
                        'admin_procurement',
                        'admin_logistic',
                        'admin_finance',
                    ]);
                @endphp

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="datatable">
                        <thead class="table-dark align-middle text-nowrap">
                            <tr>
                                <th width="50">#</th>
                                <th>Type</th>
                                <th>Material Name</th>
                                <th>Required Qty</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                @if ($canViewUnitPrice)
                                    <th>Unit Price</th>
                                @endif
                                <th>Currency</th>
                                <th>Approval Status</th>
                                <th>Delivery Date
                                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Delivery date to forwarder"></i>
                                </th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Remark</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            @forelse ($requests as $index => $req)
                                <tr data-id="{{ $req->id }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $req->type)) }}</td>
                                    <td>
                                        <div>
                                            @if ($req->stock_level !== null)
                                                <i class="bi bi-info-circle text-secondary" style="cursor: pointer;"
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Current stock: {{ number_format($req->stock_level, 2) }} {{ $req->unit }}"></i>
                                            @endif
                                            {{ $req->material_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $req->unit }}">
                                            {{ number_format($req->required_quantity, 2) }}
                                        </span>
                                    </td>
                                    <td>{{ $req->project->name ?? '-' }}</td>
                                    <td>
                                        @if ($canViewUnitPrice)
                                            <select class="form-select form-select-sm supplier-select"
                                                data-id="{{ $req->id }}">
                                                <option value="">-</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        @if ($req->supplier_id == $supplier->id) selected @endif>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $req->supplier->name ?? '-' }}
                                        @endif
                                    </td>
                                    @if ($canViewUnitPrice)
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                class="form-control form-control-sm price-input"
                                                value="{{ $req->price_per_unit ?? '' }}" data-id="{{ $req->id }}">
                                        </td>
                                    @endif
                                    <td>
                                        @if ($canViewUnitPrice)
                                            <select class="form-select form-select-sm currency-select"
                                                data-id="{{ $req->id }}">
                                                <option value="">-</option>
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->id }}"
                                                        @if ($req->currency_id == $currency->id) selected @endif>
                                                        {{ $currency->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $req->currency->name ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canViewUnitPrice)
                                            <select class="form-select form-select-sm approval-select"
                                                data-id="{{ $req->id }}">
                                                <option value="">Pending</option>
                                                <option value="Approved"
                                                    @if ($req->approval_status == 'Approved') selected @endif>
                                                    Approved</option>
                                                <option value="Decline" @if ($req->approval_status == 'Decline') selected @endif>
                                                    Decline</option>
                                            </select>
                                        @else
                                            {{ $req->approval_status ?? 'Pending' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canViewUnitPrice && (auth()->user()->role === 'super_admin' || auth()->user()->role === 'admin_procurement'))
                                            <input type="date" class="form-control form-control-sm delivery-date-input"
                                                value="{{ $req->delivery_date ? $req->delivery_date->format('Y-m-d') : '' }}"
                                                data-id="{{ $req->id }}">
                                        @else
                                            {{ $req->delivery_date ? $req->delivery_date->format('d M Y') : '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $req->user->username ?? '-' }}</td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="bottom"
                                            title="{{ $req->created_at ? $req->created_at->format('l, d F Y H:i:s') : '' }}"
                                            data-order="{{ $req->created_at ? $req->created_at->timestamp : 0 }}">
                                            {{ $req->created_at ? $req->created_at->format('d M Y') : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $remark = $req->remark;
                                            $isUrl = $remark && filter_var($remark, FILTER_VALIDATE_URL);
                                        @endphp
                                        <span data-bs-toggle="tooltip" data-bs-placement="bottom"
                                            title="{{ $remark ?? 'No remark' }}">
                                            @if ($isUrl)
                                                <a href="{{ $remark }}" target="_blank" rel="noopener noreferrer">
                                                    {{ \Illuminate\Support\Str::limit($remark, 30) }}
                                                </a>
                                            @else
                                                {{ $remark ? \Illuminate\Support\Str::limit($remark, 30) : '-' }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap gap-1 justify-content-center">
                                            @if ($canViewUnitPrice)
                                                <button class="btn btn-info btn-sm btn-show-image"
                                                    data-img="{{ $req->img ? asset('storage/' . $req->img) : '' }}"
                                                    data-name="{{ $req->material_name }}" title="View Image">
                                                    <i class="bi bi-image"></i>
                                                </button>
                                                <a href="{{ route('purchase_requests.edit', $req->id) }}"
                                                    class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm btn-delete"
                                                    @if (auth()->user()->isReadOnlyAdmin()) disabled @endif
                                                    data-id="{{ $req->id }}" data-name="{{ $req->material_name }}"
                                                    title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $req->id }}"
                                                    action="{{ route('purchase_requests.destroy', $req->id) }}"
                                                    method="POST" style="display:none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @else
                                                <button class="btn btn-info btn-sm btn-show-image"
                                                    data-img="{{ $req->img ? asset('storage/' . $req->img) : '' }}"
                                                    data-name="{{ $req->material_name }}" title="View Image">
                                                    <i class="bi bi-image"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canViewUnitPrice ? 14 : 13 }}" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <br>No purchase requests found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Modal Show Image -->
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="img-container" class="mb-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#datatable').DataTable({
                responsive: false,
                stateSave: true,
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No purchase request data available</div>',
                    zeroRecords: '<div class="text-muted py-2">No matching records found</div>',
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                dom: 't<"row datatables-footer-row align-items-center"<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i><"col-md-5 dataTables_paginate justify-content-end"p>>',
                columnDefs: [{
                        targets: [0, 12, 13],
                        orderable: false
                    },
                    {
                        targets: 11, // kolom "Requested At"
                        type: 'num',
                        render: function(data, type, row, meta) {
                            var orderValue = $(row[meta.col]).data('order');
                            if (type === 'sort') {
                                return orderValue || 0;
                            }
                            return data;
                        }
                    }
                ],
                order: [
                    [11, 'desc'] // Urutkan berdasarkan kolom "Requested At" (latest)
                ],
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    $('.supplier-select, .currency-select, .approval-select').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                    });
                    $('.supplier-select, .currency-select, .approval-select').select2({
                        theme: 'bootstrap-5',
                        allowClear: false,
                        minimumResultsForSearch: 10,
                        dropdownParent: $('body'),
                        width: '100%'
                    });
                }
            });

            // Initialize Select2 for filter dropdowns
            $('#type_filter, #project_filter, #supplier_filter, #approval_filter').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function() {
                    return $(this).find('option:first').text();
                }
            });

            // Initialize Select2 for table selects
            $('.supplier-select, .currency-select, .approval-select').select2({
                theme: 'bootstrap-5',
                allowClear: false,
                minimumResultsForSearch: 10,
                dropdownParent: $('body'),
                width: '100%'
            });

            // Simple filter functionality
            function performFilter() {
                var typeFilter = $('#type_filter').val();
                var projectFilter = $('#project_filter').val();
                var supplierFilter = $('#supplier_filter').val();
                var approvalFilter = $('#approval_filter').val();
                var customSearch = $('#custom-search').val().toLowerCase();

                $('#datatable tbody tr').each(function() {
                    var $row = $(this);
                    var show = true;

                    // Skip empty row
                    if ($row.find('td').length === 1) return;

                    // Type filter
                    if (typeFilter) {
                        var typeText = $row.find('td:eq(1)').text().toLowerCase();
                        if (typeText.indexOf(typeFilter.toLowerCase().replace('_', ' ')) === -1) {
                            show = false;
                        }
                    }

                    // Project filter
                    if (projectFilter && show) {
                        var projectText = $row.find('td:eq(5)').text().trim();
                        var selectedProjectText = $('#project_filter option:selected').text();
                        if (projectText !== selectedProjectText && projectText !== '-') {
                            show = false;
                        }
                    }

                    // Supplier filter
                    if (supplierFilter && show) {
                        var supplierSelect = $row.find('.supplier-select');
                        if (supplierSelect.length) {
                            var selectedSupplierId = supplierSelect.val();
                            if (selectedSupplierId !== supplierFilter) {
                                show = false;
                            }
                        } else {
                            show = false;
                        }
                    }

                    // Approval filter
                    if (approvalFilter && show) {
                        var approvalSelect = $row.find('.approval-select');
                        if (approvalSelect.length) {
                            var selectedValue = approvalSelect.val();
                            if (approvalFilter === 'Pending' && selectedValue !== '') {
                                show = false;
                            } else if (approvalFilter !== 'Pending' && selectedValue !== approvalFilter) {
                                show = false;
                            }
                        }
                    }

                    // Custom search
                    if (customSearch && show) {
                        var rowText = $row.text().toLowerCase();
                        if (rowText.indexOf(customSearch) === -1) {
                            show = false;
                        }
                    }

                    $row.toggle(show);
                });
            }

            // Filter event handlers
            $('#type_filter, #project_filter, #supplier_filter, #approval_filter, #custom-search').on(
                'change input',
                function() {
                    performFilter();
                });

            // Reset filter
            $('#reset-filter').on('click', function() {
                $('#type_filter, #project_filter, #supplier_filter, #approval_filter').val('').trigger(
                    'change');
                $('#custom-search').val('');
                $('#datatable tbody tr').show();
            });

            // Inline AJAX update function
            function quickUpdate(id, data) {
                $.ajax({
                    url: '/purchase_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: Object.assign(data, {
                        _token: '{{ csrf_token() }}'
                    }),
                    success: function(response) {
                        console.log('Updated successfully');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update', 'error');
                    }
                });
            }

            // Event handlers for inline editing
            $(document).on('change', '.supplier-select', function() {
                let id = $(this).data('id');
                quickUpdate(id, {
                    supplier_id: $(this).val()
                });
            });

            $(document).on('change', '.price-input', function() {
                let id = $(this).data('id');
                quickUpdate(id, {
                    price_per_unit: $(this).val()
                });
            });

            $(document).on('change', '.currency-select', function() {
                let id = $(this).data('id');
                quickUpdate(id, {
                    currency_id: $(this).val()
                });
            });

            $(document).on('change', '.approval-select', function() {
                let id = $(this).data('id');
                quickUpdate(id, {
                    approval_status: $(this).val()
                });
            });

            $(document).on('change', '.delivery-date-input', function() {
                let id = $(this).data('id');
                quickUpdate(id, {
                    delivery_date: $(this).val()
                });
            });

            // Delete functionality
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete-form-' + id).submit();
                    }
                });
            });

            // Show Image Modal Handler
            $(document).on('click', '.btn-show-image', function() {
                $('#img-container').html('');

                let img = $(this).data('img');
                let name = $(this).data('name');

                $('#imageModalLabel').html(`<i class="bi bi-image me-2"></i>${name}`);

                $('#img-container').html(img && img !== '' ?
                    `<img src="${img}" alt="Image" class="img-fluid rounded" style="max-width:100%;">` :
                    '<span class="text-muted">No Image Available</span>'
                );

                $('#imageModal').modal('show');
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Ensure Select2 is working on initial load
            setTimeout(function() {
                $('.supplier-select, .currency-select, .approval-select').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            allowClear: false,
                            minimumResultsForSearch: 10,
                            dropdownParent: $('body'),
                            width: '100%'
                        });
                    }
                });
            }, 100);
        });
    </script>
@endpush
