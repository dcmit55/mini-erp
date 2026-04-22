{{-- resources/views/Procurement/Project-Purchase/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<style>
    /* Form Styling - SAME AS CREATE */
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

    .form-control:read-only,
    .form-select:disabled {
        background-color: #f1f5f9;
        cursor: not-allowed;
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
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    .btn-outline-danger:hover {
        background-color: #dc2626;
        color: white;
    }

    /* Required star */
    .text-danger {
        color: #dc2626 !important;
    }

    /* Spacing */
    .mb-2 { margin-bottom: 0.5rem !important; }
    .mb-3 { margin-bottom: 1rem !important; }
    .mb-4 { margin-bottom: 1.5rem !important; }
    .py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }

    /* Border radius */
    .rounded-2 { border-radius: 8px !important; }

    /* Fast hide class */
    .fast-hide { display: none !important; }

    /* Info note styling */
    .info-note {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    
    /* Item row styling */
    .item-row {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 1rem;
        position: relative;
    }
    
    .item-row:hover {
        border-color: #4f46e5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .item-title {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
    }
    
    .item-title i {
        color: #4f46e5;
        margin-right: 0.5rem;
    }
    
    .remove-item {
        opacity: 0.6;
        transition: all 0.2s;
    }
    
    .remove-item:hover {
        opacity: 1;
        background-color: #fee2e2;
        border-color: #ef4444;
        color: #ef4444;
    }
    
    /* Warning for read-only items */
    .readonly-item {
        opacity: 0.9;
        border-left: 4px solid #f59e0b;
        background-color: #fffbeb;
    }
    
    .readonly-badge {
        background-color: #f59e0b;
        color: white;
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        border-radius: 12px;
        margin-left: 0.5rem;
    }

    /* Badge styling */
    .badge.bg-warning { background-color: #f59e0b !important; color: white !important; }
    .badge.bg-success { background-color: #10b981 !important; color: white !important; }
    .badge.bg-danger { background-color: #ef4444 !important; color: white !important; }
    .badge.bg-secondary { background-color: #9ca3af !important; color: white !important; }
    .badge.bg-info { background-color: #3b82f6 !important; color: white !important; }

    /* Alert styling */
    .alert {
        border-radius: 8px;
        padding: 0.75rem 1rem;
    }

    /* Select2 customization */
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px;
        padding-left: 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select-readonly {
        pointer-events: none;
        background-color: #e9ecef;
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
                    <h5 class="text-dark mb-1 mt-2">Edit Purchase Order</h5>
                    <p class="text-muted small mb-0">
                        <span class="fw-medium">{{ $purchase->po_number }}</span> | 
                        {{ $poItems->count() }} item(s) | 
                        Status: <span class="badge {{ $purchase->status_badge_class }} px-2 py-1">{{ $purchase->status_text }}</span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('project-purchases.show', $purchase->uid) }}" 
                       class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                    <a href="{{ route('project-purchases.print', $purchase->uid) }}" 
                       class="btn btn-outline-primary btn-sm rounded-2 px-3" target="_blank">
                        <i class="fas fa-print me-1"></i>Print
                    </a>
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

                    <form action="{{ route('project-purchases.update', $purchase->uid) }}" method="POST" id="purchaseForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information - HEADER -->
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
                                           value="{{ old('po_number', $purchase->po_number) }}" 
                                           readonly
                                           style="background-color: #f1f5f9;">
                                    @error('po_number')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">PO Number cannot be changed</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('date') is-invalid @enderror" 
                                           name="date" 
                                           value="{{ old('date', $purchase->date->format('Y-m-d')) }}" 
                                           required>
                                    @error('date')
                                        <div class="invalid-feedback small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Project Information - HEADER -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-project-diagram me-2"></i>Project Information
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label mb-2">Project Type <span class="text-danger">*</span></label>
                                <div class="d-flex radio-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="clientProjectType" value="client" {{ old('project_type', $purchase->project_type) == 'client' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="clientProjectType">Client Project</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="internalProjectType" value="internal" {{ old('project_type', $purchase->project_type) == 'internal' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="internalProjectType">Internal Project</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Client Project -->
                            <div id="clientProjectSection" class="mb-3 {{ old('project_type', $purchase->project_type) == 'internal' ? 'fast-hide' : '' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Job Order <span class="text-danger">*</span></label>
                                        <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('job_order_id') is-invalid @enderror" name="job_order_id" id="jobOrderSelect">
                                            <option value="">Select Job Order</option>
                                            @foreach($jobOrders as $jobOrder)
                                                <option value="{{ $jobOrder['id'] }}" 
                                                        data-deptid="{{ $jobOrder['department_id'] }}"
                                                        data-deptname="{{ $jobOrder['department_name'] ?? '' }}"
                                                        data-projid="{{ $jobOrder['project_id'] }}"
                                                        data-projname="{{ $jobOrder['project_name'] ?? '' }}"
                                                        {{ old('job_order_id', $purchase->job_order_id) == $jobOrder['id'] ? 'selected' : '' }}>
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
                                            @if($purchase->project_type == 'client' && $purchase->jobOrder && $purchase->project)
                                                <div><strong>Project:</strong> {{ $purchase->project->name ?? 'N/A' }}</div>
                                                <div><strong>Department:</strong> {{ $purchase->department->name ?? 'N/A' }}</div>
                                            @else
                                                <small class="text-muted">Select a job order to view details</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Internal Project -->
                            <div id="internalProjectSection" class="mb-3 {{ old('project_type', $purchase->project_type) == 'internal' ? '' : 'fast-hide' }}">
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Internal Project <span class="text-danger">*</span></label>
                                        <select class="form-select select2 border-1 rounded-2 py-2 px-3 @error('internal_project_id') is-invalid @enderror" name="internal_project_id" id="internalProjectSelect">
                                            <option value="">Select Internal Project</option>
                                            @foreach($internal_projects as $internalProject)
                                                <option value="{{ $internalProject->id }}" 
                                                        data-project="{{ $internalProject->project }}"
                                                        data-department="{{ $internalProject->department }}"
                                                        data-job="{{ $internalProject->job }}"
                                                        data-department-id="{{ $internalProject->department_id }}"
                                                        {{ old('internal_project_id', $purchase->internal_project_id) == $internalProject->id ? 'selected' : '' }}>
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
                                            @if($purchase->project_type == 'internal' && $purchase->internalProject)
                                                <div><strong>Project:</strong> {{ $purchase->internalProject->project ?? 'N/A' }}</div>
                                                <div><strong>Job:</strong> {{ $purchase->internalProject->job ?? 'N/A' }}</div>
                                                <div><strong>Department:</strong> {{ $purchase->internalProject->department ?? 'N/A' }}</div>
                                            @else
                                                <small class="text-muted">Select an internal project to view details</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="department_id" id="departmentId" value="{{ old('department_id', $purchase->department_id) }}">
                            <input type="hidden" name="project_id" id="projectId" value="{{ old('project_id', $purchase->project_id) }}">
                        </div>

                        <!-- ===== MULTIPLE ITEMS SECTION ===== -->
                        <div class="mb-4">
                            <div class="section-header">
                                <div>
                                    <i class="fas fa-box me-2"></i>Items Information
                                    <span class="badge bg-primary ms-2">{{ $poItems->count() }}</span>
                                </div>
                            </div>
                            
                            <div id="itemsContainer">
                                @foreach($poItems as $itemIndex => $item)
                                    @php
                                        $itemIsEditable = $item->status == 'pending';
                                    @endphp
                                    <div class="item-row {{ !$itemIsEditable ? 'readonly-item' : '' }}" data-id="{{ $item->id }}" data-index="{{ $itemIndex }}">
                                        <div class="item-header">
                                            <div class="item-title">
                                                <i class="fas fa-cube"></i>
                                                Item #{{ $itemIndex + 1 }}
                                                @if(!$itemIsEditable)
                                                    <span class="readonly-badge">{{ $item->status }}</span>
                                                @endif
                                            </div>
                                            @if($itemIsEditable && $poItems->count() > 1)
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-item-uid="{{ $item->uid }}">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item->id }}">
                                        
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                                <select name="items[{{ $itemIndex }}][purchase_type]" class="form-select purchase-type" {{ !$itemIsEditable ? 'disabled' : '' }}>
                                                    <option value="restock" {{ $item->purchase_type == 'restock' ? 'selected' : '' }}>Restock</option>
                                                    <option value="new_item" {{ $item->purchase_type == 'new_item' ? 'selected' : '' }}>New Item</option>
                                                </select>
                                                @if(!$itemIsEditable)
                                                    <input type="hidden" name="items[{{ $itemIndex }}][purchase_type]" value="{{ $item->purchase_type }}">
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Restock Section -->
                                        <div class="restock-section" style="{{ $item->purchase_type == 'new_item' ? 'display: none;' : '' }}">
                                            <div class="row">
                                                <div class="col-md-12 mb-2">
                                                    <label class="form-label">Material <span class="text-danger">*</span></label>
                                                    <select name="items[{{ $itemIndex }}][material_id]" class="form-select material-select" {{ !$itemIsEditable ? 'disabled' : '' }}>
                                                        @if($item->material_id && $item->material)
                                                            <option value="{{ $item->material_id }}" selected>{{ $item->material->name }}</option>
                                                        @else
                                                            <option value="">Type to search material...</option>
                                                        @endif
                                                    </select>
                                                    @if(!$itemIsEditable)
                                                        <input type="hidden" name="items[{{ $itemIndex }}][material_id]" value="{{ $item->material_id }}">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- New Item Section -->
                                        <div class="newitem-section" style="{{ $item->purchase_type == 'restock' ? 'display: none;' : '' }}">
                                            <div class="row">
                                                <div class="col-md-12 mb-2">
                                                    <label class="form-label">New Item Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[{{ $itemIndex }}][new_item_name]" class="form-control" value="{{ $item->new_item_name }}" {{ !$itemIsEditable ? 'readonly' : '' }}>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Category & Unit - FOR ALL ITEMS -->
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                                <select name="items[{{ $itemIndex }}][category_id]" class="form-select category-select" {{ !$itemIsEditable ? 'disabled' : '' }}>
                                                    <option value="">Select Category</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if(!$itemIsEditable)
                                                    <input type="hidden" name="items[{{ $itemIndex }}][category_id]" value="{{ $item->category_id }}">
                                                @endif
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                                <select name="items[{{ $itemIndex }}][unit_id]" class="form-select unit-select" {{ !$itemIsEditable ? 'disabled' : '' }}>
                                                    <option value="">Select Unit</option>
                                                    @foreach($units as $unit)
                                                        <option value="{{ $unit->id }}" {{ $item->unit_id == $unit->id ? 'selected' : '' }}>
                                                            {{ $unit->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if(!$itemIsEditable)
                                                    <input type="hidden" name="items[{{ $itemIndex }}][unit_id]" value="{{ $item->unit_id }}">
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Quantity and Price -->
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" name="items[{{ $itemIndex }}][quantity]" class="form-control quantity" min="0.01" step="0.01" value="{{ $item->quantity }}" {{ !$itemIsEditable ? 'readonly' : '' }}>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                                <input type="number" name="items[{{ $itemIndex }}][unit_price]" class="form-control unit-price" min="0" step="0.01" value="{{ $item->unit_price }}" {{ !$itemIsEditable ? 'readonly' : '' }}>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Subtotal</label>
                                                <input type="text" class="form-control item-subtotal" readonly value="Rp {{ number_format($item->total_price, 0) }}">
                                            </div>
                                        </div>
                                        
                                        @if($item->note)
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <small class="text-muted">
                                                    <i class="fas fa-sticky-note me-1"></i>{{ $item->note }}
                                                </small>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <!-- NEW ITEM ROW TEMPLATE - SAME AS CREATE -->
                            <template id="itemRowTemplate">
                                <div class="item-row" data-index="__INDEX__">
                                    <div class="item-header">
                                        <div class="item-title">
                                            <i class="fas fa-cube"></i>
                                            Item #__INDEX_PLUS_ONE__ (New)
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Type <span class="text-danger">*</span></label>
                                            <select name="new_items[__INDEX__][purchase_type]" class="form-select purchase-type">
                                                <option value="restock" selected>Restock</option>
                                                <option value="new_item">New Item</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Restock Section -->
                                    <div class="restock-section">
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">Material <span class="text-danger">*</span></label>
                                                <select name="new_items[__INDEX__][material_id]" class="form-select material-select">
                                                    <option value="">Type to search material...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- New Item Section -->
                                    <div class="newitem-section" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">New Item Name <span class="text-danger">*</span></label>
                                                <input type="text" name="new_items[__INDEX__][new_item_name]" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Category & Unit - FOR ALL ITEMS -->
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            <select name="new_items[__INDEX__][category_id]" class="form-select category-select">
                                                <option value="">Select Category</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                                            <select name="new_items[__INDEX__][unit_id]" class="form-select unit-select">
                                                <option value="">Select Unit</option>
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Quantity and Price -->
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" name="new_items[__INDEX__][quantity]" class="form-control quantity" min="0.01" step="0.01" value="1">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                            <input type="number" name="new_items[__INDEX__][unit_price]" class="form-control unit-price" min="0" step="0.01">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Subtotal</label>
                                            <input type="text" class="form-control item-subtotal" readonly value="Rp 0">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            @if($purchase->status == 'pending')
                            <!-- Add Item button (bottom) -->
                            <div class="d-flex justify-content-end mt-2 mb-1">
                                <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                                    <i class="fas fa-plus me-1"></i>Add Item
                                </button>
                            </div>
                            @endif

                            <!-- Grand Total -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="total-box invoice-total-box">
                                        <div class="total-label">Grand Total All Items</div>
                                        <div class="total-amount" id="grandTotal">Rp {{ number_format($poItems->sum('invoice_total'), 0) }}</div>
                                    </div>
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
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
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
                                                   id="onlineOrder" value="0" {{ old('is_offline_order', $purchase->is_offline_order) == 0 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="onlineOrder">Online</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_offline_order" 
                                                   id="offlineOrder" value="1" {{ old('is_offline_order', $purchase->is_offline_order) == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="offlineOrder">Offline</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Section (ONLINE ONLY) -->
                        <div class="mb-4 shipping-section {{ old('is_offline_order', $purchase->is_offline_order) == 1 ? 'fast-hide' : '' }}">
                            <h6 class="section-header">
                                <i class="fas fa-shipping-fast me-2"></i>Shipping
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Resi Number</label>
                                    <input type="text" class="form-control" name="resi_number" id="resiNumber" value="{{ old('resi_number', $purchase->resi_number) }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Freight Cost</label>
                                    <input type="number" class="form-control" name="freight" id="freight" value="{{ old('freight', $purchase->freight ?? 0) }}" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="fas fa-sticky-note me-2"></i>Notes
                            </h6>
                            <textarea class="form-control" name="note" rows="2" placeholder="Add notes">{{ old('note', $purchase->note) }}</textarea>
                        </div>

                        <!-- Revision Info -->
                        @if(isset($revision_info) && $revision_info['total_revisions'] > 1)
                        <div class="mb-4">
                            <div class="alert alert-info d-flex align-items-center mb-0">
                                <i class="fas fa-history me-2"></i>
                                <div>
                                    <strong>Revision #{{ $revision_info['revision_number'] }} of {{ $revision_info['total_revisions'] }}</strong>
                                    @if($revision_info['total_revisions'] > 1)
                                        <br><small>Last revision: {{ $purchase->revision_at?->format('d/m/Y H:i:s') }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('project-purchases.show', $purchase->uid) }}" class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
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
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control border-1 rounded-2 py-2 px-3" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control border-1 rounded-2 py-2 px-3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control border-1 rounded-2 py-2 px-3">
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

<!-- Delete Item Form (hidden) -->
<form id="deleteItemForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({ width: '100%' });

    // Apply readonly to category/unit for existing restock items
    $('.item-row').each(function() {
        if ($(this).find('.purchase-type').val() === 'restock') {
            $(this).find('.category-select, .unit-select').addClass('select-readonly');
        }
    });

    let newItemIndex = {{ $poItems->count() }};
    const elements = {
        clientProjectSection: $('#clientProjectSection'),
        internalProjectSection: $('#internalProjectSection'),
        jobOrderSelect: $('#jobOrderSelect'),
        internalProjectSelect: $('#internalProjectSelect'),
        shippingSection: $('.shipping-section'),
        departmentId: $('#departmentId'),
        projectId: $('#projectId'),
        clientProjectDetails: $('#clientProjectDetails'),
        internalProjectDetails: $('#internalProjectDetails'),
        freight: $('#freight')
    };

    // Toggle Project Type
    $('input[name="project_type"]').change(function() {
        const isClient = $(this).val() === 'client';
        elements.clientProjectSection.toggleClass('fast-hide', !isClient);
        elements.internalProjectSection.toggleClass('fast-hide', isClient);
    });

    // Toggle Order Type
    $('input[name="is_offline_order"]').change(function() {
        elements.shippingSection.toggleClass('fast-hide', $(this).val() === '1');
    });

    // Job Order Details
    elements.jobOrderSelect.on('change', function() {
        const selected = $(this).find('option:selected');
        elements.departmentId.val(selected.data('deptid') || '');
        elements.projectId.val(selected.data('projid') || '');
        
        const deptName = selected.data('deptname');
        const projName = selected.data('projname');
        
        if (selected.val() && deptName && projName) {
            elements.clientProjectDetails.html(`
                <div><strong>Project:</strong> ${projName}</div>
                <div><strong>Department:</strong> ${deptName}</div>
            `);
        } else {
            elements.clientProjectDetails.html('<small class="text-muted">Select a job order to view details</small>');
        }
    });

    // Internal Project Details
    elements.internalProjectSelect.on('change', function() {
        const selected = $(this).find('option:selected');
        elements.departmentId.val(selected.data('department-id') || '');
        
        const project = selected.data('project');
        const dept = selected.data('department');
        const job = selected.data('job');
        
        if (selected.val() && project && dept && job) {
            elements.internalProjectDetails.html(`
                <div><strong>Project:</strong> ${project}</div>
                <div><strong>Job:</strong> ${job}</div>
                <div><strong>Department:</strong> ${dept}</div>
            `);
        } else {
            elements.internalProjectDetails.html('<small class="text-muted">Select an internal project to view details</small>');
        }
    });

    // Material Select2 AJAX — existing items (sudah punya selected value dari server)
    function initMaterialSelect2(sel) {
        const row = sel.closest('.item-row');
        sel.select2({
            width: '100%',
            placeholder: 'Type to search material...',
            minimumInputLength: 1,
            ajax: {
                url: '{{ route("project-purchases.materials.search") }}',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data.results }),
                cache: true,
            },
        });
        sel.on('select2:select', function(e) {
            const d = e.params.data;
            row.find('.unit-price').val(0);
            if (d.unit_id     && !row.find('.unit-select').val())     row.find('.unit-select').val(d.unit_id);
            if (d.category_id && !row.find('.category-select').val()) row.find('.category-select').val(d.category_id);
            calculateRowSubtotal(row);
            calculateGrandTotal();
        });
    }

    $('.material-select').each(function() { initMaterialSelect2($(this)); });

    // Quantity/Price change for existing items
    $('.quantity, .unit-price').on('input', function() {
        const row = $(this).closest('.item-row');
        calculateRowSubtotal(row);
        calculateGrandTotal();
    });

    // Function to calculate row subtotal
    function calculateRowSubtotal(row) {
        const qty = parseFloat(row.find('.quantity').val()) || 0;
        const price = parseFloat(row.find('.unit-price').val()) || 0;
        const subtotal = qty * price;
        row.find('.item-subtotal').val('Rp ' + formatNumber(subtotal));
        return subtotal;
    }

    // Function to calculate grand total
    function calculateGrandTotal() {
        let total = 0;
        $('.item-row').each(function() {
            const row = $(this);
            total += calculateRowSubtotal(row);
        });
        
        total += (parseFloat(elements.freight.val()) || 0);
        
        $('#grandTotal').text('Rp ' + formatNumber(total));
    }

    // Format number
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Freight change
    elements.freight.on('input', calculateGrandTotal);

    // Delete existing item
    $('.remove-item[data-item-uid]').click(function() {
        const itemUid = $(this).data('item-uid');
        const row = $(this).closest('.item-row');
        const materialName = row.find('.material-select option:selected').text().trim()
            || row.find('input[name*="new_item_name"]').val().trim()
            || 'this material';

        Swal.fire({
            title: 'Remove Material?',
            html: `Remove <strong>${materialName}</strong> from PO <strong>{{ $purchase->po_number }}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('#deleteItemForm');
                form.attr('action', '/project-purchases/' + itemUid);
                form.submit();
            }
        });
    });

    // Delete new item (not saved yet)
    $(document).on('click', '.item-row .remove-item:not([data-item-id])', function() {
        $(this).closest('.item-row').remove();
        calculateGrandTotal();
    });

    // Add New Item
    function addNewItem() {
        const template = document.getElementById('itemRowTemplate').innerHTML;
        const newRow = template.replace(/__INDEX__/g, newItemIndex)
                               .replace(/__INDEX_PLUS_ONE__/g, newItemIndex + 1);
        $('#itemsContainer').append(newRow);
        initializeNewRow(newItemIndex);
        newItemIndex++;
    }

    $('#addItemBtn').click(addNewItem);

    // Initialize new row
    function initializeNewRow(index) {
        const row = $(`.item-row[data-index="${index}"]`);
        
        // Toggle purchase type
        row.find('.purchase-type').change(function() {
            const isRestock = $(this).val() === 'restock';
            row.find('.restock-section').toggle(isRestock);
            row.find('.newitem-section').toggle(!isRestock);
            row.find('.category-select, .unit-select').toggleClass('select-readonly', isRestock);

            // Reset values
            if (isRestock) {
                row.find('input[name*="new_item_name"]').val('');
            } else {
                row.find('.material-select').val('');
                row.find('.unit-price').val('');
                row.find('.category-select').val('');
                row.find('.unit-select').val('');
            }
        });

        // Default type is restock — apply readonly on init
        row.find('.category-select, .unit-select').addClass('select-readonly');

        // Material select — pakai Select2 AJAX
        initMaterialSelect2(row.find('.material-select'));
        
        // Quantity/Price change
        row.find('.quantity, .unit-price').on('input', function() {
            calculateRowSubtotal(row);
            calculateGrandTotal();
        });
    }

    // Form validation before submit
    $('#purchaseForm').submit(function(e) {
        const errors = [];

        // Validate project
        const projectType = $('input[name="project_type"]:checked').val();
        if (projectType === 'client' && !elements.jobOrderSelect.val()) {
            errors.push('Job Order must be selected for Client Project.');
        }
        if (projectType === 'internal' && !elements.internalProjectSelect.val()) {
            errors.push('Internal Project must be selected.');
        }

        // Validate supplier
        if (!$('#supplierSelect').val()) {
            errors.push('Supplier must be selected.');
        }

        // Validate items
        const itemRows = $('.item-row').length;
        if (itemRows === 0) {
            errors.push('At least 1 material must be added.');
        }

        $('.item-row').each(function(index) {
            if ($(this).hasClass('readonly-item')) return true;

            const label = `Material #${index + 1}`;
            const type = $(this).find('.purchase-type').val();

            if (type === 'restock' && !$(this).find('.material-select').val()) {
                errors.push(`${label}: Material must be selected.`);
            } else if (type === 'new_item' && !$(this).find('input[name*="new_item_name"]').val().trim()) {
                errors.push(`${label}: Item name is required.`);
            }

            if (!$(this).find('.category-select').val()) {
                errors.push(`${label}: Category must be selected.`);
            }
            if (!$(this).find('.unit-select').val()) {
                errors.push(`${label}: Unit must be selected.`);
            }

            const qty = parseFloat($(this).find('.quantity').val());
            if (!qty || qty <= 0) {
                errors.push(`${label}: Quantity must be at least 1.`);
            }

            const price = parseFloat($(this).find('.unit-price').val());
            if (!price || price <= 0) {
                errors.push(`${label}: Unit price must be greater than 0.`);
            }
        });

        if (errors.length > 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Please fix the following:',
                html: '<ul class="text-start mb-0">' + errors.map(err => `<li>${err}</li>`).join('') + '</ul>',
                icon: 'warning',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'OK'
            });
            return false;
        }

        // Show loading
        $(this).find('button[type="submit"]').prop('disabled', true)
               .html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
    });

    // Add Supplier
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
                    Swal.fire({
                        title: 'Supplier Added',
                        text: `"${response.supplier.name}" has been added successfully.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
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

    // Initial calculations
    calculateGrandTotal();
});
</script>
@endsection