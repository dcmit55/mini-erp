{{-- resources/views/Procurement/Project-Purchase/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Project Purchase Orders')

@section('styles')
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
                <a href="{{ route('project-purchases.create') }}" 
                   class="btn btn-primary btn-sm rounded-3 px-4">
                    <i class="fas fa-plus me-2"></i>New PO
                </a>
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
                    <form method="GET" action="{{ route('project-purchases.index') }}">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       placeholder="Search PO Number..." 
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
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="project_type" class="form-select form-select-sm">
                                    <option value="">Project Type</option>
                                    <option value="client" {{ request('project_type') == 'client' ? 'selected' : '' }}>Client Project</option>
                                    <option value="internal" {{ request('project_type') == 'internal' ? 'selected' : '' }}>Internal Project</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date" class="form-control form-control-sm" 
                                       value="{{ request('date') }}" placeholder="Date">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-2">
                                <select name="item_status" class="form-select form-select-sm">
                                    <option value="">Receipt Status</option>
                                    <option value="pending" {{ request('item_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="matched" {{ request('item_status') == 'matched' ? 'selected' : '' }}>Matched</option>
                                    <option value="not_matched" {{ request('item_status') == 'not_matched' ? 'selected' : '' }}>Not Matched</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'item_status', 'supplier_id']))
                                <a href="{{ route('project-purchases.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                                    <i class="fas fa-times"></i> Clear Filters
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

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="purchaseTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 text-center" width="50">No</th>
                                    <th class="border-0">PO Number</th>
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
                                            <span class="fw-medium">{{ number_format($groupInfo['total_quantity']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold">Rp {{ number_format($groupInfo['total_amount'], 0) }}</span>
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
                                                <a href="{{ route('project-purchases.show', $purchase->uid) }}" 
                                                   class="btn btn-sm btn-outline-info border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- EDIT BUTTON - Bisa diedit jika pending ATAU approved -->
                                                @if(in_array($purchase->status, ['pending', 'approved']))
                                                <a href="{{ route('project-purchases.edit', $purchase->uid) }}" 
                                                   class="btn btn-sm btn-outline-primary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endif

                                                <!-- DELETE BUTTON - HANYA jika status pending -->
                                                @if($purchase->status == 'pending')
                                                <form action="{{ route('project-purchases.destroy', $purchase->uid) }}" 
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
                                                
                                                <!-- APPROVE BUTTON - UNTUK FINANCE (hanya jika pending) -->
                                                @if($purchase->status == 'pending' && auth()->user() && auth()->user()->role == 'finance')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal{{ $purchase->id }}"
                                                        title="Approve PO">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- REJECT BUTTON - UNTUK FINANCE (hanya jika pending) -->
                                                @if($purchase->status == 'pending' && auth()->user() && auth()->user()->role == 'finance')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal{{ $purchase->id }}"
                                                        title="Reject PO">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- RECEIVED BUTTON - Tampil jika status approved dan item belum diterima -->
                                                @if($purchase->status == 'approved' && in_array($purchase->item_status, ['pending_check', 'pending']) && auth()->user() && in_array(auth()->user()->role, ['super_admin', 'admin', 'inventory', 'admin_logistic', 'procurement']))
                                                <form action="{{ route('project-purchases.mark-as-received', $purchase->uid) }}" 
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
                                                
                                                <!-- PRINT BUTTON -->
                                                <a href="{{ route('project-purchases.print', $purchase->uid) }}" 
                                                   class="btn btn-sm btn-outline-secondary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Print" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Approve Modal -->
                                    @if($purchase->status == 'pending' && auth()->user() && auth()->user()->role == 'finance')
                                    <div class="modal fade" id="approveModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <form action="{{ route('project-purchases.approve', $purchase->uid) }}" method="POST">
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
                                    
                                    <!-- Reject Modal -->
                                    @if($purchase->status == 'pending' && auth()->user() && auth()->user()->role == 'finance')
                                    <div class="modal fade" id="rejectModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <form action="{{ route('project-purchases.reject', $purchase->uid) }}" method="POST">
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
                                    
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                                <h5>No Purchase Orders Found</h5>
                                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'item_status', 'supplier_id']))
                                                    <p class="mb-0">Try adjusting your filters</p>
                                                    <a href="{{ route('project-purchases.index') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-times me-1"></i>Clear Filters
                                                    </a>
                                                @else
                                                    <p class="mb-0">Start by creating your first purchase order</p>
                                                    <a href="{{ route('project-purchases.create') }}" 
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-plus me-1"></i>Create PO
                                                    </a>
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
                        
                        <div class="d-flex align-items-center">
                            <!-- DaisyUI Pagination -->
                            <div class="join">
                                @if($purchases->onFirstPage())
                                    <button class="join-item btn btn-disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                @else
                                    <a href="{{ $purchases->previousPageUrl() }}" class="join-item btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                @endif
                                
                                @php
                                    $current = $purchases->currentPage();
                                    $last = $purchases->lastPage();
                                    $maxPages = 5;
                                    $start = max($current - floor($maxPages/2), 1);
                                    $end = min($start + $maxPages - 1, $last);
                                    
                                    if ($end - $start < $maxPages - 1) {
                                        $start = max($end - $maxPages + 1, 1);
                                    }
                                    
                                    if ($start > 1) {
                                        echo '<a href="' . $purchases->url(1) . '" class="join-item btn">1</a>';
                                        if ($start > 2) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                    }
                                    
                                    for ($i = $start; $i <= $end; $i++) {
                                        if ($i == $current) {
                                            echo '<button class="join-item btn btn-active">' . $i . '</button>';
                                        } else {
                                            echo '<a href="' . $purchases->url($i) . '" class="join-item btn">' . $i . '</a>';
                                        }
                                    }
                                    
                                    if ($end < $last) {
                                        if ($end < $last - 1) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                        echo '<a href="' . $purchases->url($last) . '" class="join-item btn">' . $last . '</a>';
                                    }
                                @endphp
                                
                                @if($purchases->hasMorePages())
                                    <a href="{{ $purchases->nextPageUrl() }}" class="join-item btn">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @else
                                    <button class="join-item btn btn-disabled">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                @endif
                            </div>
                            
                            <div class="ms-3 d-none d-md-block">
                                <span class="small text-muted">{{ $purchases->perPage() }} data per page</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
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

});
</script>
@endsection