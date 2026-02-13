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

                    <!-- Spacer untuk tombol -->
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

                <!-- ========== FILTER FORM ========== -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <!-- Project Type Filter -->
                        <div class="col-md-2">
                            <select id="filter-project-type" name="project_type" class="form-select form-select-sm select2">
                                <option value="">All Project Types</option>
                                <option value="client">Client</option>
                                <option value="internal">Internal</option>
                            </select>
                        </div>

                        <!-- Client Project Filter (selalu tampil) -->
                        <div class="col-md-2">
                            <select id="filter-project" name="project" class="form-select form-select-sm select2">
                                <option value="">All Client Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Internal Project Filter (hidden by default, tampil jika project_type = internal) -->
                        <div class="col-md-2 d-none" id="filter-internal-project-wrapper">
                            <select id="filter-internal-project" name="internal_project"
                                class="form-select form-select-sm select2">
                                <option value="">All Internal Projects</option>
                                @foreach ($internalProjects as $internal)
                                    <option value="{{ $internal->id }}">{{ $internal->project }} - {{ $internal->job }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Job Order Filter -->
                        <div class="col-md-2">
                            <select id="filter-job-order" name="job_order" class="form-select form-select-sm select2">
                                <option value="">All Job Orders</option>
                                @foreach ($jobOrders as $jo)
                                    <option value="{{ $jo->id }}">{{ $jo->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Material Filter -->
                        <div class="col-md-2">
                            <select id="filter-material" name="material" class="form-select form-select-sm select2">
                                <option value="">All Materials</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <select id="filter-status" name="status" class="form-select form-select-sm select2">
                                <option value="">All Status</option>
                                <option value="pending" title="Waiting for approval from admin">Pending</option>
                                <option value="approved" title="Request approved, ready for goods out">Approved</option>
                                <option value="delivered" title="Goods have been delivered">Delivered</option>
                                <option value="canceled" title="Request has been canceled">Canceled</option>
                            </select>
                        </div>

                        <!-- Requested By Filter -->
                        <div class="col-md-1">
                            <select id="filter-requested-by" name="requested_by" class="form-select form-select-sm select2">
                                <option value="">All Requester</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}">{{ ucfirst($user->username) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Requested At Date -->
                        <div class="col-md-2">
                            <input type="text" id="filter-requested-at" name="requested_at"
                                class="form-control form-control-sm" placeholder="Requested At Date">
                        </div>

                        <!-- Custom Search -->
                        <div class="col-md-1">
                            <input type="text" id="custom-search" name="search" class="form-control form-control-sm"
                                placeholder="Search...">
                        </div>

                        <!-- Reset Button -->
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
                            <th>Job Order</th>
                            <th>Project Type</th> <!-- NEW COLUMN -->
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

    <!-- Modal Bulk Goods Out (tidak berubah) -->
    <!-- ... (kode modal tetap sama) ... -->

    <!-- Modal Material Detail (tidak berubah) -->
    <!-- ... (kode modal tetap sama) ... -->
@endsection

@push('styles')
    <style>
        /* ========== STYLE YANG SUDAH ADA, TAMBAHKAN INI ========== */
        #filter-internal-project-wrapper {
            transition: all 0.2s ease;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // ========== INISIALISASI DATATABLE ==========
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    url: "{{ route('material_requests.index') }}",
                    data: function(d) {
                        // Add all filter parameters
                        d.project_type = $('#filter-project-type').val();
                        d.project = $('#filter-project').val();
                        d.internal_project = $('#filter-internal-project').val();
                        d.job_order = $('#filter-job-order').val();
                        d.material = $('#filter-material').val();
                        d.status = $('#filter-status').val();
                        d.requested_by = $('#filter-requested-by').val();
                        d.requested_at = $('#filter-requested-at').val();
                        d.custom_search = $('#custom-search').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', {
                            xhr,
                            error,
                            thrown
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
                        data: 'job_order',
                        name: 'jobOrder.name',
                        width: '12%'
                    },
                    {
                        data: 'project_type_display',
                        name: 'project_type',
                        width: '6%'
                    }, // NEW COLUMN
                    {
                        data: 'project_name',
                        name: 'project.name',
                        width: '12%'
                    },
                    {
                        data: 'material_name',
                        name: 'inventory.name',
                        width: '12%'
                    },
                    {
                        data: 'requested_qty',
                        name: 'qty',
                        width: '7%'
                    },
                    {
                        data: 'remaining_qty',
                        name: 'remaining_qty',
                        orderable: false,
                        width: '7%'
                    },
                    {
                        data: 'processed_qty',
                        name: 'processed_qty',
                        orderable: false,
                        width: '7%'
                    },
                    {
                        data: 'requested_by',
                        name: 'requested_by',
                        width: '7%'
                    },
                    {
                        data: 'requested_at',
                        name: 'created_at',
                        width: '7%'
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
                        width: '10%'
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
                    [1, 'desc']
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

            // ========== FILTER HANDLER ==========
            const debouncedFilter = debounce(function() {
                table.draw();
            }, 300);

            // Event listener untuk semua filter
            $('#filter-project-type, #filter-project, #filter-internal-project, #filter-job-order, #filter-material, #filter-status, #filter-requested-by, #filter-requested-at, #custom-search')
                .on('change input', debouncedFilter);

            // ========== TOGGLE FILTER INTERNAL PROJECT ==========
            function toggleInternalProjectFilter() {
                const projectType = $('#filter-project-type').val();
                if (projectType === 'internal') {
                    $('#filter-internal-project-wrapper').removeClass('d-none');
                    // Sembunyikan filter client project
                    $('#filter-project').closest('.col-md-2').addClass('d-none');
                } else {
                    $('#filter-internal-project-wrapper').addClass('d-none');
                    $('#filter-project').closest('.col-md-2').removeClass('d-none');
                    // Reset nilai internal project filter
                    $('#filter-internal-project').val('').trigger('change');
                }
            }

            $('#filter-project-type').on('change', toggleInternalProjectFilter);
            toggleInternalProjectFilter(); // inisialisasi saat halaman dimuat

            // ========== RESET FILTERS ==========
            $('#reset-filters').on('click', function() {
                $('#filter-project-type, #filter-project, #filter-internal-project, #filter-job-order, #filter-material, #filter-status, #filter-requested-by, #filter-requested-at, #custom-search')
                    .val('').trigger('change');
                table.draw();
            });

            // ========== EXPORT BUTTON ==========
            $('#export-btn').on('click', function(e) {
                e.preventDefault();
                const params = {
                    project_type: $('#filter-project-type').val(),
                    project: $('#filter-project').val(),
                    internal_project: $('#filter-internal-project').val(),
                    job_order: $('#filter-job-order').val(),
                    material: $('#filter-material').val(),
                    status: $('#filter-status').val(),
                    requested_by: $('#filter-requested-by').val(),
                    requested_at: $('#filter-requested-at').val(),
                    search: $('#custom-search').val()
                };
                const query = $.param(params);
                window.location.href = '{{ route('material_requests.export') }}' + '?' + query;
            });

            // ========== SELECT2 INIT ==========
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Set placeholder
            $('#filter-project-type').attr('data-placeholder', 'All Project Types');
            $('#filter-project').attr('data-placeholder', 'All Client Projects');
            $('#filter-internal-project').attr('data-placeholder', 'All Internal Projects');
            $('#filter-job-order').attr('data-placeholder', 'All Job Orders');
            $('#filter-material').attr('data-placeholder', 'All Materials');
            $('#filter-status').attr('data-placeholder', 'All Status');
            $('#filter-requested-by').attr('data-placeholder', 'All Requesters');

            // ========== DATE INPUT ==========
            flatpickr("#filter-requested-at", {
                dateFormat: "Y-m-d",
                allowInput: true,
            });

            // ========== BULK GOODS OUT (sama seperti sebelumnya) ==========
            // ... (kode bulk goods out tidak diubah) ...

            // ========== DEBOUNCE FUNCTION ==========
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

            // ========== TOOLTIPS ==========
            function initializeTooltipsBatch(container = document) {
                $(container).find('[data-bs-toggle="tooltip"]').tooltip('dispose');
                const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipElements.forEach(element => {
                    new bootstrap.Tooltip(element, {
                        trigger: 'hover focus',
                        placement: 'right',
                        fallbackPlacements: ['top', 'bottom', 'left'],
                        boundary: 'viewport',
                        container: 'body',
                        offset: [0, 8],
                        sanitize: false,
                        html: true
                    });
                });
            }

            // Initialize tooltip untuk status select dengan hint text
            function initializeStatusSelectTooltips(container = document) {
                $(container).find('.status-quick-update').each(function() {
                    const $select = $(this);
                    const currentStatus = $select.val();

                    // Add title attribute based on current status
                    if (currentStatus === 'pending') {
                        $select.attr('title', 'Click to approve or process this request');
                    } else if (currentStatus === 'approved') {
                        $select.attr('title', 'Ready for goods out');
                    } else if (currentStatus === 'canceled') {
                        $select.attr('title', 'This request has been canceled');
                    }

                    // Initialize bootstrap tooltip
                    new bootstrap.Tooltip($select[0], {
                        trigger: 'hover',
                        placement: 'top',
                        container: 'body'
                    });
                });
            }

            // Inisialisasi tooltip saat halaman dimuat
            document.addEventListener("DOMContentLoaded", function() {
                initializeTooltipsBatch();
            });

            // ========== STATUS SELECT COLORS ==========
            function updateAllSelectColors(container) {
                const selects = container.querySelectorAll('.status-select');
                selects.forEach(select => updateSelectColor(select));
            }

            // ========== STATUS QUICK UPDATE (REAL-TIME) - NO SWEETALERT ==========
            $(document).on('change', '.status-quick-update', function() {
                const $select = $(this);
                const requestId = $select.data('id');
                const newStatus = $select.val();
                const previousStatus = $select.data('previous-value') || $select.find('option:selected')
                    .data('original-value');

                // Lock UI: disable select + change cursor to wait only
                $select.prop('disabled', true).css('cursor', 'wait');
                $select.closest('tr').css('cursor', 'wait');

                $.ajax({
                    url: `/material_requests/${requestId}/quick-update`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update previous value
                            $select.data('previous-value', newStatus);

                            // Reset cursor
                            $select.css('cursor', 'pointer');
                            $select.closest('tr').css('cursor', 'default');

                            // NO SWEETALERT - Just reload table silently
                            table.ajax.reload(null, false);

                            // Restore cursor
                            $select.prop('disabled', false).css('cursor', 'pointer');
                        } else {
                            throw new Error(response.message || 'Update failed');
                        }
                    },
                    error: function(xhr) {
                        // Restore select with previous value
                        $select.val(previousStatus).prop('disabled', false).css('cursor',
                            'pointer');
                        $select.closest('tr').css('cursor', 'default');

                        const errorMsg = xhr.responseJSON?.message ||
                            'Failed to update status. Please try again.';
                        // Only show SweetAlert on ERROR
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: errorMsg,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Initialize previous values on page load
            $(document).on('draw.dt', '#datatable', function() {
                $('.status-quick-update').each(function() {
                    if (!$(this).data('previous-value')) {
                        $(this).data('previous-value', $(this).val());
                    }
                });
            });

            // ========== DELETE MATERIAL REQUEST ==========
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const $form = $(this).closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This material request will be deleted permanently!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $form.submit();
                    }
                });
            });

            // ========== REMIND LOGISTIC ==========
            $(document).on('click', '.btn-reminder', function(e) {
                e.preventDefault();
                const requestId = $(this).data('id');
                const $btn = $(this);
                const originalHtml = $btn.html();

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: `/material_requests/${requestId}/reminder`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html(originalHtml);

                        Swal.fire({
                            icon: 'success',
                            title: 'Reminder Sent!',
                            text: response.message || 'Logistic has been notified.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);

                        const errorMsg = xhr.responseJSON?.message ||
                            'Failed to send reminder.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: errorMsg,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // ========== BULK GOODS OUT BUTTON ==========
            function updateBulkGoodsOutButton() {
                const selectedCount = $('.select-row:checked').length;
                const $btn = $('#bulk-goods-out-btn');
                const $count = $('#bulk-goods-out-count');

                if (selectedCount > 0) {
                    $btn.prop('disabled', false);
                    $count.text(selectedCount).removeClass('d-none');
                } else {
                    $btn.prop('disabled', true);
                    $count.addClass('d-none');
                }
            }

            // Update button on checkbox change
            $(document).on('change', '.select-row', updateBulkGoodsOutButton);

            // Bulk Goods Out action - FIXED: Use correct route
            $('#bulk-goods-out-btn').on('click', function() {
                const selectedIds = $('.select-row:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    Swal.fire('Warning', 'Please select at least one approved request.', 'warning');
                    return;
                }

                // Fetch material details first and show confirmation
                $.ajax({
                    url: '{{ route('material_requests.bulk_details') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        selected_ids: selectedIds
                    },
                    success: function(response) {
                        // Build confirmation table
                        let confirmHtml =
                            '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        confirmHtml +=
                            '<thead class="table-light"><tr><th>Material</th><th>Job Order</th><th>Remaining Qty</th></tr></thead><tbody>';

                        response.forEach(item => {
                            confirmHtml += `<tr>
                                <td>${item.material_name}</td>
                                <td>${item.job_order_name}</td>
                                <td><strong>${item.remaining_qty} ${item.unit}</strong></td>
                            </tr>`;
                        });

                        confirmHtml += '</tbody></table></div>';

                        // Show confirmation dialog first
                        Swal.fire({
                            title: 'Confirm Bulk Goods Out',
                            html: `<p class="mb-3">You are about to process <strong>${selectedIds.length}</strong> material request(s) for Goods Out:</p>` +
                                confirmHtml,
                            icon: 'question',
                            width: '700px',
                            showCancelButton: true,
                            confirmButtonText: 'Continue to Goods Out Form',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Build goods out form dynamically
                                let formHtml = '<form id="bulk-goods-out-form">';
                                formHtml +=
                                    '<table class="table table-sm"><thead><tr><th>Material</th><th>Job Order</th><th>Remaining Qty</th><th>Goods Out Qty</th></tr></thead><tbody>';

                                response.forEach(item => {
                                    formHtml += `<tr>
                                <td>${item.material_name}</td>
                                <td>${item.job_order_name}</td>
                                <td>${item.remaining_qty} ${item.unit}</td>
                                <td><input type="number" class="form-control form-control-sm" name="goods_out_qty[${item.id}]"
                                    value="${item.remaining_qty}" min="0.001" max="${item.remaining_qty}" step="0.001" required></td>
                            </tr>`;
                                });

                                formHtml += '</tbody></table></form>';

                                Swal.fire({
                                    title: 'Enter Goods Out Quantities',
                                    html: formHtml,
                                    width: '800px',
                                    showCancelButton: true,
                                    confirmButtonText: 'Process Goods Out',
                                    cancelButtonText: 'Cancel',
                                    preConfirm: () => {
                                        const formData = {};
                                        formData.selected_ids = selectedIds;
                                        formData.goods_out_qty = {};

                                        $('input[name^="goods_out_qty"]')
                                            .each(function() {
                                                const match = $(this)
                                                    .attr('name').match(
                                                        /\[(\d+)\]/);
                                                if (match) {
                                                    formData
                                                        .goods_out_qty[
                                                            match[1]] =
                                                        parseFloat($(
                                                                this)
                                                            .val());
                                                }
                                            });

                                        return formData;
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Submit to correct POST route
                                        $.ajax({
                                            url: '{{ route('goods_out.bulk') }}',
                                            method: 'POST',
                                            data: {
                                                _token: '{{ csrf_token() }}',
                                                selected_ids: result
                                                    .value.selected_ids,
                                                goods_out_qty: result
                                                    .value.goods_out_qty
                                            },
                                            success: function(
                                                response) {
                                                if (response
                                                    .success) {
                                                    Swal.fire(
                                                        'Success',
                                                        response
                                                        .message,
                                                        'success'
                                                    ).then(
                                                        () => {
                                                            table
                                                                .ajax
                                                                .reload();
                                                        });
                                                } else {
                                                    Swal.fire(
                                                        'Error',
                                                        response
                                                        .message,
                                                        'error');
                                                }
                                            },
                                            error: function(xhr) {
                                                const errorMsg = xhr
                                                    .responseJSON
                                                    ?.message ||
                                                    'Failed to process bulk goods out';
                                                Swal.fire('Error',
                                                    errorMsg,
                                                    'error');
                                            }
                                        });
                                    }
                                });
                            }
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to fetch material details',
                            'error');
                    }
                });
            });
        });
    </script>
@endpush
