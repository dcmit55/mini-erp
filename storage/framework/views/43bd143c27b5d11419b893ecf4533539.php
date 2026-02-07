<?php $__env->startSection('title', __('Page Not Found')); ?>
<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('message', __('Sorry, the page you are looking for doesn\'t exist. It might have been moved or deleted.')); ?>

<?php echo $__env->make('errors.minimal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/errors/404.blade.php ENDPATH**/ ?>