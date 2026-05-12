{{-- resources/views/procurement/Indo-Purchase/create.blade.php --}}
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

        .card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

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

        .text-danger {
            color: #dc2626 !important;
        }

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

        .rounded-2 {
            border-radius: 8px !important;
        }

        .fast-hide {
            display: none !important;
        }

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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

        /* Disabled field styling */
        select:disabled,
        input:disabled {
            background-color: #e9ecef;
            opacity: 0.7;
            cursor: not-allowed;
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
                        <a href="{{ route('indo-purchases.index') }}"
                            class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                        <h5 class="text-dark mb-1 mt-2">Create New Purchase Order</h5>
                        <p class="text-muted small mb-0">Complete purchase order information</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div>{{ session('error') }}</div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex align-items-center mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('indo-purchases.store') }}" method="POST" id="purchaseForm">
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
                                            name="po_number" value="{{ old('po_number') }}" required>
                                        @error('po_number')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted small mt-1">Enter PO number according to your
                                            format</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control border-1 rounded-2 py-2 px-3 @error('date') is-invalid @enderror"
                                            name="date" value="{{ old('date', date('Y-m-d')) }}" required>
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
                                                id="clientProjectType" value="client"
                                                {{ old('project_type') == 'client' ? 'checked' : 'checked' }}>
                                            <label class="form-check-label" for="clientProjectType">Client Project</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="project_type"
                                                id="internalProjectType" value="internal"
                                                {{ old('project_type') == 'internal' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="internalProjectType">Internal
                                                Project</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Client Project -->
                                <div id="clientProjectSection"
                                    class="mb-3 {{ old('project_type') == 'internal' ? 'fast-hide' : '' }}">
                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Job Order <span class="text-danger">*</span></label>
                                            <select
                                                class="form-select select2 border-1 rounded-2 py-2 px-3 @error('job_order_id') is-invalid @enderror"
                                                name="job_order_id" id="jobOrderSelect">
                                                <option value="">Select Job Order</option>
                                                @foreach ($jobOrders as $jobOrder)
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
                                <div id="internalProjectSection"
                                    class="mb-3 {{ old('project_type') == 'internal' ? '' : 'fast-hide' }}">
                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Internal Project <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-select select2 border-1 rounded-2 py-2 px-3 @error('internal_project_id') is-invalid @enderror"
                                                name="internal_project_id" id="internalProjectSelect">
                                                <option value="">Select Internal Project</option>
                                                @foreach ($internal_projects as $internalProject)
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
                                                <small class="text-muted">Select an internal project to view
                                                    details</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden Fields -->
                            <input type="hidden" name="department_id" id="departmentId"
                                value="{{ old('department_id') }}">
                            <input type="hidden" name="project_id" id="projectId" value="{{ old('project_id') }}">

                            <!-- ===== MULTIPLE ITEMS SECTION ===== -->
                            <div class="mb-4">
                                <div class="section-header">
                                    <div>
                                        <i class="fas fa-box me-2"></i>Items Information
                                    </div>
                                </div>

                                <div id="itemsContainer">
                                    <!-- Items will be added via JavaScript -->
                                </div>

                                <!-- Add Item button (bottom) -->
                                <div class="d-flex justify-content-end mt-2 mb-1">
                                    <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                                        <i class="fas fa-plus me-1"></i>Add Item
                                    </button>
                                </div>

                                <!-- Grand Total -->
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="total-box invoice-total-box">
                                            <div class="total-label">Grand Total All Items</div>
                                            <div class="total-amount" id="grandTotal">Rp 0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TEMPLATE ITEM ROW -->
                            <template id="itemRowTemplate">
                                <div class="item-row" data-index="__INDEX__">
                                    <div class="item-header">
                                        <div class="item-title">
                                            <i class="fas fa-cube"></i>
                                            Item #__INDEX_PLUS_ONE__
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Type <span class="text-danger">*</span></label>
                                            <select class="form-select purchase-type" data-index="__INDEX__">
                                                <option value="restock">Restock</option>
                                                <option value="new_item">New Item</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Restock Section -->
                                    <div class="restock-section">
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">Material <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select material-select" data-index="__INDEX__">
                                                    <option value="">Type to search material...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- New Item Section -->
                                    <div class="newitem-section" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">New Item Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control new-item-name"
                                                    data-index="__INDEX__" value="">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Category & Unit - FOR ALL ITEMS -->
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            {{-- Restock: readonly display (auto from material) --}}
                                            <div class="category-readonly-wrapper" style="display:none;">
                                                <input type="hidden" class="category-id-hidden" data-index="__INDEX__">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted"
                                                        style="font-size:0.8rem;"><i class="fas fa-link"></i></span>
                                                    <input type="text" class="form-control category-display bg-light"
                                                        readonly placeholder="Auto from material...">
                                                </div>
                                            </div>
                                            {{-- New Item: select dropdown --}}
                                            <div class="category-select-wrapper">
                                                <select class="form-select category-select" data-index="__INDEX__">
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                                            {{-- Restock: readonly display (auto from material) --}}
                                            <div class="unit-readonly-wrapper" style="display:none;">
                                                <input type="hidden" class="unit-id-hidden" data-index="__INDEX__">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted"
                                                        style="font-size:0.8rem;"><i class="fas fa-link"></i></span>
                                                    <input type="text" class="form-control unit-display bg-light"
                                                        readonly placeholder="Auto from material...">
                                                </div>
                                            </div>
                                            {{-- New Item: select dropdown --}}
                                            <div class="unit-select-wrapper">
                                                <select class="form-select unit-select" data-index="__INDEX__">
                                                    <option value="">Select Unit</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quantity and Price -->
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control quantity" data-index="__INDEX__"
                                                min="0.01" step="0.01" value="1">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Unit Price <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control unit-price" data-index="__INDEX__"
                                                min="0" step="0.01">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Subtotal</label>
                                            <input type="text" class="form-control item-subtotal" readonly
                                                value="Rp 0">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Supplier Information -->
                            <div class="mb-4">
                                <h6 class="section-header">
                                    <i class="fas fa-truck me-2"></i>Supplier Information
                                </h6>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <div class="label-with-addon">
                                            <span>Supplier <span class="text-danger">*</span></span>
                                            <a href="#" class="add-button" data-bs-toggle="modal"
                                                data-bs-target="#addSupplierModal">
                                                <i class="fas fa-plus"></i> Add Supplier
                                            </a>
                                        </div>
                                        <select
                                            class="form-select select2 border-1 rounded-2 py-2 px-3 @error('supplier_id') is-invalid @enderror"
                                            name="supplier_id" id="supplierSelect" required>
                                            <option value="">Select Supplier</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}"
                                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('supplier_id')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label mb-2">Order Type <span
                                                class="text-danger">*</span></label>
                                        <div class="d-flex radio-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="is_offline_order"
                                                    id="onlineOrder" value="0"
                                                    {{ old('is_offline_order') == '1' ? '' : 'checked' }}>
                                                <label class="form-check-label" for="onlineOrder">Online</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="is_offline_order"
                                                    id="offlineOrder" value="1"
                                                    {{ old('is_offline_order') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="offlineOrder">Offline</label>
                                            </div>
                                        </div>
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
                                        <input type="text" class="form-control" name="resi_number" id="resiNumber"
                                            value="{{ old('resi_number') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Freight Cost</label>
                                        <input type="number" class="form-control" name="freight" id="freight"
                                            value="{{ old('freight', 0) }}" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Other Costs -->
                            <div class="mb-4">
                                <h6 class="section-header">
                                    <i class="fas fa-money-bill-wave me-2"></i>Additional Costs
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Other Costs</label>
                                        <input type="number" class="form-control" name="other_costs" id="otherCosts"
                                            value="{{ old('other_costs', 0) }}" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-4">
                                <h6 class="section-header">
                                    <i class="fas fa-sticky-note me-2"></i>Notes
                                </h6>
                                <textarea class="form-control" name="note" rows="2" placeholder="Add notes">{{ old('note') }}</textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 pt-3 border-top">
                                <a href="{{ route('indo-purchases.index') }}"
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
                            <input type="text" name="name" class="form-control border-1 rounded-2 py-2 px-3"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <select name="location_id" class="form-select border-1 rounded-2 py-2 px-3" required>
                                <option value="">Select Location</option>
                                @foreach ($supplierLocations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lead Time (Days)</label>
                            <input type="number" name="lead_time_days" class="form-control border-1 rounded-2 py-2 px-3"
                                required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-2 px-3"
                            data-bs-dismiss="modal">Close</button>
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
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            let itemIndex = 0;
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
                freight: $('#freight'),
                otherCosts: $('#otherCosts')
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
                    elements.clientProjectDetails.html(
                        '<small class="text-muted">Select a job order to view details</small>');
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
                    elements.internalProjectDetails.html(
                        '<small class="text-muted">Select an internal project to view details</small>');
                }
            });


            // Function to add item
            function addItemRow() {
                const template = document.getElementById('itemRowTemplate').innerHTML;
                const newRow = template.replace(/__INDEX__/g, itemIndex)
                    .replace(/__INDEX_PLUS_ONE__/g, itemIndex + 1);
                $('#itemsContainer').append(newRow);
                initializeRow(itemIndex);
                itemIndex++;
            }

            // Initialize new row
            function initializeRow(index) {
                const row = $(`.item-row[data-index="${index}"]`);

                // Set name attributes based on purchase type
                function setFieldNames() {
                    const isRestock = row.find('.purchase-type').val() === 'restock';

                    // Remove all name attributes first
                    row.find('[data-index]').each(function() {
                        $(this).removeAttr('name');
                    });

                    // Set name for always present fields
                    row.find('.quantity').attr('name', `items[${index}][quantity]`);
                    row.find('.unit-price').attr('name', `items[${index}][unit_price]`);
                    row.find('.purchase-type').attr('name', `items[${index}][purchase_type]`);

                    // Set name based on type
                    if (isRestock) {
                        row.find('.material-select').attr('name', `items[${index}][material_id]`);
                        row.find('.category-id-hidden').attr('name', `items[${index}][category_id]`);
                        row.find('.unit-id-hidden').attr('name', `items[${index}][unit_id]`);
                        // new_item_name / selects don't need names
                    } else {
                        row.find('.new-item-name').attr('name', `items[${index}][new_item_name]`);
                        row.find('.category-select').attr('name', `items[${index}][category_id]`);
                        row.find('.unit-select').attr('name', `items[${index}][unit_id]`);
                        // hidden inputs don't need names
                    }
                }

                // Toggle purchase type
                row.find('.purchase-type').change(function() {
                    const isRestock = $(this).val() === 'restock';
                    row.find('.restock-section').toggle(isRestock);
                    row.find('.newitem-section').toggle(!isRestock);

                    if (isRestock) {
                        // Show readonly text, hide selects
                        row.find('.category-readonly-wrapper').show();
                        row.find('.category-select-wrapper').hide();
                        row.find('.unit-readonly-wrapper').show();
                        row.find('.unit-select-wrapper').hide();
                        // Clear new item name
                        row.find('.new-item-name').val('');
                        // Clear unit price too
                        row.find('.unit-price').val('');
                        // Clear readonly fields (no material selected yet)
                        row.find('.category-id-hidden, .unit-id-hidden').val('');
                        row.find('.category-display, .unit-display').val('');
                        row.find('.material-select').val(null).trigger('change');
                    } else {
                        // Show selects, hide readonly text
                        row.find('.category-readonly-wrapper').hide();
                        row.find('.category-select-wrapper').show();
                        row.find('.unit-readonly-wrapper').hide();
                        row.find('.unit-select-wrapper').show();
                        // Reset select values
                        row.find('.category-select').val('');
                        row.find('.unit-select').val('');
                        row.find('.unit-price').val('');
                        row.find('.material-select').val(null).trigger('change');
                    }

                    setFieldNames();
                });

                // Default type is restock — show readonly on init (triggered via trigger('change') below)

                // Material select — Select2 AJAX (tidak load 4414 option sekaligus)
                const materialSel = row.find('.material-select');
                materialSel.select2({
                    width: '100%',
                    placeholder: 'Type to search material...',
                    minimumInputLength: 1,
                    ajax: {
                        url: '{{ route('indo-purchases.materials.search') }}',
                        dataType: 'json',
                        delay: 300,
                        data: params => ({
                            q: params.term
                        }),
                        processResults: data => ({
                            results: data.results
                        }),
                        cache: true,
                    },
                    templateResult: item => item.loading ? item.text : $(`<span>${item.text}</span>`),
                    templateSelection: item => item.text || item.id,
                });

                materialSel.on('select2:select', function(e) {
                    const d = e.params.data;
                    row.find('.unit-price').val(0);
                    // Populate unit — prefer unit_id (FK), fallback to plain unit string column
                    row.find('.unit-id-hidden').val(d.unit_id || '');
                    if (d.unit_id) {
                        row.find('.unit-display').val(d.unit_name || `Unit #${d.unit_id}`);
                    } else if (d.unit_name) {
                        // unit_name from raw `unit` column (no FK)
                        row.find('.unit-display').val(d.unit_name + ' (text only, no FK)');
                    } else {
                        row.find('.unit-display').val('—');
                    }
                    // Populate category
                    row.find('.category-id-hidden').val(d.category_id || '');
                    row.find('.category-display').val(d.category_name || (d.category_id ?
                        `Category #${d.category_id}` : '—'));
                    calculateRowSubtotal(row);
                    calculateGrandTotal();
                });

                // Quantity/Price change
                row.find('.quantity, .unit-price').on('input', function() {
                    calculateRowSubtotal(row);
                    calculateGrandTotal();
                });

                // Remove button
                row.find('.remove-item').click(function() {
                    if (confirm('Delete this item?')) {
                        row.remove();
                        calculateGrandTotal();
                    }
                });

                // Set initial field names
                setFieldNames();

                // Trigger initial state
                row.find('.purchase-type').trigger('change');
            }

            // Calculate row subtotal
            function calculateRowSubtotal(row) {
                const qty = parseFloat(row.find('.quantity').val()) || 0;
                const price = parseFloat(row.find('.unit-price').val()) || 0;
                const subtotal = qty * price;
                row.find('.item-subtotal').val('Rp ' + formatNumber(subtotal));
                return subtotal;
            }

            // Calculate grand total
            function calculateGrandTotal() {
                let total = 0;
                $('.item-row').each(function() {
                    const row = $(this);
                    total += calculateRowSubtotal(row);
                });

                // Add freight and other costs
                total += (parseFloat(elements.freight.val()) || 0);
                total += (parseFloat(elements.otherCosts.val()) || 0);

                $('#grandTotal').text('Rp ' + formatNumber(total));
            }

            // Format number
            function formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Event listeners
            $('#addItemBtn').click(addItemRow);
            elements.freight.add(elements.otherCosts).on('input', calculateGrandTotal);

            // Add first item
            addItemRow();

            // Form validation before submit
            $('#purchaseForm').submit(function(e) {
                const itemRows = $('.item-row').length;

                if (itemRows === 0) {
                    e.preventDefault();
                    alert('At least 1 item must be filled');
                    return false;
                }

                // Validate PO Number
                if (!$('input[name="po_number"]').val().trim()) {
                    e.preventDefault();
                    alert('PO Number is required');
                    $('input[name="po_number"]').focus();
                    return false;
                }

                // Validate each item
                let valid = true;
                $('.item-row').each(function(index) {
                    const type = $(this).find('.purchase-type').val();

                    // Validation for RESTOCK
                    if (type === 'restock') {
                        if (!$(this).find('.material-select').val()) {
                            alert(`Item ${index + 1}: Material must be selected`);
                            valid = false;
                            return false;
                        }
                    }
                    // Validation for NEW ITEM
                    else {
                        if (!$(this).find('.new-item-name').val().trim()) {
                            alert(`Item ${index + 1}: New item name is required`);
                            valid = false;
                            return false;
                        }
                    }

                    // Validation for Category (FOR ALL ITEMS)
                    const catVal = type === 'restock' ?
                        $(this).find('.category-id-hidden').val() :
                        $(this).find('.category-select').val();
                    if (!catVal) {
                        alert(`Item ${index + 1}: Category must be selected`);
                        valid = false;
                        return false;
                    }

                    // Validation for Unit (FOR ALL ITEMS)
                    const unitVal = type === 'restock' ?
                        $(this).find('.unit-id-hidden').val() :
                        $(this).find('.unit-select').val();
                    if (!unitVal) {
                        alert(`Item ${index + 1}: Unit must be selected (choose a material first)`);
                        valid = false;
                        return false;
                    }

                    // Validate quantity
                    const qty = $(this).find('.quantity').val();
                    if (!qty || qty <= 0) {
                        alert(`Item ${index + 1}: Quantity is required (minimum 1)`);
                        valid = false;
                        return false;
                    }

                    // Validate price
                    const price = $(this).find('.unit-price').val();
                    if (!price || price <= 0) {
                        alert(`Item ${index + 1}: Price is required (minimum 1)`);
                        valid = false;
                        return false;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    return false;
                }

                // Validate project
                const projectType = $('input[name="project_type"]:checked').val();
                if (projectType === 'client' && !elements.jobOrderSelect.val()) {
                    e.preventDefault();
                    alert('Job Order must be selected for Client Project');
                    return false;
                }
                if (projectType === 'internal' && !elements.internalProjectSelect.val()) {
                    e.preventDefault();
                    alert('Internal Project must be selected');
                    return false;
                }

                // Validate supplier
                if (!$('#supplierSelect').val()) {
                    e.preventDefault();
                    alert('Supplier must be selected');
                    return false;
                }

                // Show loading
                $(this).find('button[type="submit"]').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
            });

            // Add Supplier
            $('#supplierForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            const newOption = new Option(response.supplier.name, response
                                .supplier.id, true, true);
                            $('#supplierSelect').append(newOption).trigger('change');
                            $('#addSupplierModal').modal('hide');
                            form[0].reset();
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
        });
    </script>
@endsection
