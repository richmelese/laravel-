
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/bc/dist/frontend/module/space/css/space.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/fotorama/fotorama.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_detail_space">
        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Space::frontend.layouts.details.space-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="bc_content">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-lg-9">
                        <?php $review_score = $row->review_data ?>
                        <?php echo $__env->make('Space::frontend.layouts.details.space-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Space::frontend.layouts.details.space-review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-12 col-lg-3">
                        <?php echo $__env->make('Tour::frontend.layouts.details.vendor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Space::frontend.layouts.details.space-form-book', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
                <div class="row end_tour_sticky">
                    <div class="col-md-12">
                        <?php echo $__env->make('Space::frontend.layouts.details.space-related', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $__env->make('Space::frontend.layouts.details.space-form-book-mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <?php echo App\Helpers\MapEngine::scripts(); ?>

    <script>
        jQuery(function($) {
            <?php if($row->map_lat && $row->map_lng): ?>
                new BCMapEngine('map_content', {
                    disableScripts: true,
                    fitBounds: true,
                    center: [<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>],
                    zoom: <?php echo e($row->map_zoom ?? '8'); ?>,
                    ready: function(engineMap) {
                        engineMap.addMarker([<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>], {
                            icon_options: {
                                iconUrl: "<?php echo e(get_file_url(setting_item('space_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png')); ?>"
                            }
                        });
                    }
                });
            <?php endif; ?>
        })
    </script>
    <script>
        var bc_booking_data = <?php echo json_encode($booking_data); ?>

        var bc_booking_i18n = {
            no_date_select: '<?php echo e(__('Please select Start and End date')); ?>',
            no_guest_select: '<?php echo e(__('Please select at least one guest')); ?>',
            load_dates_url: '<?php echo e(route('space.vendor.availability.loadDates')); ?>',
            name_required: '<?php echo e(__('Name is Required')); ?>',
            email_required: '<?php echo e(__('Email is Required')); ?>',
        };
    </script>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/fotorama/fotorama.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/sticky/jquery.sticky.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/space/js/single-space.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Space/Views/frontend/detail.blade.php ENDPATH**/ ?>