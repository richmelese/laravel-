<div wire:ignore class="bc_filter_ sidebar-filter" x-data x-init="$nextTick(() => {
            window.bcInitFilterJs();
    })">
    <?php
    $scrollIntoViewJsSnippet = <<<JS
       document.querySelector('body').scrollIntoView({
        behavior: 'smooth'
       })
    JS;
    ?>
    <div class="sidebar-filter">
        <div class="sidebar-item-wrapper">
            <h3 class="sidebar-title">{{ __('Filter By') }}
                <span class="d-md-none hidden-lg hidden-md close-filter">
                    <i class="input-icon st-border-radius field-icon fa">
                        <svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <defs></defs>
                            <g id="Ico_close" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                                <g stroke="#1A2B48" stroke-width="1.5">
                                <g id="close">
                                    <path d="M0.75,23.249 L23.25,0.749"></path>
                                    <path d="M23.25,23.249 L0.75,0.749"></path>
                                </g>
                            </g>
                        </g>
                    </svg></i>
                </span>
            </h3>
            <div class="sidebar-item range-slider">
                <div class="item-title">
                    <label>{{ __('Filter Price') }}</label>
                    <i class="fa fa-angle-up" aria-hidden="true"></i>
                </div>
                <div class="item-content">
                <div class="bc-filter-price" wire:ignore>
                        <?php
                        $price_min = $pri_from = floor(App\Currency::convertPrice($tour_min_max_price[0]));
                        $price_max = $pri_to = ceil(App\Currency::convertPrice($tour_min_max_price[1]));
                        if (!empty(($price_range))) {
                            [$pri_from, $pri_to] = explode(';', $price_range);
                        }
                        $currency = App\Currency::getCurrency(App\Currency::getCurrent());
                        ?>
                        <input type="hidden" class="filter-price irs-hidden-input" name="price_range"
                            data-symbol=" {{ $currency['symbol'] ?? '' }}" data-min="{{ $price_min }}"
                            data-max="{{ $price_max }}" data-from="{{ $pri_from }}" data-to="{{ $pri_to }}"
                            readonly="" value="{{ $price_range }}">
                    </div>
                </div>
            </div>
            <div class="sidebar-item pag bc_icheck">
                <div class="item-title">
                    <label>{{ __('Review Score') }}</label>
                    <i class="fa fa-angle-up" aria-hidden="true"></i>
                </div>
                <div class="item-content">
                    <ul>
                        @for ($number = 5; $number >= 1; $number--)
                            
                            <li class=" bc_icheck-item">
                                <label>
                                    @for ($review_score = 1; $review_score <= $number; $review_score++)
                                        <i class="fa fa-star"></i>
                                    @endfor
                                    <input class="filter-tax" wire:model.live="review_score" type="checkbox" value="{{ $number }}"
                                            @if (in_array($number, $this->review_score)) checked @endif>
                                    <span class="checkmark fcheckbox"></span>
                                </label>
                            </li>
                        @endfor
                    </ul>
                </div>
            </div>
            <div class="sidebar-item pag bc_icheck">
                <div class="item-title">
                    <label>{{ __('Tour Type') }}</label>
                    <i class="fa fa-angle-up" aria-hidden="true"></i>
                </div>
                <div class="item-content">
                    <ul style="max-height: 180px" class="overflow-auto">
                        <?php
                        $current_category_ids = $this->cat_id;
                        $traverse = function ($categories, $prefix = '')
                        use (&$traverse, $current_category_ids, $scrollIntoViewJsSnippet) {
                            $i = 0;
                            foreach ($categories as $category) {
                                $checked = '';
                                if (!empty($current_category_ids)) {
                                    foreach ($current_category_ids as $key => $current) {
                                        if ($current == $category->slug)
                                            $checked = 'checked';
                                    }
                                }
                                $traslate = $category->translate()
                        ?>
                            <li class="bc_icheck-item">
                                <label>
                                    {{ $prefix }} {{ $traslate->name }}
                                    <input class="filter-tax" x-on:click="{{$scrollIntoViewJsSnippet}}" wire:model.live="cat_id"
                                            {{ $checked }} type="checkbox"
                                            value="{{ $category->slug }}">
                                            <span class="checkmark fcheckbox"></span>
                                </label>
                            </li>
                        <?php
                                $i++;
                                $traverse($category->children, $prefix . '-');
                            }
                        };
                        $traverse($this->tour_category);
                        ?>
                    </ul>
                </div>
            </div>
            @include('Layout::livewire.search.filters.attrs',['attributes' => $this->tour_attributes])
        </div>
    </div>
</div>
@script
<script>
    if (typeof window.bcInitFilterJs !== 'function') {
        window.bcInitFilterJs = function() {
            window.toggleEclose();
            // Initialize ion range slider
            $(".bc-filter-price").each(function() {
                $(this).find('.irs').remove();
                var input_price = $(this).find(".filter-price");
                if (input_price.data("ionRangeSlider")) {
                    input_price.data("ionRangeSlider").destroy();
                }
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
                    onFinish: function(data) {

                        Livewire.dispatch('onFilterPriceChanged', {
                            params: {
                                data_from: data.from,
                                data_to: data.to
                            }
                        });
                    }
                });
            });
        }
    }
    document.addEventListener('livewire:init', () => {
        if (typeof window.bcInitFilterJs === 'function') {
            window.bcInitFilterJs();
        }
    });
    document.addEventListener('livewire:navigated', () => {
        if (typeof window.bcInitFilterJs === 'function') {
            window.bcInitFilterJs();
        }
    });
</script>
@endscript