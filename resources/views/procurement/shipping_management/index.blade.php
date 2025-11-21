@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <h4>Shipping Management</h4>
        <div class="card">
            <div class="card-body">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Int. Waybill</th>
                            <th>ETA to Arrived</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Goods Receive</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shippings as $shipping)
                            <tr>
                                <td>
                                    <button class="btn btn-link btn-sm toggle-detail" data-id="{{ $shipping->id }}">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </td>
                                <td>{{ $shipping->international_waybill_no }}</td>
                                <td>{{ $shipping->eta_to_arrived }}</td>
                                <td>{{ $shipping->shipment_status ?? '-' }}</td>
                                <td>{{ $shipping->remarks ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm btn-goods-receive"
                                        data-shipping-id="{{ $shipping->id }}">
                                        Goods Receive
                                    </button>
                                </td>
                            </tr>
                            <tr class="detail-row" id="detail-{{ $shipping->id }}" style="display:none;">
                                <td colspan="6">
                                    <div class="p-2">
                                        {{-- Cek apakah details ada dan purchaseRequest tidak null --}}
                                        @forelse ($shipping->details as $detail)
                                            @if ($detail->preShipping && $detail->preShipping->purchaseRequest)
                                                <div class="border rounded-3 mb-2 p-2" style="background:#f8f9fa;">
                                                    <div class="row fw-semibold mb-1">
                                                        <div class="col-md-2">
                                                            <span class="text-muted">Purchase Type:</span>
                                                            <div>
                                                                {{ ucfirst(str_replace('_', ' ', $detail->preShipping->purchaseRequest->type)) }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted">Material Name:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->material_name }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <span class="text-muted">Qty To Buy:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->qty_to_buy ?? $detail->preShipping->purchaseRequest->required_quantity }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <span class="text-muted">Unit Type:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->unit }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted">Supplier:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->supplier->name ?? '-' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <span class="text-muted">Unit Price:</span>
                                                            <div>
                                                                {{ number_format($detail->preShipping->purchaseRequest->price_per_unit, 2) }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <span class="text-muted">Project Name:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->project->name ?? '-' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-1">
                                                        <div class="col-md-2 small text-muted">
                                                            Domestic Waybill:<br>
                                                            <span class="fw-semibold">
                                                                {{ $detail->preShipping->domestic_waybill_no ?? '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-2 small text-muted">
                                                            Domestic Cost:<br>
                                                            <span class="fw-semibold">
                                                                {{ number_format($detail->preShipping->domestic_cost ?? 0, 2) }}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-2 small text-muted">
                                                            Int. Cost:<br>
                                                            <span class="fw-semibold">
                                                                {{ number_format($detail->int_cost ?? 0, 2) }}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-2 small text-muted">
                                                            Destination:<br>
                                                            <span
                                                                class="badge bg-{{ $detail->destination_badge_color ?? 'secondary' }}">
                                                                <i class="bi bi-geo-alt-fill me-1"></i>
                                                                {{ $detail->destination_label ?? '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                {{-- Detail tanpa PreShipping atau PurchaseRequest --}}
                                                <div class="alert alert-warning small mb-2">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Data incomplete (missing pre-shipping or purchase request information)
                                                </div>
                                            @endif
                                        @empty
                                            {{-- Tidak ada details --}}
                                            <div class="alert alert-info small mb-0">
                                                <i class="fas fa-info-circle me-1"></i>
                                                No shipping details available
                                            </div>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- Tidak ada shippings --}}
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No shipping data available</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Create Goods Receive -->
    <div class="modal fade" id="goodsReceiveModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Create Goods Receive
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="goods-receive-form">
                    @csrf
                    <input type="hidden" name="shipping_id" id="shipping_id">

                    <div class="modal-body">
                        <!-- Header Info -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Int. Waybill</label>
                                <input type="text" class="form-control" id="waybill" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Freight Company</label>
                                <input type="text" class="form-control" id="freight_company" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Int. Freight Cost</label>
                                <input type="text" class="form-control" id="freight_price" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Arrived Date <span
                                        class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="arrived_date" name="arrived_date"
                                    required>
                            </div>
                        </div>
                        <!-- Detail List -->
                        <div id="detail-list" class="mt-3"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Submit Goods Receive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Custom Modal Width untuk Goods Receive */
        #goodsReceiveModal .modal-dialog {
            max-width: 95vw !important;
            /* 95% viewport width */
            margin: 1.75rem auto;
            /* Center dengan margin atas/bawah */
        }

        /* Responsive breakpoints untuk layar besar */
        @media (min-width: 1400px) {
            #goodsReceiveModal .modal-dialog {
                max-width: 1800px !important;
                /* Extra large screens */
            }
        }

        @media (min-width: 1920px) {
            #goodsReceiveModal .modal-dialog {
                max-width: 2200px !important;
                /* Ultra wide screens (1080p+) */
            }
        }

        /* Responsive untuk layar kecil */
        @media (max-width: 767.98px) {
            #goodsReceiveModal .modal-dialog {
                max-width: 100vw !important;
                margin: 0.5rem !important;
            }

            /* Optimize table untuk mobile */
            #goodsReceiveModal .table-goods-receive {
                font-size: 0.75rem;
            }

            #goodsReceiveModal .table-goods-receive th,
            #goodsReceiveModal .table-goods-receive td {
                padding: 0.25rem !important;
            }
        }

        /* Improve modal body height */
        #goodsReceiveModal .modal-body {
            max-height: calc(100vh - 200px);
            /* Prevent modal terlalu tinggi */
            overflow-y: auto;
            /* Enable scroll jika konten panjang */
        }

        /* Improve table layout di modal */
        #goodsReceiveModal .table-responsive {
            margin: 0 -0.5rem;
            /* Negative margin untuk maximize space */
        }

        #goodsReceiveModal .table-goods-receive {
            margin-bottom: 0;
        }

        /* Optimize column widths */
        #goodsReceiveModal .table-goods-receive th,
        #goodsReceiveModal .table-goods-receive td {
            padding: 0.5rem 0.25rem;
            /* Reduce horizontal padding */
            white-space: nowrap;
            /* Prevent text wrapping di header */
        }

        /* Material name column bisa wrap */
        #goodsReceiveModal .table-goods-receive td:nth-child(3) {
            white-space: normal;
            min-width: 150px;
            /* Minimum width untuk material name */
        }

        /* Custom styling untuk destination select */
        .destination-select {
            font-weight: 600;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
            min-width: 120px;
            /* Minimum width untuk select dropdown */
        }

        .destination-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .destination-changed {
            border-color: #ffc107 !important;
            background-color: #fff3cd;
        }

        .destination-info {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
            white-space: normal;
            /* Allow text wrapping */
        }

        .table-goods-receive thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 0.875rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-goods-receive tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Improve input field width di table */
        #goodsReceiveModal .received-qty {
            min-width: 100px;
        }

        #goodsReceiveModal .form-select-sm {
            font-size: 0.875rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            // Toggle detail row
            $('.toggle-detail').on('click', function() {
                var id = $(this).data('id');
                $('#detail-' + id).toggle();
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            });

            // Handle goods receive modal
            $('.btn-goods-receive').on('click', function(e) {
                e.preventDefault();
                var shippingId = $(this).data('shipping-id');
                $('#shipping_id').val(shippingId);

                // Load shipping details via AJAX
                $.get('/shipping-management/detail/' + shippingId, function(response) {
                    // Fill header info
                    $('#waybill').val(response.shipping.international_waybill_no);
                    $('#freight_company').val(response.shipping.freight_company);
                    $('#freight_price').val(response.shipping.freight_price);
                    $('#arrived_date').val('');

                    // Generate detail list dengan destination select
                    let detailHtml = '<div class="table-responsive">';
                    detailHtml +=
                        '<table class="table table-bordered table-hover table-sm table-goods-receive">';
                    detailHtml += '<thead>';
                    detailHtml += '<tr>';
                    detailHtml += '<th width="12%">Purchase Type</th>';
                    detailHtml += '<th width="12%">Project</th>';
                    detailHtml += '<th width="18%">Material</th>';
                    detailHtml += '<th width="12%">Supplier</th>';
                    detailHtml += '<th width="10%">Unit Price</th>';
                    detailHtml += '<th width="10%">Purchased Qty</th>';
                    detailHtml +=
                        '<th width="13%">Received Qty <span class="text-danger">*</span></th>';
                    detailHtml += '<th width="13%">';
                    detailHtml += 'Destination <span class="text-danger">*</span>';
                    detailHtml += '<i class="bi bi-info-circle text-muted ms-1" ';
                    detailHtml += 'data-bs-toggle="tooltip" ';
                    detailHtml += 'data-bs-placement="top" ';
                    detailHtml += 'data-bs-html="true" ';
                    detailHtml +=
                        'title="<div class=\'text-start\'><strong>Change Allowed</strong><br>';
                    detailHtml +=
                        '<small>Destination can be changed if the item is redirected to another location. ';
                    detailHtml +=
                        'All changes will be recorded in the audit trail.</small></div>" ';
                    detailHtml += 'style="font-size: 0.75rem; cursor: help;"></i>';
                    detailHtml += '</th>';

                    detailHtml += '</tr>';
                    detailHtml += '</thead>';
                    detailHtml += '<tbody>';

                    response.details.forEach((detail, index) => {
                        // Get destination dari shipping detail
                        const currentDestination = detail.destination || 'BT';
                        const destinationOptions = [{
                                value: 'SG',
                                label: 'Singapore (SG)',
                                color: 'success'
                            },
                            {
                                value: 'BT',
                                label: 'Batam (BT)',
                                color: 'info'
                            },
                            {
                                value: 'CN',
                                label: 'China (CN)',
                                color: 'danger'
                            },
                            {
                                value: 'MY',
                                label: 'Malaysia (MY)',
                                color: 'warning'
                            },
                            {
                                value: 'Other',
                                label: 'Other',
                                color: 'secondary'
                            }
                        ];

                        detailHtml += `<tr>
                            <td>${detail.purchase_type}</td>
                            <td>${detail.project_name}</td>
                            <td>${detail.material_name}</td>
                            <td>${detail.supplier_name}</td>
                            <td>${detail.unit_price}</td>
                            <td>${detail.purchased_qty} ${detail.unit || ''}</td>
                            <td>
                                <input type="text"
                                    class="form-control form-control-sm received-qty"
                                    name="received_qty[${index}]"
                                    placeholder="Enter quantity"
                                    required>
                            </td>
                            <td>
                                <select class="form-select form-select-sm destination-select"
                                        name="destination[${index}]"
                                        data-original="${currentDestination}"
                                        required>`;

                        destinationOptions.forEach(opt => {
                            const selected = opt.value === currentDestination ?
                                'selected' : '';
                            detailHtml +=
                                `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                        });

                        detailHtml += `</select>
                                <small class="destination-info">
                                    Original: <strong>${destinationOptions.find(o => o.value === currentDestination)?.label || currentDestination}</strong>
                                </small>
                            </td>
                        </tr>`;
                    });

                    detailHtml += '</tbody></table></div>';

                    $('#detail-list').html(detailHtml);

                    // Initialize tooltips setelah HTML di-inject
                    setTimeout(function() {
                        $('[data-bs-toggle="tooltip"]').tooltip({
                            boundary: 'window',
                            html: true
                        });
                    }, 100);

                    // Track destination changes
                    $('.destination-select').on('change', function() {
                        const original = $(this).data('original');
                        const current = $(this).val();

                        if (current !== original) {
                            $(this).addClass('destination-changed');
                            $(this).closest('td').find('.destination-info').html(
                                `Original: <strong>${original}</strong> → Changed to: <strong class="text-warning">${current}</strong>`
                            );
                        } else {
                            $(this).removeClass('destination-changed');
                            $(this).closest('td').find('.destination-info').html(
                                `Original: <strong>${original}</strong>`
                            );
                        }
                    });

                    // Show modal
                    var modal = new bootstrap.Modal(document.getElementById('goodsReceiveModal'));
                    modal.show();
                });
            });

            // Destroy tooltips saat modal ditutup (prevent memory leak)
            $('#goodsReceiveModal').on('hidden.bs.modal', function() {
                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
            });

            // Handle form submit
            $('#goods-receive-form').on('submit', function(e) {
                e.preventDefault();

                // Validasi: Cek apakah ada received_qty yang kosong
                let hasEmptyQty = false;
                $('.received-qty').each(function() {
                    if (!$(this).val()) {
                        hasEmptyQty = true;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // Validasi: Cek apakah ada destination yang kosong
                let hasEmptyDestination = false;
                $('.destination-select').each(function() {
                    if (!$(this).val()) {
                        hasEmptyDestination = true;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (hasEmptyQty) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please fill in all Received Qty fields'
                    });
                    return;
                }

                if (hasEmptyDestination) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please select destination for all items'
                    });
                    return;
                }

                // ⭐ TAMBAHAN: Confirm jika ada destination yang berubah
                const changedDestinations = $('.destination-changed').length;
                if (changedDestinations > 0) {
                    Swal.fire({
                        icon: 'question',
                        title: 'Destination Changed',
                        html: `<strong>${changedDestinations}</strong> item(s) have destination changes.<br>Are you sure to proceed?`,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Proceed',
                        cancelButtonText: 'Review Again'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitGoodsReceive();
                        }
                    });
                } else {
                    submitGoodsReceive();
                }
            });

            // Function untuk submit goods receive
            function submitGoodsReceive() {
                const formData = $('#goods-receive-form').serialize();
                const submitBtn = $('#goods-receive-form button[type="submit"]');
                const originalBtnHtml = submitBtn.html();

                // Disable button & show loading
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

                $.ajax({
                    url: '{{ route('goods-receive.store') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#goodsReceiveModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Goods Receive created successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = "{{ route('goods-receive.index') }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error saving data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });

                        // Re-enable button
                        submitBtn.prop('disabled', false).html(originalBtnHtml);
                    }
                });
            }
        });
    </script>
@endpush
