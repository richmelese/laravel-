    <?php $layout->viewContext->mergeIntoNewEnvironment($__env); ?>

    

    <?php $__env->startSection($layout->slotOrSection); ?>
        <?php echo $content; ?>

    <?php $__env->stopSection(); ?>
<?php echo $__env->make($layout->view, $layout->params, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\storage\framework\views/fb053b248f796d2fd5388f5ce7190883.blade.php ENDPATH**/ ?>