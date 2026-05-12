@extends('layouts.app')

@section('title', 'Deletion Request Detail')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('purchase-approvals.deletion-requests') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back to Deletion Requests
                    </a>
                    <div class="mt-2">
                        <h5 class="text-dark mb-1">Purchase: {{ $purchase->po_number }}</h5>
                        <p class="text-muted small mb-0">
                            Deletion Request Detail |
                            <span class="fw-medium">{{ $poItems->count() }} item(s)</span>
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-danger px-3 py-2">Deletion Requested</span>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $purchase->date->format('M d, Y') }}
                    </div>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Main Card -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                <div class="card-body p-4">

                    <!-- Header Info -->
                    <div class="row mb-4 pb-3 border-bottom">
                        <div class="col-md-3">
                            <div class="info-item">
                                <div class="info-label">PIC</div>
                                <div class="info-value">{{ $purchase->pic->username ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value">{{ $purchase->department->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <div class="info-label">Project Type</div>
                                <div class="info-value">{{ ucfirst($purchase->project_type) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <div class="info-label">Order Type</div>
                                <div class="info-value">{{ $purchase->is_offline_order ? 'Offline' : 'Online' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deletion Reason -->
                    <div class="mb-4">
                        <h6 class="section-header mb-3">
                            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Alasan Penghapusan
                        </h6>
                        <div class="border border-danger border-opacity-25 rounded-3 p-3 bg-danger bg-opacity-10">
                            <p class="mb-1">{{ $purchase->deletion_reason }}</p>
                            <div class="text-muted small mt-2">
                                Diminta pada: {{ $purchase->deletion_requested_at ? \Carbon\Carbon::parse($purchase->deletion_requested_at)->format('d/m/Y H:i') : '-' }}
                            </div>
                        </div>
                    </div>

                    <!-- Project Details -->
                    <div class="mb-4">
                        <h6 class="section-header mb-3">
                            <i class="fas fa-briefcase me-2 text-primary"></i>Project Details
                        </h6>
                        <div class="border rounded-3 p-3 bg-light">
                            @if($purchase->project_type == 'client')
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Project</div>
                                        <div class="info-value">{{ $purchase->project->name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Job Order</div>
                                        <div class="info-value">{{ $purchase->jobOrder->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Internal Project</div>
                                        <div class="info-value">{{ $purchase->internalProject->project ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Job</div>
                                        <div class="info-value">{{ $purchase->internalProject->job ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Department</div>
                                        <div class="info-value">{{ $purchase->internalProject->department ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-4">
                        <h6 class="section-header mb-3">
                            <i class="fas fa-box me-2 text-primary"></i>Items List ({{ $poItems->count() }} items)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">#</th>
                                        <th>Item Name</th>
                                        <th>Type</th>
                                        <th class="text-end">Quantity</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($poItems as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($item->purchase_type == 'restock')
                                                {{ $item->material->name ?? 'Unknown' }}
                                            @else
                                                {{ $item->new_item_name }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ ucfirst(str_replace('_', ' ', $item->purchase_type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($item->quantity) }}</td>
                                        <td>{{ $item->category->name ?? '-' }}</td>
                                        <td>{{ $item->unit->name ?? '-' }}</td>
                                        <td class="text-end">Rp {{ number_format($item->unit_price, 0) }}</td>
                                        <td class="text-end">Rp {{ number_format($item->total_price, 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="7" class="text-end">Subtotal:</th>
                                        <th class="text-end">Rp {{ number_format($poItems->sum('total_price'), 0) }}</th>
                                    </tr>
                                    @if($purchase->freight > 0)
                                    <tr>
                                        <th colspan="7" class="text-end">Freight Cost:</th>
                                        <th class="text-end">Rp {{ number_format($purchase->freight, 0) }}</th>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th colspan="7" class="text-end fw-bold">GRAND TOTAL:</th>
                                        <th class="text-end fw-bold text-primary">Rp {{ number_format($poItems->sum('invoice_total'), 0) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Supplier Information -->
                    <div class="mb-4">
                        <h6 class="section-header mb-3">
                            <i class="fas fa-truck me-2 text-primary"></i>Supplier Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Supplier Name</div>
                                    <div class="info-value mb-2">{{ $purchase->supplier->name ?? 'N/A' }}</div>
                                    @if($purchase->supplier && $purchase->supplier->address)
                                        <div class="text-muted small">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $purchase->supplier->address }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Finance Notes</div>
                                    <div class="info-value mb-2">{{ $purchase->finance_notes ?: '-' }}</div>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        Approved at: {{ $purchase->approved_at ? $purchase->approved_at->format('M d, Y h:i A') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($purchase->note)
                    <div class="mb-4">
                        <h6 class="section-header mb-3">
                            <i class="fas fa-sticky-note me-2 text-primary"></i>Notes
                        </h6>
                        <div class="border rounded-3 p-3 bg-light">
                            <p class="mb-0">{{ $purchase->note }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="pt-4 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('purchase-approvals.deletion-requests') }}"
                               class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                            <div class="d-flex gap-2">
                                <form action="{{ route('purchase-approvals.approve-deletion', $purchase->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Setujui penghapusan {{ $purchase->po_number }}? Semua item akan dihapus permanen.')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm rounded-2 px-3">
                                        <i class="fas fa-trash me-1"></i>Setujui & Hapus
                                    </button>
                                </form>
                                <form action="{{ route('purchase-approvals.reject-deletion', $purchase->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                                        <i class="fas fa-undo me-1"></i>Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .section-header {
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 1rem;
        font-weight: 600;
        color: #334155;
    }
    .info-item { margin-bottom: 8px; }
    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .info-value {
        font-size: 0.9rem;
        color: #1f2937;
        font-weight: 500;
    }
    .info-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        background: #ffffff;
        height: 100%;
    }
    .table { font-size: 0.85rem; }
    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background-color: #f8fafc;
    }
</style>
@endsection
