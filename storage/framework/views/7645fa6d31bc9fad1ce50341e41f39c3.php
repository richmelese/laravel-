

<?php $__env->startSection('content'); ?>
    <div class="container">
        <div class="row justify-content-center bc-login-form-page bc-login-page">
            <div class="col-md-5">
                <?php echo $__env->make('Layout::admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="">
                    <h4 class="form-title"><?php echo e(__('Login')); ?></h4>
                    <?php echo $__env->make('Layout::auth.login-form',['captcha_action'=>'login_normal'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\resources\views/auth/login.blade.php ENDPATH**/ ?>