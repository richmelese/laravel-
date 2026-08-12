<?php

namespace Modules\Api\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class TelebirrAccount extends Model
{
    protected $table = 'telebirr_accounts';

    protected $fillable = [
        'user_id',
        'open_id',
        'identity_id',
        'identity_type',
        'wallet_identity_id',
        'identifier',
        'nickname',
        'status',
        'profile',
        'last_login_at',
    ];

    protected $casts = [
        'profile' => 'array',
        'last_login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
