<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Booking\Gateways\ChapaGateway;

/**
 * JSON API for managing per-vendor Chapa subaccounts (split payments).
 *
 * Routes are mounted under /api/vendor/chapa/* via modules/Vendor/Routes/api.php
 * and protected by Sanctum + the vendor permission check.
 */
class ChapaApiController extends Controller
{
    /**
     * GET /api/vendor/chapa/banks
     *
     * Returns the list of supported banks from Chapa. Cached server-side for
     * an hour. Useful for populating a "Bank" dropdown on the client.
     */
    public function banks(Request $request)
    {
        if ($error = $this->ensureVendor()) {
            return $error;
        }

        $gateway = $this->getChapaGateway();
        if (!$gateway) {
            return $this->sendError(__('Chapa gateway is not enabled or configured.'));
        }

        $banks = $gateway->getBanks();

        return $this->sendSuccess([
            'data' => $banks,
            'total' => count($banks),
        ]);
    }

    /**
     * GET /api/vendor/chapa/subaccount
     *
     * Returns the authenticated vendor's stored subaccount details.
     * The Chapa secret_key is never exposed.
     */
    public function show(Request $request)
    {
        if ($error = $this->ensureVendor()) {
            return $error;
        }

        return $this->sendSuccess($this->buildSubaccountPayload(Auth::user()));
    }

