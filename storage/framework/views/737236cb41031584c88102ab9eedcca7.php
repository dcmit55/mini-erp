

<?php $__env->startSection('title', 'Purchase Approvals - Finance'); ?>

<?php $__env->startSection('content'); ?>
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
                    <a href="<?php echo e(route('dcm-costings.index')); ?>" 
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
                    <form method="GET" action="<?php echo e(route('purchase-approvals.index')); ?>" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Search</label>
                            <input type="text" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="search" 
                                   value="<?php echo e(request('search')); ?>"
                                   placeholder="PO, Item, Job Order...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Department</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="department">
                                <option value="">All Departments</option>
                                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($dept->id); ?>" <?php echo e(request('department') == $dept->id ? 'selected' : ''); ?>>
                                        <?php echo e($dept->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Purchase Type</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="purchase_type">
                                <option value="">All Types</option>
                                <option value="restock" <?php echo e(request('purchase_type') == 'restock' ? 'selected' : ''); ?>>Restock</option>
                                <option value="new_item" <?php echo e(request('purchase_type') == 'new_item' ? 'selected' : ''); ?>>New Item</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Start Date</label>
                            <input type="date" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="start_date" 
                                   value="<?php echo e(request('start_date')); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">End Date</label>
                            <input type="date" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="end_date" 
                                   value="<?php echo e(request('end_date')); ?>">
                        </div>
                        
                        <div class="col-md-1 d-flex align-items-end">
                            <div class="d-flex gap-1 w-100">
                                <button type="submit" class="btn btn-primary rounded-2 px-3 w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="<?php echo e(route('purchase-approvals.index')); ?>" 
                                   class="btn btn-outline-secondary rounded-2 px-3">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Purchases Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <?php if($purchases->isEmpty()): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h6 class="text-muted">No Pending Approvals</h6>
                            <p class="small text-muted">All purchases have been processed by finance.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-center" style="width: 50px;">No</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">PO Number</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Date</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Department</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Item</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Supplier</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Amount</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Days</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $startNumber = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
                                    ?>
                                    <?php $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-top">
                                        <td class="px-3 py-2 text-center text-muted">
                                            <?php echo e($startNumber + $loop->index); ?>

                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium text-dark"><?php echo e($purchase->po_number); ?></div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <?php echo e($purchase->date->format('d/m/Y')); ?>

                                            <br>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25 rounded-2 px-2 py-1">
                                                <?php echo e($purchase->department->name ?? 'N/A'); ?>

                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium"><?php echo e($purchase->material_name ?? 'N/A'); ?></div>
                                        </td>
                                        <td class="px-3 py-2"><?php echo e($purchase->supplier->name ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="fw-medium text-primary">
                                                Rp <?php echo e(number_format($purchase->invoice_total, 0, ',', '.')); ?>

                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <?php
                                                $daysPending = $purchase->created_at->diffInDays(now());
                                            ?>
                                            <span class="badge bg-<?php echo e($daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success')); ?> bg-opacity-10 text-<?php echo e($daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success')); ?> border border-<?php echo e($daysPending > 7 ? 'danger' : ($daysPending > 3 ? 'warning' : 'success')); ?> border-opacity-25 rounded-2 px-2 py-1">
                                                <?php echo e($daysPending); ?> days
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <!-- VIEW BUTTON -->
                                                <a href="<?php echo e(route('project-purchases.show', $purchase->id)); ?>" 
                                                   target="_blank"
                                                   class="btn btn-outline-info btn-sm rounded-2 px-2 py-1"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- QUICK APPROVE FORM (SIMPLE) -->
                                                <form action="<?php echo e(route('purchase-approvals.approve', $purchase->id)); ?>" 
                                                      method="POST" 
                                                      class="d-inline quick-approve-form"
                                                      onsubmit="return confirm('Approve purchase <?php echo e($purchase->po_number); ?>?')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="finance_notes" value="">
                                                    <button type="submit" 
                                                            class="btn btn-outline-success btn-sm rounded-2 px-2 py-1"
                                                            title="Quick Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- APPROVE WITH NOTES BUTTON -->
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm rounded-2 px-2 py-1 approve-with-notes"
                                                        data-purchase-id="<?php echo e($purchase->id); ?>"
                                                        data-po-number="<?php echo e($purchase->po_number); ?>"
                                                        title="Approve with Notes">
                                                    <i class="fas fa-file-signature"></i>
                                                </button>
                                                
                                                <!-- REJECT BUTTON -->
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1 reject-purchase"
                                                        data-purchase-id="<?php echo e($purchase->id); ?>"
                                                        data-po-number="<?php echo e($purchase->po_number); ?>"
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($purchases->hasPages()): ?>
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing <?php echo e($purchases->firstItem()); ?> to <?php echo e($purchases->lastItem()); ?> of <?php echo e($purchases->total()); ?> entries
                                </div>
                                <div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0">
                                            <!-- Previous Page Link -->
                                            <?php if($purchases->onFirstPage()): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link py-1 px-3 rounded-2 me-1" aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </span>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2 me-1" 
                                                       href="<?php echo e($purchases->previousPageUrl()); ?>"
                                                       aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <!-- Page Numbers -->
                                            <?php
                                                $current = $purchases->currentPage();
                                                $last = $purchases->lastPage();
                                                $start = max($current - 2, 1);
                                                $end = min($current + 2, $last);
                                                
                                                if ($start > 1) {
                                                    echo '<li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>';
                                                }
                                            ?>
                                            
                                            <?php for($i = $start; $i <= $end; $i++): ?>
                                                <?php if($i == $current): ?>
                                                    <li class="page-item active">
                                                        <span class="page-link py-1 px-3 rounded-2 me-1"><?php echo e($i); ?></span>
                                                    </li>
                                                <?php else: ?>
                                                    <li class="page-item">
                                                        <a class="page-link py-1 px-3 rounded-2 me-1" 
                                                           href="<?php echo e($purchases->url($i)); ?>"><?php echo e($i); ?></a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if($end < $last): ?>
                                                <li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>
                                            <?php endif; ?>

                                            <!-- Next Page Link -->
                                            <?php if($purchases->hasMorePages()): ?>
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2" 
                                                       href="<?php echo e($purchases->nextPageUrl()); ?>"
                                                       aria-label="Next">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link py-1 px-3 rounded-2" aria-label="Next">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="purchase_id" id="approvePurchaseId">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="approvePoNumber" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Finance Notes (Optional)</label>
                        <textarea name="finance_notes" class="form-control" rows="3" 
                                  placeholder="Add notes for this approval..."></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tracking Number (Optional)</label>
                                <input type="text" name="tracking_number" class="form-control" 
                                       placeholder="Enter tracking number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Resi Number (Optional)</label>
                                <input type="text" name="resi_number" class="form-control" 
                                       placeholder="Enter resi number">
                            </div>
                        </div>
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
                <h5 class="modal-title">Reject Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="purchase_id" id="rejectPurchaseId">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="rejectPoNumber" class="form-control" readonly>
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

    .table-hover tbody tr:hover {
        background-color: rgba(79, 70, 229, 0.04);
    }

    .badge {
        font-size: 0.75rem;
        font-weight: 500;
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

    .page-link {
        color: #4f46e5;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        min-width: 36px;
        text-align: center;
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
    // Load statistics
    loadStatistics();
    
    // Setup button event listeners
    setupEventListeners();
    
    // Auto-refresh statistics every 30 seconds
    setInterval(loadStatistics, 30000);
});

async function loadStatistics() {
    try {
        const response = await fetch('<?php echo e(route("purchase-approvals.statistics")); ?>');
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
            
            document.getElementById('approvePurchaseId').value = purchaseId;
            document.getElementById('approvePoNumber').value = poNumber;
            document.getElementById('approveForm').action = `/purchase-approvals/${purchaseId}/approve`;
            
            // Clear previous values
            document.querySelector('#approveForm textarea').value = '';
            document.querySelector('#approveForm input[name="tracking_number"]').value = '';
            document.querySelector('#approveForm input[name="resi_number"]').value = '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        });
    });
    
    // Reject button
    document.querySelectorAll('.reject-purchase').forEach(button => {
        button.addEventListener('click', function() {
            const purchaseId = this.dataset.purchaseId;
            const poNumber = this.dataset.poNumber;
            
            document.getElementById('rejectPurchaseId').value = purchaseId;
            document.getElementById('rejectPoNumber').value = poNumber;
            document.getElementById('rejectForm').action = `/purchase-approvals/${purchaseId}/reject`;
            
            // Clear previous value
            document.querySelector('#rejectForm textarea').value = '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        });
    });
    
    // Quick approve form
    document.querySelectorAll('.quick-approve-form').forEach(form => {
        // CSRF token sudah ada di form
        console.log('Quick approve form found');
    });
}

// Handle form submissions dengan AJAX untuk feedback lebih baik
document.addEventListener('submit', function(e) {
    // Untuk form approve dan reject, kita biarkan submit normal
    // Tapi tambahkan loading state
    if (e.target.matches('#approveForm, #rejectForm, .quick-approve-form')) {
        const submitButton = e.target.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        // Show loading
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitButton.disabled = true;
        
        // Re-enable setelah 5 detik (jika ada masalah)
        setTimeout(() => {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }, 5000);
    }
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/finance/purchase-approvals/index.blade.php ENDPATH**/ ?>