<div class=" bc_solo-banner-bg d-flex align-items-center" style="background-image: url({{ get_file_url($bg_image) }});">
	<div class="container ">
		<div class="row">
			<div class="col-md-12 text-center">
				<div class="wpb_content_element solo-banner-title">
					<div class="wpb_wrapper">
						<div class="withPadding">
							<h3 class="sidebarHeader text-center">{!! $title !!}</h3>
						</div>

					</div>
				</div>

				<div class="wpb_content_element solo-banner-sub-title">
					<div class="wpb_wrapper">
						<p class="text-center">{{ $sub_title }}</p>
					</div>
				</div>
				<div class="d-inline-block">
					<a class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-rounded vc_btn3-style-modern vc_btn3-color-grey" 
						href="{{ $link_url }}" title="{{ $link_title }}" target="_blank">
						{{ $link_title }}
					</a>
				</div>
			</div>
		</div><!--End .row-->
	</div><!--End .container-->
</div>