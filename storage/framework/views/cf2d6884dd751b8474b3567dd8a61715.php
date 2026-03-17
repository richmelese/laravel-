<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($hideMap)): ?>
<div class="item">
    <a href="<?php echo e(route($routeName,['_layout'=>'map'])); ?>"><?php echo e(__("Show on the map")); ?></a>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<div class="item orderby">
    <?php
        $param = request()->input();
        $orderby =  request()->input("orderby");
    ?>
    <div class="item-title">
        <?php echo e(__("Sort by:")); ?>

    </div>
    <input type="hidden" wire:model.live="orderby" name="orderby" value="<?php echo e($orderby); ?>">
    <div class="dropdown " wire:ignore>
        <span class=" dropdown-toggle"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($orderby):
                case ("price_low_high"): ?>
                <?php echo e(__("Price (Low to high)")); ?>

                <?php break; ?>
                <?php case ("price_high_low"): ?>
                <?php echo e(__("Price (High to low)")); ?>

                <?php break; ?>
                <?php case ("rate_high_low"): ?>
                <?php echo e(__("Rating (High to low)")); ?>

                <?php break; ?>
                <?php default: ?>
                <?php echo e(__("Recommended")); ?>

            <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </span>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item" href="#" data-value=""><?php echo e(__("Recommended")); ?></a>
            <a class="dropdown-item" href="#" data-value="price_low_high"><?php echo e(__("Price (Low to high)")); ?></a>
            <a class="dropdown-item" href="#" data-value="price_high_low"><?php echo e(__("Price (High to low)")); ?></a>
            <a class="dropdown-item" href="#" data-value="rate_high_low"><?php echo e(__("Rating (High to low)")); ?></a>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Layout/global/search/orderby.blade.php ENDPATH**/ ?>