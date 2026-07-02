<?php

namespace Modules\AppPromo\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AppPromo\Models\AppPromoRedemption;
use Modules\Booking\Models\Booking;

class AppPromoController extends Controller
{
    /**
     * GET /api/app-promo
     * Returns the promotion section content for the homepage.
     */
    public function section()
    {
        if (!setting_item('app_promo_enabled')) {
            return response()->json(['data' => null, 'active' => false]);
        }

        return response()->json([
            'active' => true,
            'data'   => [
                'title'          => setting_item('app_promo_title', 'Absolutely worth it!'),
                'subtitle'       => setting_item('app_promo_subtitle', 'Book smarter with our app'),
                'description'    => setting_item('app_promo_description', 'Enjoy our best offers, exclusive prices, and priority customer support with our app. Plus, get 10% off your first booking when you book through the app.'),
                'badge_text'     => setting_item('app_promo_badge_text', '10% OFF'),
                'promo_code'     => setting_item('app_promo_code', ''),
                'discount'       => (float) setting_item('app_promo_discount', 10),
                'discount_type'  => setting_item('app_promo_discount_type', 'percent'),
                'ios_url'        => setting_item('app_promo_ios_url', ''),
                'android_url'    => setting_item('app_promo_android_url', ''),
                'promo_image'    => setting_item('app_promo_image', ''),
                'condition_note' => __('Promo code valid once per account. Cannot be reused.'),
            ],
        ]);
    }

    /**
     * POST /api/app-promo/validate
     * Pre-check: is this code valid and unused for this email?
     * No side effects — safe to call multiple times.
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string',
        ]);

        $result = $this->resolveCode(
            $request->input('code'),
            $request->input('email'),
            Auth::id()
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    /**
     * POST /api/app-promo/redeem
     * Apply the promo to a booking and lock the code for this account.
     * Works for guests (no auth) and logged-in users.
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'code'         => 'required|string',
            'booking_code' => 'required|string',
        ]);

        $email       = $request->input('email');
        $code        = $request->input('code');
        $bookingCode = $request->input('booking_code');
        $userId      = Auth::id();

        $check = $this->resolveCode($code, $email, $userId);
        if (!$check['valid']) {
            return response()->json(['status' => 0, 'message' => $check['message']], 422);
        }

        $booking = Booking::where('code', $bookingCode)
            ->where('email', strtolower(trim($email)))
            ->whereIn('status', ['draft', 'unpaid'])
            ->first();

        if (empty($booking)) {
            return response()->json([
                'status'  => 0,
                'message' => __('Booking not found or email does not match.'),
            ], 404);
        }

        $discount       = (float) setting_item('app_promo_discount', 10);
        $discountType   = setting_item('app_promo_discount_type', 'percent');
        $discountAmount = $discountType === 'percent'
            ? round($booking->total * ($discount / 100), 2)
            : min($discount, $booking->total);

        $newTotal = max(0, $booking->total - $discountAmount);

        $booking->total = $newTotal;
        $booking->save();

        AppPromoRedemption::record($email, $userId, $bookingCode, $discountAmount);

        return response()->json([
            'status'  => 1,
            'message' => __('Promo code applied successfully!'),
            'data'    => [
                'discount_amount' => $discountAmount,
                'new_total'       => $newTotal,
                'currency'        => $booking->currency,
            ],
        ]);
    }

    /**
     * Shared logic: validate code + check if already redeemed.
     */
    protected function resolveCode(string $inputCode, string $email, ?int $userId): array
    {
        if (!setting_item('app_promo_enabled')) {
            return ['valid' => false, 'message' => __('Promo is not active.')];
        }

        $storedCode = strtoupper(trim(setting_item('app_promo_code', '')));

        if (empty($storedCode)) {
            return ['valid' => false, 'message' => __('No promo code is currently active.')];
        }

        if (strtoupper(trim($inputCode)) !== $storedCode) {
            return ['valid' => false, 'message' => __('Invalid promo code.')];
        }

        if (AppPromoRedemption::hasRedeemed($email, $userId)) {
            return [
                'valid'   => false,
                'message' => __('This promo code has already been used on this account.'),
            ];
        }

        $discount     = (float) setting_item('app_promo_discount', 10);
        $discountType = setting_item('app_promo_discount_type', 'percent');

        return [
            'valid'         => true,
            'message'       => __('Promo code is valid!'),
            'discount'      => $discount,
            'discount_type' => $discountType,
            'label'         => $discountType === 'percent'
                ? "{$discount}% off your booking"
                : number_format($discount, 2) . ' off your booking',
        ];
    }
}
