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
                <div class="section-footer">
                    <div class="d-flex bc_flex space-between justify-content-between bc_price__wrapper">
                        <div class="right">
                            <span class=" price--tour">
                                <span class="text-small lh1em item onsale ">{{ $row->display_sale_price }}</span>
                                <span class="text-lg lh1em item ">{{ $row->display_price }}</span>
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
