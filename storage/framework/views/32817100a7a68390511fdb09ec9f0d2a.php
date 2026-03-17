
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/bc/dist/frontend/module/tour/css/tour.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/fotorama/fotorama.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_detail_tour">
        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Tour::frontend.layouts.details.tour-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="bc_content">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-lg-9">
                        <?php $review_score = $row->review_data ?>
                        <?php echo $__env->make('Tour::frontend.layouts.details.tour-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Tour::frontend.layouts.details.tour-review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-12 col-lg-3">
                        <?php echo $__env->make('Tour::frontend.layouts.details.vendor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Tour::frontend.layouts.details.tour-form-book', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Tour::frontend.layouts.details.open-hours', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
                <div class="row end_tour_sticky">
                    <div class="col-md-12">
                        <?php echo $__env->make('Tour::frontend.layouts.details.tour-related', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="bc-more-book-mobile">
            <div class="container">
                <div class="left">
                    <div class="g-price">
                        <div class="prefix">
                            <span class="fr_text"><?php echo e(__('from')); ?></span>
                        </div>
                        <div class="price">
                            <span class="onsale"><?php echo e($row->display_sale_price); ?></span>
                            <span class="text-price"><?php echo e($row->display_price); ?></span>
                        </div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(setting_item('tour_enable_review')): ?>
                        <?php
                        $reviewData = $row->getScoreReview();
                        $score_total = $reviewData['score_total'];
                        ?>
                        <div class="service-review tour-review-<?php echo e($score_total); ?>">
                            <div class="list-star">
                                <ul class="booking-item-rating-stars">
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                </ul>
                                <div class="booking-item-rating-stars-active"
                                    style="width: <?php echo e($score_total * 2 * 10 ?? 0); ?>%">
                                    <ul class="booking-item-rating-stars">
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                            <span class="review">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reviewData['total_review'] > 1): ?>
                                    <?php echo e(__(':number Reviews', ['number' => $reviewData['total_review']])); ?>

                                <?php else: ?>
                                    <?php echo e(__(':number Review', ['number' => $reviewData['total_review']])); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div class="right">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->getBookingEnquiryType() === 'book'): ?>
                        <a class="btn btn-primary bc-button-book-mobile"><?php echo e(__('Book Now')); ?></a>
                    <?php else: ?>
                        <a class="btn btn-primary" data-toggle="modal"
                            data-target="#enquiry_form_modal"><?php echo e(__('Contact Now')); ?></a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

    <?php
        $__assetKey = '3403428403-0';

        ob_start();
    ?>
    <?php echo App\Helpers\MapEngine::scripts([
        'defer' => true,
    ]); ?>

    
    <?php
        $__output = ob_get_clean();

        // If the asset has already been loaded anywhere during this request, skip it...
        if (in_array($__assetKey, \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys)) {
            // Skip it...
        } else {
            \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys[] = $__assetKey;

            // Check if we're in a Livewire component or not and store the asset accordingly...
            if (isset($this)) {
                \Livewire\store($this)->push('assets', $__output, $__assetKey);
            } else {
                \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$nonLivewireAssets[$__assetKey] = $__output;
            }
        }
    ?>

<?php $__env->startPush('js'); ?>
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
                                iconUrl: "<?php echo e(get_file_url(setting_item('tour_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png')); ?>"
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
            no_date_select: '<?php echo e(__('Please select Start date')); ?>',
            no_guest_select: '<?php echo e(__('Please select at least one guest')); ?>',
            load_dates_url: '<?php echo e(route('tour.vendor.availability.loadDates')); ?>',
            name_required: '<?php echo e(__('Name is Required')); ?>',
            email_required: '<?php echo e(__('Email is Required')); ?>',
        };
    </script>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/fotorama/fotorama.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/sticky/jquery.sticky.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/tour/js/single-tour.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Tour/Views/frontend/detail.blade.php ENDPATH**/ ?>