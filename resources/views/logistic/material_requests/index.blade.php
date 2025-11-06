@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-clipboard-list gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Material Requests</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('material_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Request
                        </a>
                        <a href="{{ route('material_requests.bulk_create') }}" class="btn btn-info btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Bulk Request
                        </a>
                        @if (auth()->user()->isLogisticAdmin() || auth()->user()->isReadOnlyAdmin())
                            <span id="bulk-goods-out-tooltip-wrapper" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                title="To perform Bulk Goods Out, please select material requests with Approved status.">
                                <button id="bulk-goods-out-btn" class="btn btn-success btn-sm flex-shrink-0" disabled>
                                    <i class="bi bi-box-arrow-in-right me-1"></i>
                                    <span id="bulk-goods-out-text">Bulk Goods Out</span>
                                    <span id="bulk-goods-out-count" class="badge bg-light text-dark ms-1 d-none">0</span>
                                </button>
                            </span>
                        @endif
                        <a href="{{ route('material_requests.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm flex-shrink-0">
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

                <!-- filter form -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-2">
                            <select id="filter-project" name="project" class="form-select form-select-sm select2">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-material" name="material" class="form-select form-select-sm select2">
                                <option value="">All Materials</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-status" name="status" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="delivered">Delivered</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-requested-by" name="requested_by" class="form-select form-select-sm select2">
                                <option value="">All Requester</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}">{{ ucfirst($user->username) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="filter-requested-at" name="requested_at"
                                class="form-control form-control-sm" placeholder="Requested At Date">
                        </div>
                        <div class="col-md-1">
                            <input type="text" id="custom-search" name="search" class="form-control form-control-sm"
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
                <table class="table table-hover table-sm align-middle" id="datatable" data-material-request-table="1">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th></th>
                            <th style="display:none">ID</th>
                            <th>Project</th>
                            <th>Material</th>
                            <th>Requested Qty</th>
                            <th>Remaining Qty <i class="bi bi-question-circle" data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="The quantity of material requests that have not yet been processed for goods out."
                                    style="font-size: 0.8rem; cursor: pointer;"></i>
                            </th>
                            <th>Processed Qty <i class="bi bi-question-circle" data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="The quantity of material requests that have already been processed and issued as goods out."
                                    style="font-size: 0.8rem; cursor: pointer;"></i>
                            </th>
                            <th>Requested By</th>
                            <th>Requested At</th>
                            <th>Status</th>
                            <th>Remark</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan dimuat via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Goods Out -->
    <div class="modal fade" id="bulkGoodsOutModal" tabindex="-1" aria-labelledby="bulkGoodsOutModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkGoodsOutModalLabel">Bulk Goods Out Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulk-goods-out-form">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" style="font-size: 0.92rem;">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Project</th>
                                        <th>Req / Rem Qty</th>
                                        <th>Qty to Goods Out</th>
                                        <th> </th>
                                    </tr>
                                </thead>
                                <tbody id="bulk-goods-out-table-body">
                                    <!-- Rows will be dynamically added here -->
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submit-bulk-goods-out" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                            aria-hidden="true"></span>
                        Submit All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Material Detail Modal -->
    <div class="modal fade" id="materialDetailModal" tabindex="-1" aria-labelledby="materialDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialDetailModalLabel">Material Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="material-detail-modal-body">
                    <div class="table-responsive">
                        <!-- Konten detail akan dimuat via AJAX -->
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status"></div>
                        </div>
                    </div>
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

        @media (max-width: 768px) {
            .ms-sm-auto.d-flex.flex-wrap {
                margin-bottom: 0.8rem !important;
                /* Tambah margin bottom khusus mobile */
            }
        }

        #datatable th {
            font-size: 0.90rem;
            white-space: nowrap;
            vertical-align: middle;
            text-align: left !important;
        }

        #datatable td {
            vertical-align: middle;
            text-align: left !important;
        }

        /* Batasi lebar kolom tertentu jika perlu */
        #datatable th,
        #datatable td {
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
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

        /* Vertical divider */
        .vr-divider {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        /* DataTables footer styling */
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

        .material-detail-link {
            color: inherit !important;
            text-decoration: none !important;
            cursor: pointer;
        }

        .material-detail-link:hover {
            text-decoration: underline dotted;
        }

        /* Gradient link styling */
        .gradient-link {
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none !important;
        }

        .gradient-link:hover {
            text-decoration: underline dotted;
            filter: brightness(1.2);
        }

        /* Rounded status select - Bootstrap standard colors */
        .status-select-rounded {
            border-radius: 20px !important;
            padding: 0.25rem 0.75rem !important;
            font-size: 0.85rem !important;
            font-weight: 400 !important;
            outline: none !important;
            box-shadow: none !important;
            width: 15ch !important;
            min-width: 0 !important;
            max-width: 20ch !important;
        }

        .status-select-rounded:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        select.form-select option:disabled {
            color: #999;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }

        .status-select:disabled {
            cursor: not-allowed;
        }

        .status-quick-update.border-success {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }

        .status-quick-update.border-danger {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .status-quick-update:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {

            /* Force all DataTable cells to center their content */
            .dataTable.dtr-inline.collapsed tbody td:not(.dtr-control) {
                text-align: center !important;
            }

            /* Specific fixes for status and actions */
            .status-select,
            .status-select-rounded,
            .btn-group,
            .d-flex {
                margin: 0 auto !important;
                justify-content: center !important;
            }

            /* Override any left alignment on mobile */
            .dtr-details .dtr-data {
                text-align: center !important;
            }
        }

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

        /* Force right placement for table tooltips */
        .table [data-bs-toggle="tooltip"] {
            position: relative;
        }

        /* Ensure table cells have enough space for tooltips */
        #datatable td {
            position: relative;
            overflow: visible !important;
        }

        /* Override DataTables responsive styles that might clip tooltips */
        .dataTables_wrapper {
            overflow: visible !important;
        }

        /* Ensure tooltips work properly in responsive mode */
        @media (max-width: 768px) {
            .tooltip-inner {
                max-width: 150px;
                font-size: 0.6rem;
            }

            .table [data-bs-toggle="tooltip"] {
                --bs-tooltip-placement: top;
            }
        }

        #bulkGoodsOutModal .table-sm th,
        #bulkGoodsOutModal .table-sm td {
            font-size: 0.92rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Bulk goods out button styling */
        #bulk-goods-out-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #bulk-goods-out-count {
            border-radius: 50px;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            min-width: 20px;
            text-align: center;
        }

        /* Ensure specific alignment for quantity columns - Headers and Cells */
        #datatable thead th:nth-child(5),
        #datatable thead th:nth-child(6),
        #datatable thead th:nth-child(7),
        #datatable tbody td:nth-child(5),
        #datatable tbody td:nth-child(6),
        #datatable tbody td:nth-child(7) {
            text-align: left !important;
        }

        /* Center align only for checkbox column */
        #datatable thead th:nth-child(1),
        #datatable tbody td:nth-child(1) {
            text-align: center !important;
        }

        /* Ensure Action column is centered */
        #datatable thead th:nth-child(12),
        #datatable tbody td:nth-child(12) {
            text-align: center !important;
        }

        .badge-custom {
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .quantity-badge {
            background: linear-gradient(135deg, #8F12FE, #6610f2);
            color: white;
        }

        .price-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .text-primary {
            color: #6610f2 !important;
        }

        #materialDetailModal .modal-body {
            overflow-x: auto !important;
            max-width: 100vw;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable with Server-Side Processing
            const table = $('#datatable').DataTable({
                processing: false, // Hide processing indicator
                serverSide: true, // Enable server-side processing
                searching: false,
                ajax: {
                    url: "{{ route('material_requests.index') }}",
                    data: function(d) {
                        // Add filter parameters
                        d.project = $('#filter-project').val();
                        d.material = $('#filter-material').val();
                        d.status = $('#filter-status').val();
                        d.requested_by = $('#filter-requested-by').val();
                        d.requested_at = $('#filter-requested-at').val();
                        d.custom_search = $('#custom-search').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', {
                            xhr: xhr,
                            error: error,
                            thrown: thrown,
                            responseText: xhr.responseText
                        });
                        Swal.fire('Error', 'Failed to load data. Please refresh the page.', 'error');
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false,
                        width: '2%',
                        className: 'text-center'
                    },
                    {
                        data: 'id',
                        name: 'id',
                        visible: false
                    },
                    {
                        data: 'project_name',
                        name: 'project.name',
                        width: '16%'
                    },
                    {
                        data: 'material_name',
                        name: 'inventory.name',
                        width: '16%'
                    },
                    {
                        data: 'requested_qty',
                        name: 'qty',
                        width: '8%'
                    },
                    {
                        data: 'remaining_qty',
                        name: 'remaining_qty',
                        orderable: false,
                        width: '8%'
                    },
                    {
                        data: 'processed_qty',
                        name: 'processed_qty',
                        orderable: false,
                        width: '8%'
                    },
                    {
                        data: 'requested_by',
                        name: 'requested_by',
                        width: '8%'
                    },
                    {
                        data: 'requested_at',
                        name: 'created_at', // For sorting
                        width: '8%'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        width: '6%',
                        className: 'text-center'
                    },
                    {
                        data: 'remark',
                        name: 'remark',
                        width: '12%'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '8%',
                        className: 'text-center'
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
                    emptyTable: '<div class="text-muted py-2">No material requests available</div>',
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
                stateSave: true,

                drawCallback: function() {
                    const container = this.api().table().container();
                    setTimeout(() => {
                        updateBulkGoodsOutButton();
                        updateAllSelectColors(container);
                        initializeStatusSelectTooltips(container);
                        initializeTooltipsBatch(container);
                    }, 100);
                }
            });

            // Debounced Filter Functionality
            const debouncedFilter = debounce(function() {
                table.draw();
            }, 300);

            // Update event listener untuk include custom search
            $('#filter-project, #filter-material, #filter-status, #filter-requested-by, #filter-requested-at, #custom-search')
                .on('change input', debouncedFilter);

            // Reset filters juga harus reset search
            $('#reset-filters').on('click', function() {
                $('#filter-project, #filter-material, #filter-status, #filter-requested-by, #filter-requested-at, #custom-search')
                    .val('').trigger('change');
                table.draw();
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Set placeholder untuk setiap select
            $('#filter-project').attr('data-placeholder', 'All Projects');
            $('#filter-material').attr('data-placeholder', 'All Materials');
            $('#filter-status').attr('data-placeholder', 'All Status');
            $('#filter-requested-by').attr('data-placeholder', 'All Requesters');

            // Date Input Enhancement
            const dateInput = document.getElementById('filter-requested-at');
            if (dateInput) {
                dateInput.onfocus = function() {
                    this.type = 'date';
                };
                dateInput.onblur = function() {
                    if (!this.value) this.type = 'text';
                };
                if (!dateInput.value) dateInput.type = 'text';
            }

            // Initialize flatpickr for date input
            flatpickr("#filter-requested-at", {
                dateFormat: "Y-m-d",
                allowInput: true,
            });

            // Bulk Goods Out Button Management
            function updateBulkGoodsOutButton() {
                const selectedCount = $('.select-row:checked').length;
                const bulkBtn = $('#bulk-goods-out-btn');
                const countBadge = $('#bulk-goods-out-count');
                const tooltipWrapper = $('#bulk-goods-out-tooltip-wrapper');

                requestAnimationFrame(() => {
                    if (selectedCount > 0) {
                        bulkBtn.prop('disabled', false);
                        countBadge.removeClass('d-none').text(selectedCount);

                        // Remove tooltip title so it won't show on hover
                        tooltipWrapper.removeAttr('data-bs-original-title');
                    } else {
                        bulkBtn.prop('disabled', true);
                        countBadge.addClass('d-none').text('0');

                        // Set tooltip title, but DO NOT call .tooltip('show')
                        tooltipWrapper.attr(
                            'data-bs-original-title',
                            'To perform Bulk Goods Out, please select material requests with Approved status.'
                        );
                    }
                });
            }

            // Initialize tooltip on page load (trigger: hover only)
            $(function() {
                $('#bulk-goods-out-tooltip-wrapper').tooltip({
                    trigger: 'hover focus',
                    placement: 'bottom'
                });
            });

            // Handle checkbox changes with debouncing
            const debouncedCheckboxUpdate = debounce(updateBulkGoodsOutButton, 50);
            $(document).on('change', '.select-row', debouncedCheckboxUpdate);

            // Handle select all checkbox
            $('#select-all').on('change', function() {
                $('.select-row').prop('checked', $(this).prop('checked'));
                updateBulkGoodsOutButton();
            });

            // Enhanced Delete Confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');
                const title = $(this).attr('title') || 'Delete';

                Swal.fire({
                    title: 'Are you sure?',
                    text: `${title} - This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Bulk Goods Out Modal Handler
            $('#bulk-goods-out-btn').on('click', function() {
                const selectedIds = $('.select-row:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    Swal.fire('Error', 'Please select at least one material request.', 'error');
                    return;
                }

                // Show loading in modal
                $('#bulk-goods-out-table-body').html(
                    '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border" role="status"></div></td></tr>'
                );
                $('#bulkGoodsOutModal').modal('show');

                // Fetch detail data for selected material requests
                $.ajax({
                    url: "{{ route('material_requests.bulk_details') }}",
                    method: 'GET',
                    data: {
                        selected_ids: selectedIds
                    },
                    success: function(response) {
                        $('#bulk-goods-out-table-body').empty();
                        response.forEach(function(item) {
                            $('#bulk-goods-out-table-body').append(`
                        <tr>
                            <td>${item.material_name}</td>
                            <td>
                                ${item.project_name}
                                <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Requested By: ${item.requested_by}">
                                    <i class="bi bi-person-circle"></i>
                                </span>
                            </td>
                            <td>${item.requested_qty} / ${item.remaining_qty} <span class="text-muted">${item.unit}</span></td>
                            <td>
                                <input type="number" name="goods_out_qty[${item.id}]" class="form-control form-control-sm"
                                    value="${item.remaining_qty}" min="0.001" max="${item.remaining_qty}" step="any" required>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-danger btn-remove-row" title="Remove Row">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                        });

                        // Initialize tooltips after content is added
                        setTimeout(() => {
                            initializeTooltipsBatch(document.getElementById(
                                'bulkGoodsOutModal'));
                        }, 100);
                    },
                    error: function(xhr) {
                        $('#bulk-goods-out-table-body').html(
                            '<tr><td colspan="5" class="text-center text-danger">Failed to load data. Please try again.</td></tr>'
                        );
                        console.error('Bulk details error:', xhr);
                    }
                });
            });

            // Remove row from bulk goods out modal
            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
            });

            // Enhanced Bulk Goods Out Submission
            $('#submit-bulk-goods-out').on('click', function() {
                const submitBtn = $(this);
                const spinner = submitBtn.find('.spinner-border');
                const btnText = submitBtn.contents().filter(function() {
                    return this.nodeType === 3; // Text nodes only
                }).last();

                // Validate quantities
                let isValid = true;
                $('#bulk-goods-out-table-body input[type="number"]').each(function() {
                    const max = parseFloat($(this).attr('max'));
                    const val = parseFloat($(this).val());
                    if (isNaN(val) || val < 0.001 || val > max) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    Swal.fire('Error', 'Qty to Goods Out must be between 0.001 and Remaining Qty.',
                        'error');
                    return;
                }

                // Function to reset button state
                function resetButtonState() {
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                    btnText[0].textContent = ' Submit All';
                }

                // Disable button and show spinner
                submitBtn.prop('disabled', true);
                spinner.removeClass('d-none');
                btnText[0].textContent = ' Processing...';

                // Add selected IDs to form
                $('#bulk-goods-out-form input[name="selected_ids[]"]').remove();
                $('#bulk-goods-out-table-body tr').each(function() {
                    const id = $(this).find('input[type="number"]').attr('name').match(/\d+/)[0];
                    $('#bulk-goods-out-form').append(
                        `<input type="hidden" name="selected_ids[]" value="${id}">`
                    );
                });

                // Submit form
                const formData = $('#bulk-goods-out-form').serialize();
                $.ajax({
                    url: "{{ route('goods_out.bulk') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        resetButtonState();
                        if (response.success) {
                            $('#bulkGoodsOutModal').modal('hide');
                            Swal.fire('Success', response.message, 'success').then(() => {
                                table.ajax.reload(null, false); // Reload DataTable
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Bulk Goods Out failed.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        resetButtonState();
                        let msg = xhr.responseJSON?.message || 'Bulk Goods Out failed.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Reset button state when modal is closed
            $('#bulkGoodsOutModal').on('hidden.bs.modal', function() {
                const submitBtn = $('#submit-bulk-goods-out');
                const spinner = submitBtn.find('.spinner-border');
                const btnText = submitBtn.contents().filter(function() {
                    return this.nodeType === 3;
                }).last();

                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                btnText[0].textContent = ' Submit All';
            });

            // Material Detail Modal Handler
            $(document).on('click', '.material-detail-link', function(e) {
                e.preventDefault();
                const inventoryId = $(this).data('id');
                if (!inventoryId) return;

                $('#material-detail-modal-body').html(
                    '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>'
                );
                $('#materialDetailModal').modal('show');

                $.get('/inventory/detail/' + inventoryId, function(res) {
                    const html = $('<div>').html(res);
                    const cardBody = html.find('.card-body').html();
                    if (cardBody) {
                        $('#material-detail-modal-body').html(cardBody);
                    } else {
                        $('#material-detail-modal-body').html(
                            '<div class="alert alert-danger">Failed to load material details.</div>'
                        );
                    }
                }).fail(function() {
                    $('#material-detail-modal-body').html(
                        '<div class="alert alert-danger">Failed to load material details.</div>'
                    );
                });
            });

            // Reminder Button Handler
            $(document).on('click', '.btn-reminder', function() {
                const id = $(this).data('id');
                const btn = $(this);
                const originalHtml = btn.html();

                // Show loading state
                btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

                $.post(`/material_requests/${id}/reminder`, {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Reminder sent!', 'Logistic will be notified.', 'success');
                    } else {
                        Swal.fire('Error', res.message || 'Failed to send reminder.', 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Failed to send reminder. Please try again.', 'error');
                }).always(function() {
                    // Reset button state
                    btn.prop('disabled', false).html(originalHtml);
                });
            });

            // Status Select Enhancement
            function updateStatusTitle($select) {
                const val = $select.val();
                let tip = '';
                if (val === 'pending') tip = 'Waiting for approval';
                else if (val === 'approved') tip = 'Ready for goods out';
                else if (val === 'delivered') tip = 'Already delivered';
                else if (val === 'canceled') tip = 'Request canceled';
                $select.attr('title', tip);
            }

            function initializeStatusSelectTooltips(container = document) {
                $(container).find('.status-select').each(function() {
                    updateStatusTitle($(this));
                });
            }

            // Initial update of bulk goods out button
            updateBulkGoodsOutButton();

            // Quick Update Status Handler
            $(document).on('change', '.status-quick-update', function() {
                const $select = $(this);
                const id = $select.data('id');
                const newStatus = $select.val();
                const oldStatus = $select.data('previous-value') || $select.find('option[selected]').val();

                // Store current value as previous
                $select.data('previous-value', newStatus);

                // Disable select while updating
                $select.prop('disabled', true);

                // Show loading state
                const originalWidth = $select.width();
                $select.css('width', originalWidth + 'px');

                $.ajax({
                    url: `/material_requests/${id}/quick-update`,
                    method: 'POST',
                    data: {
                        status: newStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update select color
                            updateSelectColor($select[0]);

                            // Show success feedback (optional)
                            $select.addClass('border-success');
                            setTimeout(() => {
                                $select.removeClass('border-success');
                            }, 1000);

                            // Update checkbox visibility if status changed to/from approved
                            const $row = $select.closest('tr');
                            const $checkbox = $row.find('.select-row');

                            if (newStatus === 'approved') {
                                if ($checkbox.length === 0) {
                                    $row.find('td:first').html(
                                        '<input type="checkbox" class="select-row" value="' +
                                        id + '">');
                                }
                            } else {
                                $checkbox.remove();
                                $row.find('td:first').empty();
                            }

                            // Update bulk goods out button count
                            if (window.updateBulkGoodsOutButton) {
                                window.updateBulkGoodsOutButton();
                            }

                            console.log(`Status updated: ${oldStatus} → ${newStatus}`);
                        } else {
                            throw new Error(response.message || 'Update failed');
                        }
                    },
                    error: function(xhr) {
                        // Revert to previous value on error
                        $select.val(oldStatus);
                        $select.data('previous-value', oldStatus);

                        const errorMessage = xhr.responseJSON?.message ||
                            'Failed to update status';

                        // Show error feedback
                        $select.addClass('border-danger');
                        setTimeout(() => {
                            $select.removeClass('border-danger');
                        }, 2000);

                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: errorMessage,
                            timer: 3000,
                            showConfirmButton: false
                        });

                        console.error('Status update failed:', errorMessage);
                    },
                    complete: function() {
                        // Re-enable select
                        $select.prop('disabled', false);
                        $select.css('width', '');
                    }
                });
            });

            // Initialize previous values for status selects
            $(document).on('draw.dt', '#datatable', function() {
                $('.status-quick-update').each(function() {
                    const $select = $(this);
                    $select.data('previous-value', $select.val());
                });
            });
        });

        // Utility Functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function initializeTooltipsBatch(container = document) {
            // Dispose existing tooltips first
            $(container).find('[data-bs-toggle="tooltip"]').tooltip('dispose');

            // Initialize new tooltips with enhanced configuration
            const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipElements.forEach(element => {
                new bootstrap.Tooltip(element, {
                    trigger: 'hover focus',
                    placement: 'right', // Force right placement
                    fallbackPlacements: ['top', 'bottom', 'left'], // Fallback order
                    boundary: 'viewport', // Use viewport as boundary instead of container
                    container: 'body', // Append to body instead of parent container
                    offset: [0, 8], // Add some spacing from element
                    sanitize: false,
                    html: true // Allow HTML in tooltips if needed
                });
            });
        }

        function updateAllSelectColors(container) {
            const selects = container.querySelectorAll('.status-select');
            selects.forEach(select => updateSelectColor(select));
        }

        // Global Variables for Auth User
        window.authUser = {
            username: "{{ auth()->user()->username }}",
            is_logistic_admin: {{ auth()->user()->isLogisticAdmin() ? 'true' : 'false' }},
            is_super_admin: {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }}
        };

        // Initialize Tooltips on Page Load
        document.addEventListener("DOMContentLoaded", function() {
            initializeTooltipsBatch();
        });
    </script>
@endpush
