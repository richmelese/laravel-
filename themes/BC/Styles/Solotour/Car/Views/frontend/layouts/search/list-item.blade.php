<div class="row search-result-page bc_tours bc_tour--solo bc-service-result bc_hotel-result">
    <div class="col-lg-3 col-md-12">
        @livewire('car::filter',['lazy' => true])
    </div>
    <div class="col-lg-9 col-md-12">
        <div class="bc-list-item">
            <div class="topbar-search">
                <h2 class="text result-count">
                    @if($rows->total() > 1)
                        {{ __(":count cars found",['count'=>$rows->total()]) }}
                    @else
                        {{ __(":count car found",['count'=>$rows->total()]) }}
                    @endif
                </h2>
                <div class="control bc-form-order">
                    @include('Layout::global.search.orderby',['routeName'=>'car.search'])
                </div>
            </div>
            <div class="ajax-search-result">
                @include('Car::frontend.ajax.search-result')
            </div>
        </div>
    </div>
</div>
@push('js')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            $(document).on("click",".sidebar-item .item-title",function () {
                var t = $(this);
                t.parent().toggleClass('open');
                t.parent().find('.item-content').slideToggle();
            });
        })
    </script>
@endpush
