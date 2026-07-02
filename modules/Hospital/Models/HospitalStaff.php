<?php

namespace Modules\Hospital\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalStaff extends Model
{
    protected $table = 'bc_hospital_staff';

    protected $fillable = ['hospital_id', 'user_id', 'role'];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public static function getRoles(): array
    {
        return [
            'owner'   => __('Owner'),
            'manager' => __('Manager'),
            'staff'   => __('Staff'),
        ];
    }
}
