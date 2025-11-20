@extends('layouts.app')

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        /* Form controls in table */
        .form-select-sm,
        .form-control-sm {
            font-size: 0.875rem;
        }

        #filter-form {
            background: #f8f9fa;
            padding: .75rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
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
            max-width: 280px;
            /* Lebih lebar untuk text yang panjang */
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            line-height: 1.4;
            text-align: left;
            /* Left align untuk multi-line */
            background-color: #2c3e50ea;
            /* Darker background */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Icon helper styling */
        [data-bs-toggle="tooltip"] {
            transition: color 0.2s ease;
        }

        [data-bs-toggle="tooltip"]:hover {
            color: #0d6efd !important;
            /* Bootstrap primary color */
        }

        /* Table header dengan icon */
        th .bi-info-circle {
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        th:hover .bi-info-circle {
            opacity: 1;
        }

        #supplierChangeModal .modal-body {
            min-height: 200px;
            /* Set minimum height */
        }

        #supplierChangeModal .form-control,
        #supplierChangeModal .form-label {
            transition: none;
            /* Disable transitions yang bisa trigger reflow */
        }

        /* Optimize modal rendering */
        .modal.fade .modal-dialog {
            transform: translate(0, 0);
            transition: transform 0.15s ease-out;
        }

        .modal.show .modal-dialog {
            transform: translate(0, 0);
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
                        @if (in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin']))
                            <button type="button" class="btn btn-outline-success btn-sm flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                <i class="bi bi-plus-circle me-1"></i> Quick Add Supplier
                            </button>
                        @endif
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
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="type_filter" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="new_material">New Material</option>
                                <option value="restock">Restock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="project_filter" class="form-select form-select-sm select2">
                                <option value="">All Projects</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="supplier_filter" class="form-select form-select-sm select2">
                                <option value="">All Suppliers</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="approval_filter" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Decline">Decline</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="custom-search" class="form-control form-control-sm"
                                placeholder="Search requests...">
                        </div>
                        <div class="col-md-1">
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
                                <th width="50">#</th>
                                <th>Type</th>
                                <th>Material Name</th>
                                <th>Required Qty</th>
                                <th>Qty to Buy</th>
                                <th>Supplier</th>
                                <th>Unit Price</th>
                                <th>Currency</th>
                                <th>Approval Status</th>
                                <th>
                                    <span>Delivery Date</span>
                                    <i class="bi bi-info-circle text-secondary" data-bs-toggle="tooltip" data-bs-html="true"
                                        title="Expected date when supplier delivers goods to forwarder/shipping agent"
                                        style="font-size: 0.85rem; cursor: help;"></i>
                                </th>
                                <th class="text center">Delivery Status</th>
                                <th>Project</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Remark</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>

                <!-- Modal Show Image -->
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
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

                <!-- Modal Add Supplier -->
                <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <form id="supplierForm" method="POST" action="{{ route('suppliers.quick_store') }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addSupplierModalLabel">
                                        <i class="bi bi-plus-circle me-2"></i>Quick Add Supplier
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="supplier-error" class="alert alert-danger d-none"></div>

                                    <!-- Supplier Name -->
                                    <div class="mb-3">
                                        <label for="supplier_name" class="form-label">Supplier Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="supplier_name" name="name" class="form-control"
                                            required>
                                    </div>

                                    <!-- Location -->
                                    <div class="mb-3">
                                        <label for="supplier_location_id" class="form-label">Supplier Location <span
                                                class="text-danger">*</span></label>
                                        <select id="supplier_location_id" name="location_id" class="form-select select2"
                                            required>
                                            <option value="">Select Location</option>
                                            @foreach ($locations ?? [] as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Lead Time Days -->
                                    <div class="mb-3">
                                        <label for="supplier_lead_time" class="form-label">Lead Time (Days) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="supplier_lead_time" name="lead_time_days"
                                            class="form-control" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-success" id="supplier-submit-btn">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                            aria-hidden="true"></span>
                                        Add Supplier
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Supplier Change Reason -->
                <div class="modal fade" id="supplierChangeModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reason for Supplier Change</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="change-pr-id">
                                <input type="hidden" id="change-new-supplier-id">

                                <div class="mb-3">
                                    <label class="form-label">Original Supplier</label>
                                    <input type="text" class="form-control" id="original-supplier-name" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Supplier</label>
                                    <input type="text" class="form-control" id="new-supplier-name" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="supplier-change-reason" rows="3"
                                        placeholder="Enter reason for changing supplier..." required></textarea>
                                    <small class="text-muted">This change will be audited</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirm-supplier-change">Confirm
                                    Change</button>
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
            // Ambil locations untuk modal
            const locations = @json($locations ?? []);

            // Initialize DataTable dengan server-side processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    url: "{{ route('purchase_requests.index') }}",
                    data: function(d) {
                        d.type_filter = $('#type_filter').val();
                        d.project_filter = $('#project_filter').val();
                        d.supplier_filter = $('#supplier_filter').val();
                        d.approval_filter = $('#approval_filter').val();
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
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'material_name',
                        name: 'material_name'
                    },
                    {
                        data: 'required_quantity',
                        name: 'required_quantity',
                        orderable: false
                    },
                    {
                        data: 'qty_to_buy',
                        name: 'qty_to_buy',
                        orderable: false
                    },
                    {
                        data: 'supplier',
                        name: 'supplier'
                    },
                    {
                        data: 'unit_price',
                        name: 'unit_price',
                        orderable: false
                    },
                    {
                        data: 'currency',
                        name: 'currency'
                    },
                    {
                        data: 'approval_status',
                        name: 'approval_status'
                    },
                    {
                        data: 'delivery_date',
                        name: 'delivery_date'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            const status = row.DT_status;
                            let badge = '';

                            switch (status) {
                                case 'received':
                                    badge =
                                        '<span class="badge bg-success">Received</span>';
                                    break;
                                case 'in_shipping':
                                    badge =
                                        '<span class="badge bg-info">In Shipping</span>';
                                    break;
                                case 'in_pre_shipping':
                                    badge =
                                        '<span class="badge bg-warning">Pre-Shipping</span>';
                                    break;
                                case 'not_in_pre_shipping':
                                    badge =
                                        '<span class="badge bg-secondary">Draft</span>';
                                    break;
                                default:
                                    badge = '<span class="badge bg-light">-</span>';
                            }
                            return badge;
                        }
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
                        name: 'created_at'
                    },
                    {
                        data: 'remark',
                        name: 'remark',
                        orderable: false
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
                    emptyTable: '<div class="text-muted py-2">No purchase request data available</div>',
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
                responsive: false,
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Initialize Select2 for filters
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function() {
                    return $(this).find('option:first').text();
                },
                width: '100%'
            });

            // Filter functionality
            $('#type_filter, #project_filter, #supplier_filter, #approval_filter').on('change', function() {
                table.ajax.reload();
            });

            $('#custom-search').on('input', debounce(function() {
                table.ajax.reload();
            }, 500));

            // Reset filter
            $('#reset-filters').on('click', function() {
                $('#type_filter, #project_filter, #supplier_filter, #approval_filter').val('').trigger(
                    'change');
                $('#custom-search').val('');
                table.ajax.reload();
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

            // Delete functionality with AJAX
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
                        $.ajax({
                            url: '/purchase_requests/' + id,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function() {
                                Swal.fire('Deleted!',
                                    'Purchase request deleted successfully',
                                    'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', 'Failed to delete purchase request',
                                    'error');
                            }
                        });
                    }
                });
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Initialize inline editable elements after table draw
            $(document).on('draw.dt', '#datatable', function() {
                initializeInlineEdits();
            });

            function initializeInlineEdits() {
                // Initialize Select2 untuk inline selects
                $('.supplier-select, .currency-select, .approval-select').each(function() {
                    if (!$(this).data('select2')) {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            allowClear: false,
                            minimumResultsForSearch: 10,
                            dropdownParent: $('body'),
                            width: '100%'
                        });
                    }
                });

                // Double-click handler untuk material name
                $(document).on('dblclick', '.material-name-cell', function() {
                    const $cell = $(this);
                    const id = $cell.data('id');
                    const type = $cell.data('type');
                    const currentValue = $cell.data('value');

                    // Hanya untuk new_material
                    if (type !== 'new_material') return;

                    // Prevent duplicate input
                    if ($cell.find('input').length) return;

                    $cell.html(`
                        <input type="text" class="form-control form-control-sm material-name-edit-input" data-id="${id}" value="${currentValue}">
                    `);
                    $cell.find('input').focus().select();
                });

                // Save material name on blur/enter
                $(document).on('blur', '.material-name-edit-input', function() {
                    saveMaterialNameInline($(this));
                });
                $(document).on('keydown', '.material-name-edit-input', function(e) {
                    if (e.key === 'Enter') saveMaterialNameInline($(this));
                    if (e.key === 'Escape') location.reload();
                });

                // Double-click handler untuk remark
                $(document).on('dblclick', '.remark-cell', function() {
                    const $cell = $(this);
                    const id = $cell.data('id');
                    const currentValue = $cell.data('value');

                    // Prevent duplicate input
                    if ($cell.find('textarea').length) return;

                    $cell.html(`
                        <textarea class="form-control form-control-sm remark-edit-input" data-id="${id}" rows="2">${currentValue}</textarea>
                    `);
                    $cell.find('textarea').focus();
                });

                // Save remark on blur/enter
                $(document).on('blur', '.remark-edit-input', function() {
                    saveRemarkInline($(this));
                });
                $(document).on('keydown', '.remark-edit-input', function(e) {
                    if (e.ctrlKey && e.key === 'Enter') saveRemarkInline($(this));
                });

                // Qty to buy change
                $(document).on('change', '.qty-to-buy-input', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        qty_to_buy: $(this).val()
                    });
                });

                // Price input change
                $(document).on('change', '.price-input', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        price_per_unit: $(this).val()
                    });
                });

                // Currency change
                $(document).on('change', '.currency-select', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        currency_id: $(this).val()
                    });
                });

                // Approval status change
                $(document).on('change', '.approval-select', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        approval_status: $(this).val()
                    });
                });

                // Delivery date change
                $(document).on('change', '.delivery-date-input', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        delivery_date: $(this).val()
                    });
                });
            }

            // Supplier change dengan reason modal
            let pendingSupplierChange = null;

            $(document).on('change', '.supplier-select', function() {
                const $select = $(this);
                const id = $select.data('id');
                const newSupplierId = $(this).val();
                const newSupplierName = $(this).find('option:selected').text();

                const originalSupplierId = $select.data('original-supplier-id');
                const originalSupplierName = $select.data('original-supplier-name');

                // Jika supplier berubah dari original
                if (originalSupplierId && newSupplierId != originalSupplierId) {
                    // ⭐ SIMPAN data dulu, populate nanti setelah modal shown
                    pendingSupplierChange = {
                        id: id,
                        newSupplierId: newSupplierId,
                        newSupplierName: newSupplierName,
                        originalSupplierId: originalSupplierId,
                        originalSupplierName: originalSupplierName,
                        $select: $select
                    };

                    // LANGSUNG show modal (tanpa populate data)
                    $('#supplierChangeModal').modal('show');
                } else {
                    // Direct update
                    quickUpdate(id, {
                        supplier_id: newSupplierId
                    });
                }
            });

            // Populate data SETELAH modal fully rendered
            $('#supplierChangeModal').on('shown.bs.modal', function() {
                if (pendingSupplierChange) {
                    // Populate data setelah modal selesai render (no reflow)
                    $('#change-pr-id').val(pendingSupplierChange.id);
                    $('#change-new-supplier-id').val(pendingSupplierChange.newSupplierId);
                    $('#original-supplier-name').val(pendingSupplierChange.originalSupplierName || '-');
                    $('#new-supplier-name').val(pendingSupplierChange.newSupplierName);
                    $('#supplier-change-reason').val('').focus(); // Auto focus textarea
                }
            });

            // Rollback supplier select on cancel
            $('#supplierChangeModal').on('hidden.bs.modal', function() {
                if (pendingSupplierChange && pendingSupplierChange.$select) {
                    // Rollback ke original value jika user cancel
                    const currentValue = pendingSupplierChange.$select.val();

                    // Hanya rollback jika user tidak confirm change
                    if (currentValue == pendingSupplierChange.newSupplierId) {
                        pendingSupplierChange.$select.val(pendingSupplierChange.originalSupplierId).trigger(
                            'change.select2');
                    }
                }
                pendingSupplierChange = null;
            });

            // Confirm supplier change dengan reason
            $('#confirm-supplier-change').on('click', function() {
                const reason = $('#supplier-change-reason').val().trim();

                if (!reason) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'Please provide a reason for changing supplier',
                        confirmButtonText: 'OK'
                    });
                    $('#supplier-change-reason').focus();
                    return;
                }

                if (!pendingSupplierChange) {
                    Swal.fire('Error', 'No pending supplier change', 'error');
                    return;
                }

                const id = pendingSupplierChange.id;
                const newSupplierId = pendingSupplierChange.newSupplierId;

                // Disable button sementara
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

                quickUpdate(id, {
                    supplier_id: newSupplierId,
                    supplier_change_reason: reason
                }, function(success) {
                    if (success) {
                        $('#supplierChangeModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Supplier changed successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Clear pending change (prevent rollback)
                        pendingSupplierChange = null;

                        // Reload table untuk update data attributes
                        table.ajax.reload(null, false);
                    }

                    // Reset button
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });

            // Function untuk update supplier langsung (tanpa reason)
            function updateSupplierDirect(id, supplierId) {
                quickUpdate(id, {
                    supplier_id: supplierId
                });
            }

            // Quick update function
            function quickUpdate(id, data, callback) {
                $.ajax({
                    url: `/purchase_requests/${id}/quick-update`,
                    method: 'POST',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (callback) callback();
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Failed to update';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }

            // Save material name inline
            function saveMaterialNameInline($input) {
                const id = $input.data('id');
                const newValue = $input.val();
                const $cell = $input.closest('.material-name-cell');

                $.ajax({
                    url: '/purchase_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        material_name: newValue
                    },
                    success: function() {
                        $cell.html('<span class="material-name-text">' + newValue + '</span>');
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to save material name', 'error');
                    }
                });
            }

            // Save remark inline
            function saveRemarkInline($input) {
                const id = $input.data('id');
                const newValue = $input.val();
                const $cell = $input.closest('.remark-cell');

                $.ajax({
                    url: '/purchase_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        remark: newValue
                    },
                    success: function() {
                        $cell.html('<span>' + (newValue ? newValue.substring(0, 30) : '-') + '</span>');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to save remark', 'error');
                    }
                });
            }

            // Initialize on page load
            initializeInlineEdits();

            // ===== QUICK ADD SUPPLIER HANDLER =====
            $('#supplierForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let errorDiv = $('#supplier-error');
                let submitBtn = $('#supplier-submit-btn');
                let spinner = submitBtn.find('.spinner-border');

                errorDiv.hide().addClass('d-none').text('');
                submitBtn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.supplier) {
                            // Reset form
                            form[0].reset();
                            errorDiv.hide().addClass('d-none');

                            // ✅ KEY LOGIC: Update semua supplier-select di table dengan opsi baru
                            const newOption = new Option(response.supplier.name, response
                                .supplier.id, false, false);

                            // Update semua supplier-select instances di DataTable
                            $('.supplier-select').each(function() {
                                $(this).append(newOption);
                                // Trigger change agar Select2 refresh
                                $(this).trigger('change');
                            });

                            // Update supplier filter Select2 jika ada
                            const filterOption = new Option(response.supplier.name, response
                                .supplier.id, false, false);
                            $('#supplier_filter').append(filterOption).trigger('change');

                            // Tutup modal
                            bootstrap.Modal.getInstance(document.getElementById(
                                'addSupplierModal')).hide();

                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Supplier "' + response.supplier.name +
                                    '" added successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            errorDiv.show().removeClass('d-none').text(response.message ||
                                'Failed to add supplier');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ||
                            'Failed to add supplier. Please try again.';
                        errorDiv.show().removeClass('d-none').text(msg);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Initialize Select2 untuk supplier location di modal
            $('#supplier_location_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Location',
                allowClear: true,
                dropdownParent: $('#addSupplierModal')
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 100);
            });

            // Reset modal saat ditutup
            $('#addSupplierModal').on('hidden.bs.modal', function() {
                $('#supplierForm')[0].reset();
                $('#supplier-error').hide().addClass('d-none').text('');
                $('#supplier_location_id').val(null).trigger('change');
            });
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
