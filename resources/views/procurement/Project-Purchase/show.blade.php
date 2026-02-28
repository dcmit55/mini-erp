{{-- resources/views/Procurement/Project-Purchase/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Purchase Order Details')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('project-purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <div class="mt-2">
                        <h5 class="text-dark mb-1">PO: {{ $purchase->po_number }}</h5>
                        <p class="text-muted small mb-0">
                            Purchase Order Details | 
                            <span class="fw-medium">{{ $poItems->count() }} item(s)</span>
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    @php
                        // Status keseluruhan PO
                        $allApproved = $poItems->every(function($item) { return $item->status == 'approved'; });
                        $allRejected = $poItems->every(function($item) { return $item->status == 'rejected'; });
                        $anyPending = $poItems->contains(function($item) { return $item->status == 'pending'; });
                        
                        if ($allApproved) {
                            $statusBadge = 'bg-success';
                            $statusText = 'Approved';
                        } elseif ($allRejected) {
                            $statusBadge = 'bg-danger';
                            $statusText = 'Rejected';
                        } elseif ($anyPending) {
                            $statusBadge = 'bg-warning text-dark';
                            $statusText = 'Pending';
                        } else {
                            $statusBadge = 'bg-secondary';
                            $statusText = 'Mixed';
                        }
                    @endphp
                    <span class="badge rounded-pill px-3 py-1 {{ $statusBadge }} small fw-medium">
                        <i class="fas fa-circle me-1" style="font-size: 0.5rem"></i>
                        {{ $statusText }}
                    </span>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $purchase->date->format('M d, Y') }}
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 d-flex align-items-center mb-3 p-2">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Main Card -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    
                    <!-- Section 1: Header Info -->
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

                    <!-- Section 2: Project Details -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-briefcase me-2 text-primary"></i>Project Details
                            </h6>
                        </div>
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

                    <!-- ===== SECTION 3: ITEMS TABLE ===== -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-box me-2 text-primary"></i>Items List ({{ $poItems->count() }} items)
                            </h6>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">#</th>
                                        <th>Item Name</th>
                                        <th>Type</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Receipt</th>
                                        @if($purchase->status == 'approved')
                                        <th class="text-center">Action</th>
                                        @endif
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
                                            @if($item->material && $item->material->code)
                                                <small class="text-muted">Code: {{ $item->material->code }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ ucfirst(str_replace('_', ' ', $item->purchase_type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($item->quantity) }}</td>
                                        <td class="text-end">Rp {{ number_format($item->unit_price, 0) }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($item->total_price, 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $item->status_badge_class }} px-2 py-1">
                                                {{ $item->status_text }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($item->item_status == 'matched')
                                                <span class="badge bg-success px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i>Received
                                                </span>
                                                @if($item->received_at)
                                                    <small class="d-block text-muted">
                                                        {{ $item->received_at->format('d/m/Y') }}
                                                    </small>
                                                @endif
                                            @elseif($item->item_status == 'not_matched')
                                                <span class="badge bg-danger px-2 py-1">
                                                    <i class="fas fa-exclamation-circle me-1"></i>Not Matched
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark px-2 py-1">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            @endif
                                        </td>
                                        @if($purchase->status == 'approved')
                                        <td class="text-center">
                                            @if(in_array($item->item_status, ['pending', 'pending_check']) && auth()->user() && in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory', 'admin_logistic', 'procurement']))
                                                <form action="{{ route('project-purchases.mark-as-received', $item->id) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Mark this item as received and add to inventory?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-box-open me-1"></i>Receive
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end">Subtotal:</th>
                                        <th class="text-end">Rp {{ number_format($poItems->sum('total_price'), 0) }}</th>
                                        <th colspan="{{ $purchase->status == 'approved' ? 3 : 2 }}"></th>
                                    </tr>
                                    @if($purchase->freight > 0)
                                    <tr>
                                        <th colspan="5" class="text-end">Freight Cost:</th>
                                        <th class="text-end">Rp {{ number_format($purchase->freight, 0) }}</th>
                                        <th colspan="{{ $purchase->status == 'approved' ? 3 : 2 }}"></th>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th colspan="5" class="text-end fw-bold">GRAND TOTAL:</th>
                                        <th class="text-end fw-bold text-primary">Rp {{ number_format($poTotal, 0) }}</th>
                                        <th colspan="{{ $purchase->status == 'approved' ? 3 : 2 }}"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Summary Cards -->
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <div class="info-label">Total Items</div>
                                    <div class="info-value-large">{{ $poItems->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <div class="info-label">Received Items</div>
                                    <div class="info-value-large">{{ $poItems->where('item_status', 'matched')->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <div class="info-label">Pending Items</div>
                                    <div class="info-value-large">{{ $poItems->whereIn('item_status', ['pending', 'pending_check'])->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Supplier Information -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-truck me-2 text-primary"></i>Supplier Information
                            </h6>
                        </div>
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
                                            <span class="badge bg-success ms-2">Active</span>
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                            <span class="badge bg-warning ms-2">Pending</span>
                                        @endif
                                    </div>
                                    <div class="text-muted smaller">
                                        <i class="fas fa-clock me-1"></i>
                                        Created: {{ $purchase->created_at->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Approval Timeline -->
                    @if($purchase->approved_at)
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-check-circle me-2 text-primary"></i>Approval Information
                            </h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Approved At</div>
                                    <div class="info-value">{{ $purchase->approved_at->format('M d, Y h:i A') }}</div>
                                    <div class="text-muted smaller">
                                        By: {{ $purchase->approver->username ?? 'Finance' }}
                                    </div>
                                </div>
                            </div>
                            
                            @if($purchase->finance_notes)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Finance Notes</div>
                                    <div class="info-value small">{{ $purchase->finance_notes }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Section 6: Notes -->
                    @if($purchase->note)
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Notes
                            </h6>
                        </div>
                        <div class="notes-container">
                            <div class="border rounded-3 p-3 bg-light">
                                <p class="mb-0">{{ $purchase->note }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Section 7: Revision History -->
                    @if($revisions->count() > 1)
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-history me-2 text-primary"></i>Revision History
                            </h6>
                        </div>
                        <div class="timeline">
                            @foreach($revisions as $revision)
                            <div class="timeline-item {{ $revision->id == $purchase->id ? 'active' : '' }}">
                                <div class="timeline-marker {{ $revision->id == $purchase->id ? 'active' : '' }}"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        Revision {{ $loop->iteration }}
                                        @if($revision->id == $purchase->id)
                                            <span class="badge bg-primary ms-2">Current</span>
                                        @endif
                                    </div>
                                    <div class="timeline-time">
                                        {{ $revision->created_at->format('M d, Y h:i A') }}
                                    </div>
                                    <div class="timeline-status mt-1">
                                        <span class="badge {{ $revision->status_badge_class }} me-1">{{ $revision->status_text }}</span>
                                        <span class="badge {{ $revision->item_status_badge_class }}">{{ $revision->item_status_text }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="pt-4 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('project-purchases.index') }}" 
                                   class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back to List
                                </a>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('project-purchases.print', $purchase->id) }}" 
                                   class="btn btn-outline-primary rounded-2 px-3 btn-sm" target="_blank">
                                    <i class="fas fa-print me-1"></i>Print
                                </a>
                                
                                @if($purchase->status == 'pending')
                                    <a href="{{ route('project-purchases.edit', $purchase->id) }}" 
                                       class="btn btn-primary rounded-2 px-3 btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    
                                    @if(auth()->user() && auth()->user()->role == 'finance')
                                    <button type="button" 
                                            class="btn btn-success rounded-2 px-3 btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#approveModal">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                    
                                    <button type="button" 
                                            class="btn btn-danger rounded-2 px-3 btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rejectModal">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
@if($purchase->status == 'pending' && auth()->user() && auth()->user()->role == 'finance')
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('project-purchases.approve', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Approve Purchase Order</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to approve all {{ $poItems->count() }} items in this PO:</p>
                    
                    <div class="bg-light p-2 rounded mb-3">
                        <ul class="small mb-0" style="max-height: 200px; overflow-y: auto;">
                            @foreach($poItems as $item)
                                <li>
                                    {{ $item->purchase_type == 'restock' ? $item->material->name : $item->new_item_name }}
                                    ({{ number_format($item->quantity) }} pcs) - Rp {{ number_format($item->total_price, 0) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    @if(!$purchase->is_offline_order)
                    <div class="mb-2">
                        <label class="form-label small">Resi Number</label>
                        <input type="text" name="resi_number" class="form-control form-control-sm">
                    </div>
                    @endif
                    
                    <div class="mb-2">
                        <label class="form-label small">Notes</label>
                        <textarea name="finance_notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Approve All Items</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('project-purchases.reject', $purchase->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Reject Purchase Order</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to reject all {{ $poItems->count() }} items in this PO.</p>
                    
                    <div class="mb-2">
                        <label class="form-label small">Reason <span class="text-danger">*</span></label>
                        <textarea name="finance_notes" class="form-control form-control-sm" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Reject All Items</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    /* Main Structure */
    .card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }

    /* Section Headers */
    .section-header {
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }

    .section-header h6 {
        color: #334155;
        font-weight: 600;
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

    .info-value-large {
        font-size: 1.1rem;
        color: #4f46e5;
        font-weight: 600;
        margin: 4px 0;
    }

    /* Info Cards */
    .info-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        background: #ffffff;
        height: 100%;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 24px;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }

    .timeline-item.active .timeline-title {
        font-weight: 600;
        color: #4f46e5;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .timeline-marker {
        position: absolute;
        left: -24px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #cbd5e1;
        border: 2px solid #ffffff;
    }

    .timeline-marker.active {
        background: #4f46e5;
    }

    .timeline-content {
        padding-left: 8px;
    }

    .timeline-title {
        font-size: 0.9rem;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 2px;
    }

    .timeline-time {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .timeline-status {
        font-size: 0.7rem;
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
    }

    /* Notes Container */
    .notes-container {
        min-height: 80px;
    }

    /* Badge Styling */
    .badge {
        font-size: 0.7rem;
        font-weight: 500;
        padding: 0.2rem 0.5rem;
    }

    .badge.bg-warning { background-color: #f59e0b !important; color: white !important; }
    .badge.bg-success { background-color: #10b981 !important; color: white !important; }
    .badge.bg-danger { background-color: #ef4444 !important; color: white !important; }
    .badge.bg-secondary { background-color: #6c757d !important; color: white !important; }
    .badge.bg-info { background-color: #0dcaf0 !important; color: white !important; }

    /* Button Styling */
    .btn {
        font-size: 0.85rem;
        font-weight: 500;
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

    /* Text Utilities */
    .small {
        font-size: 0.85rem;
    }

    .smaller {
        font-size: 0.75rem;
    }

    .text-primary {
        color: #4f46e5 !important;
    }

    .text-muted {
        color: #64748b !important;
    }

    .fw-semibold {
        font-weight: 600;
    }

    /* Border Radius */
    .rounded-2 {
        border-radius: 8px;
    }

    .rounded-3 {
        border-radius: 12px;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 12px;
        border: none;
    }

    .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
    }

    .modal-body {
        padding: 1.25rem;
    }

    .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
    }
</style>
@endsection