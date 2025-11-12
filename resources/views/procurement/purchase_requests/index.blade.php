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
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="type_filter" class="form-select form-select-sm select2">
                                <option value="">All Types</option>
                                <option value="new_material">New Material</option>
                                <option value="restock">Restock</option>
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
                            <select id="supplier_filter" class="form-select form-select-sm select2">
                                <option value="">All Suppliers</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="approval_filter" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Decline">Declined</option>
                                <option value="Pending">Pending</option>
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
                                <th>Delivery Date</th>
                                <th>Project</th>
                                <th>Requested By</th>
                                <th>Remark</th>
                                <th>Requested At</th>
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
                        name: 'approval_status',
                        orderable: false
                    },
                    {
                        data: 'delivery_date',
                        name: 'delivery_date'
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
                        data: 'remark',
                        name: 'remark',
                        orderable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
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
                            url: `/purchase_requests/${id}`,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    `<b>${name}</b> has been deleted.`,
                                    'success'
                                );
                                table.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                let errorMsg = xhr.responseJSON?.message ||
                                    'Failed to delete';
                                Swal.fire('Error!', errorMsg, 'error');
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
                        <input type="text" class="form-control form-control-sm material-name-edit-input"
                            data-id="${id}" value="${currentValue}" style="width:100%;">
                    `);
                    $cell.find('input').focus().select();
                });

                // Save material name on blur/enter
                $(document).on('blur', '.material-name-edit-input', function() {
                    saveMaterialNameInline($(this));
                });
                $(document).on('keydown', '.material-name-edit-input', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $(this).blur();
                    }
                });

                // Double-click handler untuk remark
                $(document).on('dblclick', '.remark-cell', function() {
                    const $cell = $(this);
                    const id = $cell.data('id');
                    const currentValue = $cell.data('value');

                    // Prevent duplicate input
                    if ($cell.find('textarea').length) return;

                    $cell.html(`
                        <textarea class="form-control form-control-sm remark-edit-input"
                            data-id="${id}" style="min-width:150px; max-width:300px;">${currentValue}</textarea>
                    `);
                    $cell.find('textarea').focus();
                });

                // Save remark on blur/enter
                $(document).on('blur', '.remark-edit-input', function() {
                    saveRemarkInline($(this));
                });
                $(document).on('keydown', '.remark-edit-input', function(e) {
                    if (e.ctrlKey && e.key === 'Enter') {
                        e.preventDefault();
                        $(this).blur();
                    }
                });

                // Qty to buy change
                $(document).on('change', '.qty-to-buy-input', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        qty_to_buy: $(this).val()
                    });
                });

                // Supplier change
                $(document).on('change', '.supplier-select', function() {
                    let id = $(this).data('id');
                    quickUpdate(id, {
                        supplier_id: $(this).val()
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

            // Quick update function
            function quickUpdate(id, data) {
                $.ajax({
                    url: '/purchase_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: Object.assign(data, {
                        _token: '{{ csrf_token() }}'
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Optional: show success toast
                            console.log('Updated successfully');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update', 'error');
                    }
                });
            }

            // Save material name inline
            function saveMaterialNameInline($input) {
                const id = $input.data('id');
                const newValue = $input.val();
                const $cell = $input.closest('.material-name-cell');
                const type = $cell.data('type'); // Get the type dari cell

                $.ajax({
                    url: '/purchase_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: {
                        material_name: newValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $cell.data('value', newValue);

                        // Hanya tambahkan icon jika tipe adalah 'restock'
                        if (type === 'restock') {
                            $cell.html(`
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-info-circle text-secondary" style="cursor: pointer;"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                    title="Current stock info"></i>
                                    <span class="material-name-text">${newValue}</span>
                                </div>
                            `);
                        } else {
                            // Untuk new_material, tidak perlu icon
                            $cell.html(`
                                <div class="d-flex align-items-center gap-1">
                                    <span class="material-name-text">${newValue}</span>
                                </div>
                            `);
                        }

                        $('[data-bs-toggle="tooltip"]').tooltip();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update', 'error');
                        $cell.html(`<span>${$cell.data('value')}</span>`);
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
                        remark: newValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $cell.data('value', newValue);
                        if (newValue && /^https?:\/\/\S+$/i.test(newValue)) {
                            $cell.html(`<a href="${newValue}" target="_blank">${newValue}</a>`);
                        } else {
                            $cell.html(`<span>${newValue || '-'}</span>`);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update', 'error');
                        $cell.html(`<span>${$cell.data('value') || '-'}</span>`);
                    }
                });
            }

            // Initialize on page load
            initializeInlineEdits();
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
