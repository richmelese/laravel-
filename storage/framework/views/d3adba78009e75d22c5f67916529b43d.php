<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($translation->itinerary): ?>
    <div class="g-itinerary">
        <h3> <?php echo e(__("Itinerary")); ?> </h3>
        <div class="list-item owl-carousel">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $translation->itinerary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="item" style="background-image: url('<?php echo e(!empty($item['image_id']) ? get_file_url($item['image_id'],"full") : ""); ?>')">
                    <div class="header">
                        <div class="item-title"><?php echo e($item['title'] ?? ''); ?></div>
                        <div class="item-desc"><?php echo e($item['desc'] ?? ''); ?></div>
                    </div>
                    <div class="body">
                        <div class="item-title"><?php echo e($item['title'] ?? ''); ?></div>
                        <div class="item-desc"><?php echo e($item['desc'] ?? ''); ?></div>
                        <div class="item-context">
                            <?php echo clean($item['content']); ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Tour/Views/frontend/layouts/details/tour-itinerary.blade.php ENDPATH**/ ?>