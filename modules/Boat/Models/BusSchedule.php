<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;

class BusSchedule extends Model
{
    protected $table = 'bc_bus_schedules';

    protected $fillable = [
        'bus_id',
        'route_id',
        'departure_time',
        'arrival_time',
        'price',
        'status',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'price' => 'float',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    public function route()
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }
}
