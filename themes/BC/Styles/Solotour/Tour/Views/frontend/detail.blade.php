@extends('layouts.app')
@push('css')
    <link rel='stylesheet' id='magnific-css-css'
          href='https://solotour.travelerwp.com/wp-content/themes/traveler/v2/js/magnific-popup/magnific-popup.css?ver=6.8.2'
          type='text/css' media='all'/>
@endpush
@section('content')
    <div id="bc_content-wrapper" class="bc_detail_tour bc_single-tour">


        <div class="bc_content bc_tour-content style7">

            @include('Tour::frontend.layouts.details.tour-banner')

            <div class="bc_tour-booking" data-screen="992px" style="width: auto;">
                <div class="container">
                    <div class="widgets widgets--margin">
                        <div id="booking-request" data-screen="992px">
                            <div class="close-icon d-none">
                                <i class="input-icon bc_border-radius field-icon fa">
                                    <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1"
                                         xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <!-- Generator: Sketch 49 (51002) - http://www.bohemiancoding.com/sketch -->
                                        <defs></defs>
                                        <g id="Ico_close" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                                           stroke-linecap="round" stroke-linejoin="round">
                                            <g stroke="#1A2B48" stroke-width="1.5">
                                                <g id="close">
                                                    <path d="M0.75,23.249 L23.25,0.749"></path>
                                                    <path d="M23.25,23.249 L0.75,0.749"></path>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                </i>
                            </div>

                            <div class="form-book-wrapper bc_tour-booking__bg relative">
                                <form id="form-booking-inpage" method="post" action="#booking-request" class="tour-booking-form form-has-guest-name bc_tour-booking__info">
                                    <div class="bc_tour-booking__border form-date-field form-date-search clearfix" data-format="MM/DD/YYYY"  data-availability-date="08/13/2025">
                                        <div class="date-wrapper bc_tour-booking__date--wrapper clearfix" data-custom-class="solo-datepicker">
                                            <i class="input-icon bc_border-radius field-icon fa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                     fill="none" viewBox="0 0 16 16">
                                                    <path fill="#123a32"
                                                          d="M5 7h-.666c-.369 0-.667.298-.667.667 0 .368.298.666.667.666H5c.369 0 .667-.298.667-.666C5.667 7.298 5.369 7 5 7zM8.333 7h-.666C7.298 7 7 7.298 7 7.667c0 .368.298.666.667.666h.666c.369 0 .667-.298.667-.666C9 7.298 8.702 7 8.333 7zM11.667 7H11c-.368 0-.666.298-.666.667 0 .368.298.666.666.666h.667c.368 0 .667-.298.667-.666 0-.369-.299-.667-.667-.667zM5 9.667h-.666c-.369 0-.667.298-.667.666 0 .368.298.667.667.667H5c.369 0 .667-.299.667-.667 0-.368-.298-.666-.667-.666zM8.333 9.667h-.666c-.369 0-.667.298-.667.666 0 .368.298.667.667.667h.666c.369 0 .667-.299.667-.667 0-.368-.298-.666-.667-.666zM11.667 9.667H11c-.368 0-.666.298-.666.666 0 .368.298.667.666.667h.667c.368 0 .667-.299.667-.667 0-.368-.299-.666-.667-.666zM5 12.333h-.666c-.369 0-.667.299-.667.667 0 .368.298.667.667.667H5c.369 0 .667-.299.667-.667 0-.368-.298-.667-.667-.667zM8.333 12.333h-.666c-.369 0-.667.299-.667.667 0 .368.298.667.667.667h.666c.369 0 .667-.299.667-.667 0-.368-.298-.667-.667-.667zM11.667 12.333H11c-.368 0-.666.299-.666.667 0 .368.298.667.666.667h.667c.368 0 .667-.299.667-.667 0-.368-.299-.667-.667-.667z"></path>
                                                    <path fill="#123a32" fill-rule="evenodd"
                                                          d="M12.5 2h1.834c.736 0 1.333.597 1.333 1.333v11.334c0 .736-.597 1.333-1.333 1.333H1.667C.93 16 .333 15.403.333 14.667V3.333C.333 2.597.93 2 1.667 2h1C2.85 2 3 2.15 3 2.333v1.5c0 .276.224.5.5.5s.5-.224.5-.5V.667C4 .298 4.3 0 4.667 0c.368 0 .667.298.667.667v1.167c0 .092.074.166.166.166h4.167c.184 0 .333.15.333.333v1.5c0 .276.224.5.5.5s.5-.224.5-.5V.667C11 .298 11.3 0 11.667 0c.368 0 .667.298.667.667v1.166c0 .092.074.167.166.167zm1.834 12.667c.184 0 .333-.15.333-.334v-8c0-.184-.15-.333-.333-.333h-12C2.149 6 2 6.15 2 6.333v8c0 .184.15.334.333.334h12z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                            </i>
                                            <div class="check-in-wrapper bc_tour-booking__check-in">
                                                <div class="render check-in-render">08/13/2025</div>
                                            </div>
                                            <i class="fa fa-angle-down arrow"></i>
                                        </div>
                                    </div>
                                    <div class="bc_tour-booking__border form-guest-search form-book-tour clearfix">
                                        <div class="dropdown bc_tour-booking__flex" data-toggle="dropdown" id="dropdown-1" aria-expanded="false">
                                            <i class="input-icon bc_border-radius field-icon fa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                     fill="none" viewBox="0 0 16 16">
                                                    <g fill="#123a32" clip-path="url(#prefix__clip0)">
                                                        <path
                                                            d="M15.09 10.466c-1.371-.866-2.942-1.34-4.565-1.43.376.176.747.362 1.1.584.848.535 1.375 1.503 1.375 2.527V15h3v-2.854c0-.682-.348-1.326-.91-1.68z"></path>
                                                        <path
                                                            d="M11.09 10.466c-3.04-1.918-7.14-1.918-10.18 0-.561.353-.91.997-.91 1.68V15h12v-2.854c0-.683-.349-1.327-.91-1.68zM8.994 7.834C9.317 7.932 9.651 8 10 8c1.93 0 3.5-1.57 3.5-3.5S11.93 1 10 1c-.349 0-.683.068-1.006.166C9.911 1.99 10.5 3.173 10.5 4.5c0 1.328-.589 2.51-1.506 3.334z"></path>
                                                        <path
                                                            d="M8.475 2.025c1.367 1.367 1.367 3.583 0 4.95s-3.583 1.367-4.95 0-1.367-3.583 0-4.95 3.583-1.367 4.95 0z"></path>
                                                    </g>
                                                    <defs>
                                                        <clipPath id="prefix__clip0">
                                                            <path fill="#fff" d="M0 0H16V16H0z"></path>
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </i>
                                            <div class="bc_tour-booking__tour-info tour-info render">
                                                <span class="adult" data-text="Adult"
                                                      data-text-multi="Adults">Adult x 0</span>
                                                ,
                                                <span class="children" data-text="Children" data-text-multi="Children">0 Children</span>
                                                ,
                                                <span class="infant" data-text="Infant" data-text-multi="Infant">0 Infant</span>
                                            </div>
                                            <i class="fa fa-angle-down arrow"></i>
                                        </div>
                                        <ul class="dropdown-menu bc_tour-booking__position" id="popup-menu"
                                            aria-labelledby="dropdown-1" style="display: none;">
                                            <li class="item">
                                                <div class="guest-wrapper bc_tour--guest-wraper clearfix">
                                                    <div class="bc_tour-booking__check-in check-in-wrapper">
                                                        <label>Adults</label>
                                                        <div class="bc_tour-booking__render render">Age 18+</div>
                                                    </div>
                                                    <div class="select-wrapper">
                                                        <div class="bc_tour-booking__bc_-number bc_number-wrapper">
                                                            <span class="next">
                                                                <svg width="18px" height="18px" viewBox="0 0 18 18" version="1.1"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    xmlns:xlink="http://www.w3.org/1999/xlink">
                                                                    <!-- Generator: Sketch 49 (51002) - http://www.bohemiancoding.com/sketch -->
                                                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"
                                                                       stroke-linejoin="round">
                                                                        <g id="Tour_Detail_1" transform="translate(-1258.000000, -1077.000000)" stroke="#5E6D77" stroke-width="1.5">
                                                                            <g id="check-avai" transform="translate(1034.000000, 867.000000)">
                                                                                <g id="adults" transform="translate(0.000000, 184.000000)">
                                                                                    <g id="ico_add" transform="translate(225.000000, 27.000000)">
                                                                                        <path d="M0.5,8 L15.5,8" id="Shape"></path>
                                                                                        <path d="M8,0.5 L8,15.5" id="Shape"></path>
                                                                                    </g>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                    </g>
                                                                </svg>
                                                            </span>
                                                            <input type="text" name="adult_number" value="0" class="bc_tour-booking__bc_-number__item form-control bc_input-number adult_number" autocomplete="off" readonly="" data-min="0" data-max="6">
                                                            <span class="prev">
                                                                <svg width="18px" height="2px" viewBox="0 0 18 2" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                                    <!-- Generator: Sketch 49 (51002) - http://www.bohemiancoding.com/sketch -->
                                                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round"
                                                                       stroke-linejoin="round">
                                                                        <g id="Tour_Detail_1" transform="translate(-1180.000000, -1085.000000)" stroke="#5E6D77" stroke-width="1.5">
                                                                            <g id="check-avai" transform="translate(1034.000000, 867.000000)">
                                                                                <g id="adults" transform="translate(0.000000, 184.000000)">
                                                                                    <g id="ico_subtract" transform="translate(147.000000, 35.000000)">
                                                                                        <path d="M0.5,0.038 L15.5,0.038" id="Shape"></path>
                                                                                    </g>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                    </g>
                                                                </svg>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="form-more-extra-solo">
                                        <div class="button-extra">
                                            <a href="#dropdown-more-extra" class="dropdown-more-extra" data-toggle="collapse" aria-expanded="false">
                                                <span>
                                                    Extras
                                                </span>
                                                <i class="fa fa-angle-down arrow"></i>
                                            </a>
                                        </div>
                                        <ul id="dropdown-more-extra" class="dropdown-menu extras collapse" aria-expanded="false" style="">
                                            <li>
                                                <span class="name-extra-title">Extra</span>
                                            </li>
                                            <li class="item mt10">
                                                <div class="bc_flex space-between">
                                                    <span>
                                                        <span class="title-extra">
                                                            Pick up
                                                        </span>
                                                        <span class="extra-price-item">
                                                            €0,00
                                                        </span>
                                                    </span>
                                                    <div class="select-wrapper" style="width: 88px;">
                                                        <div class="caculator-item">
                                                            <i class="fa fa-minus"></i>
                                                            <input type="input" class="form-control app extra-service-select" name="extra_price[value][extra_pickup]" id="field-extra_pickup" data-extra-price="0" step="1" value="0" min="0" max="4">
                                                            <i class="fa fa-plus"></i>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="extra_price[price][extra_pickup]" value="0">
                                                    <input type="hidden" name="extra_price[title][extra_pickup]" value="Pick up">
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="bc_tour-booking__price">
                                        <div class="bc_tour-booking__price--item price">
                                            <span class="value">
                                                <span class="text-small lh1em item onsale mb-0">€2.000,00</span>
                                                <span class="text-lg lh1em item "> €1.950,00</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="submit-group bc_tour-booking__submit">
                                        <button class="btn btn-large btn-full upper btn-book-ajax" type="submit" name="submit">
                                            Book Now <i class="fa fa-spinner fa-spin d-none"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            @include('Tour::frontend.layouts.details.tour-detail')

            @include('Tour::frontend.layouts.details.tour-itinerary')

            @include('Tour::frontend.layouts.details.tour-attributes')

            @include('Tour::frontend.layouts.details.tour-faqs')

            @include('Tour::frontend.layouts.details.tour-review')

            @include('Tour::frontend.layouts.details.tour-related')


        </div>

    </div>
