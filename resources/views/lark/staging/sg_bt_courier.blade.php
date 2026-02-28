@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-left-info shadow h-100">
                    <div class="card-body">
                        <div class="text-info font-weight-bold text-uppercase mb-1">Total Records</div>
                        <div class="h3 mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success shadow h-100">
                    <div class="card-body">
                        <div class="text-success font-weight-bold text-uppercase mb-1">Synced Today</div>
                        <div class="h3 mb-0">{{ $stats['today'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-warning shadow h-100">
                    <div class="card-body">
                        <div class="text-warning font-weight-bold text-uppercase mb-1">Total Cost</div>
                        <div class="h3 mb-0">${{ number_format($stats['total_cost'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow rounded">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-database gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Lark SG-BT Courier Data</h2>
                    </div>
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="{{ route('lark.staging.bt-sg-courier') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left-right me-1"></i> BT-SG Courier
                        </a>
                        <button type="button" class="btn btn-success btn-sm" onclick="syncFromLark()">
                            <i class="bi bi-cloud-download me-1"></i> Sync from Lark
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="courierTable" class="table table-bordered table-hover" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Courier ID</th>
                                <th>Type Movement</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Transport Cost</th>
                                <th>Baggage Cost</th>
                                <th>GST Cost</th>
                                <th>Total Cost</th>
                                <th>QTY Total</th>
                                <th>Cost/Item</th>
                                <th>Synced At</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Confirmation Modal -->
    <div class="modal fade" id="syncModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sync from Lark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will fetch latest data from Lark Base and sync to this staging table.</p>
                    <p class="text-muted">Direction: <strong>SG → BT (Singapore to Batam)</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('lark.staging.sync-sg-bt-courier') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cloud-download me-1"></i> Sync Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#courierTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('lark.staging.sg-bt-courier') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'type_movement',
                        name: 'type_movement'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },

                    {
                        data: 'items_list',
                        name: 'items_list',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'transport_cost',
                        name: 'transport_cost'
                    },
                    {
                        data: 'baggage_cost',
                        name: 'baggage_cost'
                    },
                    {
                        data: 'gst_cost',
                        name: 'gst_cost'
                    },
                    {
                        data: 'total_cost',
                        name: 'total_cost',
                        orderable: false
                    },
                    {
                        data: 'qty_total',
                        name: 'qty_total'
                    },
                    {
                        data: 'cost_per_item',
                        name: 'cost_per_item'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    }
                ],
                order: [
                    [11, 'desc']
                ],
                pageLength: 25
            });
        });

        function syncFromLark() {
            $('#syncModal').modal('show');
        }
    </script>
@endpush
