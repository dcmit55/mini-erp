@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<style>
    /* Form Styling */
    .form-control,
    .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        border-width: 1px;
        height: 42px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc2626;
    }

    /* Labels */
    .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        color: #334155;
        font-weight: 500;
    }

    .label-with-addon {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 0.25rem;
    }

    .add-button {
        font-size: 0.75rem;
        color: #4f46e5;
        text-decoration: none;
        cursor: pointer;
        font-weight: 400;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .add-button:hover {
        background-color: #f1f5f9;
        color: #4338ca;
    }

    /* Card Styling */
    .card {
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Section Headers */
    .section-header {
        color: #334155;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
        font-size: 1rem;
        font-weight: 600;
    }

    .section-header i {
        color: #4f46e5;
        font-size: 0.9rem;
    }

    /* Project Details Box */
    .project-details-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem;
        font-size: 0.85rem;
        min-height: 58px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .project-details-box small {
        color: #64748b;
    }

    .project-details-box div {
        margin-bottom: 0.25rem;
    }

    /* Totals Display */
    .total-box {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem;
        background-color: #ffffff;
    }

    .total-box .total-label {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .total-box .total-amount {
        font-size: 1rem;
        font-weight: 600;
        color: #334155;
    }

    .invoice-total-box {
        background-color: #f0f9ff;
        border-color: #bae6fd;
    }

    .invoice-total-box .total-amount {
        color: #0369a1;
    }

    /* Radio buttons group spacing */
    .radio-group {
        gap: 1.5rem;
        display: flex;
    }

    .radio-group .form-check {
        margin-bottom: 0;
    }

    .form-check-input:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .form-check-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
    }

    /* Buttons */
    .btn {
        font-size: 0.9rem;
        border-radius: 8px;
        padding: 0.5rem 1rem;
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

    /* Required star */
    .text-danger {
        color: #dc2626 !important;
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

    /* Fast hide class */
    .fast-hide {
        display: none !important;
    }

    /* Info note styling */
    .info-note {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('project-purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h5 class="text-dark mb-1 mt-2">Create New Purchase Order</h5>
                    <p class="text-muted small mb-0">Complete purchase order information</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div>{{ session('error') }}</div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('project-purchases.store') }}" method="POST" id="purchaseForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">PO Number <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('po_number') is-invalid @enderror" 
                                           name="po_number" 
                                           value="{{ old('po_number') }}" 
                                           required>
                                    @error('po_number')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">Isi dengan nomor PO sesuai format Anda (harus unik)</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('date') is-invalid @enderror" 
                                           name="date" 
                                           value="{{ old('date', date('Y-m-d')) }}" 
                                           required>
                                    @error('date')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Project Information -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-project-diagram me-2"></i>Project Information
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label mb-2">Project Type <span class="text-danger">*</span></label>
                                <div class="d-flex radio-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="clientProjectType" value="client" {{ old('project_type') == 'client' ? 'checked' : 'checked' }}>
                                        <label class="form-check-label" for="clientProjectType">
                                            Client Project
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="internalProjectType" value="internal" {{ old('project_type') == 'internal' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="internalProjectType">
                                            Internal Project
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Client Project -->
                            <div id="clientProjectSection" class="mb-3 {{ old('project_type') == 'internal' ? 'fast-hide' : '' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Job Order <span class="text-danger">*</span></label>
                                        <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('job_order_id') is-invalid @enderror" name="job_order_id" id="jobOrderSelect" {{ old('project_type') != 'internal' ? 'required' : '' }}>
                                            <option value="">Select Job Order</option>
                                            @foreach($jobOrders as $jobOrder)
                                                <option value="{{ $jobOrder['id'] }}" 
                                                        data-deptid="{{ $jobOrder['department_id'] }}"
                                                        data-deptname="{{ $jobOrder['department_name'] ?? '' }}"
                                                        data-projid="{{ $jobOrder['project_id'] }}"
                                                        data-projname="{{ $jobOrder['project_name'] ?? '' }}"
                                                        {{ old('job_order_id') == $jobOrder['id'] ? 'selected' : '' }}>
                                                    {{ $jobOrder['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('job_order_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Project Details</label>
                                        <div id="clientProjectDetails" class="project-details-box">
                                            <small class="text-muted">Select a job order to view details</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Internal Project -->
                            <div id="internalProjectSection" class="mb-3 {{ old('project_type') == 'internal' ? '' : 'fast-hide' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Internal Project <span class="text-danger">*</span></label>
                                        <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('internal_project_id') is-invalid @enderror" name="internal_project_id" id="internalProjectSelect" {{ old('project_type') == 'internal' ? 'required' : '' }}>
                                            <option value="">Select Internal Project</option>
                                            @foreach($internal_projects as $internalProject)
                                                <option value="{{ $internalProject->id }}" 
                                                        data-project="{{ $internalProject->project }}"
                                                        data-department="{{ $internalProject->department }}"
                                                        data-job="{{ $internalProject->job }}"
                                                        data-department-id="{{ $internalProject->department_id }}"
                                                        {{ old('internal_project_id') == $internalProject->id ? 'selected' : '' }}>
                                                    {{ $internalProject->job }} - {{ $internalProject->project }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('internal_project_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Project Details</label>
                                        <div id="internalProjectDetails" class="project-details-box">
                                            <small class="text-muted">Select an internal project to view details</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Fields -->
                        <input type="hidden" name="department_id" id="departmentId" value="{{ old('department_id') }}">
                        <input type="hidden" name="project_id" id="projectId" value="{{ old('project_id') }}">

                        <!-- Item Information -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-box me-2"></i>Item Information
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label mb-2">Purchase Type <span class="text-danger">*</span></label>
                                <div class="d-flex radio-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_type" 
                                               id="restockType" value="restock" {{ old('purchase_type') == 'new_item' ? '' : 'checked' }}>
                                        <label class="form-check-label" for="restockType">
                                            Restock
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_type" 
                                               id="newItemType" value="new_item" {{ old('purchase_type') == 'new_item' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="newItemType">
                                            New Item
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Material (Restock) -->
                            <div id="restockSection" class="mb-3 {{ old('purchase_type') == 'new_item' ? 'fast-hide' : '' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Material <span class="text-danger">*</span></label>
                                        <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('material_id') is-invalid @enderror" name="material_id" id="materialSelect" {{ old('purchase_type') != 'new_item' ? 'required' : '' }}>
                                            <option value="">Select Material</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" 
                                                        data-price="{{ $material->price ?? 0 }}"
                                                        data-unit-id="{{ $material->unit_id }}"
                                                        data-category-id="{{ $material->category_id }}"
                                                        {{ old('material_id') == $material->id ? 'selected' : '' }}>
                                                    {{ $material->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('material_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- New Item -->
                            <div id="newItemSection" class="mb-3 {{ old('purchase_type') == 'new_item' ? '' : 'fast-hide' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control border-1 rounded-2 py-2 px-3 @error('new_item_name') is-invalid @enderror" name="new_item_name" id="newItemName" value="{{ old('new_item_name') }}" {{ old('purchase_type') == 'new_item' ? 'required' : '' }}>
                                        @error('new_item_name')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Item Details -->
                            <div class="row g-2">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control border-1 rounded-2 py-2 px-3 @error('quantity') is-invalid @enderror" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" min="1" required>
                                    @error('quantity')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('unit_id') is-invalid @enderror" name="unit_id" id="unitSelect" required>
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control border-1 rounded-2 py-2 px-3 @error('unit_price') is-invalid @enderror" name="unit_price" id="unitPrice" step="100" min="0" value="{{ old('unit_price') }}" required>
                                    @error('unit_price')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select border-1 rounded-2 py-2 px-3 @error('category_id') is-invalid @enderror" name="category_id" id="categorySelect" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Information -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-truck me-2"></i>Supplier Information
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <div class="label-with-addon">
                                        <span>Supplier <span class="text-danger">*</span></span>
                                        <a href="#" class="add-button" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                            <i class="fas fa-plus"></i> Add Supplier
                                        </a>
                                    </div>
                                    <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect" required>
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-2">Order Type <span class="text-danger">*</span></label>
                                    <div class="d-flex radio-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_offline_order" 
                                                   id="onlineOrder" value="0" {{ old('is_offline_order') == '1' ? '' : 'checked' }}>
                                            <label class="form-check-label" for="onlineOrder">
                                                Online
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_offline_order" 
                                                   id="offlineOrder" value="1" {{ old('is_offline_order') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="offlineOrder">
                                                Offline
                                            </label>
                                        </div>
                                    </div>
                                    <small class="info-note">Other costs remain visible for both online and offline orders</small>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Section (ONLINE ONLY) -->
                        <div class="mb-4 shipping-section {{ old('is_offline_order') == '1' ? 'fast-hide' : '' }}">
                            <h6 class="section-header">
                                <i class="fas fa-shipping-fast me-2"></i>Shipping
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Resi Number</label>
                                    <input type="text" class="form-control border-1 rounded-2 py-2 px-3" name="resi_number" id="resiNumber" value="{{ old('resi_number') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Freight Cost</label>
                                    <input type="number" class="form-control border-1 rounded-2 py-2 px-3" name="freight" id="freight" value="{{ old('freight', 0) }}" step="100" min="0">
                                    <small class="info-note">Shipping costs for online orders only</small>
                                </div>
                            </div>
                        </div>

                        <!-- Other Costs Section (ALWAYS VISIBLE) -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-money-bill-wave me-2"></i>Additional Costs
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Other Costs</label>
                                    <input type="number" class="form-control border-1 rounded-2 py-2 px-3" name="other_costs" id="otherCosts" value="{{ old('other_costs', 0) }}" step="100" min="0">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">&nbsp;</label>
                                    <small class="info-note d-block">Additional costs will be included in invoice total for both online and offline orders</small>
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-calculator me-2"></i>Totals
                            </h6>
                            
                            <div class="row g-2">
                                <div class="col-md-4 mb-2">
                                    <div class="total-box">
                                        <div class="total-label">Total Price</div>
                                        <div class="total-amount" id="displayTotalPrice">Rp 0</div>
                                        <input type="hidden" name="total_price" id="totalPrice" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="total-box">
                                        <div class="total-label">Additional Costs</div>
                                        <div class="total-amount" id="displayAdditionalCosts">Rp 0</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="total-box invoice-total-box">
                                        <div class="total-label">Invoice Total</div>
                                        <div class="total-amount" id="displayInvoiceTotal">Rp 0</div>
                                        <input type="hidden" name="invoice_total" id="invoiceTotal" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-sticky-note me-2"></i>Notes
                            </h6>
                            
                            <div class="mb-2">
                                <textarea class="form-control border-1 rounded-2 py-2 px-3" name="note" rows="2" placeholder="Add notes">{{ old('note') }}</textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('project-purchases.index') }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2 px-3 btn-sm">
                                <i class="fas fa-save me-1"></i>Create Purchase Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal">
    <div class="modal-dialog modal-sm">
        <form id="supplierForm" method="POST" action="{{ route('suppliers.quick_store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control border-1 rounded-2 py-2 px-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-select border-1 rounded-2 py-2 px-3" required>
                            <option value="">Select Location</option>
                            @foreach($supplierLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lead Time (Days)</label>
                        <input type="number" name="lead_time_days" class="form-control border-1 rounded-2 py-2 px-3" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-2 px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-3">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Document ready - Initializing Purchase Order Form');
    
    // **INISIALISASI CACHE ELEMENTS UNTUK PERFORMANCE**
    const elements = {
        clientProjectSection: $('#clientProjectSection'),
        internalProjectSection: $('#internalProjectSection'),
        jobOrderSelect: $('#jobOrderSelect'),
        internalProjectSelect: $('#internalProjectSelect'),
        restockSection: $('#restockSection'),
        newItemSection: $('#newItemSection'),
        shippingSection: $('.shipping-section'),
        resiNumber: $('#resiNumber'),
        freight: $('#freight'),
        otherCosts: $('#otherCosts'),
        quantity: $('#quantity'),
        unitPrice: $('#unitPrice'),
        displayTotalPrice: $('#displayTotalPrice'),
        displayAdditionalCosts: $('#displayAdditionalCosts'),
        displayInvoiceTotal: $('#displayInvoiceTotal'),
        totalPrice: $('#totalPrice'),
        invoiceTotal: $('#invoiceTotal'),
        departmentId: $('#departmentId'),
        projectId: $('#projectId'),
        clientProjectDetails: $('#clientProjectDetails'),
        internalProjectDetails: $('#internalProjectDetails')
    };

    // Initialize Select2
    $('.select2').select2({
        width: '100%'
    });

    // **TOGGLE PROJECT TYPE**
    $('input[name="project_type"]').change(function() {
        const isClientProject = $(this).val() === 'client';
        
        if (isClientProject) {
            elements.clientProjectSection.removeClass('fast-hide');
            elements.internalProjectSection.addClass('fast-hide');
            elements.jobOrderSelect.prop('required', true);
            elements.internalProjectSelect.prop('required', false).val('').trigger('change');
            elements.internalProjectDetails.html('<small class="text-muted">Select an internal project to view details</small>');
        } else {
            elements.clientProjectSection.addClass('fast-hide');
            elements.internalProjectSection.removeClass('fast-hide');
            elements.jobOrderSelect.prop('required', false).val('').trigger('change');
            elements.internalProjectSelect.prop('required', true);
            elements.clientProjectDetails.html('<small class="text-muted">Select a job order to view details</small>');
        }
    });

    // **TOGGLE ORDER TYPE (ONLINE/OFFLINE)**
    $('input[name="is_offline_order"]').change(function() {
        const isOfflineOrder = $(this).val() === '1';
        
        if (isOfflineOrder) {
            elements.shippingSection.addClass('fast-hide');
            elements.resiNumber.val('');
            elements.freight.val(0);
        } else {
            elements.shippingSection.removeClass('fast-hide');
        }
        calculateTotals();
    });

    // **TOGGLE PURCHASE TYPE**
    $('input[name="purchase_type"]').change(function() {
        const isRestock = $(this).val() === 'restock';
        
        if (isRestock) {
            elements.restockSection.removeClass('fast-hide');
            elements.newItemSection.addClass('fast-hide');
            $('#materialSelect').prop('required', true);
            $('#newItemName').prop('required', false);
        } else {
            elements.restockSection.addClass('fast-hide');
            elements.newItemSection.removeClass('fast-hide');
            $('#materialSelect').prop('required', false).val('').trigger('change');
            $('#newItemName').prop('required', true);
            
            // Reset values for new item
            elements.unitPrice.val('');
            $('#unitSelect').val('').trigger('change');
            $('#categorySelect').val('').trigger('change');
        }
    });

    // **JOB ORDER DETAILS**
    elements.jobOrderSelect.on('change', function() {
        const selected = elements.jobOrderSelect.find('option:selected');
        const deptId = selected.data('deptid');
        const deptName = selected.data('deptname');
        const projId = selected.data('projid');
        const projName = selected.data('projname');
        
        elements.departmentId.val(deptId || '');
        elements.projectId.val(projId || '');
        
        if (selected.val() && deptName && projName) {
            elements.clientProjectDetails.html(`
                <div><strong>Project:</strong> ${projName}</div>
                <div><strong>Department:</strong> ${deptName}</div>
            `);
        } else if (selected.val()) {
            elements.clientProjectDetails.html(`
                <div><strong>Job Order:</strong> ${selected.text()}</div>
                <div class="text-warning small">Details not available</div>
            `);
        } else {
            elements.clientProjectDetails.html('<small class="text-muted">Select a job order to view details</small>');
        }
    });

    // **INTERNAL PROJECT DETAILS**
    elements.internalProjectSelect.on('change', function() {
        const selected = elements.internalProjectSelect.find('option:selected');
        const project = selected.data('project');
        const department = selected.data('department');
        const job = selected.data('job');
        const deptId = selected.data('department-id');
        
        elements.departmentId.val(deptId || '');
        elements.projectId.val('');
        
        if (selected.val() && project && department && job) {
            elements.internalProjectDetails.html(`
                <div><strong>Project:</strong> ${project}</div>
                <div><strong>Job:</strong> ${job}</div>
                <div><strong>Department:</strong> ${department}</div>
            `);
        } else if (selected.val()) {
            elements.internalProjectDetails.html(`
                <div><strong>Internal Project:</strong> ${selected.text()}</div>
                <div class="text-warning small">Details not available</div>
            `);
        } else {
            elements.internalProjectDetails.html('<small class="text-muted">Select an internal project to view details</small>');
        }
    });

    // **MATERIAL AUTO-FILL**
    $('#materialSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = selected.data('price') || 0;
        const unitId = selected.data('unit-id');
        const categoryId = selected.data('category-id');
        
        if (price > 0) {
            elements.unitPrice.val(price);
        }
        
        if (unitId) {
            $('#unitSelect').val(unitId).trigger('change');
        }
        
        if (categoryId) {
            $('#categorySelect').val(categoryId).trigger('change');
        }
        
        calculateTotals();
    });

    // **CALCULATE TOTALS - DEBOUNCED**
    let calculateTimeout;
    elements.quantity.add(elements.unitPrice).add(elements.freight).add(elements.otherCosts).on('input', function() {
        clearTimeout(calculateTimeout);
        calculateTimeout = setTimeout(calculateTotals, 50);
    });
    
    function calculateTotals() {
        const quantity = parseFloat(elements.quantity.val()) || 0;
        const unitPrice = parseFloat(elements.unitPrice.val()) || 0;
        const freight = parseFloat(elements.freight.val()) || 0;
        const otherCosts = parseFloat(elements.otherCosts.val()) || 0;
        
        const totalPrice = quantity * unitPrice;
        const additionalCosts = freight + otherCosts;
        const invoiceTotal = totalPrice + additionalCosts;
        
        elements.displayTotalPrice.text('Rp ' + formatCurrency(totalPrice));
        elements.displayAdditionalCosts.text('Rp ' + formatCurrency(additionalCosts));
        elements.displayInvoiceTotal.text('Rp ' + formatCurrency(invoiceTotal));
        elements.totalPrice.val(totalPrice);
        elements.invoiceTotal.val(invoiceTotal);
    }
    
    function formatCurrency(amount) {
        return amount.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // **ADD SUPPLIER**
    $('#supplierForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    const newOption = new Option(response.supplier.name, response.supplier.id, true, true);
                    $('#supplierSelect').append(newOption).trigger('change');
                    $('#addSupplierModal').modal('hide');
                    form[0].reset();
                    
                    // Show success message
                    alert('Supplier added successfully');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to add supplier';
                alert(msg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Save');
            }
        });
    });

    // **FORM VALIDATION**
    $('#purchaseForm').submit(function(e) {
        const projectType = $('input[name="project_type"]:checked').val();
        const purchaseType = $('input[name="purchase_type"]:checked').val();
        const poNumber = $('input[name="po_number"]').val().trim();
        
        // Validate PO Number
        if (!poNumber) {
            e.preventDefault();
            alert('Please enter PO Number');
            $('input[name="po_number"]').focus();
            return false;
        }
        
        // Validate project type specific fields
        if (projectType === 'client') {
            if (!elements.jobOrderSelect.val()) {
                e.preventDefault();
                alert('Please select a Job Order for client project');
                elements.jobOrderSelect.focus();
                return false;
            }
        } else if (projectType === 'internal') {
            if (!elements.internalProjectSelect.val()) {
                e.preventDefault();
                alert('Please select an Internal Project');
                elements.internalProjectSelect.focus();
                return false;
            }
        }
        
        // Validate purchase type specific fields
        if (purchaseType === 'restock') {
            if (!$('#materialSelect').val()) {
                e.preventDefault();
                alert('Please select a material for restock purchase');
                $('#materialSelect').focus();
                return false;
            }
        } else if (purchaseType === 'new_item') {
            if (!$('#newItemName').val().trim()) {
                e.preventDefault();
                alert('Please enter new item name');
                $('#newItemName').focus();
                return false;
            }
        }
        
        // Validate unit price and quantity
        const unitPrice = parseFloat(elements.unitPrice.val()) || 0;
        const quantity = parseFloat(elements.quantity.val()) || 0;
        
        if (unitPrice <= 0) {
            e.preventDefault();
            alert('Unit price must be greater than 0');
            elements.unitPrice.focus();
            return false;
        }
        
        if (quantity <= 0) {
            e.preventDefault();
            alert('Quantity must be greater than 0');
            elements.quantity.focus();
            return false;
        }
        
        // Validate online order tracking
        if ($('input[name="is_offline_order"]:checked').val() === '0') {
            const resiNumber = elements.resiNumber.val().trim();
            const freight = parseFloat(elements.freight.val()) || 0;
            
            // Optional: Add validation for online orders if needed
            // For example, require resi number or freight cost
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        
        return true;
    });

    // Initialize calculations
    calculateTotals();
    
    console.log('Purchase Order Form initialized successfully');
});
</script>
@endsection