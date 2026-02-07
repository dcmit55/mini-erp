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
                        <p class="text-muted small mb-0">Purchase Order Details</p>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge rounded-pill px-3 py-1 
                        {{ $purchase->status == 'approved' ? 'bg-success' : 
                           ($purchase->status == 'rejected' ? 'bg-danger' : 
                           'bg-warning text-dark') }} small fw-medium">
                        <i class="fas fa-circle me-1" style="font-size: 0.5rem"></i>
                        {{ ucfirst($purchase->status) }}
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
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">PIC</div>
                                <div class="info-value">{{ $purchase->pic->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value">{{ $purchase->department->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">Project</div>
                                <div class="info-value">{{ $purchase->project->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Job Order -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-briefcase me-2 text-primary"></i>Job Order
                            </h6>
                        </div>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="info-value">{{ $purchase->jobOrder->name ?? 'Not Assigned' }}</div>
                            <div class="text-muted small mt-1">Linked to this purchase order</div>
                        </div>
                    </div>

                    <!-- Section 3: Material Details -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-box me-2 text-primary"></i>Material Details
                            </h6>
                        </div>
                        <div class="row g-3">
                            <!-- Material Name -->
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Material</div>
                                    <div class="info-value mb-1">{{ $purchase->material->name ?? 'N/A' }}</div>
                                    @if($purchase->material && $purchase->material->code)
                                        <div class="text-muted smaller">
                                            <i class="fas fa-barcode me-1"></i>Code: {{ $purchase->material->code }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Quantity & Price -->
                            <div class="col-md-6">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="info-card text-center">
                                            <div class="info-label">Quantity</div>
                                            <div class="info-value-large">{{ number_format($purchase->quantity) }}</div>
                                            <div class="text-muted smaller">pcs</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-card text-center">
                                            <div class="info-label">Unit Price</div>
                                            <div class="info-value-large">${{ number_format($purchase->unit_price, 2) }}</div>
                                            <div class="text-muted smaller">per unit</div>
                                        </div>
                                    </div>
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
                                    @if($purchase->supplier->contact_person || $purchase->supplier->phone)
                                        <div class="contact-info">
                                            @if($purchase->supplier->contact_person)
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="fas fa-user text-muted me-2 small-icon"></i>
                                                    <span class="small">{{ $purchase->supplier->contact_person }}</span>
                                                </div>
                                            @endif
                                            @if($purchase->supplier->phone)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-phone text-muted me-2 small-icon"></i>
                                                    <span class="small">{{ $purchase->supplier->phone }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label">Tracking Information</div>
                                    <div class="info-value mb-2">
                                        @if($purchase->tracking_number)
                                            <span class="text-dark">{{ $purchase->tracking_number }}</span>
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

                    <!-- Section 5: Financial Summary -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-calculator me-2 text-primary"></i>Financial Summary
                            </h6>
                        </div>
                        <div class="financial-summary">
                            <div class="row g-3">
                                <!-- Material Total -->
                                <div class="col-md-4">
                                    <div class="financial-card">
                                        <div class="financial-label">Material Total</div>
                                        <div class="financial-amount">${{ number_format($purchase->total_price, 2) }}</div>
                                        <div class="financial-detail">
                                            {{ number_format($purchase->quantity) }} × ${{ number_format($purchase->unit_price, 2) }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Freight Cost -->
                                <div class="col-md-4">
                                    <div class="financial-card">
                                        <div class="financial-label">Freight Cost</div>
                                        <div class="financial-amount">${{ number_format($purchase->freight, 2) }}</div>
                                        <div class="financial-detail">
                                            Shipping & Handling
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Invoice Total -->
                                <div class="col-md-4">
                                    <div class="financial-card highlight">
                                        <div class="financial-label">Invoice Total</div>
                                        <div class="financial-amount highlight">${{ number_format($purchase->invoice_total, 2) }}</div>
                                        <div class="financial-detail">
                                            Total Amount
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 6: Notes -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Notes
                            </h6>
                        </div>
                        <div class="notes-container">
                            @if($purchase->note)
                                <div class="border rounded-3 p-3 bg-light">
                                    <p class="mb-0">{{ $purchase->note }}</p>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-sticky-note fa-2x mb-2"></i>
                                    <p class="mb-0">No additional notes provided</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Section 7: Timeline -->
                    <div class="mb-4">
                        <div class="section-header mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-history me-2 text-primary"></i>Status Timeline
                            </h6>
                        </div>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Purchase Order Created</div>
                                    <div class="timeline-time">{{ $purchase->created_at->format('M d, Y h:i A') }}</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker active"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Current Status: {{ ucfirst($purchase->status) }}</div>
                                    <div class="timeline-time">Last updated</div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <button type="button" class="btn btn-outline-primary rounded-2 px-3 btn-sm" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>Print
                                </button>
                                
                                @if($purchase->status == 'pending')
                                    <a href="{{ route('project-purchases.edit', $purchase->id) }}" 
                                       class="btn btn-primary rounded-2 px-3 btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    
                                    <form action="{{ route('project-purchases.destroy', $purchase->id) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this purchase order?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger rounded-2 px-3 btn-sm">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

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

    /* Contact Info */
    .contact-info {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }

    .small-icon {
        width: 16px;
        text-align: center;
    }

    /* Financial Cards */
    .financial-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        background: #f8fafc;
        height: 100%;
    }

    .financial-card.highlight {
        background: rgba(16, 185, 129, 0.05);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .financial-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .financial-amount {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 4px;
        color: #1f2937;
    }

    .financial-amount.highlight {
        color: #10b981;
    }

    .financial-detail {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 4px;
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

    /* Responsive Design */
    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .d-flex.gap-2 {
            flex-wrap: wrap;
        }
        
        .financial-amount {
            font-size: 1.1rem;
        }
        
        .info-value-large {
            font-size: 1rem;
        }
    }

    /* Print Styles */
    @media print {
        .btn,
        .badge {
            display: none !important;
        }
        
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
        }
        
        .financial-card {
            break-inside: avoid;
        }
    }
</style>
@endsection