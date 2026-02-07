@extends('layouts.app')

@section('title', 'DCM Costing Details')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('dcm-costings.index') }}" 
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">DCM Costing Details</h5>
                    <p class="text-muted small mb-0">PO Number: {{ $costing->po_number }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dcm-costings.edit', ['costing' => $costing->uid]) }}" 
                       class="btn btn-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    @if($costing->purchase)
                    <a href="{{ route('project-purchases.show', $costing->purchase_id) }}" 
                       target="_blank"
                       class="btn btn-outline-info btn-sm rounded-2 px-3">
                        <i class="fas fa-external-link-alt me-1"></i>View Purchase
                    </a>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('success'))
                        <div class="alert alert-success border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Status Badges -->
                    <div class="mb-4">
                        <!-- Main Status -->
                        @if($costing->status == 'approved')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Approved</strong>
                                @if($costing->approved_at)
                                    <span class="ms-2">• {{ $costing->approved_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </span>
                        @elseif($costing->status == 'rejected')
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Rejected</strong>
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Pending Approval</strong>
                            </span>
                        @endif
                        
                        <!-- Item Status -->
                        @if($costing->item_status == 'received')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center ms-2">
                                <i class="fas fa-check me-2"></i>
                                <strong>Received</strong>
                            </span>
                        @elseif($costing->item_status == 'not_received')
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center ms-2">
                                <i class="fas fa-times me-2"></i>
                                <strong>Not Received</strong>
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-2 px-3 py-2 d-inline-flex align-items-center ms-2">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Pending Receipt</strong>
                            </span>
                        @endif
                    </div>

                    <!-- Information Sections -->
                    <div class="row g-3">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="bg-light border rounded-2 p-3 h-100">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>Basic Information
                                </h6>
                                
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-0">PO Number</label>
                                        <p class="mb-2 fw-medium">{{ $costing->po_number }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Date</label>
                                        <p class="mb-2 fw-medium">{{ $costing->date->format('d/m/Y') }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Department</label>
                                        <p class="mb-2 fw-medium">{{ $costing->department }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Project Type</label>
                                        <p class="mb-2 fw-medium">{{ ucfirst($costing->project_type) }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Project Name</label>
                                        <p class="mb-2 fw-medium">{{ $costing->project_name ?? 'N/A' }}</p>
                                    </div>
                                    
                                    @if($costing->job_order)
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-0">Job Order</label>
                                        <p class="mb-0 fw-medium">{{ $costing->job_order }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Supplier & Tracking -->
                        <div class="col-md-6">
                            <div class="bg-light border rounded-2 p-3 h-100">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-truck me-2 text-primary"></i>Supplier & Tracking
                                </h6>
                                
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-0">Supplier</label>
                                        <p class="mb-2 fw-medium">{{ $costing->supplier }}</p>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-0">PIC</label>
                                        <p class="mb-2 fw-medium">{{ $costing->pic }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Tracking Number</label>
                                        <p class="mb-2 fw-medium">{{ $costing->tracking_number ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-0">Resi Number</label>
                                        <p class="mb-2 fw-medium">{{ $costing->resi_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Item Details -->
                        <div class="col-12">
                            <div class="bg-light border rounded-2 p-3">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-box me-2 text-primary"></i>Item Details
                                </h6>
                                
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-0">Item Name</label>
                                        <p class="mb-2 fw-medium">{{ $costing->item_name }}</p>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Purchase Type</label>
                                        <p class="mb-2 fw-medium">{{ ucfirst($costing->purchase_type) }}</p>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Quantity</label>
                                        <p class="mb-2 fw-medium">{{ $costing->quantity }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Financial Details -->
                        <div class="col-12">
                            <div class="bg-light border rounded-2 p-3">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-money-bill-wave me-2 text-primary"></i>Financial Details
                                </h6>
                                
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Unit Price</label>
                                        <p class="mb-2 fw-medium text-primary">
                                            Rp {{ number_format($costing->unit_price, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Total Price</label>
                                        <p class="mb-2 fw-medium text-primary">
                                            Rp {{ number_format($costing->total_price, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Freight</label>
                                        <p class="mb-2 fw-medium text-primary">
                                            Rp {{ number_format($costing->freight, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-0">Invoice Total</label>
                                        <p class="mb-2 fw-medium text-primary">
                                            Rp {{ number_format($costing->invoice_total, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Finance Notes -->
                        @if($costing->finance_notes)
                        <div class="col-12">
                            <div class="bg-light border rounded-2 p-3">
                                <h6 class="text-dark fw-medium mb-2">
                                    <i class="fas fa-sticky-note me-2 text-primary"></i>Finance Notes
                                </h6>
                                <p class="mb-0">{{ $costing->finance_notes }}</p>
                            </div>
                        </div>
                        @endif
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
    }

    .btn {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    .badge {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .bg-light {
        background-color: #f8fafc !important;
    }

    .text-muted {
        color: #6b7280 !important;
    }

    .text-dark {
        color: #374151 !important;
    }

    .text-primary {
        color: #4f46e5 !important;
    }

    .rounded-2 {
        border-radius: 0.5rem !important;
    }

    .rounded-3 {
        border-radius: 0.75rem !important;
    }

    .border {
        border-color: #e2e8f0 !important;
    }

    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .border-opacity-25 {
        --bs-border-opacity: 0.25;
    }

    .fw-medium {
        font-weight: 500 !important;
    }

    p {
        margin-bottom: 0.25rem;
    }

    .small {
        font-size: 0.85rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy UID functionality
    document.querySelectorAll('.copy-uid').forEach(button => {
        button.addEventListener('click', function() {
            const uid = this.getAttribute('data-uid');
            navigator.clipboard.writeText(uid).then(() => {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success');
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-secondary');
                }, 2000);
            });
        });
    });
});
</script>
@endsection