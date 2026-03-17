@php
    $terms_ids = $row->tour_term->pluck('term_id');
    $attributes = \Modules\Core\Models\Terms::getTermsById($terms_ids);
@endphp
@if(!empty($terms_ids) and !empty($attributes))


<div class="bc_content--hightlight" style="background-image: url('https://solotour.travelerwp.com/wp-content/uploads/2015/01/bg_tour_hightlight.png');">
    <div class="container">
        <div class="bc_highlight">
            @foreach($attributes as $attribute )
                @php $translate_attribute = $attribute['parent']->translate() @endphp
                @if(empty($attribute['parent']['hide_in_single']))
                    <div class="mb-3 mt-3 {{$attribute['parent']->slug}} attr-{{$attribute['parent']->id}}">
                        <h3 class="hightlight__title">{{ $translate_attribute->name }}</h3>
                        @php $terms = $attribute['child'] @endphp
                        <ul class="row">
                            @foreach($terms as $term )
                                @php $translate_term = $term->translate() @endphp
                                <li class="col-xs-12 col-sm-3 col-sm-offset-1 col-md-3 col-md-offset-1 col-lg-3 col-lg-offset-1 mb-3 {{$term->slug}} term-{{$term->id}}">
                                    @if(!empty($term->image_id))
                                        @php $image_url = get_file_url($term->image_id, 'full'); @endphp
                                        <img src="{{$image_url}}" class="img-responsive" alt="{{$translate_term->name}}">
                                    @else
                                        @if( $term->icon )
                                            <i class="{{ $term->icon }}"></i>
                                        @else
                                            <i class="input-icon bc_border-radius field-icon fa">
                                                <svg height="15px" width="15px" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;" xml:space="preserve">

    <g fill="#36bca1">
        <path d="M6.347,24.003c-0.601,0-1.182-0.183-1.68-0.529c-0.261-0.181-0.489-0.403-0.68-0.658L0.15,17.7
            c-0.12-0.16-0.171-0.358-0.143-0.556C0.036,16.946,0.14,16.77,0.3,16.65c0.131-0.098,0.286-0.15,0.45-0.15
            c0.235,0,0.459,0.112,0.6,0.3l3.839,5.118c0.094,0.127,0.207,0.236,0.335,0.325c0.245,0.17,0.53,0.26,0.826,0.26
            c0.086,0,0.173-0.008,0.259-0.023c0.381-0.068,0.712-0.281,0.933-0.599L22.636,0.32C22.775,0.12,23.005,0,23.25,0
            c0.154,0,0.303,0.047,0.429,0.135c0.165,0.115,0.274,0.287,0.309,0.484c0.035,0.197-0.009,0.396-0.124,0.561L8.772,22.739
            c-0.449,0.645-1.124,1.078-1.9,1.217C6.699,23.987,6.522,24.003,6.347,24.003z"></path>
    </g>
    </svg>
                                            </i>
                                        @endif
                                    @endif
                                    {{$translate_term->name}}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif
