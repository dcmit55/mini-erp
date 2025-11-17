@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h4>Shipping Management</h4>
        <div class="card">
            <div class="card-body">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Int. Waybill No</th>
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
                                                            <span class="text-muted">Project Name:</span>
                                                            <div>
                                                                {{ $detail->preShipping->purchaseRequest->project->name ?? '-' }}
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
                                                        <div class="col-md-2">
                                                            <span class="text-muted">Unit Price:</span>
                                                            <div>
                                                                {{ number_format($detail->preShipping->purchaseRequest->price_per_unit, 2) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-1">
                                                        <div class="col-md-2 small text-muted">
                                                            Domestic WBL:<br>
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
                                                            International Cost:<br>
                                                            <span class="fw-semibold">
                                                                {{ number_format($detail->int_cost ?? 0, 2) }}
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

    <!-- Modal Input Goods Receive -->
    <div class="modal fade" id="goodsReceiveModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Goods Receive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="goods-receive-form">
                    @csrf
                    <input type="hidden" name="shipping_id" id="shipping_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Int WBL Number</label>
                                <input type="text" class="form-control" id="waybill" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Freight Comp</label>
                                <input type="text" class="form-control" id="freight_company" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Freight Price</label>
                                <input type="text" class="form-control" id="freight_price" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Arrived Date</label>
                                <input type="datetime-local" class="form-control" id="arrived_date" name="arrived_date"
                                    required>
                            </div>
                        </div>
                        <div id="detail-list" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

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

                    // Generate detail list
                    let detailHtml = '<table class="table table-bordered">';
                    detailHtml += '<thead><tr>';
                    detailHtml += '<th>Purchase Type</th><th>Project</th><th>Material</th>';
                    detailHtml += '<th>Supplier</th><th>Unit Price</th><th>Domestic WBL</th>';
                    detailHtml += '<th>Purchased Qty</th><th>Received Qty</th>';
                    detailHtml += '</tr></thead><tbody>';

                    response.details.forEach((detail, index) => {
                        detailHtml += `<tr>
                        <td>${detail.purchase_type}</td>
                        <td>${detail.project_name}</td>
                        <td>${detail.material_name}</td>
                        <td>${detail.supplier_name}</td>
                        <td>${detail.unit_price}</td>
                        <td>${detail.domestic_waybill_no}</td>
                        <td>${detail.purchased_qty}</td>
                        <td><input type="text" class="form-control received-qty"
                            name="received_qty[${index}]" required></td>
                    </tr>`;
                    });

                    detailHtml += '</tbody></table>';
                    $('#detail-list').html(detailHtml);

                    // Show modal
                    var modal = new bootstrap.Modal(document.getElementById('goodsReceiveModal'));
                    modal.show();
                });
            });

            // Handle form submit
            $('#goods-receive-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: '{{ route('goods-receive.store') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#goodsReceiveModal').modal('hide');
                            window.location.href = "{{ route('goods-receive.index') }}";
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error saving data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        alert(msg);
                    }
                });
            });
        });
    </script>
@endpush
