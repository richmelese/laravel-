@if (!is_api())
<footer id="main-footer" class="clearfix main-footer--solo " style="
    background: #441461 !important;
">
    <div class="wpb-content-wrapper">

        <div data-vc-full-width="true" data-vc-full-width-init="true" class="vc_row wpb_row st bg-holder bc_footer-solo vc_custom_1587351086002 vc_row-has-fill" style="position: relative; left: 15px; box-sizing: border-box; width: 1905px; max-width: 1905px; padding-left: 0px; padding-right: 0px;">
            <div class="container ">
                <div class="row">
                    <div class="wpb_column column_container col-md-12">
                        <div class="vc_column-inner wpb_wrapper">
                            <div class="row wpb_row vc_inner">
                                <div class="bc_footer-solo--mutil-language wpb_column column_container col-md-3">
                                    <div class="vc_column-inner">
                                        <div class="wpb_wrapper">
                                            <div class="wpb_text_column wpb_content_element vc_custom_1588583780437 solo-title-footer">
                                                <div class="wpb_wrapper">
                                                    <h4>Solo</h4>

                                                </div>
                                            </div>
                                            <div class="wpb_text_column wpb_content_element bc_footer-solo--social">
                                                <div class="wpb_wrapper">
                                                    <p><img loading="lazy" decoding="async" class="alignnone wp-image-8420" src="https://solotour.travelerwp.com/wp-content/uploads/2020/04/fb-300x300.png" alt="" width="40" height="45" srcset="https://solotour.travelerwp.com/wp-content/uploads/2020/04/fb-600x675.png 600w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/fb-768x864.png 768w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/fb.png 40w" sizes="auto, (max-width: 40px) 100vw, 40px"><img loading="lazy" decoding="async" class="alignnone wp-image-8421" src="https://solotour.travelerwp.com/wp-content/uploads/2020/04/insta-300x300.png" alt="" width="40" height="45" srcset="https://solotour.travelerwp.com/wp-content/uploads/2020/04/insta-600x675.png 600w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/insta-768x864.png 768w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/insta.png 40w" sizes="auto, (max-width: 40px) 100vw, 40px"><img loading="lazy" decoding="async" class="alignnone wp-image-8422" src="https://solotour.travelerwp.com/wp-content/uploads/2020/04/pin-300x300.png" alt="" width="40" height="45" srcset="https://solotour.travelerwp.com/wp-content/uploads/2020/04/pin-600x675.png 600w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/pin-768x864.png 768w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/pin.png 40w" sizes="auto, (max-width: 40px) 100vw, 40px"><img loading="lazy" decoding="async" class="alignnone wp-image-8423" src="https://solotour.travelerwp.com/wp-content/uploads/2020/04/twitter-300x300.png" alt="" width="40" height="45" srcset="https://solotour.travelerwp.com/wp-content/uploads/2020/04/twitter-600x675.png 600w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/twitter-768x864.png 768w, https://solotour.travelerwp.com/wp-content/uploads/2020/04/twitter.png 40w" sizes="auto, (max-width: 40px) 100vw, 40px"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ($list_widget_footers = setting_item_with_lang('solotour_list_widget_footer'))
                                    <?php $list_widget_footers = json_decode($list_widget_footers); ?>
                                    @foreach ($list_widget_footers as $key => $item)                     
                                        <div class="bc_footer-solo--tour wpb_column col-md-{{ $item->size ?? '3' }}">
                                            <div class="wpb_text_column wpb_content_element vc_custom_1588583795690">
                                                <div class="wpb_wrapper">
                                                    <h4>{{ $item->title }}</h4>
                                                </div>
                                            </div>
                                            <div class="widget widget_nav_menu">
                                                {!! $item->content !!}
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row bc_footer-solo--sub">
                                <div class="bc_footer-solo--address  col-md-6">
                                    {!! clean(setting_item_with_lang('solotour_footer_text_left')) !!}
                                </div>
                                <div class="bc_footer--copy-right col-md-6">
                                    {!! clean(setting_item_with_lang('solotour_footer_text_right')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!--End .row-->
            </div><!--End .container-->
        </div>
        <div class="vc_row-full-width vc_clearfix"></div>
    </div>
</footer>
@endif



@include('Layout::parts.login-register-modal')
@include('Popup::frontend.popup')
@if (Auth::check() and !empty($is_user_page))
@include('Media::browser')
@endif
<link rel="stylesheet" href="{{ asset('libs/flags/css/flag-icon.min.css') }}">

{!! \App\Helpers\Assets::css(true) !!}

{{-- Lazy Load --}}
<script src="{{ asset('libs/lazy-load/intersection-observer.js') }}"></script>
<script async src="{{ asset('libs/lazy-load/lazyload.min.js') }}"></script>
<script>
    // Set the options to make LazyLoad self-initialize
    window.lazyLoadOptions = {
        elements_selector: ".lazy",
        // ... more custom settings?
    };

    // Listen to the initialization event and get the instance of LazyLoad
    window.addEventListener('LazyLoad::Initialized', function(event) {
        window.lazyLoadInstance = event.detail.instance;
    }, false);

    document.addEventListener('livewire:init', function() {
        Livewire.hook('morph.updated', (el, comp) => {
            window.setTimeout(() => {
                if (window.lazyLoadInstance) {
                    window.lazyLoadInstance.update();
                }
            }, 100);
        });
    });
</script>
<script src="{{ asset('libs/jquery-3.6.3.min.js') }}"></script>
<script src="{{ asset('libs/vue/vue' . (!env('APP_DEBUG') ? '.min' : '') . '.js') }}"></script>
<script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('libs/bootbox/bootbox.min.js') }}"></script>
@if (Auth::check() and !empty($is_user_page))
<script src="{{ asset('module/media/js/browser.js?_ver=' . config('app.asset_version')) }}"></script>
@endif
<script src="{{ asset('libs/carousel-2/owl.carousel.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('libs/daterange/moment.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('libs/daterange/daterangepicker.min.js') }}"></script>
<script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('js/functions.js?_ver=' . config('app.asset_version')) }}"></script>

