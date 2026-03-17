
<?php $__env->startSection('title',__("Oops! It looks like you're lost.")); ?>
<?php $__env->startSection('message',!empty($exception->getMessage())? $exception->getMessage() :__("The page you're looking for isn't available. Try to search again or use the go to.")); ?>
<?php $__env->startSection('code',404); ?>

<?php echo $__env->make('errors.illustrated-layout',['title'=>__('Page not found')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/resources/views/errors/404.blade.php ENDPATH**/ ?>