    /**
     * GET /api/vendor/chapa/subaccount/{vendor}
     *
     * Admin lookup: returns the Chapa subaccount details for any vendor by
     * their user id. Requires the `user_update` admin permission. Allows the
     * caller to read their own record without that permission.
     */
    public function showByVendorId(Request $request, $vendor)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 0, 'message' => __('Unauthenticated.')], 401);
        }

        $vendorId = (int) $vendor;
        if ($vendorId <= 0) {
            return $this->sendError(__('Invalid vendor id.'));
        }

        $caller = Auth::user();
        $isSelf = (int) $caller->id === $vendorId;
        if (!$isSelf && !$caller->hasPermission('user_update')) {
            return response()->json([
                'status'  => 0,
                'message' => __('You do not have permission to view other vendors\' subaccounts.'),
            ], 403);
        }

        $user = \App\User::find($vendorId);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => __('Vendor not found.'),
            ], 404);
        }

        return $this->sendSuccess(array_merge(
            ['vendor_id' => (int) $user->id],
            $this->buildSubaccountPayload($user)
        ));
    }

    /**
     * Build the JSON payload describing a user's stored Chapa subaccount.
     *
     * @param \App\User $user
     * @return array<string,mixed>
     */
    protected function buildSubaccountPayload($user): array
    {
        $subaccountId = (string) $user->getMeta('chapa_subaccount_id');

        return [
            'has_subaccount' => $subaccountId !== '',
            'subaccount_id'  => $subaccountId !== '' ? $subaccountId : null,
            'business_name'  => (string) ($user->getMeta('chapa_business_name') ?: $user->business_name),
            'account_name'   => (string) ($user->getMeta('chapa_account_name') ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))),
            'bank_code'      => (string) $user->getMeta('chapa_bank_code'),
            'account_number' => (string) $user->getMeta('chapa_account_number'),
            'split_type'     => (string) ($user->getMeta('chapa_split_type') ?: 'percentage'),
            'split_value'    => (string) $user->getMeta('chapa_split_value'),
        ];
    }

    /**
     * POST /api/vendor/chapa/subaccount
     *
     * Vendor self-service creation is disabled; admins create subaccounts when
     * saving the vendor user in the admin panel.
     */
    public function store(Request $request)
    {
        return response()->json([
            'status'  => 0,
            'message' => __('Chapa subaccounts are created by an administrator when your vendor profile is saved in the admin panel.'),
        ], 403);
    }

    /**
     * POST /api/vendor/chapa/subaccount/link
     *
     * Self-service variant of linkForVendor() — vendor pastes an existing
     * subaccount id (from the Chapa dashboard) instead of creating a new one.
     */
    public function link(Request $request)
    {
        if ($error = $this->ensureVendor()) {
            return $error;
        }
        return $this->linkSubaccountFor(Auth::user(), $request, false);
    }

    /**
     * POST /api/vendor/chapa/subaccount/{vendor}/link
     *
     * Admin variant of link() — stores an existing Chapa subaccount id on a
     * specific vendor's user_meta without re-creating it on Chapa. Useful
     * when Chapa returns "This subaccount does exist" because a previous
     * create call succeeded but the id never persisted on our side.
     */
    public function linkForVendor(Request $request, $vendor)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 0, 'message' => __('Unauthenticated.')], 401);
        }

        $vendorId = (int) $vendor;
        if ($vendorId <= 0) {
            return $this->sendError(__('Invalid vendor id.'));
        }

        $caller = Auth::user();
        $isSelf = (int) $caller->id === $vendorId;
        if (!$isSelf && !$caller->hasPermission('user_update')) {
            return response()->json([
                'status'  => 0,
                'message' => __('You do not have permission to link subaccounts for other vendors.'),
            ], 403);
        }

        $user = \App\User::find($vendorId);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => __('Vendor not found.'),
            ], 404);
        }

        return $this->linkSubaccountFor($user, $request, !$isSelf);
    }

    /**
     * Shared logic to attach an existing Chapa subaccount id to a user. No
     * Chapa API call is made — we trust the caller's id.
     */
    protected function linkSubaccountFor($user, Request $request, bool $isAdminAction)
    {
        $validator = Validator::make($request->all(), [
            'subaccount_id'  => ['required', 'string', 'max:64'],
            // Optional bookkeeping fields so the UI stays in sync.
            'business_name'  => ['nullable', 'string', 'max:255'],
            'account_name'   => ['nullable', 'string', 'max:255'],
            'bank_code'      => ['nullable'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'split_type'     => ['nullable', 'in:percentage,flat'],
            'split_value'    => ['nullable', 'numeric', 'min:0'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'   => 0,
                'error'    => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        $subaccountId = trim((string) $request->input('subaccount_id'));
        $user->addMeta('chapa_subaccount_id', $subaccountId);

        foreach ([
            'business_name'  => 'chapa_business_name',
            'account_name'   => 'chapa_account_name',
            'bank_code'      => 'chapa_bank_code',
            'account_number' => 'chapa_account_number',
            'split_type'     => 'chapa_split_type',
            'split_value'    => 'chapa_split_value',
        ] as $input => $metaKey) {
            if ($request->filled($input)) {
                $user->addMeta($metaKey, (string) $request->input($input));
            }
        }

        $message = $isAdminAction
            ? __('Existing subaccount linked to vendor #:id.', ['id' => $user->id])
            : __('Existing Chapa subaccount linked to your account.');

        return $this->sendSuccess([
            'vendor_id'     => (int) $user->id,
            'subaccount_id' => $subaccountId,
        ], $message);
    }

    /**
     * POST /api/vendor/chapa/subaccount/{vendor}
     *
     * Direct API creation for another vendor is disabled; use the admin user
     * save flow with Chapa settlement fields instead.
     */
    public function storeForVendor(Request $request, $vendor)
    {
        return response()->json([
            'status'  => 0,
            'message' => __('Chapa subaccounts are created by an administrator when the vendor user is saved in the admin panel.'),
        ], 403);
    }

    /**
     * Reject the request when the caller is not an authenticated vendor with
     * the right permission. Returns the JSON response, or null when allowed.
     */
    protected function ensureVendor()
    {
        if (!Auth::check()) {
            return response()->json([
                'status'  => 0,
                'message' => __('Unauthenticated.'),
            ], 401);
        }
        $user = Auth::user();
        $hasVendorAccess = $user->hasPermission('dashboard_vendor_access');
        $isAdmin = $user->hasPermission('user_update');
        if (!$hasVendorAccess && !$isAdmin) {
            return response()->json([
                'status'  => 0,
                'message' => __('You do not have access to vendor features.'),
            ], 403);
        }
        return null;
    }

    /**
     * Resolve the configured Chapa gateway, or null when it is missing/disabled.
     */
    protected function getChapaGateway(): ?ChapaGateway
    {
        $obj = get_payment_gateway_obj('chapa');
        return $obj instanceof ChapaGateway ? $obj : null;
    }
}
