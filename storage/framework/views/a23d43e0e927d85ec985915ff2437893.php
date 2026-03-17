<?php
    $list_category = $model_category->with('translation')->get()->toTree();
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($list_category)): ?>
<div class="sidebar-widget widget_category">
    <div class="sidebar-title">
        <h2><?php echo e($item->title); ?></h2>
    </div>
    <ul>
        <?php
        $traverse = function ($categories, $prefix = '') use (&$traverse) {
            foreach ($categories as $category) {
                $translation = $category->translate();
                ?>
                    <li>
                        <span></span>
                        <a href="<?php echo e($category->getDetailUrl()); ?>"><?php echo e($prefix); ?> <?php echo e($translation->name); ?></a>
                    </li>
                <?php
                $traverse($category->children, $prefix . '-');
            }
        };
        $traverse($list_category);
        ?>
    </ul>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/News/Views/frontend/layouts/sidebars/category.blade.php ENDPATH**/ ?>