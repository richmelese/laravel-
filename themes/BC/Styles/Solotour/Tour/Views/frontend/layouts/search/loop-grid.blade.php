@php
$translation = $row->translate();
@endphp
<div class="bc_list-service--bg bc_list-service--transparent">
    <div class="mb-5">
        <div class="bc_list-tour-related  mt50">
            <div class="item related__item">
                <div class="featured featured--position">
                    <div class="thumb">
                        <a href="{{$row->getDetailUrl($include_param ?? true)}}">
                            @if($row->image_url)
                                <img src="{{$row->image_url}}" class="img-responsive wp-post-image height-auto" alt="{{$translation->title}}">
                            @endif
                        </a>
                    </div>
                    <span class="ml5 f14 address bc_location--style4" style="background:#36bca1">
                        @if(!empty($row->location->name))
                            @php $location = $row->location->translate() @endphp
                            <i class="icofont-paper-plane"></i>
                            {{$location->name ?? ''}}
                        @endif
                    </span>
                </div>
                <h4 class="title title--color">
                    <a href="{{$row->getDetailUrl($include_param ?? true)}}"
                        class="bc_link c-main">{{$translation->title}}</a>
                </h4>
                <div class="bc_tour--description">
                    {!! Str::limit($row->content, 150, '...') !!}
                </div>

                <div class="bc_tour--feature">
                    <div class="bc_tour__item">
                        <div class="item__icon">
                            <i class="input-icon bc_border-radius field-icon fa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path fill="#ec927e" d="M7.5 10.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM12.5 10.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM17.5 10.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM7.5 14.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM12.5 14.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM17.5 14.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM7.5 18.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM12.5 18.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1zM17.5 18.5h-1c-.552 0-1 .448-1 1s.448 1 1 1h1c.552 0 1-.448 1-1s-.448-1-1-1z" />
                                    <path fill="#ec927e" fill-rule="evenodd" d="M18.75 3h2.75c1.105 0 2 .895 2 2v17c0 1.105-.895 2-2 2h-19c-1.105 0-2-.895-2-2V5c0-1.105.895-2 2-2H4c.276 0 .5.224.5.5v2.25c0 .414.336.75.75.75S6 6.164 6 5.75V1c0-.552.448-1 1-1s1 .448 1 1v1.751c0 .138.112.249.25.249h6.25c.276 0 .5.224.5.5v2.25c0 .414.336.75.75.75s.75-.336.75-.75V1c0-.552.448-1 1-1s1 .448 1 1v1.75c0 .138.112.25.25.25zm2.75 19c.276 0 .5-.224.5-.5v-12c0-.276-.224-.5-.5-.5h-18c-.276 0-.5.224-.5.5v12c0 .276.224.5.5.5h18z" clip-rule="evenodd" />
                                </svg>
                            </i>
                        </div>
                        <div class="item__info">
                            <h4 class="info__name">{{__('Duration')}}</h4>
                            <p class="info__value">
                                {{duration_format($row->duration)}}
                            </p>
                        </div>
                    </div>
                    <div class="bc_tour__item">
                        <div class="item__icon">
                            <i class="input-icon bc_border-radius field-icon fa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path fill="#ec927e" d="M20 23.25c0 .276.224.5.5.5h2.82c.167 0 .323-.083.415-.22.093-.139.111-.314.049-.468-.604-1.51-1.985-2.57-3.6-2.765-.169-.02-.336.046-.445.177-.108.13-.143.307-.092.469.234.747.353 1.525.353 2.307zM4.867 20.9c.049-.152.022-.318-.072-.447-.094-.13-.243-.206-.403-.206H4.38c-1.833 0-3.48 1.116-4.164 2.816-.062.154-.044.33.05.467.092.137.248.22.414.22H4c.276 0 .5-.224.5-.5 0-.798.123-1.59.367-2.35zM7.05 6.043c-.26.251-.364.623-.273.972.092.35.365.623.714.715.35.091.722-.013.973-.273.938-.938 2.21-1.465 3.536-1.465s2.598.527 3.536 1.465c.392.379 1.016.373 1.402-.012.386-.386.39-1.01.012-1.402-1.313-1.313-3.093-2.05-4.95-2.05-1.857 0-3.637.737-4.95 2.05z" />
                                    <path fill="#ec927e" d="M6.343 4.593C7.843 3.093 9.878 2.25 12 2.25s4.157.843 5.657 2.343c.251.26.623.364.972.273.35-.092.623-.365.715-.715.091-.35-.013-.72-.273-.972-3.905-3.905-10.237-3.905-14.142 0-.38.392-.374 1.016.012 1.402.386.386 1.01.391 1.402.012z" />
                                    <path fill="#ec927e" fill-rule="evenodd" d="M9.949 17.288c.006-.093-.04-.181-.119-.23v.003c-1.836-1.084-2.72-3.263-2.16-5.32.561-2.057 2.429-3.486 4.561-3.488 2.132-.003 4.003 1.42 4.57 3.477.565 2.056-.314 4.236-2.147 5.325-.08.049-.127.138-.12.232.006.093.064.176.15.213 2.31.978 3.812 3.242 3.816 5.75 0 .276-.224.5-.5.5H6.5c-.276 0-.5-.224-.5-.5.002-2.505 1.497-4.767 3.8-5.75.085-.038.142-.12.149-.212zm-.386-4.863c-.042.184-.063.373-.063.562-.001 1.412 1.072 2.594 2.478 2.727 1.406.133 2.683-.827 2.946-2.214.027-.158-.024-.32-.136-.434-.112-.114-.273-.168-.431-.144-.29.043-.582.065-.874.065-1.119 0-2.216-.304-3.175-.881-.138-.083-.306-.094-.454-.03-.148.062-.256.192-.291.349z" clip-rule="evenodd" />
                                    <path fill="#ec927e" d="M19.62 19.25c1.177.007 2.23-.73 2.624-1.84.394-1.108.044-2.345-.873-3.083s-2.2-.814-3.199-.191c-.998.623-1.494 1.81-1.234 2.957.289 1.252 1.397 2.143 2.682 2.157zM4.38 19.25c1.519 0 2.75-1.231 2.75-2.75s-1.231-2.75-2.75-2.75-2.75 1.231-2.75 2.75 1.231 2.75 2.75 2.75z" />
                                </svg>
                            </i>
                        </div>
                        <div class="item__info">
                            <h4 class="info__name">{{__('Group Size')}}</h4>
                            <p class="info__value">
                                @if($row->max_people > 1)
                                {{ __(":number persons",array('number'=>$row->max_people)) }}
                                @else
                                {{ __(":number person",array('number'=>$row->max_people)) }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="section-footer">
                    <div class="d-flex bc_flex space-between justify-content-between bc_price__wrapper">
                        <div class="right">
                            <span class=" price--tour">
                                <span class="text-small lh1em item onsale ">{{ $row->display_price }}</span>
                                <span class="text-lg lh1em item ">{{ $row->display_sale_price }}</span> 
                            </span>
                        </div>
                        <div class="bc_btn--book">
                            <a href="{{$row->getDetailUrl($include_param ?? true)}}">{{__('Book Now')}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>