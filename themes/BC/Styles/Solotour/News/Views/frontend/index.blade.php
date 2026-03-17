@extends('layouts.app', ['body_class' => ' bc_header-8'])
@push('css')
    <link href="{{ asset('themes/bc/dist/frontend/module/news/css/news.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <link href="{{ asset('themes/bc/dist/frontend/css/app.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/daterange/daterangepicker.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/fotorama/fotorama.css') }}" />
@endpush
@section('content')
    <div id="bc-content-wrapper" class="bc-news search-result-page bc_blog-solo--wrapper">
        @php
            $title_page = setting_item_with_lang('news_page_list_title');
            if (!empty($custom_title_page)) {
                $title_page = $custom_title_page;
            }
        @endphp
        @if (!empty($title_page))
            <div class="bc_blog--banner">
                <div class="banner banner--solo" @if ($bg = setting_item('news_page_list_banner')) style="background-image: url({{ get_file_url($bg, 'full') }})" @endif>
                    <div class="container">
                        <div class="banner-headding">
                            <p class="banner-title-solo">{{ __('Latest News') }}</p>
                            <h3 class="banner-content-solo">{{ $title_page }}</h3>
                        </div>
                    </div>
                </div>
                @include('News::frontend.layouts.details.news-breadcrumb')
            </div>
        @endif
        <div class="bc_content">
            <div class="container">
                <div class="bc-blog bc_blog--search">
                    <div class="search-form-top">
                        <form role="search" method="get" class="search search--blog-solo"
                            action="{{ url(app_get_locale(false, false, '/') . config('news.news_route_prefix')) }}">
                            <input type="text" class="form-text" name="s" placeholder="{{ __('Search ...') }}"
                                value="{{ Request::query('s') }}">
                            @php
                                $list_category = $model_category->with('translation')->get()->toTree();
                            @endphp
                            @if (!empty($list_category))
                                <div class="form-icon-fa">
                                    <select name="cat" id="cat" class="form-select form-slelect-2">
                                        <option value="">{{ __('Categories') }}</option>
                                        @foreach ($list_category as $category)
                                            <option value="{{ $category->id }}" {{ Request::query('cat') == $category->id ? 'selected' : '' }}>
                                                {{ $category->translate()->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fa fa-angle-down"></i>
                                </div>
                            @endif
                        </form>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-xs-12">
                            <div class="content">
                                <div class="blog-wrapper row">
                                    @if ($rows->count() > 0)
                                        @include('News::frontend.layouts.details.news-loop')
                                    @else
                                        <div class="col-12">
                                            <div class="alert alert-danger">
                                                {{ __('Sorry, but nothing matched your search terms. Please try again with some different keywords.') }}
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            $('.form-slelect-2').select2();
        });
    </script>
@endpush
