<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($hide_form_search)): ?>
    <div class="g-form-control">
        <ul class="nav nav-tabs" role="tablist">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if (empty($allServices[$service_type])) {
                            continue;
                        }
                        $module = new ($allServices[$service_type])();
                    ?>
                    <li role="bc_<?php echo e($service_type); ?>">
                        <a href="#bc_<?php echo e($service_type); ?>" class="<?php if($number == 0): ?> active <?php endif; ?>"
                            aria-controls="bc_<?php echo e($service_type); ?>" role="tab" data-toggle="tab">
                            <i class="<?php echo e($module->getServiceIconFeatured()); ?>"></i>
                            <?php echo e(!empty($modelBlock['title_for_' . $service_type]) ? $modelBlock['title_for_' . $service_type] : $module->getModelName()); ?>

                        </a>
                    </li>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </ul>
        <div class="tab-content">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if (empty($allServices[$service_type])) {
                            continue;
                        }
                        $module = new ($allServices[$service_type])();
                    ?>
                    <div role="tabpanel" class="tab-pane <?php if($number == 0): ?> active <?php endif; ?>"
                        id="bc_<?php echo e($service_type); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($service_type):
                            case ('visa'): ?>
                            <?php case ('tour'): ?>
                                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split($service_type . '::search-form', ['shouldRedirect' => true]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2153305152-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                            <?php break; ?>

                            <?php default: ?>
                                <?php if ($__env->exists(ucfirst($service_type) . '::frontend.layouts.search.form-search')) echo $__env->make(ucfirst($service_type) . '::frontend.layouts.search.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php break; ?>
                        <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
<?php else: ?>
    
    <div style="display: none;"></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Template/Views/frontend/blocks/form-search-all-service/form-search.blade.php ENDPATH**/ ?>