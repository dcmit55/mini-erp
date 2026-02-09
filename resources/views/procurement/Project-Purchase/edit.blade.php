{{-- resources/views/Procurement/Project-Purchase/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="{{ route('project-purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Edit Purchase Order</h5>
                    <p class="text-muted small mb-0">PO Number: {{ $purchase->po_number }}</p>
                </div>
                <div>
                    <a href="{{ route('project-purchases.show', $purchase->id) }}" 
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    @if(session('error'))
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-3 p-2">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('project-purchases.update', $purchase->id) }}" method="POST" id="purchaseForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Purchase Type & PO Number -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-shopping-cart me-2 text-primary"></i>Purchase Information
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Purchase Type <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3" 
                                            name="purchase_type" 
                                            id="purchaseType" 
                                            required>
                                        <option value="restock" {{ $purchase->purchase_type == 'restock' ? 'selected' : '' }}>Restock Material</option>
                                        <option value="new_item" {{ $purchase->purchase_type == 'new_item' ? 'selected' : '' }}>New Item</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">PO Number <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3" 
                                           name="po_number" 
                                           value="{{ $purchase->po_number }}" 
                                           required>
                                    <div class="form-text text-muted small mt-1">Unique Purchase Order number</div>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Job Order -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-file-alt me-2 text-primary"></i>Date & Job Order
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light py-2">
                                            <i class="fas fa-calendar text-muted small"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control border-1 rounded-2 py-2 px-3" 
                                               name="date" 
                                               value="{{ $purchase->date->format('Y-m-d') }}" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Job Order</label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 select2-joborder" 
                                            name="job_order_id" 
                                            id="jobOrderSelect">
                                        <option value="">Select Job Order</option>
                                        @foreach($jobOrders as $jobOrder)
                                            <option value="{{ $jobOrder->id }}" 
                                                    data-department-id="{{ $jobOrder->department_id }}"
                                                    data-project-id="{{ $jobOrder->project_id }}"
                                                    {{ $purchase->job_order_id == $jobOrder->id ? 'selected' : '' }}>
                                                {{ $jobOrder->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted small mt-1">Selection will auto-fill department and project</div>
                                </div>
                            </div>
                        </div>

                        <!-- Department & Project -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">Department & Project</h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Department <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 select2-department" 
                                            name="department_id" 
                                            id="departmentSelect" 
                                            required>
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" 
                                                    {{ $purchase->department_id == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Project</label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 select2-project" 
                                            name="project_id" 
                                            id="projectSelect">
                                        <option value="">Select Project</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" 
                                                    {{ $purchase->project_id == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Material Details -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">Material Details</h6>
                            
                            <!-- Restock Section (shown when purchase_type is restock) -->
                            <div id="restockSection" class="{{ $purchase->purchase_type != 'restock' ? 'd-none' : '' }}">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label small text-dark">Material <span class="text-danger">*</span></label>
                                        <select class="form-select border-1 rounded-2 py-2 px-3 select2-material" 
                                                name="material_id" 
                                                id="materialSelect">
                                            <option value="">Select Material</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" 
                                                        data-price="{{ $material->price ?? 0 }}"
                                                        data-unit-id="{{ $material->unit_id ?? '' }}"
                                                        data-category-id="{{ $material->category_id ?? '' }}"
                                                        {{ $purchase->material_id == $material->id ? 'selected' : '' }}>
                                                    {{ $material->name }} 
                                                    @if($material->price)
                                                        (Rp {{ number_format($material->price, 0) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-dark">&nbsp;</label>
                                        <div class="alert alert-light border small p-2 rounded-2 h-100">
                                            <i class="fas fa-info-circle me-1 text-muted"></i>
                                            Select material to auto-fill price & unit
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New Item Section (shown when purchase_type is new_item) -->
                            <div id="newItemSection" class="{{ $purchase->purchase_type != 'new_item' ? 'd-none' : '' }}">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label small text-dark">New Item Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control border-1 rounded-2 py-2 px-3" 
                                               name="new_item_name" 
                                               id="newItemName"
                                               value="{{ $purchase->new_item_name }}"
                                               placeholder="Enter new item name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-dark">&nbsp;</label>
                                        <div class="alert alert-info border small p-2 rounded-2 h-100">
                                            <i class="fas fa-plus-circle me-1"></i>
                                            This will create a new inventory item
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quantity, Unit & Price -->
                            <div class="row g-2">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small text-dark">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control border-1 rounded-2 py-2 px-3" 
                                           name="quantity" 
                                           id="quantity" 
                                           min="1" 
                                           step="1" 
                                           value="{{ $purchase->quantity }}" 
                                           required>
                                </div>
                                
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small text-dark">Unit <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3" 
                                            name="unit_id" 
                                            id="unitSelect" 
                                            required>
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" 
                                                    {{ $purchase->unit_id == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small text-dark">Unit Price (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light py-2">Rp</span>
                                        <input type="number" 
                                               class="form-control border-1 rounded-2 py-2 px-3" 
                                               name="unit_price" 
                                               id="unitPrice" 
                                               step="100" 
                                               min="0" 
                                               value="{{ $purchase->unit_price }}" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small text-dark">Category <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3" 
                                            name="category_id" 
                                            id="categorySelect" 
                                            required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ $purchase->category_id == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier & Order Type -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">Supplier Information</h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 select2-supplier" 
                                            name="supplier_id" 
                                            id="supplierSelect" 
                                            required>
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" 
                                                    {{ $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Order Type</label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="is_offline_order" 
                                                   id="onlineOrder" value="0" 
                                                   {{ !$purchase->is_offline_order ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="onlineOrder">
                                                <i class="fas fa-globe me-1"></i>Online Order
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="is_offline_order" 
                                                   id="offlineOrder" value="1"
                                                   {{ $purchase->is_offline_order ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="offlineOrder">
                                                <i class="fas fa-store me-1"></i>Offline Order
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tracking Information (only for online orders) -->
                            <div class="row g-2 mt-2" id="trackingSection">                                
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-dark">Resi Number</label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3" 
                                           name="resi_number" 
                                           value="{{ $purchase->resi_number }}"
                                           placeholder="Optional resi number">
                                </div>
                            </div>
                        </div>

                        <!-- Financial Details -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calculator me-2 text-primary"></i>Financial Details
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-4 mb-2">
                                    <div class="border rounded-2 p-3 bg-light text-center">
                                        <label class="form-label small text-muted mb-1">Total Price</label>
                                        <div class="small text-primary fw-medium" id="displayTotalPrice">
                                            Rp {{ number_format($purchase->total_price, 0) }}
                                        </div>
                                        <input type="hidden" name="total_price" id="totalPrice" value="{{ $purchase->total_price }}">
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small text-dark">Freight Cost (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-end-0 bg-light py-2">Rp</span>
                                        <input type="number" 
                                               class="form-control border-1 rounded-2 py-2 px-3" 
                                               name="freight" 
                                               id="freight" 
                                               step="100" 
                                               min="0" 
                                               value="{{ $purchase->freight }}" 
                                               placeholder="0">
                                    </div>
                                    <div class="form-text text-muted small mt-1">Optional shipping costs</div>
                                </div>
                                
                                <div class="col-md-4 mb-2">
                                    <div class="border rounded-2 p-3 bg-success bg-opacity-10 text-center">
                                        <label class="form-label small text-muted mb-1">Invoice Total</label>
                                        <div class="small text-success fw-medium" id="displayInvoiceTotal">
                                            Rp {{ number_format($purchase->invoice_total, 0) }}
                                        </div>
                                        <input type="hidden" name="invoice_total" id="invoiceTotal" value="{{ $purchase->invoice_total }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Additional Notes
                            </h6>
                            
                            <div class="mb-2">
                                <label class="form-label small text-dark">Notes</label>
                                <textarea class="form-control border-1 rounded-2 py-2 px-3" 
                                          name="note" 
                                          rows="3"
                                          placeholder="Optional notes or special instructions...">{{ $purchase->note }}</textarea>
                            </div>
                        </div>

                        <!-- PIC Information -->
                        <div class="alert alert-light border small mb-4 p-2 rounded-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user text-muted me-2"></i>
                                <div>
                                    <div class="small text-muted">PIC (Person In Charge)</div>
                                    <div class="small text-dark">{{ $purchase->pic->name ?? auth()->user()->name }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('project-purchases.show', $purchase->id) }}" 
                               class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>Update Purchase Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Form Styling */
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc2626;
    }

    .form-control:disabled, .form-select:disabled {
        background-color: #f8fafc;
        cursor: not-allowed;
    }

    /* Labels */
    .form-label.small {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }

    /* Card Styling */
    .card {
        background: #ffffff;
    }

    /* Section Headers */
    h6.fw-medium {
        color: #334155;
        padding-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Icons in headers */
    h6.fw-medium i {
        color: #4f46e5;
        font-size: 0.9rem;
    }

    /* Input Group Styling */
    .input-group-text {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        border-radius: 6px 0 0 6px;
        font-size: 0.9rem;
    }

    /* Buttons */
    .btn {
        font-size: 0.9rem;
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
        padding: 0.4rem 0.8rem;
    }

    /* Spacing */
    .mb-2 {
        margin-bottom: 0.5rem !important;
    }
    
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .py-3 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    /* Border radius */
    .rounded-2 {
        border-radius: 8px !important;
    }

    .rounded-3 {
        border-radius: 12px !important;
    }

    /* Alert */
    .alert {
        font-size: 0.9rem;
        border-radius: 8px;
    }

    /* Required star */
    .text-danger {
        color: #dc2626 !important;
    }

    /* Select2 custom styling */
    .select2-container .select2-selection--single {
        height: 42px;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        font-size: 0.9rem;
        padding-left: 12px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 6px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #6b7280 transparent transparent transparent;
        border-width: 5px 4px 0 4px;
    }
    
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #6b7280 transparent;
        border-width: 0 4px 5px 4px;
    }

    /* Text colors */
    .text-primary {
        color: #4f46e5 !important;
    }

    .text-success {
        color: #10b981 !important;
    }

    .text-warning {
        color: #f59e0b !important;
    }

    /* Small text */
    .small {
        font-size: 0.85rem;
    }

    /* Form text */
    .form-text {
        font-size: 0.8rem;
    }

    /* Background opacity */
    .bg-opacity-5 {
        background-color: rgba(79, 70, 229, 0.05) !important;
    }
    
    .bg-opacity-10 {
        background-color: rgba(16, 185, 129, 0.1) !important;
    }

    /* Form check styling */
    .form-check-input:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .form-check-label {
        font-size: 0.85rem;
    }

    /* Hide tracking section for offline orders */
    #trackingSection {
        transition: all 0.3s ease;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2-material, .select2-department, .select2-project, .select2-joborder, .select2-supplier').select2({
        placeholder: "Select an option",
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 5
    });

    // Elements
    const purchaseType = document.getElementById('purchaseType');
    const restockSection = document.getElementById('restockSection');
    const newItemSection = document.getElementById('newItemSection');
    const materialSelect = document.getElementById('materialSelect');
    const newItemName = document.getElementById('newItemName');
    const jobOrderSelect = document.getElementById('jobOrderSelect');
    const departmentSelect = document.getElementById('departmentSelect');
    const projectSelect = document.getElementById('projectSelect');
    const unitSelect = document.getElementById('unitSelect');
    const categorySelect = document.getElementById('categorySelect');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unitPrice');
    const freightInput = document.getElementById('freight');
    const supplierSelect = document.getElementById('supplierSelect');
    const onlineOrderRadio = document.getElementById('onlineOrder');
    const offlineOrderRadio = document.getElementById('offlineOrder');
    const trackingSection = document.getElementById('trackingSection');
    const totalPriceInput = document.getElementById('totalPrice');
    const invoiceTotalInput = document.getElementById('invoiceTotal');
    const displayTotalPrice = document.getElementById('displayTotalPrice');
    const displayInvoiceTotal = document.getElementById('displayInvoiceTotal');

    // Toggle purchase type sections
    function togglePurchaseType() {
        const type = purchaseType.value;
        
        if (type === 'restock') {
            restockSection.classList.remove('d-none');
            newItemSection.classList.add('d-none');
            materialSelect.required = true;
            newItemName.required = false;
        } else if (type === 'new_item') {
            restockSection.classList.add('d-none');
            newItemSection.classList.remove('d-none');
            materialSelect.required = false;
            newItemName.required = true;
        }
    }

    // Toggle tracking section based on order type
    function toggleTrackingSection() {
        if (onlineOrderRadio.checked) {
            trackingSection.style.display = 'flex';
        } else {
            trackingSection.style.display = 'none';
        }
    }

    // Format currency
    function formatCurrency(amount) {
        return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
    }

    // Calculate totals
    function calculateTotals() {
        const quantity = parseInt(quantityInput.value) || 0;
        const unitPrice = parseInt(unitPriceInput.value) || 0;
        const freight = parseInt(freightInput.value) || 0;
        
        const totalPrice = quantity * unitPrice;
        const invoiceTotal = totalPrice + freight;
        
        // Update hidden inputs
        totalPriceInput.value = totalPrice;
        invoiceTotalInput.value = invoiceTotal;
        
        // Update display
        displayTotalPrice.textContent = formatCurrency(totalPrice);
        displayInvoiceTotal.textContent = formatCurrency(invoiceTotal);
        
        // Visual feedback for changes
        if (totalPrice !== parseInt("{{ $purchase->total_price }}")) {
            displayTotalPrice.classList.add('text-warning');
        } else {
            displayTotalPrice.classList.remove('text-warning');
        }
        
        if (invoiceTotal !== parseInt("{{ $purchase->invoice_total }}")) {
            displayInvoiceTotal.classList.add('text-warning');
        } else {
            displayInvoiceTotal.classList.remove('text-warning');
        }
    }

    // Auto-fill department and project when job order is selected
    $(jobOrderSelect).on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const departmentId = selectedOption.getAttribute('data-department-id');
        const projectId = selectedOption.getAttribute('data-project-id');
        
        if (departmentId && departmentId !== 'null') {
            $(departmentSelect).val(departmentId).trigger('change');
        }
        
        if (projectId && projectId !== 'null') {
            $(projectSelect).val(projectId).trigger('change');
        }
    });

    // Auto-fill unit price, unit and category when material is selected
    $(materialSelect).on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const materialPrice = selectedOption.getAttribute('data-price');
        const unitId = selectedOption.getAttribute('data-unit-id');
        const categoryId = selectedOption.getAttribute('data-category-id');
        
        if (materialPrice && materialPrice > 0) {
            unitPriceInput.value = materialPrice;
        }
        
        if (unitId && unitId !== 'null') {
            unitSelect.value = unitId;
        }
        
        if (categoryId && categoryId !== 'null') {
            categorySelect.value = categoryId;
        }
        
        calculateTotals();
    });

    // Event Listeners
    purchaseType.addEventListener('change', togglePurchaseType);
    onlineOrderRadio.addEventListener('change', toggleTrackingSection);
    offlineOrderRadio.addEventListener('change', toggleTrackingSection);
    
    // Calculate totals on input change
    [quantityInput, unitPriceInput, freightInput].forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('change', calculateTotals);
    });

    // Form validation
    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        const unitPrice = parseInt(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value);
        
        // Validate purchase type specific fields
        if (purchaseType.value === 'restock' && !materialSelect.value) {
            e.preventDefault();
            alert('Please select a material for restock purchase');
            materialSelect.focus();
            return;
        }
        
        if (purchaseType.value === 'new_item' && !newItemName.value.trim()) {
            e.preventDefault();
            alert('Please enter new item name');
            newItemName.focus();
            return;
        }
        
        // Validate required fields
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;
        
        requiredFields.forEach(field => {
            if (!field.value.trim() || field.value === '') {
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return;
        }
        
        if (unitPrice <= 0) {
            e.preventDefault();
            alert('Unit price must be greater than 0');
            unitPriceInput.focus();
            unitPriceInput.classList.add('is-invalid');
            return;
        } else {
            unitPriceInput.classList.remove('is-invalid');
        }
        
        if (quantity <= 0) {
            e.preventDefault();
            alert('Quantity must be greater than 0');
            quantityInput.focus();
            quantityInput.classList.add('is-invalid');
            return;
        } else {
            quantityInput.classList.remove('is-invalid');
        }
        
        // Validate online order tracking
        if (onlineOrderRadio.checked) {
            const trackingNumber = this.querySelector('input[name="tracking_number"]').value;
            const resiNumber = this.querySelector('input[name="resi_number"]').value;
            
            if (!trackingNumber && !resiNumber) {
                if (!confirm('No tracking number or resi number provided for online order. Continue anyway?')) {
                    e.preventDefault();
                    return;
                }
            }
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
        submitBtn.disabled = true;
        
        // Re-enable after 3 seconds if form submission fails
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Initialize on page load
    togglePurchaseType();
    toggleTrackingSection();
    calculateTotals();
    
    // Auto-calculate with debounce
    let calculateTimeout;
    [quantityInput, unitPriceInput, freightInput].forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(calculateTimeout);
            calculateTimeout = setTimeout(calculateTotals, 300);
        });
    });
});
</script>
@endsection