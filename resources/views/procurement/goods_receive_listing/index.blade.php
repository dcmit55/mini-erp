@extends('layouts.app')
@section('content')
    <div class="container-fluid mt-4">
        <h4 class="mb-4">Goods Receive Modules</h4>
        <div class="card shadow-sm rounded-4">
            <div class="card-body">
                <table class="table align-middle table-hover table-sm">
                    <thead class="align-top table-light">
                        <tr>
                            <th>Int. Waybill</th>
                            <th>Arrived Date</th>
                            <th>Freight Comp</th>
                            <th>Int. Freight Cost</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody class="align-top">
                        @foreach ($goodsReceives as $gr)
                            <tr>
                                <td class="fw-semibold">{{ $gr->international_waybill_no }}</td>
                                <td>{{ \Carbon\Carbon::parse($gr->arrived_date)->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $gr->freight_company }}</td>
                                <td>{{ number_format($gr->freight_price, 2) }}</td>
                                <td>
                                    <div class="row g-2">
                                        @foreach ($gr->details as $d)
                                            <div class="col-12 mb-2">
                                                <div class="border rounded-3 p-3 bg-light">
                                                    <div class="row mb-2">
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Purchase Type</span>
                                                            <div class="fw-bold">{{ ucfirst($d->purchase_type) }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Project</span>
                                                            <div class="fw-bold">{{ $d->project_name }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Material Name</span>
                                                            <div class="fw-bold">{{ $d->material_name }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Supplier</span>
                                                            <div class="fw-bold">{{ $d->supplier_name }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Unit Price</span>
                                                            <div class="fw-bold">{{ number_format($d->unit_price, 2) }}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Domestic Waybill</span>
                                                            <div class="fw-bold">{{ $d->domestic_waybill_no }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Purchased Qty</span>
                                                            <div class="fw-bold">{{ $d->purchased_qty }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Received Qty</span>
                                                            <div class="fw-bold text-success">{{ $d->received_qty }}</div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <span class="text-muted small">Destination</span>
                                                            <div>
                                                                @php
                                                                    $destinationBadge = match ($d->destination) {
                                                                        'SG' => [
                                                                            'color' => 'success',
                                                                            'label' => 'Singapore',
                                                                        ],
                                                                        'BT' => ['color' => 'info', 'label' => 'Batam'],
                                                                        'CN' => [
                                                                            'color' => 'danger',
                                                                            'label' => 'China',
                                                                        ],
                                                                        'MY' => [
                                                                            'color' => 'warning',
                                                                            'label' => 'Malaysia',
                                                                        ],
                                                                        'Other' => [
                                                                            'color' => 'secondary',
                                                                            'label' => 'Other',
                                                                        ],
                                                                        default => [
                                                                            'color' => 'secondary',
                                                                            'label' => '-',
                                                                        ],
                                                                    };
                                                                @endphp
                                                                <span class="badge bg-{{ $destinationBadge['color'] }}">
                                                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                                                    {{ $destinationBadge['label'] }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .text-success {
            color: #198754 !important;
        }
    </style>
@endpush
