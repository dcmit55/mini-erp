{{-- resources/views/finance/purchase-approvals/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Purchase Approvals - Finance')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Purchase Approvals</h5>
                    <p class="text-muted small mb-0">Purchases waiting for finance approval</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dcm-costings.index') }}" 
                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-file-invoice-dollar me-1"></i> DCM Costings
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clipboard-list text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Pending Approvals</h6>
                                    <h4 class="mb-0" id="totalPending">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-calendar-alt text-success"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">This Month</h6>
                                    <h4 class="mb-0" id="thisMonth">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-money-bill-wave text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Amount</h6>
                                    <h4 class="mb-0" id="totalAmount">Rp 0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Avg. Days</h6>
                                    <h4 class="mb-0" id="avgProcessing">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('purchase-approvals.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Search</label>
                            <input type="text" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="PO, Item, Job Order...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Department</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Project Type</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="project_type">
                                <option value="">All Projects</option>
                                <option value="client" {{ request('project_type') == 'client' ? 'selected' : '' }}>Client Project</option>
                                <option value="internal" {{ request('project_type') == 'internal' ? 'selected' : '' }}>Internal Project</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Purchase Type</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="purchase_type">
                                <option value="">All Types</option>
                                <option value="restock" {{ request('purchase_type') == 'restock' ? 'selected' : '' }}>Restock</option>
                                <option value="new_item" {{ request('purchase_type') == 'new_item' ? 'selected' : '' }}>New Item</option>
                            </select>
                        </div>                        
                        <div class="col-md-1 d-flex align-items-end">
                            <div class="d-flex gap-1 w-100">
                                <button type="submit" class="btn btn-primary rounded-2 px-3 w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="{{ route('purchase-approvals.index') }}" 
                                   class="btn btn-outline-secondary rounded-2 px-3">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Bulk Action Toolbar -->
            <div id="bulkActionBar" class="card border-0 shadow-sm rounded-3 mb-3 d-none">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-dark fw-medium">
                            <span id="selectedCount">0</span> PO(s) selected
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-sm rounded-2 px-3" id="bulkApproveBtn">
                                <i class="fas fa-check me-1"></i> Approve All
                            </button>
                            <button type="button" class="btn btn-danger btn-sm rounded-2 px-3" id="bulkRejectBtn">
                                <i class="fas fa-times me-1"></i> Reject All
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 px-3" id="clearSelectionBtn">
                                <i class="fas fa-ban me-1"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    @if($purchases->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h6 class="text-muted">No Pending Approvals</h6>
                            <p class="small text-muted">All purchases have been processed by finance.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center" style="width: 100px;">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 px-2 py-1" id="selectAllBtn" style="font-size:0.75rem; white-space:nowrap;">
                                                <i class="fas fa-check-square me-1"></i> Select All
                                            </button>
                                        </th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center" style="width: 50px;">No</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">PO Number</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Date</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Department</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Project</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Items</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Supplier</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Amount</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Days</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $startNumber = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
                                    @endphp
                                    @foreach($purchases as $index => $purchase)
                                    <tr class="border-top purchase-row" data-po="{{ $purchase['po_number'] }}">
                                        <td class="px-3 py-2 text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox"
                                                   value="{{ $purchase['first_item_id'] }}"
                                                   data-po="{{ $purchase['po_number'] }}">
                                        </td>
                                        <td class="px-3 py-2 text-center text-muted">
                                            {{ $startNumber + $loop->index }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium text-dark">{{ $purchase['po_number'] }}</div>
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ $purchase['date']->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25 rounded-2 px-2 py-1">
                                                {{ $purchase['department']->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($purchase['project_type'] == 'client')
                                                <div>
                                                    <span class="fw-medium">{{ $purchase['project']->name ?? 'N/A' }}</span>
                                                    <br>
                                                </div>
                                            @else
                                                <div>
                                                    <span class="fw-medium">{{ $purchase['internalProject']->project ?? 'N/A' }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ $purchase['internalProject']->job ?? '' }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-2 px-2 py-1">
                                                {{ $purchase['total_items'] }} item(s)
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">{{ $purchase['supplier']->name ?? 'N/A' }}</td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="fw-medium text-primary">
                                                Rp {{ number_format($purchase['total_amount'], 0, ',', '.') }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            @php
                                                $daysPending = $purchase['created_at']->diffInDays(now());
                                            @endphp
                                            <span class="badge bg-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} bg-opacity-10 text-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border border-{{ $daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success') }} border-opacity-25 rounded-2 px-2 py-1">
                                                {{ $daysPending }} days
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <!-- VIEW DETAILS BUTTON - TANPA TARGET_BLANK -->
                                                <a href="{{ route('purchase-approvals.view-details', $purchase['first_item_id']) }}" 
                                                   class="btn btn-outline-info btn-sm rounded-2 px-2 py-1"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- QUICK APPROVE FORM -->
                                                <form action="{{ route('purchase-approvals.approve', $purchase['first_item_id']) }}" 
                                                      method="POST" 
                                                      class="d-inline quick-approve-form"
                                                      onsubmit="return confirm('Approve PO {{ $purchase['po_number'] }} with {{ $purchase['total_items'] }} items?')">
                                                    @csrf
                                                    <input type="hidden" name="finance_notes" value="">
                                                    <button type="submit" 
                                                            class="btn btn-outline-success btn-sm rounded-2 px-2 py-1"
                                                            title="Quick Approve All Items">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- APPROVE WITH NOTES BUTTON -->
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm rounded-2 px-2 py-1 approve-with-notes"
                                                        data-purchase-id="{{ $purchase['first_item_id'] }}"
                                                        data-po-number="{{ $purchase['po_number'] }}"
                                                        data-total-items="{{ $purchase['total_items'] }}"
                                                        data-total-amount="{{ $purchase['total_amount'] }}"
                                                        title="Approve with Notes">
                                                    <i class="fas fa-file-signature"></i>
                                                </button>
                                                
                                                <!-- REJECT BUTTON -->
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1 reject-purchase"
                                                        data-purchase-id="{{ $purchase['first_item_id'] }}"
                                                        data-po-number="{{ $purchase['po_number'] }}"
                                                        data-total-items="{{ $purchase['total_items'] }}"
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        @if($purchases->hasPages())
                        <div class="card-footer border-0 bg-light px-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} entries
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    @if($purchases->onFirstPage())
                                        <span class="btn btn-outline-secondary btn-sm rounded-2 px-3 disabled" style="font-size:0.78rem;">Previous</span>
                                    @else
                                        <a href="{{ $purchases->previousPageUrl() }}" class="btn btn-outline-primary btn-sm rounded-2 px-3" style="font-size:0.78rem;">Previous</a>
                                    @endif
                                    <span class="text-muted small">Page {{ $purchases->currentPage() }} of {{ $purchases->lastPage() }}</span>
                                    @if($purchases->hasMorePages())
                                        <a href="{{ $purchases->nextPageUrl() }}" class="btn btn-outline-primary btn-sm rounded-2 px-3" style="font-size:0.78rem;">Next</a>
                                    @else
                                        <span class="btn btn-outline-secondary btn-sm rounded-2 px-3 disabled" style="font-size:0.78rem;">Next</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Approve Modal -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Approve Purchase Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkApproveForm" method="POST" action="{{ route('purchase-approvals.bulk-approve') }}">
                @csrf
                <div id="bulkApproveHiddenIds"></div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Approving <strong><span id="bulkApproveCount">0</span> PO(s)</strong> at once.
                    </div>
                    <div id="bulkApprovePoList" class="mb-3 small text-muted"></div>
                    <div class="mb-3">
                        <label class="form-label">Finance Notes (Optional)</label>
                        <textarea name="finance_notes" class="form-control" rows="3"
                                  placeholder="Add notes for these approvals..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Confirm Approve Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Reject Purchase Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkRejectForm" method="POST" action="{{ route('purchase-approvals.bulk-reject') }}">
                @csrf
                <div id="bulkRejectHiddenIds"></div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Rejecting <strong><span id="bulkRejectCount">0</span> PO(s)</strong> at once.
                    </div>
                    <div id="bulkRejectPoList" class="mb-3 small text-muted"></div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="finance_notes" class="form-control" rows="4"
                                  placeholder="Explain why these purchase orders are being rejected..." required minlength="5"></textarea>
                        <div class="form-text">This reason will be recorded and visible to the requester.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="purchase_id" id="approvePurchaseId">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="approvePoNumber" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Items</label>
                        <input type="text" id="approveTotalItems" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount</label>
                        <input type="text" id="approveTotalAmount" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Finance Notes (Optional)</label>
                        <textarea name="finance_notes" class="form-control" rows="3" 
                                  placeholder="Add notes for this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="purchase_id" id="rejectPurchaseId">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="rejectPoNumber" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Items</label>
                        <input type="text" id="rejectTotalItems" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="finance_notes" class="form-control" rows="4" 
                                  placeholder="Explain why this purchase is being rejected..." required></textarea>
                        <div class="form-text">This reason will be recorded and visible to the requester.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        height: 42px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
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

    .btn-outline-danger {
        color: #ef4444;
        border-color: #ef4444;
    }

    .btn-outline-danger:hover {
        background-color: #ef4444;
        color: white;
    }

    .btn-outline-info {
        color: #0ea5e9;
        border-color: #0ea5e9;
    }

    .btn-outline-info:hover {
        background-color: #0ea5e9;
        color: white;
    }

    .btn-outline-secondary {
        color: #64748b;
        border-color: #e2e8f0;
    }

    .btn-outline-secondary:hover {
        background-color: #f1f5f9;
        color: #334155;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(79, 70, 229, 0.04);
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge.bg-secondary.bg-opacity-10 {
        background-color: rgba(108, 117, 125, 0.1) !important;
    }

    .badge.bg-primary.bg-opacity-10 {
        background-color: rgba(79, 70, 229, 0.1) !important;
    }

    .badge.bg-success.bg-opacity-10 {
        background-color: rgba(16, 185, 129, 0.1) !important;
    }

    .badge.bg-warning.bg-opacity-10 {
        background-color: rgba(245, 158, 11, 0.1) !important;
    }

    .badge.bg-danger.bg-opacity-10 {
        background-color: rgba(239, 68, 68, 0.1) !important;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
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

    .table td, .table th {
        vertical-align: middle;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #4f46e5;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        min-width: 26px;
        text-align: center;
        padding: 0.15rem 0.35rem;
        font-size: 0.7rem;
    }

    .page-link:hover {
        color: #4338ca;
        background-color: #f8f9fa;
        border-color: #e2e8f0;
    }

    .page-item.active .page-link {
        background-color: #4f46e5;
        border-color: #4f46e5;
        color: white;
    }

    .page-item.disabled .page-link {
        color: #9ca3af;
        background-color: #f9fafb;
        border-color: #e2e8f0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    setupEventListeners();
    setupBulkSelection();
    setInterval(loadStatistics, 30000);
});

async function loadStatistics() {
    try {
        const response = await fetch('{{ route("purchase-approvals.statistics") }}');
        const data = await response.json();

        document.getElementById('totalPending').textContent = data.total_pending;
        document.getElementById('thisMonth').textContent = data.this_month;
        document.getElementById('totalAmount').textContent = formatCurrency(data.total_amount);
        document.getElementById('avgProcessing').textContent = data.avg_processing_days + ' days';
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

function formatCurrency(amount) {
    return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function setupEventListeners() {
    // Approve with notes button
    document.querySelectorAll('.approve-with-notes').forEach(button => {
        button.addEventListener('click', function() {
            const purchaseId = this.dataset.purchaseId;
            const poNumber = this.dataset.poNumber;
            const totalItems = this.dataset.totalItems;
            const totalAmount = this.dataset.totalAmount;

            document.getElementById('approvePurchaseId').value = purchaseId;
            document.getElementById('approvePoNumber').value = poNumber;
            document.getElementById('approveTotalItems').value = totalItems + ' item(s)';
            document.getElementById('approveTotalAmount').value = 'Rp ' + formatNumber(totalAmount);
            document.getElementById('approveForm').action = '/purchase-approvals/' + purchaseId + '/approve';
            document.querySelector('#approveForm textarea').value = '';

            new bootstrap.Modal(document.getElementById('approveModal')).show();
        });
    });

    // Reject button
    document.querySelectorAll('.reject-purchase').forEach(button => {
        button.addEventListener('click', function() {
            const purchaseId = this.dataset.purchaseId;
            const poNumber = this.dataset.poNumber;
            const totalItems = this.dataset.totalItems;

            document.getElementById('rejectPurchaseId').value = purchaseId;
            document.getElementById('rejectPoNumber').value = poNumber;
            document.getElementById('rejectTotalItems').value = totalItems + ' item(s)';
            document.getElementById('rejectForm').action = '/purchase-approvals/' + purchaseId + '/reject';
            document.querySelector('#rejectForm textarea').value = '';

            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        });
    });
}

function setupBulkSelection() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountEl = document.getElementById('selectedCount');
    let allSelected = false;

    function getChecked() {
        return document.querySelectorAll('.row-checkbox:checked');
    }

    function updateBulkBar() {
        const checked = getChecked();
        const all = document.querySelectorAll('.row-checkbox');
        const count = checked.length;
        selectedCountEl.textContent = count;
        bulkActionBar.classList.toggle('d-none', count === 0);

        // Highlight selected rows
        all.forEach(cb => {
            cb.closest('tr').classList.toggle('table-active', cb.checked);
        });

        // Toggle button label
        allSelected = count === all.length && all.length > 0;
        if (selectAllBtn) {
            selectAllBtn.innerHTML = allSelected
                ? '<i class="fas fa-square me-1"></i> Deselect All'
                : '<i class="fas fa-check-square me-1"></i> Select All';
        }
    }

    // Select All button
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            const all = document.querySelectorAll('.row-checkbox');
            const shouldSelectAll = !allSelected;
            all.forEach(cb => cb.checked = shouldSelectAll);
            updateBulkBar();
        });
    }

    // Individual checkboxes
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    // Clear selection
    document.getElementById('clearSelectionBtn')?.addEventListener('click', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        updateBulkBar();
    });

    // Bulk Approve
    document.getElementById('bulkApproveBtn')?.addEventListener('click', function() {
        const checked = getChecked();
        if (checked.length === 0) return;

        const ids = Array.from(checked).map(cb => cb.value);
        const poNumbers = Array.from(checked).map(cb => cb.dataset.po);

        // Populate hidden inputs
        const container = document.getElementById('bulkApproveHiddenIds');
        container.innerHTML = ids.map(id => `<input type="hidden" name="purchase_ids[]" value="${id}">`).join('');

        document.getElementById('bulkApproveCount').textContent = checked.length;
        document.getElementById('bulkApprovePoList').innerHTML = 'PO: ' + poNumbers.join(', ');
        document.querySelector('#bulkApproveForm textarea').value = '';

        new bootstrap.Modal(document.getElementById('bulkApproveModal')).show();
    });

    // Bulk Reject
    document.getElementById('bulkRejectBtn')?.addEventListener('click', function() {
        const checked = getChecked();
        if (checked.length === 0) return;

        const ids = Array.from(checked).map(cb => cb.value);
        const poNumbers = Array.from(checked).map(cb => cb.dataset.po);

        const container = document.getElementById('bulkRejectHiddenIds');
        container.innerHTML = ids.map(id => `<input type="hidden" name="purchase_ids[]" value="${id}">`).join('');

        document.getElementById('bulkRejectCount').textContent = checked.length;
        document.getElementById('bulkRejectPoList').innerHTML = 'PO: ' + poNumbers.join(', ');
        document.querySelector('#bulkRejectForm textarea').value = '';

        new bootstrap.Modal(document.getElementById('bulkRejectModal')).show();
    });
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>
@endsection