@extends('layouts.app')

@section('title', 'Stock Adjustment')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>Stock Adjustment</h4>
                <small class="text-muted">Kelola penyesuaian stok: Initial Stock dan Adjustment</small>
            </div>
            @can('logistic.stock-adjustment.create')
                <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>New Adjustment
                </a>
            @endcan
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filters --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-4 col-md-3">
                        <select id="filterType" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="initial_stock">Initial Stock</option>
                            <option value="adjustment">Adjustment</option>

                        </select>
                    </div>
                    <div class="col-sm-5 col-md-4">
                        <select id="filterInventory" class="form-select form-select-sm select2-filter">
                            <option value="">All Materials</option>
                            @foreach ($inventories as $inv)
                                <option value="{{ $inv->id }}">
                                    {{ $inv->name }}{{ $inv->material_code ? ' (' . $inv->material_code . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button id="btnResetFilter" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="stockAdjustmentsTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Material</th>
                                <th>Code</th>
                                <th>Batch</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Reason</th>
                                <th>By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Select2 for filter
            $('#filterInventory').select2({
                theme: 'bootstrap-5',
                placeholder: 'All Materials',
                allowClear: true,
                width: '100%'
            }).on('select2:open', function() {
                setTimeout(() => document.querySelector('.select2-search__field')?.focus(), 100);
            });

            var table = $('#stockAdjustmentsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock-adjustments.index') }}',
                    data: function(d) {
                        d.type = $('#filterType').val();
                        d.inventory_id = $('#filterInventory').val();
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
                        data: 'material_name',
                        name: 'material_name'
                    },
                    {
                        data: 'material_code',
                        name: 'material_code'
                    },
                    {
                        data: 'batch_number',
                        name: 'batch_number',
                        orderable: false
                    },
                    {
                        data: 'type_badge',
                        name: 'type',
                        orderable: false
                    },
                    {
                        data: 'qty_display',
                        name: 'qty',
                        orderable: false
                    },
                    {
                        data: 'price_display',
                        name: 'price',
                        orderable: false
                    },
                    {
                        data: 'reason',
                        name: 'reason',
                        orderable: false,
                        defaultContent: '<em class="text-muted">—</em>'
                    },
                    {
                        data: 'creator_name',
                        name: 'creator_name',
                        orderable: false
                    },
                    {
                        data: 'formatted_date',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '60px'
                    }
                ],
                order: [
                    [9, 'desc']
                ],
                pageLength: 25,
                language: {
                    processing: '<div class="d-flex align-items-center gap-2"><div class="spinner-border spinner-border-sm text-primary"></div><span>Loading...</span></div>'
                }
            });

            $('#filterType').on('change', function() {
                table.ajax.reload();
            });
            $('#filterInventory').on('change', function() {
                table.ajax.reload();
            });
            $('#btnResetFilter').on('click', function() {
                $('#filterType').val('');
                $('#filterInventory').val(null).trigger('change');
                table.ajax.reload();
            });
        });
    </script>
@endpush
