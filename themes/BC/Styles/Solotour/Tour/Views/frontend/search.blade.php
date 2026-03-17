<div class="bc_search_tour bc_tour--solo search-result-page">
    <div class="bc_tour--solo__banner">
        <div class="banner banner--solo" @if ($bg=setting_item('tour_page_search_banner')) style="background-image: url({{ get_file_url($bg, 'full') }})" @endif>
            <div class="container">
                <div class="banner-headding">
                    <p class="banner-title-solo">
                        {{ __('Tour listing') }}
                    </p>
                    <h3 class="banner-content-solo">
                        {{ setting_item_with_lang('tour_page_search_title') }}
                    </h3>
                    <svg class="mt-4" xmlns="http://www.w3.org/2000/svg" width="104" height="15" fill="none" viewBox="0 0 104 15">
                        <path stroke="#EC927E" stroke-width="4" d="M1.644 7.091c2.689-3.77 10.468-9.05 20.072 0s17.595 3.771 20.39 0m0 .025c2.689-3.77 10.468-9.05 20.072 0s17.595 3.77 20.39 0m-.064.038c2.795-3.875 10.722-9.299 20.073 0"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bc_breadcrumb hidden-xs breadcrumbs-solo">
            <div class="container">
                <ul>
                    <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li class="active">{{ __('Tour listing') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bc_form_search">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @livewire('tour::search-form')
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        @include('Tour::frontend.layouts.search.list-item')
    </div>
</div>

@assets
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css') }}" />
    <script defer type="text/javascript" src="{{ asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js') }}"></script>
@endassets

@script
    <script>
        $('[name=orderby]', $wire.$el).on('change', function(e) {
            $wire.set('orderby', $(this).val());
        });
        $('.orderby .dropdown-item').on('click', function(e) {
            e.preventDefault();
            $('[name=orderby]').val($(this).data('value')).trigger('change');
            $('.orderby .dropdown-toggle').html($(this).html());
        })
    </script>
@endscript
