@extends('layouts.app')

@section('title', 'Edit DCM Costing - Create New Revision')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('dcm-costings.show', $costing->uid) }}" 
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Edit DCM Costing</h5>
                    <p class="text-muted small mb-0">
                        PO Number: <strong>{{ $costing->po_number }}</strong> | 
                        @if($costing->revision_at)
                            Last Revision: {{ $costing->revision_at->format('d/m/Y H:i') }}
                        @else
                            Original Entry
                        @endif
                    </p>
                </div>
                <div>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-2 px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i> This will create a new revision
                    </span>
                </div>
            </div>

            <!-- Alert Information -->
            <div class="alert alert-info border-0 d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle fa-lg me-3 text-info"></i>
                <div>
                    <strong>Important Information:</strong> Editing financial values will create a new revision. 
                    The previous revision will be preserved in history. 
                    Only the amount fields below can be modified.
                </div>
            </div>

            <!-- Current Values Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-light border-0 py-3">
                    <h6 class="text-dark fw-medium mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>Current Values
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Current Unit Price</label>
                            <div class="fw-medium text-dark">
                                Rp {{ number_format($costing->unit_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Current Total Price</label>
                            <div class="fw-medium text-dark">
                                Rp {{ number_format($costing->total_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Current Freight</label>
                            <div class="fw-medium text-dark">
                                Rp {{ number_format($costing->freight ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Current Invoice Total</label>
                            <div class="fw-medium text-dark">
                                Rp {{ number_format($costing->invoice_total, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('error'))
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <form action="{{ route('dcm-costings.update', $costing->uid) }}" method="POST" id="editForm">
                        @csrf
                        @method('PUT')
                        
                        <h6 class="text-dark fw-medium mb-3">
                            <i class="fas fa-edit me-2 text-primary"></i>New Values
                        </h6>
                        
                        <div class="row g-3">
                            <!-- Unit Price -->
                            <div class="col-md-3">
                                <label class="form-label small text-dark fw-medium">Unit Price <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" 
                                           name="unit_price" 
                                           id="unit_price"
                                           class="form-control border-start-0 @error('unit_price') is-invalid @enderror" 
                                           value="{{ old('unit_price', $costing->unit_price) }}" 
                                           step="0.01" 
                                           min="0" 
                                           required
                                           onchange="calculateTotal()">
                                    @error('unit_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text text-muted small">Per unit price</div>
                            </div>
                            
                            <!-- Total Price -->
                            <div class="col-md-3">
                                <label class="form-label small text-dark fw-medium">Total Price <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" 
                                           name="total_price" 
                                           id="total_price"
                                           class="form-control border-start-0 @error('total_price') is-invalid @enderror" 
                                           value="{{ old('total_price', $costing->total_price) }}" 
                                           step="0.01" 
                                           min="0" 
                                           required
                                           onchange="calculateUnitPrice()">
                                    @error('total_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text text-muted small">Quantity × Unit Price</div>
                            </div>
                            
                            <!-- Freight -->
                            <div class="col-md-3">
                                <label class="form-label small text-dark fw-medium">Freight</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" 
                                           name="freight" 
                                           id="freight"
                                           class="form-control border-start-0 @error('freight') is-invalid @enderror" 
                                           value="{{ old('freight', $costing->freight) }}" 
                                           step="0.01" 
                                           min="0"
                                           onchange="calculateInvoiceTotal()">
                                    @error('freight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text text-muted small">Shipping cost</div>
                            </div>
                            
                            <!-- Invoice Total -->
                            <div class="col-md-3">
                                <label class="form-label small text-dark fw-medium">Invoice Total <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" 
                                           name="invoice_total" 
                                           id="invoice_total"
                                           class="form-control border-start-0 @error('invoice_total') is-invalid @enderror" 
                                           value="{{ old('invoice_total', $costing->invoice_total) }}" 
                                           step="0.01" 
                                           min="0" 
                                           required>
                                    @error('invoice_total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text text-muted small">Total Price + Freight</div>
                            </div>
                            
                            <!-- Revision Notes -->
                            <div class="col-12 mt-2">
                                <label class="form-label small text-dark fw-medium">Revision Notes</label>
                                <textarea name="revision_notes" 
                                          id="revision_notes"
                                          class="form-control @error('revision_notes') is-invalid @enderror" 
                                          rows="3" 
                                          placeholder="Explain the reason for changing these amounts (e.g., price adjustment, currency change, discount, etc.)">{{ old('revision_notes') }}</textarea>
                                <div class="form-text text-muted small">
                                    This note will be added to the finance notes for audit trail.
                                </div>
                                @error('revision_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i> 
                                    Current revision will be archived. New revision will be created.
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('dcm-costings.show', $costing->uid) }}" 
                                       class="btn btn-outline-secondary rounded-2 px-4">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary rounded-2 px-4" id="submitBtn">
                                        <i class="fas fa-save me-1"></i> Create New Revision
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Revision History Preview -->
            @php
                $revisions = \App\Models\Finance\DcmCosting::where('po_number', $costing->po_number)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            @endphp
            
            @if($revisions->count() > 1)
            <div class="card border-0 shadow-sm rounded-3 mt-4">
                <div class="card-header bg-light border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-dark fw-medium mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>Recent Revisions
                        </h6>
                        <a href="{{ route('dcm-costings.revisions', $costing->po_number) }}" 
                           class="btn btn-outline-primary btn-sm rounded-2 px-3">
                            <i class="fas fa-list me-1"></i> View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="small text-muted">Version</th>
                                    <th class="small text-muted">Date</th>
                                    <th class="small text-muted text-end">Amount</th>
                                    <th class="small text-muted">Status</th>
                                    <th class="small text-muted text-center">Current</th>
                                    <th class="small text-muted text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revisions as $rev)
                                <tr class="{{ $rev->id == $costing->id ? 'bg-primary bg-opacity-10' : '' }}">
                                    <td class="small">
                                        @if($rev->revision_at)
                                            Revision
                                        @else
                                            <strong>Original</strong>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($rev->revision_at)
                                            {{ $rev->revision_at->format('d/m/Y') }}
                                        @else
                                            {{ $rev->created_at->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td class="small text-end fw-medium">
                                        Rp {{ number_format($rev->invoice_total, 0, ',', '.') }}
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-{{ $rev->status == 'approved' ? 'success' : ($rev->status == 'pending' ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $rev->status == 'approved' ? 'success' : ($rev->status == 'pending' ? 'warning' : 'danger') }} border border-{{ $rev->status == 'approved' ? 'success' : ($rev->status == 'pending' ? 'warning' : 'danger') }} border-opacity-25">
                                            {{ ucfirst($rev->status) }}
                                        </span>
                                    </td>
                                    <td class="small text-center">
                                        @if($rev->is_current)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                <i class="fas fa-check"></i> Current
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="{{ route('dcm-costings.show', $rev->uid) }}" 
                                               class="btn btn-outline-info btn-sm rounded-2 px-2 py-1"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(!$rev->is_current)
                                            <form action="{{ route('dcm-costings.restore-revision', $rev->uid) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Restore this revision? A new revision will be created.')">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-outline-warning btn-sm rounded-2 px-2 py-1"
                                                        title="Restore this version">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        height: 38px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .input-group-sm > .form-control,
    .input-group-sm > .form-select,
    .input-group-sm > .input-group-text {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        height: 32px;
    }

    .input-group-text {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        color: #64748b;
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

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

    .text-muted {
        color: #6b7280 !important;
    }

    .text-dark {
        color: #374151 !important;
    }

    .rounded-2 {
        border-radius: 0.5rem !important;
    }

    .rounded-3 {
        border-radius: 0.75rem !important;
    }

    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .border-opacity-25 {
        --bs-border-opacity: 0.25;
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 500;
    }

    .table-sm th,
    .table-sm td {
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .form-text {
        font-size: 0.8rem;
    }

    textarea.form-control {
        min-height: 80px;
        resize: vertical;
    }

    .alert {
        font-size: 0.9rem;
        padding: 0.75rem 1rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set quantity from PHP
    const quantity = {{ $costing->quantity }};
    const unitPriceInput = document.getElementById('unit_price');
    const totalPriceInput = document.getElementById('total_price');
    const freightInput = document.getElementById('freight');
    const invoiceTotalInput = document.getElementById('invoice_total');
    
    // Format currency for display
    function formatCurrency(value) {
        return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Calculate total price from unit price
    window.calculateTotal = function() {
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const totalPrice = unitPrice * quantity;
        totalPriceInput.value = totalPrice.toFixed(2);
        calculateInvoiceTotal();
    }
    
    // Calculate unit price from total price
    window.calculateUnitPrice = function() {
        const totalPrice = parseFloat(totalPriceInput.value) || 0;
        if (quantity > 0) {
            const unitPrice = totalPrice / quantity;
            unitPriceInput.value = unitPrice.toFixed(2);
        }
        calculateInvoiceTotal();
    }
    
    // Calculate invoice total
    window.calculateInvoiceTotal = function() {
        const totalPrice = parseFloat(totalPriceInput.value) || 0;
        const freight = parseFloat(freightInput.value) || 0;
        const invoiceTotal = totalPrice + freight;
        invoiceTotalInput.value = invoiceTotal.toFixed(2);
    }
    
    // Auto-calculate on input
    if (unitPriceInput) {
        unitPriceInput.addEventListener('input', calculateTotal);
    }
    
    if (totalPriceInput) {
        totalPriceInput.addEventListener('input', calculateUnitPrice);
    }
    
    if (freightInput) {
        freightInput.addEventListener('input', calculateInvoiceTotal);
    }
    
    // Form submission confirmation
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const revisionNotes = document.getElementById('revision_notes').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            
            if (!revisionNotes) {
                const confirmed = confirm('You haven\'t added any revision notes. Are you sure you want to create a new revision without notes?');
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creating Revision...';
            submitBtn.disabled = true;
            
            return true;
        });
    }
    
    // Initialize calculations
    calculateTotal();
});
</script>
@endsection