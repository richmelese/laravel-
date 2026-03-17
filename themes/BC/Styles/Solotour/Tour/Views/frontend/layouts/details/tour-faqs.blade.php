@if($translation->faqs)
    <div class="container">
        <div class="bc_faq">
            <h3 class="bc_section-title">
                {{ __("Frequently Asked Questions") }}
            </h3>
            <div class="bc_flex--faq row">
                @foreach($translation->faqs as $item)
                    <div class="col-md-6 bc_faq--content">
                        <div class="item">
                            <div class="header">
                                <h5>{{$item['title'] ?? ''}}</h5>
                                <span class="arrow"><i class="fa fa-angle-down"></i></span>
                            </div>
                            <div class="body">
                                {!! clean($item['content']) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
