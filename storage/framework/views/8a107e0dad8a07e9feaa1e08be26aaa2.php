<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/bc/dist/frontend/module/car/css/car.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
<?php $__env->stopPush(); ?>
    <div class="bc_search_car">
        <div class="bc_banner"
            <?php if($bg = setting_item('car_page_search_banner')): ?> style="background-image: url(<?php echo e(get_file_url($bg, 'full')); ?>)" <?php endif; ?>>
            <div class="container">
                <h1>
                    <?php echo e(setting_item_with_lang('car_page_search_title')); ?>

                </h1>
            </div>
        </div>
        <div class="bc_form_search">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('car::search-form');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-749127247-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <?php echo $__env->make('Car::frontend.layouts.search.list-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script>
        $('.orderby .dropdown-item').on('click',function (e){
            e.preventDefault();
            $('[name=orderby]').val($(this).data('value')).trigger('change');
            $('.orderby .dropdown-toggle').html($(this).html());
        })
    </script>
<?php $__env->stopPush(); ?>
    <?php
        $__scriptKey = '749127247-0';
        ob_start();
    ?>
    <script>
        $('[name=orderby]', $wire.$el).on('change', function (e) {
            $wire.set('orderby', $(this).val());
        });
    </script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Car/Views/frontend/search.blade.php ENDPATH**/ ?>