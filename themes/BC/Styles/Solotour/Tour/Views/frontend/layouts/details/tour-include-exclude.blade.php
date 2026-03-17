@php
    if(!empty($translation->include)){
        $title = __("Included");
    }
    if(!empty($translation->exclude)){
        $title = __("Excluded");
    }
    if(!empty($translation->exclude) and !empty($translation->include)){
        $title = __("Included/Excluded");
    }
@endphp
@if(!empty($title))
    <div class="bc_include--info">
        <h3 class="info__title">
            {{ $title }}
        </h3>
        <div class="info__description">
            <ul class="info__content info__margin">
                @foreach($translation->include as $item)
                    <li>
                        <i class="input-icon bc_border-radius field-icon fa">
                            <svg height="15px" width="15px" version="1.1"
                                 xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;"
                                 xml:space="preserve">
                                <g fill="#36bca1">
                                    <path d="M6.347,24.003c-0.601,0-1.182-0.183-1.68-0.529c-0.261-0.181-0.489-0.403-0.68-0.658L0.15,17.7
                                    c-0.12-0.16-0.171-0.358-0.143-0.556C0.036,16.946,0.14,16.77,0.3,16.65c0.131-0.098,0.286-0.15,0.45-0.15
                                    c0.235,0,0.459,0.112,0.6,0.3l3.839,5.118c0.094,0.127,0.207,0.236,0.335,0.325c0.245,0.17,0.53,0.26,0.826,0.26
                                    c0.086,0,0.173-0.008,0.259-0.023c0.381-0.068,0.712-0.281,0.933-0.599L22.636,0.32C22.775,0.12,23.005,0,23.25,0
                                    c0.154,0,0.303,0.047,0.429,0.135c0.165,0.115,0.274,0.287,0.309,0.484c0.035,0.197-0.009,0.396-0.124,0.561L8.772,22.739
                                    c-0.449,0.645-1.124,1.078-1.9,1.217C6.699,23.987,6.522,24.003,6.347,24.003z"></path>
                                </g>
                            </svg>
                        </i>{{$item['title'] ?? ''}}
                    </li>
                @endforeach
            </ul>
            <ul class="info__content">
                @if($translation->exclude)
                    @foreach($translation->exclude as $item)
                        <li>
                            <i class="input-icon bc_border-radius field-icon fa">
                                <svg height="15px" width="15px" version="1.1"
                                     xmlns="http://www.w3.org/2000/svg"
                                     xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                     viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;"
                                     xml:space="preserve">
                                        <g fill="#ec927e">
                                            <path d="M19.5,20.25c-0.2,0-0.389-0.078-0.53-0.22L12,13.061l-6.97,6.97c-0.142,0.142-0.33,0.22-0.53,0.22s-0.389-0.078-0.53-0.22
                                            c-0.292-0.292-0.292-0.768,0-1.061l6.97-6.97L3.97,5.03C3.828,4.889,3.75,4.7,3.75,4.5s0.078-0.389,0.22-0.53
                                            C4.111,3.828,4.3,3.75,4.5,3.75s0.389,0.078,0.53,0.22l6.97,6.97l6.97-6.97c0.142-0.142,0.33-0.22,0.53-0.22s0.389,0.078,0.53,0.22
                                            c0.142,0.141,0.22,0.33,0.22,0.53s-0.078,0.389-0.22,0.53L13.061,12l6.97,6.97c0.292,0.292,0.292,0.768,0,1.061
                                            C19.889,20.172,19.7,20.25,19.5,20.25z"></path>
                                        </g>
                                    </svg>
                            </i>
                            {{$item['title'] ?? ''}}
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
@endif
