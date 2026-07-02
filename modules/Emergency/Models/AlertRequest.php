<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertRequest extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_alert_requests';

    protected $fillable = [
        'user_id',
        'booking_code',
        'booked_services',
        'name',
        'email',
        'phone',
        'alert_type',
        'message',
        'location',
        'latitude',
        'longitude',
        'status',
        'is_active',
        'admin_note',
        'responded_at',
    ];

    protected $casts = [
        'latitude'        => 'float',
        'longitude'       => 'float',
        'is_active'       => 'boolean',
        'responded_at'    => 'datetime',
        'booked_services' => 'array',
    ];

    public static function getAlertTypes(): array
    {
        return [
            'sos'     => __('SOS / Emergency'),
            'medical' => __('Medical Help'),
            'help'    => __('General Help'),
            'general' => __('General Inquiry'),
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'new'          => __('New'),
            'acknowledged' => __('Acknowledged'),
            'in_progress'  => __('In Progress'),
            'resolved'     => __('Resolved'),
            'dismissed'    => __('Dismissed'),
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }
}
