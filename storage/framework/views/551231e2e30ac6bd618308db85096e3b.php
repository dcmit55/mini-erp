

<?php $__env->startSection('title', 'DCM Costings'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">DCM Costings</h5>
                    <p class="text-muted small mb-0">Finance costing management</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo e(route('purchase-approvals.index')); ?>" 
                       class="btn btn-outline-primary btn-sm rounded-2 px-3">
                        <i class="fas fa-clipboard-check me-1"></i>Purchase Approvals
                    </a>
                    <a href="<?php echo e(route('dcm-costings.export')); ?>" 
                       class="btn btn-outline-success btn-sm rounded-2 px-3">
                        <i class="fas fa-download me-1"></i>Export
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
                                    <i class="fas fa-file-invoice-dollar text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Costings</h6>
                                    <h4 class="mb-0" id="totalCostings">0</h4>
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
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Approved</h6>
                                    <h4 class="mb-0" id="approvedCostings">0</h4>
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
                                    <h6 class="text-muted small mb-1">Pending</h6>
                                    <h4 class="mb-0" id="pendingCostings">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-times-circle text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Rejected</h6>
                                    <h4 class="mb-0" id="rejectedCostings">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="<?php echo e(route('dcm-costings.index')); ?>" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Search</label>
                            <input type="text" 
                                   class="form-control border-1 rounded-2 py-2 px-3" 
                                   name="search" 
                                   value="<?php echo e(request('search')); ?>"
                                   placeholder="PO, Item, Supplier...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Status</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo e(request('status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value="approved" <?php echo e(request('status') == 'approved' ? 'selected' : ''); ?>>Approved</option>
                                <option value="rejected" <?php echo e(request('status') == 'rejected' ? 'selected' : ''); ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label small text-dark">Department</label>
                            <select class="form-select border-1 rounded-2 py-2 px-3" name="department">
                                <option value="">All Departments</option>
                                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($dept); ?>" <?php echo e(request('department') == $dept ? 'selected' : ''); ?>>
                                        <?php echo e($dept); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <a href="<?php echo e(route('dcm-costings.index')); ?>" 
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

            <!-- Costings Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <?php if($costings->isEmpty()): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-file-invoice-dollar fa-3x text-muted"></i>
                            </div>
                            <h6 class="text-muted">No DCM Costings Found</h6>
                            <p class="small text-muted">Start by approving purchases from the Purchase Approvals page.</p>
                            <a href="<?php echo e(route('purchase-approvals.index')); ?>" 
                               class="btn btn-primary btn-sm rounded-2 px-3 mt-2">
                                <i class="fas fa-clipboard-check me-1"></i>Go to Purchase Approvals
                            </a>
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
                                        <th class="border-0 small text-dark fw-medium px-3 py-2">Status</th>
                                        <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $startNumber = ($costings->currentPage() - 1) * $costings->perPage() + 1;
                                    ?>
                                    <?php $__currentLoopData = $costings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $costing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-top">
                                        <td class="px-3 py-2 text-center text-muted">
                                            <?php echo e($startNumber + $loop->index); ?>

                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium text-dark"><?php echo e($costing->po_number); ?></div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <?php echo e($costing->date->format('d/m/Y')); ?>

                                            <br>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25 rounded-2 px-2 py-1">
                                                <?php echo e($costing->department); ?>

                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium"><?php echo e($costing->item_name); ?></div>
                                        </td>
                                        <td class="px-3 py-2"><?php echo e($costing->supplier); ?></td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="fw-medium text-primary">
                                                Rp <?php echo e(number_format($costing->invoice_total, 0, ',', '.')); ?>

                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <?php if($costing->status == 'approved'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-2 px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i>Approved
                                                </span>
                                            <?php elseif($costing->status == 'rejected'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-2 px-2 py-1">
                                                    <i class="fas fa-times-circle me-1"></i>Rejected
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-2 px-2 py-1">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                            <br>
                                            <?php if($costing->item_status == 'received'): ?>
                                                <small class="text-success">
                                                    <i class="fas fa-check me-1"></i>Received
                                                </small>
                                            <?php elseif($costing->item_status == 'not_received'): ?>
                                                <small class="text-danger">
                                                    <i class="fas fa-times me-1"></i>Not Received
                                                </small>
                                            <?php else: ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="<?php echo e(route('dcm-costings.show', $costing->uid)); ?>" 
                                                   class="btn btn-outline-info btn-sm rounded-2 px-2 py-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo e(route('dcm-costings.edit', $costing->uid)); ?>" 
                                                   class="btn btn-outline-primary btn-sm rounded-2 px-2 py-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="<?php echo e(route('dcm-costings.destroy', $costing->uid)); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Delete this DCM costing?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 px-2 py-1">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($costings->hasPages()): ?>
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing <?php echo e($costings->firstItem()); ?> to <?php echo e($costings->lastItem()); ?> of <?php echo e($costings->total()); ?> entries
                                </div>
                                <div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0">
                                            <!-- Previous Page Link -->
                                            <?php if($costings->onFirstPage()): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link py-1 px-3 rounded-2 me-1" aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </span>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2 me-1" 
                                                       href="<?php echo e($costings->previousPageUrl()); ?>"
                                                       aria-label="Previous">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <!-- Page Numbers -->
                                            <?php
                                                $current = $costings->currentPage();
                                                $last = $costings->lastPage();
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
                                                           href="<?php echo e($costings->url($i)); ?>"><?php echo e($i); ?></a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if($end < $last): ?>
                                                <li class="page-item"><span class="page-link py-1 px-3 rounded-2 me-1">...</span></li>
                                            <?php endif; ?>

                                            <!-- Next Page Link -->
                                            <?php if($costings->hasMorePages()): ?>
                                                <li class="page-item">
                                                    <a class="page-link py-1 px-3 rounded-2" 
                                                       href="<?php echo e($costings->nextPageUrl()); ?>"
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
    
    // Auto-refresh statistics every 30 seconds
    setInterval(loadStatistics, 30000);
});

async function loadStatistics() {
    try {
        const response = await fetch('<?php echo e(route("dcm-costings.statistics")); ?>');
        const data = await response.json();
        
        document.getElementById('totalCostings').textContent = data.total;
        document.getElementById('approvedCostings').textContent = data.approved;
        document.getElementById('pendingCostings').textContent = data.pending;
        document.getElementById('rejectedCostings').textContent = data.rejected;
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/finance/dcm-costings/index.blade.php ENDPATH**/ ?>