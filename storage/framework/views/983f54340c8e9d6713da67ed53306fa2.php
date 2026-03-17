<div class="article">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->gallery): ?>
        <?php echo $__env->make('Layout::global.details.gallery', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?>
        <div class="header">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image_url = get_file_url($row->image_id, 'full')): ?>
                <header class="post-header">
                    <img src="<?php echo e($image_url); ?>" alt="<?php echo e($translation->title); ?>">
                </header>
                <div class="cate">
                    <?php $category = $row->category; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($category)): ?>
                        <?php $t = $category->translate(); ?>
                        <ul>
                            <li>
                                <a href="<?php echo e($category->getDetailUrl(app()->getLocale())); ?>">
                                    <?php echo e($t->name ?? ''); ?>

                                </a>
                            </li>
                        </ul>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <h2 class="title"><?php echo e($translation->title); ?></h2>
    <div class="post-info">
        <ul>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($row->author)): ?>
                <li>
                    <span> <?php echo e(__('BY ')); ?> </span>
                    <?php echo e($row->author->getDisplayName() ?? ''); ?>

                </li>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <li> <?php echo e(__('DATE ')); ?>  <?php echo e(display_date($row->updated_at)); ?>  </li>
        </ul>
    </div>
    <div class="post-content"> <?php echo $translation->content; ?></div>
    <div class="space-between">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($tags = $row->getTags()) and count($tags) > 0): ?>
            <div class="tags">
                <?php echo e(__("Tags:")); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $t = $tag->translate(); ?>
                    <a href="<?php echo e($tag->getDetailUrl(app()->getLocale())); ?>" class="tag-item"> <?php echo e($t->name ?? ''); ?> </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="share"> <?php echo e(__("Share")); ?>

            <a class="facebook share-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>" target="_blank" original-title="<?php echo e(__("Facebook")); ?>"><i class="fa fa-facebook fa-lg"></i></a>
            <a class="twitter share-item" href="https://twitter.com/share?url=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>" target="_blank" original-title="<?php echo e(__("X")); ?>"><i class="fa fa-twitter fa-lg"></i></a>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/News/Views/frontend/layouts/details/news-detail.blade.php ENDPATH**/ ?>