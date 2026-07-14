<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;

class BusBooking extends Model
{
    protected $table = 'bc_bus_bookings';

    protected $fillable = [
        'user_id',
        'booking_group',
        'schedule_id',
        'seat_number',
        'status',
        'payment_status',
        'ticket_code',
        'trip_type',
        'departure_date',
        'passengers',
        'passenger_name',
        'email',
        'phone',
        'boarding_point',
        'dropping_point',
        'paid_luggage',
        'special_luggage',
    ];

    protected $casts = [
        'departure_date' => 'date',
    ];

    public function schedule()
    {
        return $this->belongsTo(BusSchedule::class, 'schedule_id');
    }
}
