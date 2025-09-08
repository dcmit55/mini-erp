@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-clipboard-list gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Material Requests</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('material_requests.create') }}" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Request
                        </a>
                        <a href="{{ route('material_requests.bulk_create') }}" class="btn btn-info btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Bulk Request
                        </a>
                        @if (auth()->user()->isLogisticAdmin())
                            <button id="bulk-goods-out-btn" class="btn btn-success btn-sm flex-shrink-0" disabled>
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                <span id="bulk-goods-out-text">Bulk Goods Out</span>
                                <span id="bulk-goods-out-count" class="badge bg-light text-dark ms-1 d-none">0</span>
                            </button>
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

                <div class="mb-3">
                    <form id="filter-form" method="GET" action="{{ route('material_requests.index') }}" class="row g-2">
                        <div class="col-lg-2">
                            <select id="filter-project" name="project" class="form-select select2">
                                <option value="">All Projects</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ request('project') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-material" name="material" class="form-select select2">
                                <option value="">All Materials</option>
                                @foreach ($materials as $material)
                                    <option value="{{ $material->id }}"
                                        {{ request('material') == $material->id ? 'selected' : '' }}>
                                        {{ $material->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-status" name="status" class="form-select select2">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved
                                </option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>
                                    Delivered</option>
                                <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>
                                    Canceled</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-requested-by" name="requested_by" class="form-select select2">
                                <option value="">All Requested By</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->username }}"
                                        {{ request('requested_by') == $user->username ? 'selected' : '' }}>
                                        {{ ucfirst($user->username) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="text" id="filter-requested-at" name="requested_at" class="form-control"
                                value="{{ request('requested_at') }}" placeholder="Requested At Date" autocomplete="off">
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary" id="filter-btn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                Filter
                            </button>
                            <a href="{{ route('material_requests.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-striped table-hover table-bordered table-sm" id="datatable"
                    data-material-request-table="1">
                    <thead class="align-middle text-nowrap">
                        <tr>
                            <th></th>
                            <th style="display:none">ID</th>
                            <th>Project</th>
                            <th>Material</th>
                            <th>Requested Qty</th>
                            <th><i class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="The quantity of material requests that have not yet been processed for goods out."
                                    style="font-size: 0.8rem; cursor: pointer;"></i> Remaining Qty
                            </th>
                            <th><i class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="The quantity of material requests that have already been processed and issued as goods out."
                                    style="font-size: 0.8rem; cursor: pointer;"></i> Processed Qty
                            </th>
                            <th>Requested By</th>
                            <th>Requested At</th>
                            <th>Status</th>
                            <th>Remark</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @foreach ($requests as $req)
                            <tr id="row-{{ $req->id }}">
                                <td class="text-center">
                                    @if ($req->status === 'approved')
                                        <input type="checkbox" class="select-row" id="checkbox-{{ $req->id }}"
                                            value="{{ $req->id }}">
                                    @endif
                                </td>
                                <td style="display:none">{{ $req->id }}</td>
                                <td>{{ $req->project->name ?? '(No Project)' }}</td>
                                <td>
                                    <span class="material-detail-link gradient-link"
                                        data-id="{{ $req->inventory->id ?? '' }}">
                                        {{ $req->inventory->name ?? '(No Material)' }}
                                    </span>
                                </td>
                                <td>{{ rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.') }}
                                    {{ $req->inventory->unit ?? '(No Unit)' }}</td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="right"
                                        title="{{ $req->inventory->unit ?? '(No Unit)' }}" class="tooltip-td">
                                        {{ rtrim(rtrim(number_format($req->remaining_qty, 2, '.', ''), '0'), '.') }}
                                    </span>
                                </td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="right"
                                        title="{{ $req->inventory->unit ?? '(No Unit)' }}" class="tooltip-td">
                                        {{ rtrim(rtrim(number_format($req->processed_qty, 2, '.', ''), '0'), '.') }}
                                    </span>
                                </td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="right"
                                        title="{{ $req->user && $req->user->department ? ucfirst($req->user->department->name) : '-' }}"
                                        class="tooltip-td">
                                        {{ ucfirst($req->requested_by) }}
                                    </span>
                                </td>
                                <td>{{ $req->created_at?->format('Y-m-d, H:i') }}</td>
                                <td>
                                    @if (auth()->user()->isLogisticAdmin())
                                        <form method="POST" action="{{ route('material_requests.update', $req->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="filter_project"
                                                value="{{ request('project') }}">
                                            <input type="hidden" name="filter_material"
                                                value="{{ request('material') }}">
                                            <input type="hidden" name="filter_status" value="{{ request('status') }}">
                                            <input type="hidden" name="filter_requested_by"
                                                value="{{ request('requested_by') }}">
                                            <input type="hidden" name="filter_requested_at"
                                                value="{{ request('requested_at') }}">
                                            <select name="status"
                                                class="form-select form-select-sm status-select status-select-rounded"
                                                onchange="this.form.submit()"
                                                title="{{ $req->status === 'pending' ? 'Waiting for approval' : ($req->status === 'approved' ? 'Ready for goods out' : ($req->status === 'delivered' ? 'Already delivered' : 'Request canceled')) }}"
                                                {{ $req->status === 'delivered' ? 'disabled' : '' }}>
                                                <option value="pending"
                                                    {{ $req->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="approved"
                                                    {{ $req->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="canceled"
                                                    {{ $req->status === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                                <option value="delivered"
                                                    {{ $req->status === 'delivered' ? 'selected' : '' }} disabled>Delivered
                                                </option>
                                            </select>
                                        </form>
                                    @else
                                        <span
                                            class="badge rounded-pill {{ $req->getStatusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $req->remark }}</td>
                                <td>
                                    <div class="d-flex flex-nowrap gap-1">
                                        @if (
                                            $req->status === 'approved' &&
                                                $req->status !== 'canceled' &&
                                                $req->remaining_qty > 0 &&
                                                auth()->user()->isLogisticAdmin())
                                            <a href="{{ route('goods_out.create_with_id', $req->id) }}"
                                                class="btn btn-sm btn-success" data-bs-toggle="tooltip"
                                                data-bs-placement="bottom" title="Goods Out"><i
                                                    class="bi bi-box-arrow-right"></i></a>
                                        @endif
                                        @if (
                                            $req->status === 'pending' &&
                                                (auth()->user()->username === $req->requested_by || auth()->user()->isLogisticAdmin()))
                                            <a href="{{ route('material_requests.edit', [$req->id] + request()->query()) }}"
                                                class="btn btn-sm btn-warning" data-bs-toggle="tooltip"
                                                data-bs-placement="bottom" title="Edit"><i
                                                    class="bi bi-pencil-square"></i></a>
                                        @endif
                                        @if (
                                            $req->status !== 'canceled' &&
                                                $req->status !== 'delivered' &&
                                                (auth()->user()->isRequestOwner($req->requested_by) || auth()->user()->isSuperAdmin()))
                                            <form action="{{ route('material_requests.destroy', $req->id) }}"
                                                method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"><i
                                                        class="bi bi-trash3"></i></button>
                                            </form>
                                        @endif
                                        @if (in_array($req->status, ['pending', 'approved']) &&
                                                (auth()->user()->username === $req->requested_by || auth()->user()->isSuperAdmin()))
                                            <button class="btn btn-sm btn-primary btn-reminder"
                                                data-id="{{ $req->id }}" data-bs-toggle="tooltip"
                                                data-bs-placement="bottom" title="Remind Logistic">
                                                <i class="bi bi-bell"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialDetailModalLabel">Material Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="material-detail-modal-body">
                    <!-- Detail akan dimuat via AJAX -->
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        #datatable th {
            font-size: 0.90rem;
            white-space: nowrap;
            vertical-align: middle;
            text-align: left !important;
            /* Force left alignment for all table headers */
        }

        #datatable td {
            vertical-align: middle;
            text-align: left !important;
            /* Force left alignment for all table cells */
        }

        /* Batasi lebar kolom tertentu jika perlu */
        #datatable th,
        #datatable td {
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
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
            min-width: 100px !important;
            outline: none !important;
            box-shadow: none !important;
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

        .tooltip-td {
            cursor: pointer;
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
        /* Requested Qty Header */
        #datatable thead th:nth-child(6),
        /* Remaining Qty Header */
        #datatable thead th:nth-child(7),
        /* Processed Qty Header */
        #datatable tbody td:nth-child(5),
        /* Requested Qty */
        #datatable tbody td:nth-child(6),
        /* Remaining Qty */
        #datatable tbody td:nth-child(7) {
            /* Processed Qty */
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
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#datatable').DataTable({
                responsive: true,
                destroy: true,
                stateSave: true,
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    }, // Kolom checkbox tidak dapat diurutkan
                    {
                        visible: false,
                        targets: 1
                    }, // Sembunyikan kolom ID di DataTable
                ],
                rowId: function(data) {
                    return 'row-' + data[1]; // data[1] adalah kolom ID
                },
            });

            $('#datatable').on('draw.dt', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                    '[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Update bulk goods out button setelah redraw
                updateBulkGoodsOutButton();
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // Add placeholder support for input[type="date"]
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

            // Function to update bulk goods out button
            function updateBulkGoodsOutButton() {
                const selectedCount = $('.select-row:checked').length;
                const bulkBtn = $('#bulk-goods-out-btn');
                const countBadge = $('#bulk-goods-out-count');

                if (selectedCount > 0) {
                    bulkBtn.prop('disabled', false);
                    countBadge.removeClass('d-none').text(selectedCount);
                } else {
                    bulkBtn.prop('disabled', true);
                    countBadge.addClass('d-none').text('0');
                }
            }

            // Handle checkbox changes
            $(document).on('change', '.select-row', function() {
                updateBulkGoodsOutButton();
            });

            // Handle select all checkbox (if exists)
            $('#select-all').on('change', function() {
                $('.select-row').prop('checked', $(this).prop('checked'));
                updateBulkGoodsOutButton();
            });

            // SweetAlert for delete confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $('#bulk-goods-out-btn').on('click', function() {
                const selectedIds = $('.select-row:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    Swal.fire('Error', 'Please select at least one material request.', 'error');
                    return;
                }

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
                        $('#bulkGoodsOutModal').modal('show');
                        // Inisialisasi tooltip setelah modal tampil
                        setTimeout(function() {
                            var tooltipTriggerList = [].slice.call(document
                                .querySelectorAll('[data-bs-toggle="tooltip"]'));
                            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                                new bootstrap.Tooltip(tooltipTriggerEl);
                            });
                        }, 300); // delay agar modal sudah render
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to fetch material request details.',
                            'error');
                    }
                });
            });

            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
            });

            $('#submit-bulk-goods-out').on('click', function() {
                const submitBtn = $(this);
                const spinner = submitBtn.find('.spinner-border');
                const btnText = submitBtn.contents().filter(function() {
                    return this.nodeType === 3; // Text nodes only
                }).last();

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
                    Swal.fire('Error', 'Qty to Goods Out must be between 0.001 or Remaining Qty.', 'error');
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

                // Tambahkan input hidden selected_ids[] ke form
                $('#bulk-goods-out-form input[name="selected_ids[]"]')
                    .remove(); // hapus dulu biar tidak dobel
                $('#bulk-goods-out-table-body tr').each(function() {
                    const id = $(this).find('input[type="number"]').attr('name').match(/\d+/)[0];
                    $('#bulk-goods-out-form').append(
                        `<input type="hidden" name="selected_ids[]" value="${id}">`);
                });

                // Serialize form and send AJAX
                const formData = $('#bulk-goods-out-form').serialize();
                $.ajax({
                    url: "{{ route('goods_out.bulk') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        // Reset button state immediately after response
                        resetButtonState();

                        if (response.success) {
                            Swal.fire('Success', response.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.message || 'Bulk Goods Out failed.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        // Reset button state immediately after error
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

            // Initialize flatpickr for date input
            flatpickr("#filter-requested-at", {
                dateFormat: "Y-m-d",
                allowInput: true,
            });

            // Spinner filter button handling
            const filterBtn = $('#filter-btn');
            const filterSpinner = filterBtn.find('.spinner-border');
            const filterForm = $('#filter-form');
            const filterBtnHtml = filterBtn.html();

            if (filterForm.length && filterBtn.length && filterSpinner.length) {
                filterForm.on('submit', function() {
                    filterBtn.prop('disabled', true);
                    filterSpinner.removeClass('d-none');
                });
            }

            // Fungsi untuk update title pada select status
            function updateStatusTitle($select) {
                const val = $select.val();
                let tip = '';
                if (val === 'pending') tip = 'Waiting for approval';
                else if (val === 'approved') tip = 'Ready for goods out';
                else if (val === 'delivered') tip = 'Already delivered';
                else if (val === 'canceled') tip = 'Request canceled';
                $select.attr('title', tip);
            }

            // Inisialisasi title pada semua status select saat halaman load
            $('.status-select').each(function() {
                updateStatusTitle($(this));
            });

            // Update title saat select berubah
            $(document).on('change', '.status-select', function() {
                updateStatusTitle($(this));
            });

            // Jika pakai DataTable, update title setelah redraw
            $('#datatable').on('draw.dt', function() {
                $('.status-select').each(function() {
                    updateStatusTitle($(this));
                });
            });

            // Initial update of bulk goods out button
            updateBulkGoodsOutButton();
        });

        // Material detail link click handler
        $(document).on('click', '.material-detail-link', function(e) {
            e.preventDefault();
            const inventoryId = $(this).data('id');
            if (!inventoryId) return;

            $('#material-detail-modal-body').html(
                '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>');
            $('#materialDetailModal').modal('show');

            $.get('/inventory/detail/' + inventoryId, function(res) {
                // Ambil hanya isi <div class="card-body">...</div> dari halaman detail
                const html = $('<div>').html(res);
                const cardBody = html.find('.card-body').html();
                if (cardBody) {
                    $('#material-detail-modal-body').html(cardBody);
                } else {
                    $('#material-detail-modal-body').html(
                        '<div class="alert alert-danger">Failed to load material details.</div>');
                }
            }).fail(function() {
                $('#material-detail-modal-body').html(
                    '<div class="alert alert-danger">Failed to load material details.</div>');
            });
        });

        // Tooltip initialization
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Global variable for authenticated user
        window.authUser = {
            username: "{{ auth()->user()->username }}",
            is_logistic_admin: {{ auth()->user()->isLogisticAdmin() ? 'true' : 'false' }},
            is_super_admin: {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }}
        };

        // Reminder button click handler
        $(document).on('click', '.btn-reminder', function() {
            const id = $(this).data('id');
            $.post(`/material_requests/${id}/reminder`, {}, function(res) {
                if (res.success) {
                    Swal.fire('Reminder sent!', 'Logistic will be notified.', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Failed to send reminder.', 'error');
                }
            }).fail(function(xhr) {
                Swal.fire('Error', 'Failed to send reminder. Please try again.', 'error');
            });
        });
    </script>
@endpush
