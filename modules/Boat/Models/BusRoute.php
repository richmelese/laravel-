<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;

class BusRoute extends Model
{
    protected $table = 'bc_bus_routes';

    protected $fillable = [
        'from_location',
        'to_location',
        'distance_km',
        'status',
    ];

    protected $casts = [
        'distance_km' => 'float',
    ];
}
