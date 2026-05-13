{{-- resources/views/procurement/Indo-Purchase/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Project Purchase Orders')

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 34px !important;
        border-color: #e2e8f0 !important;
        border-radius: 6px !important;
        font-size: 0.8rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px !important;
        padding-left: 8px !important;
        font-size: 0.8rem;
        color: #334155;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
    }
    .select2-container--default .select2-results__option {
        font-size: 0.8rem;
    }
    .select2-dropdown {
        border-color: #e2e8f0;
        border-radius: 6px;
    }
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #4f46e5 !important;
        box-shadow: 0 0 0 0.15rem rgba(79,70,229,0.1) !important;
        outline: none;
    }
</style>
<style>
    /* Custom Styling */
    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.8rem;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
    }

    .action-btn.btn-outline-danger:hover {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .icon-shape {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Badge styling */
    .badge.bg-warning {
        background-color: #f2a41dff !important;
        color: white !important;
    }

    .badge.bg-success {
        background-color: #04c665ff !important;
        color: white !important;
    }

    .badge.bg-danger {
        background-color: #ef4444 !important;
        color: white !important;
    }

    .badge.bg-secondary {
        background-color: #838b9bff !important;
        color: white !important;
    }

    .badge.bg-info {
        background-color: #0ea5e9 !important;
        color: white !important;
    }

    /* Modal styling */
    .modal-sm {
        max-width: 400px;
    }

    .modal-md {
        max-width: 500px;
    }

    .modal-content {
        border-radius: 10px;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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

    /* Button Styling - MENGIKUTI ATTENDANCE */
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 8px;
    }

    .btn-outline-primary {
        color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-outline-primary:hover {
        background-color: #4f46e5;
        color: white;
    }

    .btn-outline-success {
        color: #10b981;
        border-color: #10b981;
    }

    .btn-outline-success:hover {
        background-color: #10b981;
        color: white;
    }

    .btn-outline-secondary {
        color: #64748b;
        border-color: #e2e8f0;
    }

    .btn-outline-secondary:hover {
        background-color: #f1f5f9;
        color: #334155;
        border-color: #cbd5e1;
    }

    .btn-outline-info {
        color: #0ea5e9;
        border-color: #0ea5e9;
    }

    .btn-outline-info:hover {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-outline-info.active {
        background-color: #0ea5e9;
        color: white;
        border-color: #0ea5e9;
    }

    /* ── Bulk Receive ── */
    .bulk-action-bar {
        background: #4f46e5;
        color: white;
        padding: 0.55rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.83rem;
        animation: slideDown .15s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .bulk-check, .bulk-check-all {
        width: 13px;
        height: 13px;
        cursor: pointer;
        accent-color: #4f46e5;
        flex-shrink: 0;
    }
    th.col-check { width: 32px; padding-left: 0.75rem; }
    td.col-check { padding-left: 0.75rem; text-align: center; }
    .select-all-pages-bar {
        font-size: 0.78rem;
        background: #eef2ff;
        color: #3730a3;
        padding: 0.35rem 1.25rem;
        border-bottom: 1px solid #c7d2fe;
        display: none;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
        color: white;
    }

    .btn-primary:hover {
        background-color: #4338ca;
        border-color: #4338ca;
    }

    /* Form controls - mengikuti attendance */
    .form-control,
    .form-select {
        border-color: #e2e8f0;
        font-size: 0.85rem;
        border-width: 1px;
        height: 38px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
    }

    .form-control-sm,
    .form-select-sm {
        height: 34px;
        font-size: 0.8rem;
    }

    /* Table number */
    .table-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        text-align: center;
        transition: all 0.2s;
    }
    
    tr:hover .table-number {
        background-color: #4f46e5;
        color: white;
    }

    /* Alert styling */
    .alert {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        border: none;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .alert-info {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .alert-warning {
        background-color: #fed7aa;
        color: #92400e;
    }

    .alert-secondary {
        background-color: #f1f5f9;
        color: #334155;
    }

    /* DaisyUI Pagination */
    .join .btn {
        height: 32px;
        min-height: 32px;
        padding: 0 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .join .btn-disabled {
        background-color: #f3f4f6;
        color: #9ca3af;
        cursor: not-allowed;
        border-color: #e5e7eb;
    }
    
    .join .btn-active {
        background-color: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-responsive {
            border: 0;
        }
        
        .table thead {
            display: none;
        }
        
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            white-space: normal;
        }
        
        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            min-width: 100px;
            margin-right: 1rem;
        }
        
        .table tbody td:last-child {
            border-bottom: none;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Indonesia Orders</h4>
                    <p class="text-muted mb-0">Manage your project purchase orders</p>
                </div>
                @can('procurement.po.create')
                <a href="{{ route('indo-purchases.create') }}"
                   class="btn btn-primary btn-sm rounded-3 px-4">
                    <i class="fas fa-plus me-2"></i>New PO
                </a>
                @endcan
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Total PO</h6>
                                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Pending</h6>
                                    <h3 class="mb-0">{{ $stats['pending'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Approved</h6>
                                    <h3 class="mb-0">{{ $stats['approved'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Rejected</h6>
                                    <h3 class="mb-0">{{ $stats['rejected'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-3">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase text-muted mb-2">Received</h6>
                                    <h3 class="mb-0">{{ $stats['received'] ?? 0 }}</h3>
                                </div>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-3">
                                    <i class="fas fa-box-open"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('indo-purchases.index') }}">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="Search..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="department_id" id="filterDepartment" class="filter-select2">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="supplier_id" id="filterSupplier" class="filter-select2">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="project_type" class="form-select form-select-sm">
                                    <option value="">Project Type</option>
                                    <option value="client" {{ request('project_type') == 'client' ? 'selected' : '' }}>Client</option>
                                    <option value="internal" {{ request('project_type') == 'internal' ? 'selected' : '' }}>Internal</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <select name="item_status" class="form-select form-select-sm">
                                    <option value="">Receipt</option>
                                    <option value="pending" {{ request('item_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="matched" {{ request('item_status') == 'matched' ? 'selected' : '' }}>Matched</option>
                                    <option value="not_matched" {{ request('item_status') == 'not_matched' ? 'selected' : '' }}>Not Matched</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <input type="date" name="date" class="form-control form-control-sm"
                                       value="{{ request('date') }}">
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'item_status', 'supplier_id']))
                                <a href="{{ route('indo-purchases.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                                    <i class="fas fa-times"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-check-circle me-2"></i> 
                            <div class="flex-grow-1">{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>  
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger border-0 rounded-0 m-0 d-flex align-items-center px-4 py-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div class="flex-grow-1">{{ session('error') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Hidden form for bulk receive; checkboxes associate via form="bulkReceiveForm" --}}
                    @can('procurement.po.edit')
                    <form id="bulkReceiveForm" method="POST" action="{{ route('indo-purchases.bulk-receive') }}">
                        @csrf
                        <input type="hidden" id="selectAllEligible" name="select_all_eligible" value="0">
                    </form>

                    {{-- Bulk Action Bar (visible when ≥1 checkbox checked) --}}
                    <div id="bulkActionBar" class="bulk-action-bar" style="display:none;">
                        <i class="fas fa-box-open"></i>
                        <span class="fw-semibold"><span id="selectedCount">0</span> item dipilih</span>
                        <button type="submit" form="bulkReceiveForm"
                                class="btn btn-sm btn-light text-primary fw-semibold px-3"
                                onclick="return confirmBulkReceive()">
                            <i class="fas fa-check me-1"></i> Mark as Received
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light px-3" onclick="clearSelection()">
                            Batal
                        </button>
                        <span class="ms-auto text-white-50" style="font-size:0.75rem;">
                            Hanya item <strong class="text-white">Approved + Pending Check</strong> yang akan diproses
                        </span>
                    </div>

                    {{-- Bar "pilih semua halaman" — muncul saat semua di halaman ini dipilih --}}
                    <div id="selectAllPagesBar" class="select-all-pages-bar" data-total="{{ $totalEligible }}">
                        <i class="fas fa-info-circle"></i>
                        <span id="selectAllPagesPrompt">
                            Semua item di halaman ini dipilih.
                            <a href="#" id="selectAllPagesBtn" onclick="selectAllPages(); return false;"
                               class="fw-semibold text-indigo-700" style="color:#3730a3;">
                                Pilih semua <strong>{{ $totalEligible }}</strong> item eligible di semua halaman
                            </a>
                        </span>
                        <span id="allPagesSelectedMsg" style="display:none;">
                            <i class="fas fa-check-double me-1 text-success"></i>
                            <strong>Semua {{ $totalEligible }} item eligible</strong> di semua halaman dipilih.
                            <a href="#" onclick="clearSelection(); return false;" class="ms-2 text-danger fw-semibold">Batalkan</a>
                        </span>
                    </div>
                    @endcan

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="purchaseTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 col-check">
                                        @can('procurement.po.edit')
                                        <input type="checkbox" id="selectAll" class="bulk-check-all" title="Pilih semua">
                                        @endcan
                                    </th>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">Purchase Number</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Department</th>
                                    <th class="border-0">Project</th>
                                    <th class="border-0 text-end">Total Qty</th>
                                    <th class="border-0 text-end">Total Amount</th>
                                    <th class="border-0">PO Status</th>
                                    <th class="border-0">Receipt Status</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startingNumber = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
                                @endphp
                                @forelse($purchases as $index => $purchase)
                                    @php
                                        $groupInfo = $purchase->group_info ?? [
                                            'total_items' => 1,
                                            'total_quantity' => $purchase->quantity,
                                            'total_amount' => $purchase->invoice_total,
                                            'received_count' => $purchase->item_status == 'matched' ? 1 : 0,
                                            'not_matched_count' => $purchase->item_status == 'not_matched' ? 1 : 0,
                                        ];
                                        
                                        $allReceived = ($groupInfo['received_count'] ?? 0) == $groupInfo['total_items'];
                                        $anyNotMatched = ($groupInfo['not_matched_count'] ?? 0) > 0;
                                        $pendingCount = $groupInfo['total_items'] - ($groupInfo['received_count'] ?? 0) - ($groupInfo['not_matched_count'] ?? 0);
                                        
                                        if ($allReceived) {
                                            $receiptBadge = 'bg-success';
                                            $receiptIcon = 'fa-check-circle';
                                            $receiptText = 'All Received';
                                        } elseif ($anyNotMatched) {
                                            $receiptBadge = 'bg-danger';
                                            $receiptIcon = 'fa-exclamation-triangle';
                                            $receiptText = 'Has Issues';
                                        } elseif ($pendingCount > 0) {
                                            $receiptBadge = 'bg-warning';
                                            $receiptIcon = 'fa-clock';
                                            $receiptText = $pendingCount . ' Pending';
                                        } else {
                                            $receiptBadge = 'bg-secondary';
                                            $receiptIcon = 'fa-hourglass-half';
                                            $receiptText = 'Pending';
                                        }
                                    @endphp
                                    <tr class="align-middle">
                                        <td class="col-check">
                                            @can('procurement.po.edit')
                                            @if($purchase->canMarkAsReceived())
                                            <input type="checkbox" name="uids[]" value="{{ $purchase->uid }}"
                                                   form="bulkReceiveForm" class="bulk-check">
                                            @endif
                                            @endcan
                                        </td>
                                        <td class="ps-4 text-center">
                                            <span class="table-number">{{ $startingNumber + $index }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-primary">{{ $purchase->po_number }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $purchase->date->format('d/m/Y') }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $purchase->department->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($purchase->project_type == 'client')
                                                <span>{{ $purchase->project->name ?? 'N/A' }}</span>
                                            @else
                                                <span>{{ $purchase->internalProject->project ?? 'N/A' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span>{{ number_format($groupInfo['total_quantity']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span>Rp {{ number_format($groupInfo['total_amount'], 0) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $purchase->status_badge_class }} px-3 py-1 rounded-pill">
                                                {{ $purchase->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $receiptBadge }} px-3 py-1 rounded-pill">
                                                <i class="fas {{ $receiptIcon }} me-1"></i>{{ $receiptText }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <!-- View Details -->
                                                <a href="{{ route('indo-purchases.show', $purchase->uid) }}" 
                                                   class="btn btn-sm btn-outline-info border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- EDIT BUTTON - Hanya jika pending -->
                                                @can('procurement.po.edit')
                                                @if($purchase->status == 'pending')
                                                <a href="{{ route('indo-purchases.edit', $purchase->uid) }}"
                                                   class="btn btn-sm btn-outline-primary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endif
                                                @endcan

                                                <!-- DELETE BUTTON - Hanya jika status pending -->
                                                @can('procurement.po.delete')
                                                @if($purchase->status == 'pending')
                                                <form action="{{ route('indo-purchases.destroy', $purchase->uid) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Delete purchase order {{ $purchase->po_number }}? This will delete all {{ $groupInfo['total_items'] }} items in this PO.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif

                                                <!-- REQUEST DELETE - Hanya jika sudah approved -->
                                                @if($purchase->status == 'approved')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                        data-bs-toggle="tooltip" title="Request Deletion"
                                                        onclick="openDeletionModal('{{ $purchase->uid }}', '{{ $purchase->po_number }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @endif
                                                @endcan

                                                <!-- BADGE: Menunggu Persetujuan Hapus -->
                                                @if($purchase->status == 'deletion_requested')
                                                <span class="badge bg-warning text-dark" style="font-size:0.7rem;">Menunggu Hapus</span>
                                                @endif
                                                
                                                <!-- APPROVE BUTTON - (hanya jika pending) -->
                                                @can('procurement.po.approve')
                                                @if($purchase->status == 'pending')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#approveModal{{ $purchase->id }}"
                                                        title="Approve PO">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                @endif
                                                @endcan

                                                <!-- REJECT BUTTON - (hanya jika pending) -->
                                                @can('procurement.po.approve')
                                                @if($purchase->status == 'pending')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal{{ $purchase->id }}"
                                                        title="Reject PO">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @endif
                                                @endcan

                                                <!-- RECEIVED BUTTON - Tampil jika status approved dan item belum diterima -->
                                                @can('procurement.po.edit')
                                                @if($purchase->status == 'approved' && in_array($purchase->item_status, ['pending_check', 'pending']))
                                                <form action="{{ route('indo-purchases.mark-as-received', $purchase->uid) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Mark this item as received and add to inventory?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                            data-bs-toggle="tooltip" title="Mark as Received">
                                                        <i class="fas fa-box-open"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @endcan
                                                
                                                <!-- PRINT BUTTON -->
                                                <a href="{{ route('indo-purchases.print', $purchase->uid) }}" 
                                                   class="btn btn-sm btn-outline-secondary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Print" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Approve Modal -->
                                    @can('procurement.po.approve')
                                    @if($purchase->status == 'pending')
                                    <div class="modal fade" id="approveModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <form action="{{ route('indo-purchases.approve', $purchase->uid) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Approve Purchase Order</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small mb-2">You are about to approve all items in PO:</p>
                                                        <p class="fw-medium">{{ $purchase->po_number }}</p>
                                                        
                                                        <div class="bg-light p-3 rounded mb-3">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Items:</strong>
                                                                <span>{{ $groupInfo['total_items'] }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Quantity:</strong>
                                                                <span>{{ number_format($groupInfo['total_quantity']) }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Amount:</strong>
                                                                <span>Rp {{ number_format($groupInfo['total_amount'], 0) }}</span>
                                                            </div>
                                                        </div>
                                                        
                                                        @if(!$purchase->is_offline_order)
                                                        <div class="mb-2">
                                                            <label class="form-label small">Resi Number</label>
                                                            <input type="text" name="resi_number" class="form-control form-control-sm" 
                                                                   placeholder="Enter resi number">
                                                        </div>
                                                        @endif
                                                        
                                                        <div class="mb-2">
                                                            <label class="form-label small">Notes (Optional)</label>
                                                            <textarea name="finance_notes" class="form-control form-control-sm" rows="2" 
                                                                      placeholder="Additional notes..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check me-1"></i>Approve All ({{ $groupInfo['total_items'] }} items)
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @endcan

                                    <!-- Reject Modal -->
                                    @can('procurement.po.approve')
                                    @if($purchase->status == 'pending')
                                    <div class="modal fade" id="rejectModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <form action="{{ route('indo-purchases.reject', $purchase->uid) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Reject Purchase Order</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small mb-2">You are about to reject all items in PO:</p>
                                                        <p class="fw-medium">{{ $purchase->po_number }}</p>
                                                        
                                                        <div class="bg-light p-3 rounded mb-3">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Items:</strong>
                                                                <span>{{ $groupInfo['total_items'] }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Quantity:</strong>
                                                                <span>{{ number_format($groupInfo['total_quantity']) }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <strong>Total Amount:</strong>
                                                                <span>Rp {{ number_format($groupInfo['total_amount'], 0) }}</span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-2">
                                                            <label class="form-label small">Reason for Rejection <span class="text-danger">*</span></label>
                                                            <textarea name="finance_notes" class="form-control form-control-sm" rows="3" 
                                                                      placeholder="Required reason for rejection..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times me-1"></i>Reject All ({{ $groupInfo['total_items'] }} items)
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @endcan

                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                                <h5>No Purchase Orders Found</h5>
                                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'item_status', 'supplier_id']))
                                                    <p class="mb-0">Try adjusting your filters</p>
                                                    <a href="{{ route('indo-purchases.index') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-times me-1"></i>Clear Filters
                                                    </a>
                                                @else
                                                    <p class="mb-0">Start by creating your first purchase order</p>
                                                    @can('procurement.po.create')
                                                    <a href="{{ route('indo-purchases.create') }}"
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-plus me-1"></i>Create PO
                                                    </a>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($purchases->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="text-muted small mb-2 mb-md-0">
                            Showing {{ $purchases->firstItem() }} - {{ $purchases->lastItem() }} of {{ $purchases->total() }} PO(s)
                        </div>
                        
                        <div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Previous Page Link -->
                                    @if($purchases->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link py-1 px-3 rounded-2 me-1" aria-label="Previous">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link py-1 px-3 rounded-2 me-1"
                                               href="{{ $purchases->previousPageUrl() }}"
                                               aria-label="Previous">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    <!-- Page Numbers -->
                                    @php
                                        $current = $purchases->currentPage();
                                        $last = $purchases->lastPage();
                                        $start = max($current - 2, 1);
                                        $end = min($current + 2, $last);

                                        if ($start > 1) {
                                            echo '<li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>';
                                        }
                                    @endphp

                                    @for ($i = $start; $i <= $end; $i++)
                                        @if ($i == $current)
                                            <li class="page-item active">
                                                <span class="page-link py-1 px-3 rounded-2 me-1">{{ $i }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link py-1 px-3 rounded-2 me-1"
                                                   href="{{ $purchases->url($i) }}">{{ $i }}</a>
                                            </li>
                                        @endif
                                    @endfor

                                    @if ($end < $last)
                                        <li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>
                                    @endif

                                    <!-- Next Page Link -->
                                    @if($purchases->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link py-1 px-3 rounded-2"
                                               href="{{ $purchases->nextPageUrl() }}"
                                               aria-label="Next">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link py-1 px-3 rounded-2" aria-label="Next">
                                                <i class="fas fa-chevron-right"></i>
                                            </span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Select2 for department and supplier filters
    $('.filter-select2').select2({
        width: '100%',
        allowClear: true,
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add data labels for responsive table
    const tableHeaders = document.querySelectorAll('#purchaseTable thead th');
    
    tableHeaders.forEach((header, index) => {
        const text = header.textContent.trim();
        if (text) {
            const cells = document.querySelectorAll(`#purchaseTable tbody td:nth-child(${index + 1})`);
            cells.forEach(cell => {
                cell.setAttribute('data-label', text);
            });
        }
    });

    // Auto-close alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);

    // Form validation for reject modal
    const rejectForms = document.querySelectorAll('form[action*="/reject/"]');
    rejectForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const textarea = this.querySelector('textarea[name="finance_notes"]');
            if (textarea && !textarea.value.trim()) {
                e.preventDefault();
                alert('Please provide a reason for rejection.');
                textarea.focus();
            }
        });
    });

    // ── Bulk Receive ────────────────────────────────────────────────────────
    const selectAllCb       = document.getElementById('selectAll');
    const bulkActionBar     = document.getElementById('bulkActionBar');
    const selectedCountEl   = document.getElementById('selectedCount');
    const selectAllPagesBar = document.getElementById('selectAllPagesBar');
    const selectAllPagesPrompt  = document.getElementById('selectAllPagesPrompt');
    const allPagesSelectedMsgEl = document.getElementById('allPagesSelectedMsg');
    const selectAllEligibleInput = document.getElementById('selectAllEligible');
    const totalEligible = {{ $totalEligible ?? 0 }};

    function updateBulkBar() {
        const allChecks       = document.querySelectorAll('.bulk-check');
        const checked         = document.querySelectorAll('.bulk-check:checked');
        const count           = checked.length;
        const allOnPage       = allChecks.length > 0 && count === allChecks.length;

        // Reset all-pages mode if user deselected something
        if (!allOnPage && window._bulkAllPages) {
            window._bulkAllPages = false;
            if (selectAllEligibleInput) selectAllEligibleInput.value = '0';
            if (allPagesSelectedMsgEl) allPagesSelectedMsgEl.style.display = 'none';
            if (selectAllPagesPrompt)  selectAllPagesPrompt.style.display  = 'inline';
        }

        if (!window._bulkAllPages && selectedCountEl) selectedCountEl.textContent = count;
        if (bulkActionBar) bulkActionBar.style.display = count > 0 ? 'flex' : 'none';

        if (selectAllCb) {
            selectAllCb.indeterminate = count > 0 && count < allChecks.length;
            selectAllCb.checked = allOnPage;
        }

        // Show/hide the "select all pages" bar
        if (selectAllPagesBar) {
            const showBar = allOnPage && !window._bulkAllPages && totalEligible > allChecks.length;
            selectAllPagesBar.style.display = showBar ? 'flex' : 'none';
        }
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            document.querySelectorAll('.bulk-check').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }

    document.querySelectorAll('.bulk-check').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });
    // ────────────────────────────────────────────────────────────────────────

});
</script>

<!-- Modal: Request Deletion -->
<div class="modal fade" id="deletionRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Request Penghapusan Purchase</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deletionRequestForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Purchase ini sudah <strong>disetujui Finance</strong>. Permintaan hapus akan dikirim ke Finance untuk disetujui terlebih dahulu.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Purchase Number</label>
                        <input type="text" id="deletionPoNumber" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Alasan Penghapusan <span class="text-danger">*</span></label>
                        <textarea name="deletion_reason" class="form-control form-control-sm" rows="3"
                                  placeholder="Jelaskan alasan penghapusan..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Kirim Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDeletionModal(uid, poNumber) {
    document.getElementById('deletionPoNumber').value = poNumber;
    document.getElementById('deletionRequestForm').action = '/indo-purchases/' + uid + '/request-deletion';
    new bootstrap.Modal(document.getElementById('deletionRequestModal')).show();
}
function clearSelection() {
    window._bulkAllPages = false;
    document.querySelectorAll('.bulk-check').forEach(cb => cb.checked = false);
    const sa = document.getElementById('selectAll');
    if (sa) { sa.checked = false; sa.indeterminate = false; }
    const bar = document.getElementById('bulkActionBar');
    if (bar) bar.style.display = 'none';
    const cnt = document.getElementById('selectedCount');
    if (cnt) cnt.textContent = '0';
    const eligibleInput = document.getElementById('selectAllEligible');
    if (eligibleInput) eligibleInput.value = '0';
    const pagesBar = document.getElementById('selectAllPagesBar');
    if (pagesBar) pagesBar.style.display = 'none';
    const prompt = document.getElementById('selectAllPagesPrompt');
    if (prompt) prompt.style.display = 'inline';
    const msg = document.getElementById('allPagesSelectedMsg');
    if (msg) msg.style.display = 'none';
}
function selectAllPages() {
    window._bulkAllPages = true;
    const eligibleInput = document.getElementById('selectAllEligible');
    if (eligibleInput) eligibleInput.value = '1';
    const bar = document.getElementById('selectAllPagesBar');
    const total = bar ? bar.dataset.total : '0';
    const cnt = document.getElementById('selectedCount');
    if (cnt) cnt.textContent = total;
    const prompt = document.getElementById('selectAllPagesPrompt');
    if (prompt) prompt.style.display = 'none';
    const msg = document.getElementById('allPagesSelectedMsg');
    if (msg) msg.style.display = 'inline';
}
function confirmBulkReceive() {
    if (window._bulkAllPages) {
        const bar = document.getElementById('selectAllPagesBar');
        const total = bar ? bar.dataset.total : '?';
        return confirm('Semua ' + total + ' purchase order eligible di semua halaman akan ditandai sebagai received. Lanjutkan?');
    }
    const count = document.querySelectorAll('.bulk-check:checked').length;
    return confirm(count + ' purchase order akan ditandai sebagai received dan ditambahkan ke inventory. Lanjutkan?');
}
</script>
@endsection