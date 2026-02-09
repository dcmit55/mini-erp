@extends('layouts.app')

@section('title', 'Create Purchase Order')

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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Basic Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small text-dark">PO Number <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('po_number') is-invalid @enderror" 
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
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('date') is-invalid @enderror" 
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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-project-diagram me-2 text-primary"></i>Project Type
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
                                        <select class="form-select border-1 rounded-2 py-2 px-3 select2-joborder @error('job_order_id') is-invalid @enderror" 
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
                                        <div id="clientProjectInfo" class="border rounded-2 p-2 bg-light">
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
                                        <select class="form-select border-1 rounded-2 py-2 px-3 select2-internal-project @error('internal_project_id') is-invalid @enderror" 
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
                                        <div id="internalProjectInfo" class="border rounded-2 p-2 bg-light">
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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-shopping-cart me-2 text-primary"></i>Purchase Type
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
                                            <select class="form-select border-1 rounded-2 py-2 px-3 select2-material @error('material_id') is-invalid @enderror" 
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
                                                   class="form-control border-1 rounded-2 py-2 px-3 @error('new_item_name') is-invalid @enderror" 
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
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('quantity') is-invalid @enderror" 
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
                                        <select class="form-select border-1 rounded-2 py-2 px-3 @error('unit_id') is-invalid @enderror" 
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
                                            <span class="input-group-text border-end-0 bg-light py-2">Rp</span>
                                            <input type="number" 
                                                   class="form-control border-1 rounded-2 py-2 px-3 @error('unit_price') is-invalid @enderror" 
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
                                        <select class="form-select border-1 rounded-2 py-2 px-3 @error('category_id') is-invalid @enderror" 
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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-truck me-2 text-primary"></i>Supplier Information
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
                                        <select class="form-select border-1 rounded-2 py-2 px-3 select2-supplier @error('supplier_id') is-invalid @enderror" 
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
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('new_supplier_name') is-invalid @enderror" 
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
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('new_supplier_contact') is-invalid @enderror" 
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
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('new_supplier_phone') is-invalid @enderror" 
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
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('new_supplier_email') is-invalid @enderror" 
                                               name="new_supplier_email" 
                                               value="{{ old('new_supplier_email') }}"
                                               placeholder="Optional email">
                                        @error('new_supplier_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-dark">Address</label>
                                        <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('new_supplier_address') is-invalid @enderror" 
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
                                                <input class="form-check-input new-supplier-order-type" type="radio" 
                                                       name="new_supplier_is_offline_order" 
                                                       id="newOnlineOrder" value="0" checked required>
                                                <label class="form-check-label d-flex align-items-center small" for="newOnlineOrder">
                                                    <i class="fas fa-globe me-1 fa-sm"></i>
                                                    Online
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input new-supplier-order-type" type="radio" 
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

                        <!-- Resi Number Section - SELALU TAMPIL, TAPI TIDAK WAJIB -->
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-truck-loading me-2 text-primary"></i>Shipping Information
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small text-dark">Resi Number</label>
                                    <input type="text" 
                                           class="form-control border-1 rounded-2 py-2 px-3 @error('resi_number') is-invalid @enderror" 
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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-calculator me-2 text-primary"></i>Financial Details
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
                                        <span class="input-group-text border-end-0 bg-light py-2">Rp</span>
                                        <input type="number" 
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('freight') is-invalid @enderror" 
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
                                        <span class="input-group-text border-end-0 bg-light py-2">Rp</span>
                                        <input type="number" 
                                               class="form-control border-1 rounded-2 py-2 px-3 @error('other_costs') is-invalid @enderror" 
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
                        <div class="mb-4">
                            <h6 class="fw-medium text-dark mb-2">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Additional Notes
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label small text-dark">Notes</label>
                                <textarea class="form-control border-1 rounded-2 py-2 px-3 @error('note') is-invalid @enderror" 
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
                               class="btn btn-outline-secondary rounded-2 px-3 btn-sm">
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

    .form-label.small {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }

    /* Radio Button Styling */
    .form-check {
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }
    
    .form-check-input {
        width: 1.1em;
        height: 1.1em;
        margin-top: 0;
        margin-right: 0.5em;
        cursor: pointer;
    }
    
    .form-check-input:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }
    
    .form-check-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
    }
    
    .form-check-label {
        cursor: pointer;
        display: flex;
        align-items: center;
        font-size: 0.85rem;
        margin-bottom: 0;
    }
    
    .form-check-label i {
        font-size: 0.8rem;
        margin-right: 0.5rem;
        color: #64748b;
    }
    
    .form-check-input:checked ~ .form-check-label i {
        color: #4f46e5;
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

    .me-3 {
        margin-right: 1rem !important;
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

    /* Project Info Display */
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
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2-joborder, .select2-internal-project, .select2-material, .select2-supplier').select2({
        placeholder: "Select an option",
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 5
    });

    // Elements
    const clientProjectRadio = document.getElementById('clientProjectType');
    const internalProjectRadio = document.getElementById('internalProjectType');
    const clientProjectSection = document.querySelector('.client-project-section');
    const internalProjectSection = document.querySelector('.internal-project-section');
    const jobOrderSelect = document.getElementById('jobOrderSelect');
    const internalProjectSelect = document.getElementById('internalProjectSelect');
    const departmentIdInput = document.getElementById('departmentId');
    const projectIdInput = document.getElementById('projectId');
    
    const restockRadio = document.getElementById('restockType');
    const newItemRadio = document.getElementById('newItemType');
    const restockSection = document.getElementById('restockSection');
    const newItemSection = document.getElementById('newItemSection');
    const materialSelect = document.getElementById('materialSelect');
    const newItemName = document.getElementById('newItemName');
    const unitSelect = document.getElementById('unitSelect');
    const categorySelect = document.getElementById('categorySelect');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unitPrice');
    const freightInput = document.getElementById('freight');
    const otherCostsInput = document.getElementById('otherCosts');
    
    // Supplier elements
    const existingSupplierRadio = document.getElementById('existingSupplier');
    const newSupplierRadio = document.getElementById('newSupplier');
    const existingSupplierSection = document.getElementById('existingSupplierSection');
    const newSupplierSection = document.getElementById('newSupplierSection');
    const supplierSelect = document.getElementById('supplierSelect');
    const newSupplierName = document.getElementById('newSupplierName');
    
    // Order type elements
    const onlineOrderRadio = document.getElementById('onlineOrder');
    const offlineOrderRadio = document.getElementById('offlineOrder');
    const newSupplierOrderTypes = document.querySelectorAll('.new-supplier-order-type');
    
    // Resi elements
    const resiNumberInput = document.getElementById('resiNumber');
    
    // Price elements
    const totalPriceInput = document.getElementById('totalPrice');
    const invoiceTotalInput = document.getElementById('invoiceTotal');
    const displayTotalPrice = document.getElementById('displayTotalPrice');
    const displayInvoiceTotal = document.getElementById('displayInvoiceTotal');
    
    // Project info elements
    const clientProjectInfo = document.getElementById('clientProjectInfo');
    const internalProjectInfo = document.getElementById('internalProjectInfo');

    // Toggle project type sections
    function toggleProjectType() {
        const isClientProject = clientProjectRadio.checked;
        
        console.log('Toggle Project Type - Is Client Project:', isClientProject);
        
        if (isClientProject) {
            clientProjectSection.classList.remove('d-none');
            internalProjectSection.classList.add('d-none');
            
            // Set required attributes
            jobOrderSelect.required = true;
            internalProjectSelect.required = false;
            
            // Enable/disable select2
            $(jobOrderSelect).prop('disabled', false).trigger('change');
            $(internalProjectSelect).prop('disabled', true).trigger('change');
            
            // Clear internal project field
            internalProjectSelect.value = '';
            $(internalProjectSelect).trigger('change');
        } else {
            clientProjectSection.classList.add('d-none');
            internalProjectSection.classList.remove('d-none');
            
            // Set required attributes
            jobOrderSelect.required = false;
            internalProjectSelect.required = true;
            
            // Enable/disable select2
            $(jobOrderSelect).prop('disabled', true).trigger('change');
            $(internalProjectSelect).prop('disabled', false).trigger('change');
            
            // Clear client project field
            jobOrderSelect.value = '';
            $(jobOrderSelect).trigger('change');
        }
        
        // Clear hidden inputs when switching project type
        departmentIdInput.value = '';
        projectIdInput.value = '';
        clearProjectInfo();
    }

    // Toggle purchase type sections
    function togglePurchaseType() {
        const isRestock = restockRadio.checked;
        
        console.log('Toggle Purchase Type - Is Restock:', isRestock);
        
        if (isRestock) {
            restockSection.classList.remove('d-none');
            newItemSection.classList.add('d-none');
            materialSelect.required = true;
            newItemName.required = false;
            
            // Enable/disable fields
            materialSelect.disabled = false;
            newItemName.disabled = true;
        } else {
            restockSection.classList.add('d-none');
            newItemSection.classList.remove('d-none');
            materialSelect.required = false;
            newItemName.required = true;
            
            // Enable/disable fields
            materialSelect.disabled = true;
            newItemName.disabled = false;
        }
    }

    // Toggle supplier type sections
    function toggleSupplierType() {
        const isExistingSupplier = existingSupplierRadio.checked;
        
        console.log('Toggle Supplier Type - Is Existing Supplier:', isExistingSupplier);
        
        if (isExistingSupplier) {
            existingSupplierSection.classList.remove('d-none');
            newSupplierSection.classList.add('d-none');
            
            // Set required attributes
            supplierSelect.required = true;
            newSupplierName.required = false;
            
            // Enable/disable fields
            supplierSelect.disabled = false;
            newSupplierName.disabled = true;
            document.querySelectorAll('input[name="is_offline_order"]').forEach(radio => {
                radio.disabled = false;
            });
        } else {
            existingSupplierSection.classList.add('d-none');
            newSupplierSection.classList.remove('d-none');
            
            // Set required attributes
            supplierSelect.required = false;
            newSupplierName.required = true;
            
            // Enable/disable fields
            supplierSelect.disabled = true;
            newSupplierName.disabled = false;
            document.querySelectorAll('input[name="is_offline_order"]').forEach(radio => {
                radio.disabled = true;
            });
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
        const otherCosts = parseInt(otherCostsInput.value) || 0;
        
        const totalPrice = quantity * unitPrice;
        const invoiceTotal = totalPrice + freight + otherCosts;
        
        // Update hidden inputs
        totalPriceInput.value = totalPrice;
        invoiceTotalInput.value = invoiceTotal;
        
        // Update display
        displayTotalPrice.textContent = formatCurrency(totalPrice);
        displayInvoiceTotal.textContent = formatCurrency(invoiceTotal);
    }

    // Clear project info display
    function clearProjectInfo() {
        clientProjectInfo.innerHTML = '<small class="text-muted">Select a job order to see details</small>';
        internalProjectInfo.innerHTML = '<small class="text-muted">Select an internal project to see details</small>';
    }

    // Display client project info
    function displayClientProjectInfo(departmentId, departmentName, projectId, projectName) {
        if (departmentId && projectId) {
            clientProjectInfo.innerHTML = `
                <div class="w-100">
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="project-info-label">Department:</div>
                        <div class="project-info-value">${departmentName}</div>
                    </div>
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="project-info-label">Project:</div>
                        <div class="project-info-value">${projectName}</div>
                    </div>
                </div>
            `;
            
            // Set hidden inputs
            departmentIdInput.value = departmentId;
            projectIdInput.value = projectId;
        }
    }

    // Display internal project info
    function displayInternalProjectInfo(project, department, job, description, departmentId) {
        internalProjectInfo.innerHTML = `
            <div class="w-100">
                <div class="project-info-item">
                    <div class="project-info-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="project-info-label">Project:</div>
                    <div class="project-info-value">${project}</div>
                </div>
                <div class="project-info-item">
                    <div class="project-info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="project-info-label">Department:</div>
                    <div class="project-info-value">${department}</div>
                </div>
                <div class="project-info-item">
                    <div class="project-info-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="project-info-label">Job:</div>
                    <div class="project-info-value">${job}</div>
                </div>
                ${description ? `
                <div class="project-info-item">
                    <div class="project-info-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="project-info-label">Description:</div>
                    <div class="project-info-value small">${description}</div>
                </div>
                ` : ''}
            </div>
        `;
        
        // Set department ID directly from data attribute
        departmentIdInput.value = departmentId || '';
        projectIdInput.value = ''; // Clear project ID for internal projects
    }

    // Event listener for job order selection
    function handleJobOrderSelection() {
        const selectedOption = $(jobOrderSelect).find('option:selected');
        
        // Debug: Log raw HTML and all data attributes
        console.log('Job Order - Raw HTML:', selectedOption[0]?.outerHTML);
        console.log('Job Order - All data:', selectedOption.data());
        
        const departmentId = selectedOption.data('deptid');
        const departmentName = selectedOption.data('deptname');
        const projectId = selectedOption.data('projid');
        const projectName = selectedOption.data('projname');
        
        console.log('Job Order Selected:', { departmentId, departmentName, projectId, projectName });
        
        if (departmentId && projectId) {
            displayClientProjectInfo(departmentId, departmentName, projectId, projectName);
        } else {
            clearProjectInfo();
            departmentIdInput.value = '';
            projectIdInput.value = '';
        }
    }
    
    $(jobOrderSelect).on('select2:select', handleJobOrderSelection);
    $(jobOrderSelect).on('change', handleJobOrderSelection);

    // Event listener for internal project selection
    $(internalProjectSelect).on('select2:select', function(e) {
        const selectedOption = $(this).find('option:selected');
        const project = selectedOption.data('project');
        const department = selectedOption.data('department');
        const job = selectedOption.data('job');
        const description = selectedOption.data('description');
        const departmentId = selectedOption.data('department-id');
        
        console.log('Internal Project Selected:', { project, department, job, description, departmentId });
        
        if (project && department && job) {
            displayInternalProjectInfo(project, department, job, description, departmentId);
        } else {
            clearProjectInfo();
            departmentIdInput.value = '';
            projectIdInput.value = '';
        }
    });

    // Auto-fill unit price, unit and category when material is selected
    $(materialSelect).on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const materialPrice = selectedOption.getAttribute('data-price');
        const unitId = selectedOption.getAttribute('data-unit-id');
        const categoryId = selectedOption.getAttribute('data-category-id');
        
        console.log('Material Selected:', { materialPrice, unitId, categoryId });
        
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

    // Event Listeners for radio buttons
    clientProjectRadio.addEventListener('change', toggleProjectType);
    internalProjectRadio.addEventListener('change', toggleProjectType);
    restockRadio.addEventListener('change', togglePurchaseType);
    newItemRadio.addEventListener('change', togglePurchaseType);
    existingSupplierRadio.addEventListener('change', toggleSupplierType);
    newSupplierRadio.addEventListener('change', toggleSupplierType);
    
    // Calculate totals on input change
    [quantityInput, unitPriceInput, freightInput, otherCostsInput].forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('change', calculateTotals);
    });

    // Form validation
    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const unitPrice = parseInt(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value);
        const isClientProject = clientProjectRadio.checked;
        const isRestock = restockRadio.checked;
        const isExistingSupplier = existingSupplierRadio.checked;
        const poNumber = document.getElementById('poNumber').value.trim();
        
        console.log('Form Submit:', {
            poNumber,
            isClientProject,
            isRestock,
            isExistingSupplier,
            unitPrice,
            quantity
        });
        
        // Clear previous error states
        const errorFields = document.querySelectorAll('.is-invalid');
        errorFields.forEach(field => field.classList.remove('is-invalid'));
        
        // Validate PO Number
        if (!poNumber) {
            const poField = document.getElementById('poNumber');
            poField.classList.add('is-invalid');
            poField.focus();
            alert('Please enter PO Number');
            return;
        }
        
        // Validate project type selection
        if (isClientProject) {
            if (!jobOrderSelect.value) {
                jobOrderSelect.classList.add('is-invalid');
                alert('Please select a job order for client project');
                jobOrderSelect.focus();
                return;
            }
        } else {
            if (!internalProjectSelect.value) {
                internalProjectSelect.classList.add('is-invalid');
                alert('Please select an internal project');
                internalProjectSelect.focus();
                return;
            }
        }
        
        // Validate department is filled
        if (!departmentIdInput.value) {
            alert('Department information is missing. Please select a valid job order or internal project.');
            return;
        }
        
        // Validate purchase type specific fields
        if (isRestock) {
            if (!materialSelect.value) {
                materialSelect.classList.add('is-invalid');
                alert('Please select a material for restock purchase');
                materialSelect.focus();
                return;
            }
        } else {
            if (!newItemName.value.trim()) {
                newItemName.classList.add('is-invalid');
                alert('Please enter new item name');
                newItemName.focus();
                return;
            }
        }
        
        // Validate supplier type specific fields
        if (isExistingSupplier) {
            if (!supplierSelect.value) {
                supplierSelect.classList.add('is-invalid');
                alert('Please select a supplier');
                supplierSelect.focus();
                return;
            }
        } else {
            if (!newSupplierName.value.trim()) {
                newSupplierName.classList.add('is-invalid');
                alert('Please enter new supplier name');
                newSupplierName.focus();
                return;
            }
        }
        
        // Validate required fields
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;
        
        requiredFields.forEach(field => {
            if (!field.value.trim() || field.value === '') {
                // Skip supplier select if using new supplier
                if (field.name === 'supplier_id' && !isExistingSupplier) return;
                // Skip internal project select if using client project
                if (field.name === 'internal_project_id' && isClientProject) return;
                // Skip job order select if using internal project
                if (field.name === 'job_order_id' && !isClientProject) return;
                // Skip material select if new item
                if (field.name === 'material_id' && !isRestock) return;
                // Skip new item name if restock
                if (field.name === 'new_item_name' && isRestock) return;
                // Skip is_offline_order if new supplier
                if (field.name === 'is_offline_order' && !isExistingSupplier) return;
                // Skip new_supplier_is_offline_order if existing supplier
                if (field.name === 'new_supplier_is_offline_order' && isExistingSupplier) return;
                
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                field.classList.add('is-invalid');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return;
        }
        
        if (unitPrice <= 0) {
            alert('Unit price must be greater than 0');
            unitPriceInput.classList.add('is-invalid');
            unitPriceInput.focus();
            return;
        }
        
        if (quantity <= 0) {
            alert('Quantity must be greater than 0');
            quantityInput.classList.add('is-invalid');
            quantityInput.focus();
            return;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
        submitBtn.disabled = true;
        
        // Submit form
        setTimeout(() => {
            this.submit();
        }, 100);
    });

    // Initialize on page load
    console.log('Initializing form...');
    
    // Debug: Check if job order options have data attributes
    console.log('Job Order Options Debug:');
    $('#jobOrderSelect option').each(function() {
        console.log('Option:', $(this).val(), 'data-dept-id:', $(this).data('department-id'));
    });
    
    toggleProjectType();
    togglePurchaseType();
    toggleSupplierType();
    calculateTotals();
    
    // Auto-calculate with debounce
    let calculateTimeout;
    [quantityInput, unitPriceInput, freightInput, otherCostsInput].forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(calculateTimeout);
            calculateTimeout = setTimeout(calculateTotals, 300);
        });
    });
});
</script>
@endsection