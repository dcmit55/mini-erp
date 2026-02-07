{{-- resources/views/Procurement/Project-Purchase/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Project Purchase Orders')

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
                   class="btn btn-primary rounded-3 px-4">
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
                                       placeholder="Search PO, Material, Supplier..." 
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
                                <select name="purchase_type" class="form-select form-select-sm">
                                    <option value="">Purchase Type</option>
                                    <option value="restock" {{ request('purchase_type') == 'restock' ? 'selected' : '' }}>Restock</option>
                                    <option value="new_item" {{ request('purchase_type') == 'new_item' ? 'selected' : '' }}>New Item</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="item_status" class="form-select form-select-sm">
                                    <option value="">Receipt Status</option>
                                    <option value="pending" {{ request('item_status') == 'pending' ? 'selected' : '' }}>Pending Receipt</option>
                                    <option value="received" {{ request('item_status') == 'received' ? 'selected' : '' }}>Received</option>
                                    <option value="not_received" {{ request('item_status') == 'not_received' ? 'selected' : '' }}>Not Received</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="order_type" class="form-select form-select-sm">
                                    <option value="">Order Type</option>
                                    <option value="online" {{ request('order_type') == 'online' ? 'selected' : '' }}>Online Order</option>
                                    <option value="offline" {{ request('order_type') == 'offline' ? 'selected' : '' }}>Offline Order</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'purchase_type', 'item_status', 'order_type']))
                                <a href="{{ route('project-purchases.index') }}" class="btn btn-outline-secondary btn-sm w-100">
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
                                    <th class="border-0">Material/Item</th>
                                    <th class="border-0">Department</th>
                                    <th class="border-0">Project</th>
                                    <th class="border-0 text-end">Qty</th>
                                    <th class="border-0">Unit</th>
                                    <th class="border-0 text-end">Total Price</th>
                                    <th class="border-0">PO Status</th>
                                    <th class="border-0">Receipt</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startingNumber = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
                                @endphp
                                @forelse($purchases as $index => $purchase)
                                    <tr class="align-middle">
                                        <td class="ps-4 text-center">
                                            <span class="text-muted">{{ $startingNumber + $index }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-medium text-primary">{{ $purchase->po_number }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $purchase->date->format('d/m/Y') }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-medium">{{ $purchase->material_name }}</span>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    @if($purchase->isNewItem())
                                                    @endif
                                                    @if($purchase->isClientProject())
                                                    @elseif($purchase->isInternalProject())
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $purchase->department->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($purchase->isClientProject())
                                                <div class="d-flex flex-column">
                                                    <span>{{ $purchase->project->name ?? 'N/A' }}</span>
                                                    @if($purchase->jobOrder)
                                                    @endif
                                                </div>
                                            @else
                                                <div class="d-flex flex-column">
                                                    <span>{{ $purchase->internalProject->project ?? 'N/A' }}</span>
                                                    @if($purchase->internalProject)
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-medium">{{ number_format($purchase->quantity) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $purchase->unit->name ?? 'pcs' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-column align-items-end">
                                                <span class="fw-semibold">Rp {{ number_format($purchase->invoice_total, 0) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $purchase->status_badge_class }} px-3 py-1 rounded-pill">
                                                {{ $purchase->status_text }}
                                            </span>
                                            @if($purchase->isApproved() && $purchase->finance_approver)
                                                <small class="d-block text-muted mt-1">
                                                    <i class="fas fa-user-check me-1"></i>{{ $purchase->finance_approver->username ?? 'Finance' }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $purchase->item_status_badge_class }} px-3 py-1 rounded-pill">
                                                {{ $purchase->item_status_text }}
                                            </span>
                                            @if($purchase->isItemReceived() && $purchase->received_at)
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <!-- View Details -->
                                                <a href="{{ route('project-purchases.show', $purchase->id) }}" 
                                                   class="btn btn-sm btn-outline-info border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Edit Button (only for pending PO) -->
                                                @if($purchase->canEdit())
                                                <a href="{{ route('project-purchases.edit', $purchase->id) }}" 
                                                   class="btn btn-sm btn-outline-primary border-0 px-2 action-btn"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endif

                                                <!-- Delete Button (only for pending PO) -->
                                                @if($purchase->canDelete())
                                                <form action="{{ route('project-purchases.destroy', $purchase->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Delete purchase order {{ $purchase->po_number }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                
                                                <!-- Approve Button (for pending PO - show only if user is finance) -->
                                                @if($purchase->isPending() && auth()->user()->role == 'finance')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal{{ $purchase->id }}"
                                                        title="Approve PO">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- Reject Button (for pending PO - show only if user is finance) -->
                                                @if($purchase->isPending() && auth()->user()->role == 'finance')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger border-0 px-2 action-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal{{ $purchase->id }}"
                                                        title="Reject PO">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @endif
                                                
                                                <!-- Mark as Received Button (for approved PO with pending receipt) -->
                                                @if($purchase->isApproved() && $purchase->isItemPending())
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success border-0 px-2 action-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#receiveModal{{ $purchase->id }}"
                                                        title="Mark as Received">
                                                    <i class="fas fa-box-open"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Approve Modal -->
                                    @if($purchase->isPending() && auth()->user()->role == 'finance')
                                    <div class="modal fade" id="approveModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <form action="{{ route('project-purchases.approve', $purchase->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Approve Purchase Order</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small mb-2">Are you sure you want to approve:</p>
                                                        <p class="fw-medium">{{ $purchase->po_number }} - {{ $purchase->material_name }}</p>
                                                        
                                                        @if(!$purchase->is_offline_order)
                                                        <div class="mb-2">
                                                            <label class="form-label small">Tracking Number</label>
                                                            <input type="text" name="tracking_number" class="form-control form-control-sm" 
                                                                   placeholder="Optional">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small">Resi Number</label>
                                                            <input type="text" name="resi_number" class="form-control form-control-sm" 
                                                                   placeholder="Optional">
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
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Reject Modal -->
                                    @if($purchase->isPending() && auth()->user()->role == 'finance')
                                    <div class="modal fade" id="rejectModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <form action="{{ route('project-purchases.reject', $purchase->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Reject Purchase Order</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small mb-2">Are you sure you want to reject:</p>
                                                        <p class="fw-medium">{{ $purchase->po_number }} - {{ $purchase->material_name }}</p>
                                                        
                                                        <div class="mb-2">
                                                            <label class="form-label small">Reason for Rejection <span class="text-danger">*</span></label>
                                                            <textarea name="finance_notes" class="form-control form-control-sm" rows="3" 
                                                                      placeholder="Required reason for rejection..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Receive Modal -->
                                    @if($purchase->isApproved() && $purchase->isItemPending())
                                    <div class="modal fade" id="receiveModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <form action="{{ route('project-purchases.mark-as-received', $purchase->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Mark as Received</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small mb-2">Mark the following items as received:</p>
                                                        <p class="fw-medium">{{ $purchase->po_number }}</p>
                                                        <p class="mb-0">{{ $purchase->material_name }}</p>
                                                        <p class="small text-muted">Qty: {{ number_format($purchase->quantity) }} {{ $purchase->unit->name ?? 'pcs' }}</p>
                                                        
                                                        @if($purchase->tracking_number || $purchase->resi_number)
                                                        <div class="alert alert-info p-2 mt-2">
                                                            <small>
                                                                @if($purchase->tracking_number)
                                                                    <div><i class="fas fa-truck me-1"></i>Tracking: {{ $purchase->tracking_number }}</div>
                                                                @endif
                                                                @if($purchase->resi_number)
                                                                    <div><i class="fas fa-barcode me-1"></i>Resi: {{ $purchase->resi_number }}</div>
                                                                @endif
                                                            </small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-success">Mark as Received</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                                <h5>No Purchase Orders Found</h5>
                                                @if(request()->anyFilled(['search', 'status', 'department_id', 'project_type', 'date', 'purchase_type', 'item_status', 'order_type']))
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
                
                <!-- Pagination Section - DaisyUI Version -->
                @if($purchases->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="text-muted small mb-2 mb-md-0">
                            Menampilkan {{ $purchases->firstItem() }} - {{ $purchases->lastItem() }} dari {{ $purchases->total() }} data
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <!-- DaisyUI Pagination -->
                            <div class="join">
                                <!-- Previous Page Link -->
                                @if($purchases->onFirstPage())
                                    <button class="join-item btn btn-disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                @else
                                    <a href="{{ $purchases->previousPageUrl() }}" class="join-item btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                @endif
                                
                                <!-- Page Numbers -->
                                @php
                                    $current = $purchases->currentPage();
                                    $last = $purchases->lastPage();
                                    
                                    // Menampilkan maksimal 5 nomor halaman
                                    $maxPages = 5;
                                    $start = max($current - floor($maxPages/2), 1);
                                    $end = min($start + $maxPages - 1, $last);
                                    
                                    if ($end - $start < $maxPages - 1) {
                                        $start = max($end - $maxPages + 1, 1);
                                    }
                                    
                                    // Tampilkan tombol halaman pertama jika tidak termasuk dalam range
                                    if ($start > 1) {
                                        echo '<a href="' . $purchases->url(1) . '" class="join-item btn">1</a>';
                                        if ($start > 2) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                    }
                                    
                                    // Tampilkan nomor halaman
                                    for ($i = $start; $i <= $end; $i++) {
                                        if ($i == $current) {
                                            echo '<button class="join-item btn btn-active">' . $i . '</button>';
                                        } else {
                                            echo '<a href="' . $purchases->url($i) . '" class="join-item btn">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Tampilkan tombol halaman terakhir jika tidak termasuk dalam range
                                    if ($end < $last) {
                                        if ($end < $last - 1) {
                                            echo '<button class="join-item btn btn-disabled">...</button>';
                                        }
                                        echo '<a href="' . $purchases->url($last) . '" class="join-item btn">' . $last . '</a>';
                                    }
                                @endphp
                                
                                <!-- Next Page Link -->
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
                            
                            <!-- Info Per Page -->
                            <div class="ms-3 d-none d-md-block">
                                <span class="small text-muted">10 data per halaman</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Styling for DaisyUI Pagination */
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
        white-space: nowrap;
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
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
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
        border: none;
    }

    .badge.bg-success {
        background-color: #04c665ff !important;
        color: white !important;
        border: none;
    }

    .badge.bg-danger {
        background-color: #ef4444 !important;
        color: white !important;
        border: none;
    }

    .badge.bg-secondary {
        background-color: #838b9bff !important;
        color: white !important;
        border: none;
    }

    .badge.bg-light {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #374151 !important;
    }

    .badge.bg-info {
        background-color: #0ea5e9 !important;
        color: white !important;
        border: none;
    }

    /* Modal styling */
    .modal-sm {
        max-width: 400px;
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

    /* Status indicators */
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .status-pending { background-color: #f59e0b; }
    .status-approved { background-color: #10b981; }
    .status-rejected { background-color: #ef4444; }
    .status-received { background-color: #0ea5e9; }
    .status-pending-receipt { background-color: #8b5cf6; }

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
        
        .action-btn {
            width: 36px;
            height: 36px;
        }
        
        .col-md-2, .col-md-3 {
            margin-bottom: 0.5rem;
        }
        
        .icon-shape {
            width: 32px;
            height: 32px;
        }
        
        .stats-cards .card-body h3 {
            font-size: 1.25rem;
        }
        
        .join {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .join .btn {
            margin: 2px;
            padding: 0.25rem 0.375rem;
            min-width: 32px;
            height: 28px;
        }
        
        .card-footer {
            padding: 1rem !important;
        }
    }
</style>

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

    // Initialize all modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            // Focus on first input in modal
            const input = this.querySelector('input, textarea, select');
            if (input) input.focus();
        });
    });

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