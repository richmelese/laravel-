<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyContact extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_contacts';

    protected $fillable = [
        'label',
        'value',
        'href',
        'status',
        'sort_order',
    ];

    public static function getModelName(): string
    {
        return __('Quick Contact');
    }
}
