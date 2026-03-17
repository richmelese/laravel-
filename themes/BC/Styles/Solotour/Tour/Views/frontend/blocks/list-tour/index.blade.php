<div id="bc_content-wrapper">
    <div data-vc-full-width="true" data-vc-full-width-init="false" class="vc_row wpb_row bg-holder bc_solo-list-tour">
        <div class='container '>
            <div class='row'>
                <div class="wpb_column column_container col-md-12">
                    <div class="vc_column-inner wpb_wrapper">
                        <div class="vc_empty_space  space-top" style="height: 164px"><span class="vc_empty_space_inner"></span></div>
                        <div class="wpb_text_column wpb_content_element bc_service-heading">
                            <div class="wpb_wrapper">
                                <p style="text-align: center;">{{ $title }}</p>
                                <h3 style="text-align: center;">{{ $desc }}</h3>

                            </div>
                        </div>
                        <div class="search-result-page bc_tours service-slider-7 service-slider-wrapper modern-search-result">
                            <div class="list-service-style7 row">
                                @foreach($rows as $row)
                                    @include('Tour::frontend.layouts.search.loop-grid')
                                @endforeach
                            </div>
                        </div>
                        <div class="wpb_text_column wpb_content_element">
                            <div class="wpb_wrapper">
                                <p>
                                    <a href="{{route('tour.search')}}">
                                        {{ __('View All Tours') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div class="vc_empty_space  space-bottom" style="height: 104px">
                            <span class="vc_empty_space_inner"></span>
                        </div>
                    </div>
                </div>
            </div><!--End .row-->
        </div><!--End .container-->
    </div>
</div>
@push("js")
<script type="text/javascript">
    jQuery(document).ready(function () {
        $('.list-service-style7').each(function () {
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