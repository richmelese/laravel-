<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use SoftDeletes;

    protected $table = 'bc_buses';

    protected $fillable = [
        'title',
        'description',
        'bus_number',
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
        'image_id',
        'gallery',
        'status',
        'is_active',
        'create_user',
        'update_user',
    ];

    protected $casts = [
        'gallery' => 'array',
        'image_id' => 'integer',
        'seat_capacity' => 'integer',
        'is_active' => 'integer',
        'price' => 'float',
        'departure_time' => 'datetime:H:i',
        'arrival_time' => 'datetime:H:i',
    ];
}
