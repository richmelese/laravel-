<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use SoftDeletes;

    protected $table = 'bc_buses';

    protected $fillable = [
        'bus_name',
        'description',
        'bus_number',
        'side_number',
        'bus_type',
        'seat_capacity',
        'driver_name',
        'driver_phone',
        'departure_city',
        'arrival_city',
        'departure_location',
        'arrival_location',
        'departure_time',
        'arrival_time',
        'price',
        'price_in_words',
        'image_id',
        'gallery',
        'status',
        'is_active',
        'create_user',
        'update_user',
        'slug',
        'sale_price',
        'currency',
        'location_id',
        'address',
        'map_lat',
        'map_lng',
        'map_zoom',
        'banner_image_id',
        'video',
        'seo_title',
        'seo_desc',
        'title_ja',
        'content_ja',
        'title_eg',
        'content_eg',
        'is_featured',
        'author_id',
        'default_state',
        'ical_import_url',
        'min_day_before_booking',
        'min_day_stays',
        'start_time',
        'end_time',
        'duration_hour',
        'faqs',
        'facility_labels',
        'terms',
        'baggage',
        'door',
        'gear_shift',
    ];

    protected $casts = [
        'gallery' => 'array',
        'image_id' => 'integer',
        'seat_capacity' => 'integer',
        'is_active' => 'integer',
        'price' => 'float',
        'departure_time' => 'datetime:H:i',
        'arrival_time' => 'datetime:H:i',
        'sale_price' => 'float',
        'map_zoom' => 'integer',
        'is_featured' => 'integer',
        'default_state' => 'integer',
        'min_day_before_booking' => 'integer',
        'min_day_stays' => 'integer',
        'duration_hour' => 'integer',
        'banner_image_id' => 'integer',
        'location_id' => 'integer',
        'author_id' => 'integer',
        'faqs' => 'array',
        'facility_labels' => 'array',
        'terms' => 'array',
    ];
}
