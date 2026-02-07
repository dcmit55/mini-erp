<?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <tr>
        <td class="fw-semibold"><?php echo e($project->name); ?></td>
        <td>
            <?php if($project->departments->count()): ?>
                <?php echo e($project->departments->pluck('name')->map(fn($name) => ucfirst($name))->implode(', ')); ?>

            <?php else: ?>
                -
            <?php endif; ?>
        </td>
        <td>
            <a href="<?php echo e(route('final_project_summary.show', $project)); ?>" class="btn btn-success btn-sm">
                <i class="bi bi-eye"></i> View Final Summary
            </a>
        </td>
    </tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr class="no-data-row">
        <td colspan="3" class="text-center text-muted py-4">
            <i class="bi bi-search"></i> No projects found.
        </td>
    </tr>
<?php endif; ?>
<?php /**PATH D:\27JAN\resources\views/finance/final_project_summary/project_table.blade.php ENDPATH**/ ?>