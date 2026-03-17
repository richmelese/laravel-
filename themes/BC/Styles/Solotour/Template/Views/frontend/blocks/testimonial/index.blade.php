@if ($list_item)
<div class="vc_row wpb_row st bg-holder bc_solo-testimonial-wrapper mt-5 pt-5">
    <div class='container '>
        <div class='row'>
            <div class="wpb_column column_container col-md-12">
                <div class="vc_column-inner wpb_wrapper">
                    <div class="bc_testimonial-new style-6 ">
                        <div class="owl-carousel bc_testimonial-solo-slider style-6 ">
                            @foreach ($list_item as $item)
                                <?php $avatar_url = get_file_url($item['avatar'], 'full'); ?>
                                <div class="item ">
                                    <div class="content col-xs-12 col-lg-5 col-md-5 col-sm-5">
                                        <div class="content-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="17" fill="none" viewBox="0 0 24 17">
                                                <path fill="#B15DE4" d="M9.84 0C4.262.16-.136 4.8.003 10.38v1.296c0 2.086 1.256 3.966 3.183 4.764 1.927.799 4.145.358 5.62-1.117 1.474-1.475 1.915-3.693 1.117-5.62-.798-1.926-2.679-3.183-4.765-3.182-.392 0-.783.05-1.163.146-.102.025-.209-.012-.272-.096-.063-.084-.07-.197-.017-.288C4.944 4.047 7.284 2.645 9.84 2.608c.72 0 1.304-.584 1.304-1.304C11.144.584 10.56 0 9.84 0zM22.696 2.608c.72 0 1.304-.584 1.304-1.304C24 .584 23.416 0 22.696 0c-5.578.16-9.976 4.8-9.837 10.38v1.296c0 2.086 1.256 3.966 3.182 4.764 1.927.799 4.145.358 5.62-1.117 1.475-1.474 1.916-3.692 1.118-5.619-.798-1.927-2.678-3.183-4.764-3.183-.392 0-.783.05-1.164.146-.101.026-.208-.012-.271-.096-.063-.084-.07-.197-.018-.288 1.238-2.236 3.578-3.638 6.134-3.675z"/>
                                            </svg>
                                            <p class="pt-4">{{ $item['desc'] ?? '' }}</p>
                                            <div class="author-meta">
                                                <div><span class="author-name">{{ $item['name'] }}</span></div>
                                                <div class="star">
                                                    @for ($i = 0; $i < $item['number_star']; $i++)
                                                        <i class="fa fa-star"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="author col-xs-12 col-lg-7 col-md-7 col-sm-7">
                                        <img decoding="async" src="{{ $avatar_url }}" alt="{{ $item['name'] }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div><!--End .row-->
    </div><!--End .container-->
</div>
@push("js")
<script type="text/javascript">
    jQuery(document).ready(function () {
        $('.bc_testimonial-solo-slider').each(function () {
            $(this).owlCarousel({
                loop: false,
                items: 1,
                margin: 30,
                responsiveClass: true,
                dots: true,
                nav: false
            });
        });
    })
</script>
@endpush
@else
{{-- Empty div to satisfy Livewire root tag requirement --}}
<div style="display: none;"></div>
@endif
