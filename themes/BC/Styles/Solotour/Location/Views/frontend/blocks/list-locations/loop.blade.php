@php
    /**
    * @var $row \Modules\Location\Models\Location
    * @var $to_location_detail bool
    * @var $service_type string
    */
    $translation = $row->translate();
    $link_location = false;
    if(is_string($service_type)){
        $link_location = $row->getLinkForPageSearch($service_type);
    }
    if(is_array($service_type) and count($service_type) == 1){
        $link_location = $row->getLinkForPageSearch($service_type[0] ?? "");
    }
    if($to_location_detail){
        $link_location = $row->getDetailUrl();
    }
@endphp

<div class="has-matchHeight slider-item">
    <div class="destination-item">
        <div class="image">
            @if(!empty($link_location)) <a href="{{$link_location}}"> @endif
                <img src="{{$row->getImageUrl()}}" alt="Location">
            @if(!empty($link_location)) </a> @endif
        </div>
    </div>
</div>