<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyTravelSupport extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_travel_support';

    protected $fillable = [
        'scenario',
        'steps',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'steps' => 'array',
    ];

    public static function getModelName(): string
    {
        return __('Travel Support Entry');
    }
}
