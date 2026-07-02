<?php

namespace Modules\AppPromo\Models;

use App\BaseModel;

class AppPromoRedemption extends BaseModel
{
    protected $table = 'bc_app_promo_redemptions';

    protected $fillable = [
        'user_id',
        'email',
        'booking_code',
        'promo_code',
        'discount_amount',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    /**
     * Check if a given email OR user_id has already redeemed a promo code.
     * Checks both so a guest who later registers cannot reuse it.
     */
    public static function hasRedeemed(string $email, ?int $userId = null): bool
    {
        $promoCode = strtoupper(trim(setting_item('app_promo_code', '')));
        if (empty($promoCode)) return false;

        $query = static::query()->where('promo_code', $promoCode);

        if ($userId) {
            return $query->where(function ($q) use ($email, $userId) {
                $q->where('email', strtolower(trim($email)))
                  ->orWhere('user_id', $userId);
            })->exists();
        }

        return $query->where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Record a redemption.
     */
    public static function record(string $email, ?int $userId, string $bookingCode, float $discountAmount): self
    {
        $promoCode = strtoupper(trim(setting_item('app_promo_code', '')));

        $redemption = new static();
        $redemption->email           = strtolower(trim($email));
        $redemption->user_id         = $userId;
        $redemption->booking_code    = $bookingCode;
        $redemption->promo_code      = $promoCode;
        $redemption->discount_amount = $discountAmount;
        $redemption->redeemed_at     = now();
        $redemption->save();

        return $redemption;
    }

    /**
     * When a guest registers, link their redemption to the new account.
     */
    public static function claimByEmail(string $email, int $userId): void
    {
        static::query()
            ->where('email', strtolower(trim($email)))
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);
    }
}
