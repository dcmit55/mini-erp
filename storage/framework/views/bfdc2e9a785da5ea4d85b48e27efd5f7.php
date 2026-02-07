<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h3 class="mb-4 text-primary"><?php echo e($project->name); ?></h3>
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="w-50">Total Project Cost <span class="text-muted">(material)</span></th>
                                <td>
                                    <span class="fw-bold text-success">
                                        Rp <?php echo e(number_format($grandTotal, 0, ',', '.')); ?>

                                    </span>
                                </td>
                            </tr>
                            <tr>

                            </tr>
                        </tbody>

                            </td>
                        </tr>
                        <tr>
                            <th>Total Project Day Count</th>
                            <td>
                                <span class="fw-bold"><?php echo e($dayCount ?? '-'); ?> Days</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Manpower Envolved</th>
                            <td>
                                <span class="fw-bold"><?php echo e($manpowerCount); ?> People</span>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                    <?php if(!empty($partOutputs) && count($partOutputs)): ?>
                        <hr>
                        <h5 class="mt-4 mb-3 text-secondary">Total Output per Part</h5>
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Part Name</th>
                                    <th class="text-end">Total Output</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $partOutputs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($part['name']); ?></td>
                                        <td class="text-end fw-bold"><?php echo e($part['qty']); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info mt-4 mb-0">
                            <i class="bi bi-info-circle"></i> Project ini tidak memiliki part.
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo e(route('final_project_summary.index')); ?>" class="btn btn-outline-secondary mt-3">
                        &larr; Back to Final Project Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/finance/final_project_summary/show.blade.php ENDPATH**/ ?>