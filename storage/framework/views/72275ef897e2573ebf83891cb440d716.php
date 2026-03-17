<?php
    $selected = $this->attrs;
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($item['hide_in_filter_search'])): ?>
        <?php
            $translate = $item->translate();
        ?>
        <div class="g-filter-item">
            <div class="item-title">
                <h3> <?php echo e($translate->name); ?> </h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul style="max-height: 180px" class="overflow-auto">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $translate = $term->translate(); ?>
                        <li>
                            <div class="bc-checkbox">
                                <label>
                                    <input x-on:click="<?php echo e($scrollIntoViewJsSnippet); ?>"
                                           <?php if(in_array($term->slug,$selected[$item->id] ?? [])): ?> checked
                                           <?php endif; ?> type="checkbox" wire:change="toggleTerm(<?php echo e($item->id); ?>,'<?php echo e($term->slug); ?>')"
                                           value="<?php echo e($term->slug); ?>"> <?php echo $translate->name; ?>

                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Layout/livewire/search/filters/attrs.blade.php ENDPATH**/ ?>