@if (setting_item('tour_location_search_style') == 'autocompletePlace' ||
setting_item('hotel_location_search_style') == 'autocompletePlace' ||
setting_item('car_location_search_style') == 'autocompletePlace' ||
setting_item('space_location_search_style') == 'autocompletePlace' ||
setting_item('hotel_location_search_style') == 'autocompletePlace' ||
setting_item('event_location_search_style') == 'autocompletePlace')
{!! App\Helpers\MapEngine::scripts() !!}
@endif
<script src="{{ asset('libs/pusher.min.js') }}"></script>
<script src="{{ asset('js/home.js?_ver=' . config('app.asset_version')) }}"></script>

@if (!empty($is_user_page))
<script src="{{ asset('module/user/js/user.js?_ver=' . config('app.asset_version')) }}"></script>
@endif
@if (setting_item('cookie_agreement_type') == 'cookie_agreement'
and request()->cookie('booking_cookie_agreement_enable') !=1 and !is_api() and
!isset($_COOKIE['booking_cookie_agreement_enable']))
<div class="booking_cookie_agreement p-3 d-flex fixed-bottom">
    <div class="content-cookie">{!! clean(setting_item_with_lang('cookie_agreement_content')) !!}</div>
    <button class="btn save-cookie">{!! clean(setting_item_with_lang('cookie_agreement_button_text')) !!}</button>
</div>
<script>
    var save_cookie_url = '{{ route('
    core.cookie.check ') }}';
</script>
<script src="{{ asset('js/cookie.js?_ver=' . config('app.asset_version')) }}"></script>
@endif

@includeWhen(setting_item('cookie_agreement_type') == 'cookie_consent', 'Layout::parts.cookie-consent-init')

@if (setting_item('user_enable_2fa'))
@include('auth.confirm-password-modal')
<script src="{{ asset('/module/user/js/2fa.js') }}"></script>
@endif

{!! \App\Helpers\Assets::js(true) !!}

@php \App\Helpers\ReCaptchaEngine::scripts() @endphp

@stack('js')