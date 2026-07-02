<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyNumber extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_numbers';

    protected $fillable = [
        'label',
        'number',
        'href',
        'color',
        'icon_name',
        'status',
        'sort_order',
    ];

    public static function getModelName(): string
    {
        return __('Emergency Number');
    }
}
