<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Gateways\ChapaGateway;
use Modules\User\Models\User;

/**
 * Creates Chapa split-payment subaccounts and persists settlement meta on users.
 *
 * Subaccount creation is intended to run from the admin user save flow once
 * per vendor when bank/split fields are complete and no id exists yet.
 */
class ChapaSubaccountService
{
    /**
     * Whether this user is treated as a vendor for Chapa settlement (role id 2
     * or role code "vendor").
     */
    public function isVendorForChapaSettlement(User $user): bool
    {
        if ((int) $user->role_id === 2) {
            return true;
        }
        $user->unsetRelation('role');
        $user->loadMissing('role');
        $code = strtolower((string) ($user->role->code ?? ''));

        return $code === 'vendor';
    }

    /**
     * After admin saves a user: persist optional Chapa meta and create a
     * Chapa subaccount once when all settlement fields are posted, the user
     * is a vendor, and chapa_subaccount_id is still empty.
     *
     * @throws ValidationException
     */
    public function syncAfterAdminVendorUserSaved(User $user, array $input): void
    {
        $actor = Auth::user();
        if (!$actor || !$actor->hasPermission('user_update')) {
            return;
        }
        if (!$this->isVendorForChapaSettlement($user)) {
            return;
        }

        $prefixed = $this->extractPrefixedAdminChapaInput($input);
        $chapaKeys = [
            'chapa_business_name',
            'chapa_account_name',
            'chapa_bank_code',
            'chapa_account_number',
            'chapa_split_type',
            'chapa_split_value',
        ];
        $filledFlags = [];
        foreach ($chapaKeys as $key) {
            $filledFlags[$key] = $this->isChapaAdminFieldPresent($key, $prefixed[$key] ?? null);
        }
        $filled = count(array_filter($filledFlags));

        if ($filled === 0) {
            return;
        }
        if ($filled < 6) {
            $errors = [];
            foreach ($chapaKeys as $key) {
                if (! $filledFlags[$key]) {
                    $errors[$key] = [__('Required when any Chapa settlement field is set. Omit all Chapa fields to skip.')];
                }
            }
            throw ValidationException::withMessages($errors);
        }

        $payload = [
            'business_name'  => trim((string) $prefixed['chapa_business_name']),
            'account_name'   => trim((string) $prefixed['chapa_account_name']),
            'bank_code'      => is_numeric($prefixed['chapa_bank_code'])
                ? (int) $prefixed['chapa_bank_code']
                : (string) $prefixed['chapa_bank_code'],
            'account_number' => trim((string) $prefixed['chapa_account_number']),
            'split_type'     => (string) $prefixed['chapa_split_type'],
            'split_value'    => (float) $prefixed['chapa_split_value'],
        ];

        if (! in_array($payload['split_type'], ['percentage', 'flat'], true)) {
            throw ValidationException::withMessages([
                'chapa_split_type' => __('Split type must be either percentage or flat.'),
            ]);
        }

        $this->validatePayload($payload);

        $gateway = $this->gateway();
        if (!$gateway) {
            throw ValidationException::withMessages([
                'chapa_settlement' => __('Chapa gateway is not enabled or configured.'),
            ]);
        }

        $existingId = trim((string) $user->getMeta('chapa_subaccount_id'));
        $this->persistSettlementMeta($user, $payload);

        if ($existingId !== '') {
            return;
        }

        $result = $gateway->createSubaccount($payload);
        if (!$result['ok']) {
            $msg = (string) ($result['message'] ?? __('Unable to create Chapa subaccount. Please verify the bank details.'));
            Log::warning('Chapa subaccount creation failed (admin user save)', [
                'vendor_id' => $user->id,
                'caller_id' => $actor->id,
                'response'  => $result['body'],
            ]);
            throw ValidationException::withMessages([
                'chapa_settlement' => $msg,
            ]);
        }

        $user->addMeta('chapa_subaccount_id', (string) $result['subaccount_id']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function validatePayload(array $payload): void
    {
        if ($payload['split_type'] === 'percentage' && ($payload['split_value'] <= 0 || $payload['split_value'] >= 1)) {
            throw ValidationException::withMessages([
                'chapa_split_value' => __('For percentage split, value must be a decimal between 0 and 1 (e.g. 0.05 for 5%).'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function persistSettlementMeta(User $user, array $payload): void
    {
        $user->addMeta('chapa_business_name', $payload['business_name']);
        $user->addMeta('chapa_account_name', $payload['account_name']);
        $user->addMeta('chapa_bank_code', (string) $payload['bank_code']);
        $user->addMeta('chapa_account_number', $payload['account_number']);
        $user->addMeta('chapa_split_type', $payload['split_type']);
        $user->addMeta('chapa_split_value', (string) $payload['split_value']);
    }

    /**
     * Normalize admin/API payload: accept snake_case, camelCase (`chapaBankCode`),
     * or nested `chapa: { bank_code, ... }` as sent by some React clients.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function extractPrefixedAdminChapaInput(array $input): array
    {
        $nested = is_array($input['chapa'] ?? null) ? $input['chapa'] : [];

        return [
            'chapa_business_name' => $this->firstNonNull([
                $input['chapa_business_name'] ?? null,
                $input['chapaBusinessName'] ?? null,
                $nested['business_name'] ?? null,
                $nested['businessName'] ?? null,
                $nested['chapa_business_name'] ?? null,
            ]),
            'chapa_account_name' => $this->firstNonNull([
                $input['chapa_account_name'] ?? null,
                $input['chapaAccountName'] ?? null,
                $nested['account_name'] ?? null,
                $nested['accountName'] ?? null,
                $nested['chapa_account_name'] ?? null,
            ]),
            'chapa_bank_code' => $this->firstNonNull([
                $input['chapa_bank_code'] ?? null,
                $input['chapaBankCode'] ?? null,
                $nested['bank_code'] ?? null,
                $nested['bankCode'] ?? null,
                $nested['chapa_bank_code'] ?? null,
            ]),
            'chapa_account_number' => $this->firstNonNull([
                $input['chapa_account_number'] ?? null,
                $input['chapaAccountNumber'] ?? null,
                $nested['account_number'] ?? null,
                $nested['accountNumber'] ?? null,
                $nested['chapa_account_number'] ?? null,
            ]),
            'chapa_split_type' => $this->firstNonNull([
                $input['chapa_split_type'] ?? null,
                $input['chapaSplitType'] ?? null,
                $nested['split_type'] ?? null,
                $nested['splitType'] ?? null,
                $nested['chapa_split_type'] ?? null,
            ]),
            'chapa_split_value' => $this->firstNonNull([
                $input['chapa_split_value'] ?? null,
                $input['chapaSplitValue'] ?? null,
                $nested['split_value'] ?? null,
                $nested['splitValue'] ?? null,
                $nested['chapa_split_value'] ?? null,
            ]),
        ];
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    protected function firstNonNull(array $candidates): mixed
    {
        foreach ($candidates as $v) {
            if ($v !== null) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Detect "filled" in a way that matches JSON/React payloads (trim strings,
     * numeric bank codes, numeric split values; treat 0 bank code as empty).
     */
    protected function isChapaAdminFieldPresent(string $key, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if ($key === 'chapa_bank_code') {
            if (is_string($value)) {
                return trim($value) !== '';
            }
            if (is_int($value) || is_float($value)) {
                return (int) $value !== 0;
            }

            return true;
        }
        if ($key === 'chapa_split_value') {
            if (is_string($value)) {
                return trim($value) !== '';
            }
            if (is_int($value) || is_float($value)) {
                return true;
            }

            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_int($value) || is_float($value)) {
            return true;
        }

        return false;
    }

    protected function gateway(): ?ChapaGateway
    {
        $obj = get_payment_gateway_obj('chapa');

        return $obj instanceof ChapaGateway ? $obj : null;
    }
}
