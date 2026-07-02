<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyCovidHealth extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_covid_health';

    protected $fillable = [
        'title',
        'points',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'points' => 'array',
    ];

    public static function getModelName(): string
    {
        return __('COVID & Health Entry');
    }
}
