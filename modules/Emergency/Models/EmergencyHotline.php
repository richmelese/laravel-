<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyHotline extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_hotlines';

    protected $fillable = [
        'title',
        'number',
        'email',
        'flag',
        'description',
        'status',
        'sort_order',
    ];

    public static function getModelName(): string
    {
        return __('Emergency Hotline');
    }
}
