<?php

namespace Modules\AppPromo\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\AppPromo\Models\AppPromoRedemption;

class AppPromoController extends AdminController
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    // -------------------------------------------------------------------------
    // SETTINGS
    // -------------------------------------------------------------------------

    /**
     * GET /api-admin/app-promo/settings
     * Returns current promotion section settings.
     */
    public function getSettings(Request $request)
    {
        $this->checkPermission('app_promo_manage');

        $settings = [
            'app_promo_enabled'       => (bool) setting_item('app_promo_enabled', false),
            'app_promo_title'         => setting_item('app_promo_title', 'Absolutely worth it!'),
            'app_promo_subtitle'      => setting_item('app_promo_subtitle', 'Book smarter with our app'),
            'app_promo_description'   => setting_item('app_promo_description', 'Enjoy our best offers, exclusive prices, and priority customer support with our app. Plus, get 10% off your first booking when you book through the app.'),
            'app_promo_badge_text'    => setting_item('app_promo_badge_text', '10% OFF'),
            'app_promo_code'          => setting_item('app_promo_code', ''),
            'app_promo_discount'      => (float) setting_item('app_promo_discount', 10),
            'app_promo_discount_type' => setting_item('app_promo_discount_type', 'percent'),
            'app_promo_ios_url'       => setting_item('app_promo_ios_url', ''),
            'app_promo_android_url'   => setting_item('app_promo_android_url', ''),
            'app_promo_image'         => setting_item('app_promo_image', ''),
        ];

        return response()->json(['data' => $settings]);
    }

    /**
     * PUT /api-admin/app-promo/settings
     * Save promotion section settings.
     */
    public function saveSettings(Request $request)
    {
        $this->checkPermission('app_promo_manage');

        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $request->validate([
            'app_promo_code'          => 'nullable|string|max:50',
            'app_promo_discount'      => 'nullable|numeric|min:0|max:100',
            'app_promo_discount_type' => 'nullable|in:percent,fixed',
        ]);

        $fields = [
            'app_promo_enabled',
            'app_promo_title',
            'app_promo_subtitle',
            'app_promo_description',
            'app_promo_badge_text',
            'app_promo_code',
            'app_promo_discount',
            'app_promo_discount_type',
            'app_promo_ios_url',
            'app_promo_android_url',
            'app_promo_image',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $field === 'app_promo_code'
                    ? strtoupper(trim($request->input($field)))
                    : $request->input($field);
                setting_update_item($field, $value);
            }
        }

        return response()->json(['message' => __('Settings saved successfully.')]);
    }

    // -------------------------------------------------------------------------
    // REDEMPTIONS
    // -------------------------------------------------------------------------

    /**
     * GET /api-admin/app-promo/redemptions
     * List all promo code redemptions.
     */
    public function redemptions(Request $request)
    {
        $this->checkPermission('app_promo_manage');

        $query = AppPromoRedemption::query()->orderByDesc('id');

        if ($s = $request->query('s')) {
            $query->where('email', 'LIKE', '%' . $s . '%');
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    /**
     * DELETE /api-admin/app-promo/redemptions/{id}
     * Revoke a single redemption — allows the user to use the promo again.
     */
    public function revokeRedemption(Request $request, $id)
    {
        $this->checkPermission('app_promo_manage');

        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $row = AppPromoRedemption::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Redemption not found')], 404);
        }

        $row->delete();

        return response()->json(['message' => __('Redemption revoked. User can now use the promo code again.')]);
    }

    /**
     * POST /api-admin/app-promo/redemptions/bulk-action
     * Bulk revoke redemptions.
     */
    public function bulkAction(Request $request)
    {
        $this->checkPermission('app_promo_manage');

        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => __('No items selected!')], 422);
        }

        if ($action === 'revoke') {
            AppPromoRedemption::whereIn('id', $ids)->delete();
            return response()->json(['message' => __('Redemptions revoked successfully.')]);
        }

        return response()->json(['message' => __('Unknown action.')], 422);
    }
}
