<?php

namespace Modules\MedicalBooking\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MedicalBooking extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_medical_bookings';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'nationality',
        'passport_number',
        'date_of_birth',
        'request_type',
        'priority',
        'description',
        'preferred_date',
        'preferred_time',
        'location',
        'medical_centre_name',
        'status',
        'admin_note',
        'fee',
        'currency',
        'payment_status',
        'payment_gateway',
        'payment_reference',
        'paid_at',
        'hospital_id',
    ];

    protected $casts = [
        'date_of_birth'  => 'date',
        'preferred_date' => 'date',
        'fee'            => 'float',
        'paid_at'        => 'datetime',
    ];

    public static function generateBookingCode(): string
    {
        do {
            $code = 'MB-' . strtoupper(Str::random(8));
        } while (static::where('booking_code', $code)->exists());
        return $code;
    }

    public static function getPaymentStatuses(): array
    {
        return [
            'unpaid'   => __('Unpaid'),
            'pending'  => __('Pending'),
            'paid'     => __('Paid'),
            'refunded' => __('Refunded'),
            'failed'   => __('Failed'),
        ];
    }

    public function hospital()
    {
        return $this->belongsTo(\Modules\Hospital\Models\Hospital::class, 'hospital_id');
    }

    public static function getModelName()
    {
        return __('Medical Booking');
    }

    public static function getRequestTypes(): array
    {
        return [
            'medical_support'         => __('Medical Support'),
            'urgent_assistance'       => __('Urgent Assistance'),
            'medical_centre_contact'  => __('Contact Medical Centre'),
            'travel_emergency'        => __('Travel Emergency'),
        ];
    }

    public static function getPriorityLevels(): array
    {
        return [
            'normal'    => __('Normal'),
            'urgent'    => __('Urgent'),
            'emergency' => __('Emergency'),
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'pending'     => __('Pending'),
            'confirmed'   => __('Confirmed'),
            'in_progress' => __('In Progress'),
            'completed'   => __('Completed'),
            'cancelled'   => __('Cancelled'),
        ];
    }
}
