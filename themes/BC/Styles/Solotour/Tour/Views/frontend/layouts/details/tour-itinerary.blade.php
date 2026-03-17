@if($translation->itinerary)
    <div class="bc_program--wrapper bc_program-parent">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="bc_program bc_program--padding bc_maxheight">
                        <div class="bc_title-wrapper bc_program--title">
                            <h3 class="bc_section-title bc_title__item">{{ __("Tour Itinerary") }}</h3>
                        </div>
                        <div class="bc_program-list style4">
                            <div class="owl-carousel-wrapper">
                                <div class="owl-carousel owl-tour-program-7 owl-loaded owl-drag">
                                    @foreach($translation->itinerary as $item)
                                        <div class="item--bg">
                                            <div class="box-shadow">
                                                <img src="{{ get_file_url($item['image_id'],"full") }}" alt="{{$item['title'] ?? ''}}">
                                            </div>
                                            <div class="bc_itinerary--info">
                                                <div class="body bc_itinerary--info__content">
                                                    <h2 class="content__time">{{$item['title'] ?? ''}}</h2>
                                                    <h5 class="content__title">
                                                        {{$item['desc'] ?? ''}}

                                                        <div>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="52" height="7" fill="none" viewBox="0 0 52 7">
                                                                <path stroke="#EC927E" stroke-width="2" d="M.814 3.513c1.332-1.868 5.185-4.483 9.942 0 4.758 4.482 8.716 1.867 10.1 0m0 .012c1.332-1.868 5.185-4.483 9.942 0 4.758 4.482 8.716 1.867 10.1 0m-.031.018c1.384-1.919 5.31-4.605 9.942 0"/>
                                                            </svg>
                                                        </div>

                                                    </h5>
                                                    <div class="desc content__desc">
                                                        <p>
                                                            <i class="input-icon bc_border-radius field-icon fa">
                                                                <svg height="14px" width="14px" version="1.1" id="Regular" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;" xml:space="preserve">
                                                                    <g fill="#222222">
                                                                        <path d="M12.75,23.25c-0.2,0-0.389-0.078-0.53-0.22c-0.292-0.292-0.292-0.768,0-1.061l9.22-9.22H0.75C0.336,12.75,0,12.414,0,12
                                                                            s0.336-0.75,0.75-0.75h20.689l-9.22-9.22C12.078,1.889,12,1.7,12,1.5s0.078-0.389,0.22-0.53c0.141-0.142,0.33-0.22,0.53-0.22
                                                                            s0.389,0.078,0.53,0.22l10.5,10.5c0.07,0.07,0.125,0.152,0.163,0.245c0.003,0.008,0.007,0.017,0.01,0.026
                                                                            C23.984,11.822,24,11.911,24,12c0,0.087-0.016,0.174-0.047,0.258c-0.002,0.006-0.004,0.011-0.006,0.016
                                                                            c-0.042,0.104-0.098,0.187-0.168,0.257L13.28,23.03C13.139,23.172,12.95,23.25,12.75,23.25z"></path>
                                                                    </g>
                                                                </svg>
                                                            </i>
                                                            {!! clean($item['content']) !!}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
