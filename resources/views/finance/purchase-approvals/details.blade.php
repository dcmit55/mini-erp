{{-- resources/views/finance/purchase-approvals/details.blade.php --}}
@extends('layouts.app')

@section('title', 'Purchase Approval Details')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('purchase-approvals.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back to Approvals
                    </a>
                    <div class="mt-2">
                        <h5 class="text-dark mb-1">PO: {{ $purchase->po_number }}</h5>
                        <p class="text-muted small mb-0">
                            Purchase Approval Details | 
                            <span class="fw-medium">{{ $poItems->count() }} item(s)</span>
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning px-3 py-2">Pending Approval</span>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $purchase->date->format('M d, Y') }}
                    </div>
                </div>
            </div>

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
                                            <div class="fw-medium">
                                                @if($item->purchase_type == 'restock')
                                                    {{ $item->material->name ?? 'Unknown' }}
                                                @else
                                                    {{ $item->new_item_name }}
                                                @endif
                                            </div>
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
                                        <td class="text-end fw-semibold">Rp {{ number_format($item->total_price, 0) }}</td>
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
                                    <div class="info-label">Tracking Information</div>
                                    <div class="info-value mb-2">
                                        @if($purchase->resi_number)
                                            <span class="text-dark">{{ $purchase->resi_number }}</span>
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        Created: {{ $purchase->created_at->format('M d, Y h:i A') }}
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
                            <div>
                                <a href="{{ route('purchase-approvals.index') }}" 
                                   class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Approvals
                                </a>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm rounded-2 px-3 approve-with-notes"
                                        data-purchase-id="{{ $purchase->id }}"
                                        data-po-number="{{ $purchase->po_number }}"
                                        data-total-items="{{ $poItems->count() }}"
                                        data-total-amount="{{ $poItems->sum('invoice_total') }}"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#approveModal">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                                
                                <button type="button" 
                                        class="btn btn-outline-danger btn-sm rounded-2 px-3 reject-purchase"
                                        data-purchase-id="{{ $purchase->id }}"
                                        data-po-number="{{ $purchase->po_number }}"
                                        data-total-items="{{ $poItems->count() }}"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#rejectModal">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-approvals.approve', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" class="form-control" value="{{ $purchase->po_number }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Items</label>
                        <input type="text" class="form-control" value="{{ $poItems->count() }} item(s)" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="Rp {{ number_format($poItems->sum('invoice_total'), 0) }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Finance Notes (Optional)</label>
                        <textarea name="finance_notes" class="form-control" rows="3" 
                                  placeholder="Add notes for this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('purchase-approvals.reject', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" class="form-control" value="{{ $purchase->po_number }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Items</label>
                        <input type="text" class="form-control" value="{{ $poItems->count() }} item(s)" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="finance_notes" class="form-control" rows="4" 
                                  placeholder="Explain why this purchase is being rejected..." required></textarea>
                        <div class="form-text">This reason will be recorded and visible to the requester.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Section Headers */
    .section-header {
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 1rem;
        font-weight: 600;
        color: #334155;
    }

    .section-header i {
        color: #4f46e5;
    }

    /* Info Items */
    .info-item {
        margin-bottom: 8px;
    }

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

    /* Info Cards */
    .info-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        background: #ffffff;
        height: 100%;
    }

    /* Table */
    .table {
        font-size: 0.85rem;
    }

    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background-color: #f8fafc;
    }

    /* Badge */
    .badge {
        font-size: 0.7rem;
        font-weight: 500;
        padding: 0.2rem 0.5rem;
    }

    /* Form Controls */
    .form-control {
        border-color: #e2e8f0;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    /* Buttons */
    .btn {
        font-size: 0.85rem;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .btn-outline-primary {
        color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-outline-primary:hover {
        background-color: #4f46e5;
        color: white;
    }

    .btn-outline-danger {
        color: #ef4444;
        border-color: #ef4444;
    }

    .btn-outline-danger:hover {
        background-color: #ef4444;
        color: white;
    }

    .btn-outline-secondary {
        color: #64748b;
        border-color: #e2e8f0;
    }

    .btn-outline-secondary:hover {
        background-color: #f1f5f9;
        color: #334155;
    }

    /* Modal */
    .modal-content {
        border-radius: 10px;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
    }

    .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve with notes button - jika ada di halaman ini
    document.querySelectorAll('.approve-with-notes').forEach(button => {
        button.addEventListener('click', function() {
            // Data sudah di-set di modal, tidak perlu tambahan
        });
    });
    
    // Reject button - jika ada di halaman ini
    document.querySelectorAll('.reject-purchase').forEach(button => {
        button.addEventListener('click', function() {
            // Data sudah di-set di modal, tidak perlu tambahan
        });
    });
});
</script>
@endsection