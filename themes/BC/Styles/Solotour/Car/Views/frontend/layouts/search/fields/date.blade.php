<div data-custom-class="search-datepicker" class=" form-group form-date-field form-date-search-new clearfix  date-popup-solo   has-icon " data-format="MM/DD/YYYY">
    <i class="input-icon st-border-radius field-icon fa">
        <svg height="24px" width="24px" viewBox="0 0 24 25" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <defs></defs>
            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                <g id="Search_Result_1_Grid" transform="translate(-436.000000, -328.000000)" stroke="#A0A9B2">
                    <g id="form_search_hotel_row" transform="translate(135.000000, 290.000000)">
                        <g transform="translate(30.000000, 0.000000)">
                            <g id="check-in" transform="translate(270.000000, 26.000000)">
                                <g id="ico_calendar_search_box" transform="translate(1.000000, 12.000000)">
                                    <g id="calendar-add-1">
                                        <path d="M9.5,18.5 L1.5,18.5 C0.94771525,18.5 0.5,18.0522847 0.5,17.5 L0.5,3.5 C0.5,2.94771525 0.94771525,2.5 1.5,2.5 L19.5,2.5 C20.0522847,2.5 20.5,2.94771525 20.5,3.5 L20.5,10"></path>
                                        <path d="M5.5,0.501 L5.5,5.501"></path>
                                        <path d="M15.5,0.501 L15.5,5.501"></path>
                                        <path d="M0.5,7.501 L20.5,7.501"></path>
                                        <circle cx="17.5" cy="17.501" r="6"></circle>
                                        <path d="M17.5,14.501 L17.5,20.501"></path>
                                        <path d="M20.5,17.501 L14.5,17.501"></path>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </g>
                </g>
            </g>
        </svg>
    </i>
    <div class="date-wrapper clearfix form-date-search">
        <div class="check-in-wrapper date-wrapper">
            <div class="bc_render">
                <div class="render check-in-render">{{$start ?? display_date(strtotime("today"))}}</div>
                <span> - </span>
                <div class="render check-out-render">{{$end ?? display_date(strtotime("+1 day"))}}</div>
            </div>
        </div>
        <input type="hidden" class="check-in-input" value="{{$start ?? display_date(strtotime("today"))}}" name="start">
        <input type="hidden" class="check-out-input" value="{{$end ?? display_date(strtotime("+1 day"))}}" name="end">
        <input type="text" class="check-in-out" name="date" value="{{($start ?? date("Y-m-d"))." - ".($end ?? date("Y-m-d",strtotime("+1 day")))}}">
    </div>
</div>
