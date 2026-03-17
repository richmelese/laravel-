@foreach ($rows as $row)
    @php
        $translation = $row->translate();
        $category = $row->category;
        $image_tag = get_image_tag($row->image_id, 'full', ['alt' => $translation->title]);
    @endphp
    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="bc_blog--bg">
            <div class="bc_blog--item item-content has-matchHeight" style="height: 454px;">
                <div class="thumb text-center">
                    <a class="hover-img curved" href="{{ $row->getDetailUrl() }}">
                        {!! $image_tag !!}
                </div>
                <div class="thumb-caption ">
                    <ul class="blog-date">
                        @if (!empty($category))
                            @php $t = $category->translate(); @endphp
                            <li class="blog-location" style="color: #f2911f">{{ $t->name ?? '' }}</li>
                        @endif
                        <li>{{ display_date($row->updated_at) }}</li>
                    </ul>
                    <p class="title"><a href="{{ $row->getDetailUrl() }}">{{ $translation->title }}</a></p>
                    <div class="bc-tour--description">
                        <p>{!! get_exceprt($translation->content) !!}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