@endsection

@assets
{!! App\Helpers\MapEngine::scripts([
'defer' => true,
]) !!}

@endassets

@push('js')
    <script>
        jQuery(function ($) {
            @if ($row->map_lat && $row->map_lng)
            new BCMapEngine('map_content', {
                disableScripts: true,
                fitBounds: true,
                center: [{{ $row->map_lat }}, {{ $row->map_lng }}],
                zoom: {{ $row->map_zoom ?? '8' }},
                ready: function (engineMap) {
                    engineMap.addMarker([{{ $row->map_lat }}, {{ $row->map_lng }}], {
                        icon_options: {
                            iconUrl: "{{ get_file_url(setting_item('tour_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png') }}"
                        }
                    });
                }
            });
            @endif
        })
    </script>
    <script>
        var bc_booking_data = {!! json_encode($booking_data) !!}
            var
        bc_booking_i18n = {
            no_date_select: '{{ __('Please select Start date') }}',
            no_guest_select: '{{ __('Please select at least one guest') }}',
            load_dates_url: '{{ route('tour.vendor.availability.loadDates') }}',
            name_required: '{{ __('Name is Required') }}',
            email_required: '{{ __('Email is Required') }}',
        };
    </script>
    {{--    <script type="text/javascript" src="{{ asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js') }}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('libs/fotorama/fotorama.js') }}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('libs/sticky/jquery.sticky.js') }}"></script>--}}

    <script type="text/javascript"
            src="{{ asset('module/tour/js/single-tour.js?_ver=' . config('app.asset_version')) }}"></script>
    <script type="text/javascript"
            src="https://solotour.travelerwp.com/wp-content/themes/traveler/v2/js/magnific-popup/jquery.magnific-popup.min.js?ver=6.8.2"
            id="magnific-js-js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            $('.bc_list-tour--slide').each(function () {
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
                            dots: false
                        },
                        1200: {
                            items: 3,
                        }
                    }
                });
            });
            $('.bc_faq .item').each(function () {
                var t = $(this);
                t.find('.header').on('click', function () {
                    $('.bc_faq .item').not(t).removeClass('active');
                    t.toggleClass('active');
                });
            });
            $(".bc_video-popup").each(function () {
                $(this).magnificPopup({
                    type: 'iframe'
                })
            });
            $('.bc_gallery-popup').on('click', function (e) {
                e.preventDefault();
                var gallery = $(this).attr('href');
                $(gallery).magnificPopup({
                    delegate: 'a',
                    type: 'image',
                    gallery: {
                        enabled: true
                    },
                }).magnificPopup('open');
            });
            $('.owl-tour-program-7').each(function () {
                var parent = $(this).parent();
                var owl = $(this);
                owl.owlCarousel({
                    loop: false,
                    items: 1,
                    margin: 0,
                    responsiveClass: true,
                    dots: false,
                    nav: true,
                    responsive: {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 1,
                        },
                        1200: {
                            items: 1,
                        }
                    }
                });
                owl.on('resized.owl.carousel', function () {
                    setTimeout(function () {
                        if ($('.ovscroll').length) {
                            $.fn.getNiceScroll && $('.ovscroll').getNiceScroll().resize();
                        }
                    }, 1000);
                });
            });
            $('.shares .social-share').on('click', function (ev) {
                ev.preventDefault();
                $('.shares .share-wrapper').slideToggle(200);
            });
        })
    </script>
@endpush
