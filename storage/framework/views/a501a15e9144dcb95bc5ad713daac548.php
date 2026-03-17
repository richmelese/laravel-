<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($breadcrumbs)): ?>
    <div class="blog-breadcrumb hidden-xs">
        <div class="container">
            <ol class="ul" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="<?php echo e(url('/')); ?>" itemprop="item"><span itemprop="name"><?php echo e(__('Home')); ?></span></a>
                    <meta itemprop="position" content="1" /></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$breadcrumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class=" <?php echo e($breadcrumb['class'] ?? ''); ?>" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($breadcrumb['url'])): ?>
                            <a href="<?php echo e(url($breadcrumb['url'])); ?>" itemscope itemtype="https://schema.org/WebPage" itemprop="item" itemid="<?php echo e(url($breadcrumb['url'])); ?>"><span itemprop="name"><?php echo e($breadcrumb['name']); ?></span></a>
                        <?php else: ?>
                            <span itemprop="name"><?php echo e($breadcrumb['name']); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <meta itemprop="position" content="<?php echo e($k + 2); ?>" />
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </ol>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\modules/Layout/parts/bc.blade.php ENDPATH**/ ?>