<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;

class BusBooking extends Model
{
    protected $table = 'bc_bus_bookings';

    protected $fillable = [
        'user_id',
        'schedule_id',
        'seat_number',
        'status',
        'payment_status',
        'ticket_code',
    ];

    public function schedule()
    {
        return $this->belongsTo(BusSchedule::class, 'schedule_id');
    }
}
