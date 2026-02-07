<?php $__env->startSection('title', __('Internal Server Error')); ?>
<?php $__env->startSection('code', '500'); ?>
<?php $__env->startSection('message', __('Something went wrong on our end. Our team has been notified and is working to fix this issue.')); ?>

<?php echo $__env->make('errors.minimal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/errors/500.blade.php ENDPATH**/ ?>