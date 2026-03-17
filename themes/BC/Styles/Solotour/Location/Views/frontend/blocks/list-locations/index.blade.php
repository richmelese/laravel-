<div class="vc_row wpb_row st bg-holder bc_solo-list-location">
    <div class="container ">
        <div class="row">
            <div class="wpb_column column_container col-md-12">
                <div class="vc_column-inner wpb_wrapper">
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            <p style="text-align: center;">{{$title}}</p>
                            <h3 style="text-align: center;">{{$desc}}</h3>
                        </div>
                    </div>
                    <div class="list-destination list-destination--layout9 owl-carousel">
                        @if(!empty($rows))
                            @foreach($rows as $key=>$row)
                                @include('Location::frontend.blocks.list-locations.loop')
                            @endforeach
                        @endif
                    </div>
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            <p>
                                <a href="#">{{__('View All Destinations')}}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--End .row-->
    </div><!--End .container-->
</div>
@push("js")
<script type="text/javascript">
    jQuery(document).ready(function () {
        $('.list-destination--layout9').each(function () {
            $(this).removeClass('row');
            $(this).addClass('owl-carousel');
            $(this).owlCarousel({
                loop: false,
                items: 3,
                margin: 30,
                responsiveClass: true,
                dots: false,
                nav: false,
                responsive: {
                    0: {
                        items: 1,
                        margin: 15,
                        dots: true,
                    },
                    768: {
                        items: 2,
                        margin: 30,
                        dots: true,
                    },
                    992: {
                        items: 3,
                        margin: 30,
                        dots: false,
                    },
                    1200: {
                        items: 3,
                    }
                }
            });
        });
    })
</script>
@endpush