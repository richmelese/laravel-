
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/bc/dist/frontend/module/news/css/news.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('themes/bc/dist/frontend/css/app.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/daterange/daterangepicker.css')); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/fotorama/fotorama.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc-news">
        <?php
            $title_page = setting_item_with_lang('news_page_list_title');
            if (!empty($custom_title_page)) {
                $title_page = $custom_title_page;
            }
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($title_page)): ?>
            <div class="bc_banner"
                <?php if($bg = setting_item('news_page_list_banner')): ?> style="background-image: url(<?php echo e(get_file_url($bg, 'full')); ?>)" <?php endif; ?>>
                <div class="container">
                    <h1>
                        <?php echo e($title_page); ?>

                    </h1>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php echo $__env->make('News::frontend.layouts.details.news-breadcrumb', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="bc_content">
            <div class="container">
                <div class="row">
                    <div class="col-md-9">
                        <?php echo $__env->make('News::frontend.layouts.details.news-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-3">
                        <?php echo $__env->make('News::frontend.layouts.details.news-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/fotorama/fotorama.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/News/Views/frontend/detail.blade.php ENDPATH**/ ?>