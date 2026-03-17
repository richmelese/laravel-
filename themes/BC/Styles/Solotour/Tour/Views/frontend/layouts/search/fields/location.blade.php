@php($location_search_style = setting_item('tour_location_search_style'))
<div class="form-group form-extra-field dropdown clearfix field-detination has-icon open">
    <i class="input-icon st-border-radius field-icon fa">
        <svg width="24px" height="24px" viewBox="0 0 17 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <defs></defs>
            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                <g id="Search_Result_1_Grid" transform="translate(-165.000000, -328.000000)" stroke="#A0A9B2">
                    <g id="form_search_hotel_row" transform="translate(135.000000, 290.000000)">
                        <g transform="translate(30.000000, 0.000000)">
                            <g id="where" transform="translate(0.000000, 26.000000)">
                                <g transform="translate(0.000000, 12.000000)">
                                    <g id="ico_maps_search_box">
                                        <path d="M15.75,8.25 C15.75,12.471 12.817,14.899 10.619,17.25 C9.303,18.658 8.25,23.25 8.25,23.25 C8.25,23.25 7.2,18.661 5.887,17.257 C3.687,14.907 0.75,12.475 0.75,8.25 C0.75,4.10786438 4.10786438,0.75 8.25,0.75 C12.3921356,0.75 15.75,4.10786438 15.75,8.25 Z"></path>
                                        <circle cx="8.25" cy="8.25" r="3"></circle>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </g>
                </g>
            </g>
        </svg>
    </i>
    <div class="dropdown" id="dropdown-destination">
        <div class="render render-new wire:ignore">
            @if($location_search_style=='autocompletePlace')
            <div class="g-map-place">
                <input type="text" name="map_place" placeholder="{{__("Where are you going?")}}" value="{{$map_place}}" class="form-control border-0">
                <div class="map d-none" id="map-{{\Illuminate\Support\Str::random(10)}}"></div>
                <input type="hidden" wire:model="map_lat" value="{{$map_lat}}">
                <input type="hidden" wire:model="map_lng" value="{{$map_lng}}">
            </div>
            @else
            <?php
            $location_name = "";
            $list_json = [];
            $traverse = function ($locations, $prefix = '') use (&$traverse, &$list_json, &$location_name, $location_id) {
                foreach ($locations as $location) {
                    $translate = $location->translate();
                    if ($location_id == $location->id) {
                        $location_name = $translate->name;
                    }
                    $list_json[] = [
                        'id'    => $location->id,
                        'title' => $prefix . ' ' . $translate->name,
                    ];
                    $traverse($location->children, $prefix . '-');
                }
            };
            $traverse($tour_location);
            ?>
            <div class="smart-search">
                <input type="text" class="smart-search-location parent_text form-control text-bold" {{ ( empty(setting_item("tour_location_search_style")) or setting_item("tour_location_search_style") == "normal" ) ? "readonly" : ""  }} placeholder="{{__("Where are you going?")}}" value="{{ $location_name }}" data-onLoad="{{__("Loading...")}}"
                    data-default="{{ json_encode($list_json) }}">
                <input type="hidden" class="child_id" name="location_id" value="{{$location_id}}">
            </div>
            @endif
        </div>
    </div>
</div>