@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('css/globals.css') }}" rel="stylesheet">
{{-- CSS spesifik untuk halaman ini --}}
<style>
/* HANYA CSS SPESIFIK untuk halaman ini */
#clientProjectInfo, #internalProjectInfo {
    min-height: 60px;
    display: flex;
    align-items: center;
}

#clientProjectInfo .project-info-item,
#internalProjectInfo .project-info-item {
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
}

#clientProjectInfo .project-info-item:last-child,
#internalProjectInfo .project-info-item:last-child {
    margin-bottom: 0;
}

#clientProjectInfo .project-info-icon,
#internalProjectInfo .project-info-icon {
    width: 16px;
    margin-right: 8px;
    color: #64748b;
    font-size: 0.8rem;
}

#clientProjectInfo .project-info-label,
#internalProjectInfo .project-info-label {
    font-size: 0.8rem;
    color: #64748b;
    min-width: 80px;
}

#clientProjectInfo .project-info-value,
#internalProjectInfo .project-info-value {
    font-size: 0.85rem;
    color: #334155;
    font-weight: 500;
}

/* Spacing untuk halaman ini */
.mb-2 { margin-bottom: 0.5rem !important; }
.mb-3 { margin-bottom: 1rem !important; }
.mb-4 { margin-bottom: 1.5rem !important; }
.me-3 { margin-right: 1rem !important; }
</style>
@endsection

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
                    <h5 class="text-dark mb-1 mt-2">Create New Purchase Order</h5>
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

                    <form action="{{ route('project-purchases.store') }}" method="POST" id="purchaseForm">
                        @csrf
                        
                        <!-- PO Number & Date -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-info-circle"></i>Basic Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small text-dark">PO Number <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('po_number') is-invalid @enderror" 
                                           name="po_number" 
                                           id="poNumber"
                                           value="{{ old('po_number') }}" 
                                           required
                                           placeholder="Enter PO number (e.g., PO-001)">
                                    @error('po_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">
                                        Masukkan nomor Purchase Order sesuai format perusahaan
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small text-dark">Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control @error('date') is-invalid @enderror" 
                                           name="date" 
                                           value="{{ old('date', date('Y-m-d')) }}" 
                                           required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Project Type Selection -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-project-diagram"></i>Project Type
                            </h6>
                            
                            <div class="mb-2">
                                <label class="form-label small text-dark mb-1">Select Project Type <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="clientProjectType" value="client" checked required>
                                        <label class="form-check-label d-flex align-items-center small" for="clientProjectType">
                                            <i class="fas fa-users me-1 fa-sm"></i>
                                            Client Project
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="project_type" 
                                               id="internalProjectType" value="internal">
                                        <label class="form-check-label d-flex align-items-center small" for="internalProjectType">
                                            <i class="fas fa-building me-1 fa-sm"></i>
                                            Internal Project
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Client Project Section -->
                            <div class="client-project-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small text-dark">Job Order <span class="text-danger">*</span></label>
                                        <select class="form-select select2 @error('job_order_id') is-invalid @enderror" 
                                                name="job_order_id" 
                                                id="jobOrderSelect" 
                                                required>
                                            <option value="">Select Job Order</option>
                                            @foreach($jobOrders as $jobOrder)
                                                <option value="{{ $jobOrder->id }}" 
                                                        data-deptid="{{ $jobOrder->department_id }}"
                                                        data-deptname="{{ $jobOrder->department_name ?? '' }}"
                                                        data-projid="{{ $jobOrder->project_id }}"
                                                        data-projname="{{ $jobOrder->project_name ?? '' }}"
                                                        {{ old('job_order_id') == $jobOrder->id ? 'selected' : '' }}>
                                                    {{ $jobOrder->name }}
                                                </option>
                                            @endforeach
                                        </select>   
                                        @error('job_order_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted small mt-1">
                                            Select job order to auto-fill department and project
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small text-dark">Project Details</label>
                                        <div id="clientProjectInfo" class="project-info-display">
                                            <small class="text-muted">Select a job order to see details</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Internal Project Section -->
                            <div class="internal-project-section d-none">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small text-dark">Internal Project <span class="text-danger">*</span></label>
                                        <select class="form-select select2 @error('internal_project_id') is-invalid @enderror" 
                                                name="internal_project_id" 
                                                id="internalProjectSelect">
                                            <option value="">Select Internal Project</option>
                                            @foreach($internal_projects as $internalProject)
                                                <option value="{{ $internalProject->id }}" 
                                                        data-project="{{ $internalProject->project }}"
                                                        data-department="{{ $internalProject->department }}"
                                                        data-job="{{ $internalProject->job }}"
                                                        data-description="{{ $internalProject->description }}"
                                                        data-department-id="{{ $internalProject->department_id }}"
                                                        {{ old('internal_project_id') == $internalProject->id ? 'selected' : '' }}>
                                                    {{ $internalProject->job }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('internal_project_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted small mt-1">Select internal project to auto-fill details</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small text-dark">Internal Project Details</label>
                                        <div id="internalProjectInfo" class="project-info-display">
                                            <small class="text-muted">Select an internal project to see details</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Department Input -->
                        <input type="hidden" name="department_id" id="departmentId" required>
                        <input type="hidden" name="project_id" id="projectId">

                        <!-- Purchase Type -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-shopping-cart"></i>Purchase Type
                            </h6>
                            
                            <div class="mb-2">
                                <label class="form-label small text-dark mb-1">Select Purchase Type <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="purchase_type" 
                                               id="restockType" value="restock" checked required>
                                        <label class="form-check-label d-flex align-items-center small" for="restockType">
                                            <i class="fas fa-redo me-1 fa-sm"></i>
                                            Restock
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="purchase_type" 
                                               id="newItemType" value="new_item">
                                        <label class="form-check-label d-flex align-items-center small" for="newItemType">
                                            <i class="fas fa-plus-circle me-1 fa-sm"></i>
                                            New Item
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Material Details -->
                            <div class="mt-2">
                                <!-- Restock Section -->
                                <div id="restockSection">
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label small text-dark">Material <span class="text-danger">*</span></label>
                                            <select class="form-select select2 @error('material_id') is-invalid @enderror" 
                                                    name="material_id" 
                                                    id="materialSelect"
                                                    required>
                                                <option value="">Select Material</option>
                                                @foreach($materials as $material)
                                                    <option value="{{ $material->id }}" 
                                                            data-price="{{ $material->price ?? 0 }}"
                                                            data-unit-id="{{ $material->unit_id ?? '' }}"
                                                            data-category-id="{{ $material->category_id ?? '' }}"
                                                            {{ old('material_id') == $material->id ? 'selected' : '' }}>
                                                        {{ $material->name }} 
                                                        @if($material->price)
                                                            (Rp {{ number_format($material->price, 0) }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('material_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- New Item Section -->
                                <div id="newItemSection" class="d-none">
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label small text-dark">New Item Name <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control @error('new_item_name') is-invalid @enderror" 
                                                   name="new_item_name" 
                                                   id="newItemName"
                                                   value="{{ old('new_item_name') }}"
                                                   placeholder="Enter new item name">
                                            @error('new_item_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label small text-dark">&nbsp;</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Quantity, Unit & Price -->
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small text-dark">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" 
                                               class="form-control @error('quantity') is-invalid @enderror" 
                                               name="quantity" 
                                               id="quantity" 
                                               min="1" 
                                               step="1" 
                                               value="{{ old('quantity', 1) }}" 
                                               required>
                                        @error('quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small text-dark">Unit <span class="text-danger">*</span></label>
                                        <select class="form-select @error('unit_id') is-invalid @enderror" 
                                                name="unit_id" 
                                                id="unitSelect" 
                                                required>
                                            <option value="">Select Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unit_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small text-dark">Unit Price (Rp) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" 
                                                   class="form-control @error('unit_price') is-invalid @enderror" 
                                                   name="unit_price" 
                                                   id="unitPrice" 
                                                   step="100" 
                                                   min="0" 
                                                   value="{{ old('unit_price') }}"
                                                   placeholder="0" 
                                                   required>
                                        </div>
                                        @error('unit_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small text-dark">Category <span class="text-danger">*</span></label>
                                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                                name="category_id" 
                                                id="categorySelect" 
                                                required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Information -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-truck"></i>Supplier Information
                            </h6>
                            
                            <!-- Supplier Type Selection -->
                            <div class="mb-2">
                                <label class="form-label small text-dark mb-1">Supplier Type <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="supplier_type" 
                                               id="existingSupplier" value="existing" checked required>
                                        <label class="form-check-label d-flex align-items-center small" for="existingSupplier">
                                            <i class="fas fa-database me-1 fa-sm"></i>
                                            Existing Supplier
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="supplier_type" 
                                               id="newSupplier" value="new">
                                        <label class="form-check-label d-flex align-items-center small" for="newSupplier">
                                            <i class="fas fa-plus-circle me-1 fa-sm"></i>
                                            New Supplier
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Supplier Section -->
                            <div id="existingSupplierSection">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Supplier <span class="text-danger">*</span></label>
                                        <select class="form-select select2 @error('supplier_id') is-invalid @enderror" 
                                                name="supplier_id" 
                                                id="supplierSelect" 
                                                required>
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('supplier_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Order Type <span class="text-danger">*</span></label>
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" name="is_offline_order" 
                                                       id="onlineOrder" value="0" checked required>
                                                <label class="form-check-label d-flex align-items-center small" for="onlineOrder">
                                                    <i class="fas fa-globe me-1 fa-sm"></i>
                                                    Online
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="is_offline_order" 
                                                       id="offlineOrder" value="1">
                                                <label class="form-check-label d-flex align-items-center small" for="offlineOrder">
                                                    <i class="fas fa-store me-1 fa-sm"></i>
                                                    Offline
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Supplier Section -->
                            <div id="newSupplierSection" class="d-none">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Supplier Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('new_supplier_name') is-invalid @enderror" 
                                               name="new_supplier_name" 
                                               id="newSupplierName"
                                               value="{{ old('new_supplier_name') }}"
                                               placeholder="Enter supplier name">
                                        @error('new_supplier_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Contact Person</label>
                                        <input type="text" 
                                               class="form-control @error('new_supplier_contact') is-invalid @enderror" 
                                               name="new_supplier_contact" 
                                               value="{{ old('new_supplier_contact') }}"
                                               placeholder="Optional contact person">
                                        @error('new_supplier_contact')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Phone Number</label>
                                        <input type="text" 
                                               class="form-control @error('new_supplier_phone') is-invalid @enderror" 
                                               name="new_supplier_phone" 
                                               value="{{ old('new_supplier_phone') }}"
                                               placeholder="Optional phone number">
                                        @error('new_supplier_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-dark">Email</label>
                                        <input type="email" 
                                               class="form-control @error('new_supplier_email') is-invalid @enderror" 
                                               name="new_supplier_email" 
                                               value="{{ old('new_supplier_email') }}"
                                               placeholder="Optional email">
                                        @error('new_supplier_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-dark">Address</label>
                                        <textarea class="form-control @error('new_supplier_address') is-invalid @enderror" 
                                                  name="new_supplier_address" 
                                                  rows="2"
                                                  placeholder="Optional address">{{ old('new_supplier_address') }}</textarea>
                                        @error('new_supplier_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-dark">Order Type <span class="text-danger">*</span></label>
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" 
                                                       name="new_supplier_is_offline_order" 
                                                       id="newOnlineOrder" value="0" checked required>
                                                <label class="form-check-label d-flex align-items-center small" for="newOnlineOrder">
                                                    <i class="fas fa-globe me-1 fa-sm"></i>
                                                    Online
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="new_supplier_is_offline_order" 
                                                       id="newOfflineOrder" value="1">
                                                <label class="form-check-label d-flex align-items-center small" for="newOfflineOrder">
                                                    <i class="fas fa-store me-1 fa-sm"></i>
                                                    Offline
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resi Number Section -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-truck-loading"></i>Shipping Information
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small text-dark">Resi Number</label>
                                    <input type="text" 
                                           class="form-control @error('resi_number') is-invalid @enderror" 
                                           name="resi_number" 
                                           id="resiNumber"
                                           value="{{ old('resi_number') }}"
                                           placeholder="Enter resi/shipping number (optional)">
                                    @error('resi_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">
                                        Optional: Enter resi number for tracking (recommended for online orders)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Details -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-calculator"></i>Financial Details
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="border rounded-2 p-3 bg-light text-center">
                                        <label class="form-label small text-muted mb-1">Total Price</label>
                                        <div class="small text-primary fw-medium" id="displayTotalPrice">
                                            Rp 0
                                        </div>
                                        <input type="hidden" name="total_price" id="totalPrice" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small text-dark">Freight Cost (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" 
                                               class="form-control @error('freight') is-invalid @enderror" 
                                               name="freight" 
                                               id="freight" 
                                               step="100" 
                                               min="0" 
                                               value="{{ old('freight', 0) }}" 
                                               placeholder="0">
                                    </div>
                                    @error('freight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">Optional shipping costs</div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small text-dark">Other Costs (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" 
                                               class="form-control @error('other_costs') is-invalid @enderror" 
                                               name="other_costs" 
                                               id="otherCosts" 
                                               step="100" 
                                               min="0" 
                                               value="{{ old('other_costs', 0) }}" 
                                               placeholder="0">
                                    </div>
                                    @error('other_costs')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small mt-1">Additional costs</div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="border rounded-2 p-3 bg-success bg-opacity-10 text-center">
                                        <label class="form-label small text-muted mb-1">Invoice Total</label>
                                        <div class="small text-success fw-medium" id="displayInvoiceTotal">
                                            Rp 0
                                        </div>
                                        <input type="hidden" name="invoice_total" id="invoiceTotal" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="form-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-sticky-note"></i>Additional Notes
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label small text-dark">Notes</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" 
                                          name="note" 
                                          rows="3"
                                          placeholder="Optional notes or special instructions...">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-3 border-top">
                            <a href="{{ route('project-purchases.index') }}" 
                               class="btn btn-outline-secondary btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i>Create Purchase Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="{{ asset('js/form-utils.js') }}"></script>
<script src="{{ asset('js/purchase-order.js') }}"></script>
@endsection