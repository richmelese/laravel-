<div data-vc-full-width="true" data-vc-full-width-init="true" class="vc_row wpb_row bg-holder bc_solo-about-us vc_row-has-fill" style="background-image: url({{ get_file_url($background_image) }});">
    <div class="container ">
        <div class="row">
            <div class="bc_about-us-left wpb_column column_container col-md-6">
                <div class="vc_column-inner wpb_wrapper">
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            <p style="text-align: left; padding-left: 80px;">{{ $sub_title }}</p>
                            <h3 style="text-align: left; padding-left: 80px;">{{ $title }}</h3>
                        </div>
                    </div>
                    <div class="wpb_single_image wpb_content_element vc_align_left wpb_content_element">
                        <figure class="wpb_wrapper vc_figure">
                            <div class="vc_single_image-wrapper   vc_box_border_grey">
                                <img src="{{ get_file_url($image) }}" width="676" height="564" alt="Group 25" title="Group 25" loading="lazy"></div>
                        </figure>
                    </div>
                </div>
            </div>
            <div class="bc_about-us-right wpb_column column_container col-md-6">
                <div class="vc_column-inner wpb_wrapper">
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            <p>{{ $desc }}</p>
                            <h3>{{ $title_list }}</h3>
                        </div>
                    </div>
                    <div class="wpb_text_column wpb_content_element">
                        <div class="wpb_wrapper">
                            @if(!empty($list_item))
                                @foreach($list_item as $item)
                                    <p><img loading="lazy" decoding="async" class="alignnone wp-image-8505" src="{{ get_file_url($item['image']) }}" alt="{{ $title }}" width="49" height="49">
                                    {{ $item['title'] }}
                                    </p>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="wpb_text_column wpb_content_element bc_about--btn">
                        <div class="wpb_wrapper">
                            <p><a href="{{ $link_more }}">{{ $text_link_more }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--End .row-->
    </div><!--End .container-->
</div>