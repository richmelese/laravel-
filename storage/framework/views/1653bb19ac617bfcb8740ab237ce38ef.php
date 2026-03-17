
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/bc/dist/frontend/module/space/css/space.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_search_space">
        <div class="bc_banner"
            <?php if($bg = setting_item('space_page_search_banner')): ?> style="background-image: url(<?php echo e(get_file_url($bg, 'full')); ?>)" <?php endif; ?>>
            <div class="container">
                <h1>
                    <?php echo e(setting_item_with_lang('space_page_search_title')); ?>

                </h1>
            </div>
        </div>
        <div class="bc_form_search">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <?php echo $__env->make('Space::frontend.layouts.search.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <?php echo $__env->make('Space::frontend.layouts.search.list-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('js/filter.js?_ver=' . config('app.asset_version'))); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/space/js/space.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Space/Views/frontend/search.blade.php ENDPATH**/ ?>