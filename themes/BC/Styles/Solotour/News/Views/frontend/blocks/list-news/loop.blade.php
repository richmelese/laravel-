@php
    $translation = $row->translate();
@endphp
<div class="bc_blog--bg has-matchHeight">
    <div class="bc_blog--item item-content">
        <div class="thumb text-center">
            <a class="hover-img curved" href="{{$row->getDetailUrl()}}">
                <img loading="lazy" decoding="async" width="370" height="208" src="{{ get_file_url($row->image_id,'medium') }}" class="attachment-370x208 size-370x208 wp-post-image" alt="{{ $translation->title }}" /> 
            </a>
        </div>
        <div class="thumb-caption ">
            <ul class="blog-date">
                <li class="blog-location" style="color: #f2911f">{{ $row->category->name }}</li>
                <li>{{ display_date($row->updated_at) }}</li>
            </ul>
            <p class="title"><a href="{{$row->getDetailUrl()}}">{{ $translation->title }}</a></p>
            <p class="sub-title">
                {!! get_exceprt($translation->content,70,"...") !!}
            </p>
        </div>
    </div>
</div>