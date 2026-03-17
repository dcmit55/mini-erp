@extends('layouts.app')

@push('styles')
    <style>
        .stat-mini {
            border-radius: 0.6rem;
        }

        #batchByInvTable td {
            vertical-align: middle;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
        }
    </style>
@endpush

@section('title', 'Batches — ' . $inventory->name)

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold">
                    <i class="bi bi-layers me-2 text-primary"></i>
                    Batches: <span class="text-primary">{{ $inventory->name }}</span>
                </h4>
                <small class="text-muted">Unit: {{ $inventory->unit ?? '-' }}</small>
            </div>
            <a href="{{ route('inventory-batch.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>All Batches
            </a>
        </div>

        {{-- Inventory Info Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Material Name</div>
                        <div class="fw-semibold">{{ $inventory->name }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Category</div>
                        <div>{{ $inventory->category->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Unit</div>
                        <div>{{ $inventory->unit ?? '-' }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Current Stock</div>
                        <div class="fw-bold text-success fs-5">{{ number_format($inventory->quantity, 2) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Weighted Avg Price</div>
                        <div class="fw-bold">{{ number_format($inventory->price, 2, '.', ',') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Batch Table --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-table me-1"></i>Batch History</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="batchByInvTable" class="table table-hover table-sm mb-0" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Batch Number</th>
                                <th>Received Date</th>
                                <th>Source</th>
                                <th>Qty In</th>
                                <th>Qty Remaining</th>
                                <th>Consumption</th>
                                <th>Unit Price</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            var table = $('#batchByInvTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('inventory-batch.by-inventory', $inventory->id) }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '40px'
                    },
                    {
                        data: 'batch_number',
                        name: 'batch_number'
                    },
                    {
                        data: 'received_date_fmt',
                        name: 'received_date'
                    },
                    {
                        data: 'source_badge',
                        name: 'source_type',
                        orderable: false
                    },
                    {
                        data: 'qty_formatted',
                        name: 'qty',
                        className: 'text-end'
                    },
                    {
                        data: 'qty_remaining_formatted',
                        name: 'qty_remaining',
                        className: 'text-end'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            var pct = data.qty > 0 ?
                                Math.round(((data.qty - data.qty_remaining) / data.qty) * 100) :
                                100;
                            var color = pct >= 100 ? 'secondary' : (pct > 50 ? 'warning' :
                                'success');
                            return '<div class="progress" style="min-width:80px">' +
                                '<div class="progress-bar bg-' + color + '" style="width:' + pct +
                                '%"></div>' +
                                '</div>' +
                                '<small class="text-muted">' + pct + '% used</small>';
                        }
                    },
                    {
                        data: 'unit_price_formatted',
                        name: 'unit_price',
                        className: 'text-end'
                    },
                    {
                        data: 'total_value',
                        name: 'total_value',
                        orderable: false,
                        className: 'text-end fw-semibold'
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false,
                        className: 'text-center'
                    },
                ],
                order: [
                    [2, 'asc']
                ],
                pageLength: 25,
                language: {
                    processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
                    emptyTable: 'No batches found for this material.',
                },
            });

        });
    </script>
@endpush
