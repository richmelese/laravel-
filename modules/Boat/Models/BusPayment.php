<?php

namespace Modules\Boat\Models;

use Illuminate\Database\Eloquent\Model;

class BusPayment extends Model
{
    protected $table = 'bc_bus_payments';

    protected $fillable = [
        'booking_id',
        'amount',
        'status',
        'payment_method',
        'provider_reference',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
