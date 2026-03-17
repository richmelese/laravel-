<div wire:ignore class="bc_filter"  x-data x-init="window.bcInitFilterJs()">
    <?php
    $scrollIntoViewJsSnippet = <<<JS
       document.querySelector('body').scrollIntoView({
        behavior: 'smooth'
       })
    JS;
    ?>
        <div class="filter-title">
            <?php echo e(__('FILTER BY')); ?>

        </div>
        <div class="g-filter-item">
            <div class="item-title">
                <h3><?php echo e(__('Filter Price')); ?></h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <div class="bc-filter-price" wire:ignore>
                    <?php
                    $price_min = $pri_from = floor(App\Currency::convertPrice($car_min_max_price[0]));
                    $price_max = $pri_to = ceil(App\Currency::convertPrice($car_min_max_price[1]));
                    if (!empty(($price_range = Request::query('price_range')))) {
                        $pri_from = explode(';', $price_range)[0];
                        $pri_to = explode(';', $price_range)[1];
                    }
                    $currency = App\Currency::getCurrency(App\Currency::getCurrent());
                    ?>
                    <input type="hidden" class="filter-price irs-hidden-input" name="price_range"
                        data-symbol=" <?php echo e($currency['symbol'] ?? ''); ?>" data-min="<?php echo e($price_min); ?>"
                        data-max="<?php echo e($price_max); ?>" data-from="<?php echo e($pri_from); ?>" data-to="<?php echo e($pri_to); ?>"
                        readonly="" value="<?php echo e($price_range); ?>">
                    <button type="submit" class="btn btn-link btn-apply-price-range"><?php echo e(__('APPLY')); ?></button>
                </div>
            </div>
        </div>
        <div class="g-filter-item">
            <div class="item-title">
                <h3><?php echo e(__('Review Score')); ?></h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($number = 5; $number >= 1; $number--): ?>
                        <li>
                            <div class="bc-checkbox">
                                <label>
                                    <input wire:model.live="review_score" type="checkbox" value="<?php echo e($number); ?>"
                                        <?php if(in_array($number, request()->query('review_score', []))): ?> checked <?php endif; ?>>
                                    <span class="checkmark"></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($review_score = 1; $review_score <= $number; $review_score++): ?>
                                        <i class="fa fa-star"></i>
                                    <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </label>
                            </div>
                        </li>
                    <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>
        </div>
        <?php echo $__env->make('Layout::livewire.search.filters.attrs',['attributes' => $this->car_attributes], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
    <?php
        $__scriptKey = '3689468134-0';
        ob_start();
    ?>
<script>
    window.bcInitFilterJs = function () {
        window.toggleEclose();
        // Initialize ion range slider
        $(".bc-filter-price").each(function () {
            var input_price = $(this).find(".filter-price");
            var min = input_price.data("min");
            var max = input_price.data("max");
            var from = input_price.data("from");
            var to = input_price.data("to");
            var symbol = input_price.data("symbol");
            input_price.ionRangeSlider({
                type: "double",
                grid: true,
                min: min,
                max: max,
                from: from,
                to: to,
                prefix: symbol,
                onFinish: function (data) {
                    $wire.set('price_range', data.from + ';' + data.to);
                }
            });
        });
    }
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Car/Views/frontend/components/filter/index.blade.php ENDPATH**/ ?>