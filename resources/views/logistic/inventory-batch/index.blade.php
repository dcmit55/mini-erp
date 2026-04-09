@extends('layouts.app')

@push('styles')
    <style>
        .stat-card {
            border: none;
            border-radius: 0.75rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .08) !important;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .progress {
            height: 6px;
            border-radius: 3px;
        }

        #batchTable td {
            vertical-align: middle;
        }

        .filter-bar {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
    </style>
@endpush

@section('title', 'Inventory Batches')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold">
                    <i class="bi bi-layers me-2 text-primary"></i>Inventory Batches
                </h4>
                <small class="text-muted">Stok material berdasarkan batch penerimaan</small>
            </div>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Inventory Stock
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-stack"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-primary">{{ number_format($totalBatches) }}</div>
                            <div class="text-muted small">Total Batches</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($activeBatches) }}</div>
                            <div class="text-muted small">Active Batches</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-archive"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-secondary">{{ number_format($depletedBatches) }}</div>
                            <div class="text-muted small">Depleted Batches</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div id="batchStockValueAmount" class="fs-5 fw-bold text-warning">
                                <span class="spinner-border spinner-border-sm text-warning" role="status"></span>
                            </div>
                            <div class="text-muted small d-flex align-items-center gap-1">
                                Total Stock Value (IDR)
                                <button type="button" id="btnRefreshBatchStockValue"
                                    class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Refresh">
                                    <i class="fas fa-sync-alt" style="font-size:.7rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-bar mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Material</label>
                    <select id="filter_inventory" class="form-select form-select-sm select2">
                        <option value="">All Materials</option>
                        @foreach ($inventories as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Category</label>
                    <select id="filter_category" class="form-select form-select-sm select2-category">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Source</label>
                    <select id="filter_source" class="form-select form-select-sm">
                        <option value="">All Sources</option>
                        @foreach ($sourceTypes as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="depleted">Depleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Date From</label>
                    <input type="date" id="filter_date_from" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Date To</label>
                    <input type="date" id="filter_date_to" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button id="btn_reset" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="batchTable" class="table table-hover table-sm mb-0 align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Batch Number</th>
                                <th>Material</th>
                                <th>Category</th>
                                <th>Received Date</th>
                                <th>Source</th>
                                <th>Qty In</th>
                                <th>Qty Remaining</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
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

            $('#filter_inventory').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'All Materials',
                width: '100%'
            });

            $('#filter_category').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: 'All Categories',
                width: '100%'
            });

            var table = $('#batchTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('inventory-batch.index') }}',
                    data: function(d) {
                        d.inventory_id = $('#filter_inventory').val();
                        d.category_id = $('#filter_category').val();
                        d.source_type = $('#filter_source').val();
                        d.status = $('#filter_status').val();
                        d.date_from = $('#filter_date_from').val();
                        d.date_to = $('#filter_date_to').val();
                    }
                },
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
                        data: 'material_name',
                        name: 'inventory.name'
                    },
                    {
                        data: 'category_name',
                        name: 'inventory.category.name',
                        orderable: false
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
                    [3, 'desc']
                ],
                pageLength: 25,
                language: {
                    processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
                    emptyTable: 'No batch data found.',
                    zeroRecords: 'No matching batches found.',
                },
            });

            // Re-draw on filter change
            $('#filter_inventory, #filter_category, #filter_source, #filter_status').on('change', function() {
                table.draw();
                loadBatchStockValue();
            });
            $('#filter_date_from, #filter_date_to').on('change', function() {
                table.draw();
            });

            // Reset filters
            $('#btn_reset').on('click', function() {
                $('#filter_inventory, #filter_category').val(null).trigger('change');
                $('#filter_source, #filter_status').val('');
                $('#filter_date_from, #filter_date_to').val('');
                table.draw();
                loadBatchStockValue();
            });

            // ── Batch Stock Value Widget ──────────────────────────────────────
            function loadBatchStockValue() {
                var categoryId = $('#filter_category').val();
                var params = categoryId ? {
                    category_id: categoryId
                } : {};
                $('#batchStockValueAmount').html(
                    '<span class="spinner-border spinner-border-sm text-warning" role="status"></span>'
                );
                $.get('{{ route('inventory-batch.stock-value') }}', params, function(res) {
                    $('#batchStockValueAmount').text(res.total_idr_formatted);
                }).fail(function() {
                    $('#batchStockValueAmount').text('—');
                });
            }

            // Load on page open
            loadBatchStockValue();

            // Refresh button
            $('#btnRefreshBatchStockValue').on('click', function() {
                loadBatchStockValue();
            });
            // ─────────────────────────────────────────────────────────────────

        });
    </script>
@endpush
