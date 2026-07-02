<?php

namespace Modules\Emergency\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyCentre extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_emergency_centres';

    protected $fillable = [
        'name',
        'alias',
        'type',
        'address',
        'phone',
        'hours',
        'services',
        'note',
        'dot_color',
        'href',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    public static function getModelName(): string
    {
        return __('Medical Centre');
    }

    public static function getCentreTypes(): array
    {
        return [
            'hospital'  => __('Hospital'),
            'clinic'    => __('Clinic'),
            'pharmacy'  => __('Pharmacy'),
            'urgent'    => __('Urgent Care'),
            'dental'    => __('Dental'),
            'mental'    => __('Mental Health'),
            'other'     => __('Other'),
        ];
    }
}
