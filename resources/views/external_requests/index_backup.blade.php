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
            overflow: hidden;
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

        /* Badge styling for approval status */
        .badge-approved {
            background-color: #198754 !important;
            color: white !important;
        }

        .badge-decline {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .badge-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .badge {
            transition: all 0.2s ease-in-out;
        }

        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Button styling */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Action buttons */
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #000;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
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
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">External Requests</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('external_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
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
                            <select name="type_filter" id="type_filter" class="form-select select2">
                                <option value="">All Types</option>
                                <option value="new_material">New Material</option>
                                <option value="restock">Restock</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="project_filter" id="project_filter" class="form-select select2">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="supplier_filter" id="supplier_filter" class="form-select select2">
                                <option value="">All Suppliers</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="approval_filter" id="approval_filter" class="form-select select2">
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
                            <button type="button" id="reset-filter" class="btn btn-secondary">Reset</button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="datatable">
                        <thead class="table-dark align-middle text-nowrap">
                            <tr>
                                <th width="50">#</th>
                                <th>Type</th>
                                <th>Material Name</th>
                                <th>Stock Level</th>
                                <th>Required Qty</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Price Per Unit</th>
                                <th>Currency</th>
                                <th>Approval Status</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            @forelse ($requests as $index => $req)
                                <tr data-id="{{ $req->id }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $req->type)) }}</td>
                                    <td>{{ $req->material_name }}</td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $req->unit }}">
                                            {{ $req->stock_level !== null ? number_format($req->stock_level, 2) : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $req->unit }}">
                                            {{ number_format($req->required_quantity, 2) }}
                                        </span>
                                    </td>
                                    <td>{{ $req->project->name ?? '-' }}</td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01"
                                            class="form-control form-control-sm price-input"
                                            value="{{ $req->price_per_unit ?? '' }}" data-id="{{ $req->id }}">
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm approval-select"
                                            data-id="{{ $req->id }}">
                                            <option value="">Pending</option>
                                            <option value="Approved" @if ($req->approval_status == 'Approved') selected @endif>Approved</option>
                                            <option value="Decline" @if ($req->approval_status == 'Decline') selected @endif>Decline</option>
                                        </select>
                                    </td>
                                    <td>{{ $req->user->username ?? '-' }}</td>
                                    <td>
                                        <span data-bs-toggle="tooltip" data-bs-placement="left"
                                            title="{{ $req->created_at ? $req->created_at->format('l, d F Y H:i:s') : '' }}">
                                            {{ $req->created_at ? $req->created_at->format('d M Y, H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap gap-1 justify-content-center">
                                            <a href="{{ route('external_requests.edit', $req->id) }}"
                                                class="btn btn-warning btn-sm" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button class="btn btn-info btn-sm btn-show-image"
                                                data-img="{{ $req->img ? asset('storage/' . $req->img) : '' }}"
                                                data-name="{{ $req->material_name }}" title="View Image">
                                                <i class="bi bi-image"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-delete"
                                                data-id="{{ $req->id }}" data-name="{{ $req->material_name }}"
                                                title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $req->id }}"
                                                action="{{ route('external_requests.destroy', $req->id) }}"
                                                method="POST" style="display:none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <br>No external requests found
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
            // Initialize DataTable with static data
            const table = $('#datatable').DataTable({
                responsive: true,
                stateSave: false,
                searching: false, // Use custom search
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No external request data available</div>',
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
                order: [
                    [11, 'desc']
                ],
                drawCallback: function() {
                    // Reinitialize tooltips after table redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Initialize Select2 for filters
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Custom filtering function
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    // Only apply to our table
                    if (settings.nTable.id !== 'datatable') {
                        return true;
                    }

                    var typeFilter = $('#type_filter').val();
                    var projectFilter = $('#project_filter').val();
                    var supplierFilter = $('#supplier_filter').val();
                    var approvalFilter = $('#approval_filter').val();
                    var customSearch = $('#custom-search').val().toLowerCase();

                    var type = data[1] || '';
                    var materialName = data[2] || '';
                    var project = data[5] || '';
                    var requestedBy = data[10] || '';

                    // Get row data to check actual values
                    var rowNode = table.row(dataIndex).node();
                    var $row = $(rowNode);

                    // Custom search across multiple columns
                    if (customSearch) {
                        var searchText = (type + ' ' + materialName + ' ' + project + ' ' + requestedBy)
                            .toLowerCase();
                        if (searchText.indexOf(customSearch) === -1) {
                            return false;
                        }
                    }

                    // Type filter
                    if (typeFilter && type.toLowerCase().replace(/\s+/g, '_') !== typeFilter) {
                        return false;
                    }

                    // Project filter
                    if (projectFilter) {
                        var selectedProjectText = $('#project_filter option:selected').text();
                        var projectText = project.trim();

                        if (projectText === '-' || projectText === '') {
                            return false; // No project assigned
                        }

                        if (projectText !== selectedProjectText) {
                            return false;
                        }
                    }

                    // Supplier filter - check selected option in dropdown
                    if (supplierFilter) {
                        var supplierSelect = $row.find('.supplier-select');
                        if (supplierSelect.length && supplierSelect.val() !== supplierFilter) {
                            return false;
                        }
                    }

                    // Approval filter - check selected option or badge text
                    if (approvalFilter !== '') {
                        var approvalSelect = $row.find('.approval-select');
                        var approvalBadge = $row.find('.badge-approved, .badge-decline');

                        if (approvalBadge.length) {
                            // Has badge (approved/declined)
                            var badgeText = approvalBadge.text().trim();
                            if (approvalFilter !== badgeText) {
                                return false;
                            }
                        } else if (approvalSelect.length) {
                            // Has select dropdown (pending)
                            if (approvalFilter === 'Pending') {
                                return true; // Show pending items
                            } else {
                                return false; // Don't show pending if looking for approved/declined
                            }
                        }
                    }

                    return true;
                }
            );

            // Filter functionality
            $('#type_filter, #project_filter, #supplier_filter, #approval_filter, #custom-search').on(
                'change input',
                function() {
                    table.draw();
                });

            // Reset filter
            $('#reset-filter').on('click', function() {
                $('#type_filter, #project_filter, #supplier_filter, #approval_filter').val('').trigger(
                    'change');
                $('#custom-search').val('');
                table.draw();
            });

            // Inline AJAX update for Supplier, Price, Currency, Approval Status
            function quickUpdate(id, data) {
                $.ajax({
                    url: '/external_requests/' + id + '/quick-update',
                    method: 'POST',
                    data: Object.assign(data, {
                        _token: '{{ csrf_token() }}'
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Use toastr if available, otherwise use a simple alert
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Updated successfully');
                            } else {
                                console.log('Updated successfully');
                            }
                        }
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
                        // Use the form submission for simplicity
                        $('#delete-form-' + id).submit();
                    }
                });
            });

            // Show Image Modal Handler
            $(document).on('click', '.btn-show-image', function() {
                // Reset modal content
                $('#img-container').html('');

                let img = $(this).data('img');
                let name = $(this).data('name');

                $('#imageModalLabel').html(
                    `<i class="bi bi-image" style="margin-right: 5px; color: cornflowerblue;"></i> ${name}`
                );

                // Show image if available
                $('#img-container').html(img && img !== '' ?
                    `<a href="${img}" data-fancybox="gallery" data-caption="${name}">
                        <img src="${img}" alt="Image" class="img-fluid img-hover rounded" style="max-width:100%;">
                    </a>` :
                    '<span class="text-muted">No Image Available</span>'
                );

                // Show modal
                $('#imageModal').modal('show');
            });

            // Initialize Bootstrap Tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Fancybox if available
            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind("[data-fancybox='gallery']", {
                    Toolbar: {
                        display: [{
                                id: "counter",
                                position: "center"
                            },
                            "zoom",
                            "download",
                            "close"
                        ],
                    },
                    Thumbs: false,
                    Image: {
                        zoom: true,
                    },
                    Hash: false,
                });
            }

            // Initialize Bootstrap Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
