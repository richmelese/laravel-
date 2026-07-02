<?php

namespace Modules\Hospital\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Hospital extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_hospitals';

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'image_id',
        'status',
        'author_id',
        'booking_amount',
        'currency',
    ];

    protected $casts = [
        'latitude'       => 'float',
        'longitude'      => 'float',
        'booking_amount' => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function author()
    {
        return $this->belongsTo(\App\User::class, 'author_id');
    }

    public function staff()
    {
        return $this->hasMany(HospitalStaff::class, 'hospital_id');
    }

    public function staffUsers()
    {
        return $this->belongsToMany(\App\User::class, 'bc_hospital_staff', 'hospital_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function medicalBookings()
    {
        return $this->hasMany(\Modules\MedicalBooking\Models\MedicalBooking::class, 'hospital_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Check if a user can manage this hospital.
     * Admin (hospital_manage_others) or is the author or is staff.
     */
    public function canManagedBy($user): bool
    {
        if (!$user) return false;
        if ($user->hasPermission('hospital_manage_others')) return true;
        if ($this->author_id == $user->id) return true;
        return HospitalStaff::where('hospital_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public static function getModelName(): string
    {
        return __('Hospital');
    }

    public static function getStatuses(): array
    {
        return [
            'publish' => __('Published'),
            'draft'   => __('Draft'),
        ];
    }
}
