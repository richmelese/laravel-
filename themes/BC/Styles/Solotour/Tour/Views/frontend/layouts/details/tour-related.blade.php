@if(count($tour_related) > 0)
    <div class="bc_list-service--bg bc_list-service--slider"
         style="background-image: url('https://solotour.travelerwp.com/wp-content/uploads/2015/01/bg_tour_relates.png');">
        <div class="container bg__pd">
            <div class="bc_hr large"></div>
            <h2 class="heading text-center  mt50">You might also like</h2>
            <div class="bc_list-tour-related bc_list-tour--slide owl-carousel mt50">
                @foreach($tour_related as $k=>$item)
                    @include('Tour::frontend.layouts.search.loop-grid',['row'=>$item,'include_param'=>0])
                @endforeach
            </div>
        </div>
    </div>
@endif
