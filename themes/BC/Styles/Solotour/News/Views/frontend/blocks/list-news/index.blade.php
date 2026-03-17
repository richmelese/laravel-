<div data-vc-full-width="true" data-vc-full-width-init="false" class="mt-5 pt-5 mb-5 pb-5 vc_row wpb_row bg-holder bc_solo-blog-list">
    <div class='container '>
        <div class='row'>
            <div class="wpb_column column_container col-md-12">
                <div class="vc_column-inner wpb_wrapper">
                    <div class="wpb_text_column wpb_content_element mb-5 pb-5 text-center">
                        <div class="wpb_wrapper ">
                            <p>{{ $desc }}</p>
                            <h3>{{ $title }}</h3>
                        </div>
                        <svg class="d-inline" xmlns="http://www.w3.org/2000/svg" width="104" height="15" fill="none" viewBox="0 0 104 15">
                            <path stroke="#EC927E" stroke-width="4" d="M1.644 7.091c2.689-3.77 10.468-9.05 20.072 0s17.595 3.771 20.39 0m0 .025c2.689-3.77 10.468-9.05 20.072 0s17.595 3.77 20.39 0m-.064.038c2.795-3.875 10.722-9.299 20.073 0"/>
                        </svg>
                    </div>
                    <div class="row row-wrap bc_blog st_grid no_margin_inner style7 ">
                        @foreach($rows as $row)
                            @include('News::frontend.blocks.list-news.loop')
                        @endforeach
                    </div>
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            <p style="text-align: center;">
                                <a href="{{ route('news.index') }}">
                                    {{ __("READ MORE ARTICLES") }}
                                </a>
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
        $('.bc_solo-blog-list .bc_blog').each(function () {
            let t = $(this);
            t.removeClass('row');
            t.addClass('owl-carousel');
            t.owlCarousel({
                loop: false,
                responsiveClass: true,
                nav: false,
                margin: 30,
                responsive: {
                    0: {
                        items: 1,
                        nav: false,
                        dots: true,
                    },
                    768: {
                        items: 2,
                    },
                    992: {
                        items: 3,
                        dots:false,
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