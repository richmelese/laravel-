<?php

namespace Modules\Booking\Utils;

use InvalidArgumentException;
use phpseclib3\Crypt\RSA;
use RuntimeException;

class Tool
{
    private const EXCLUDED_FIELDS = [
        'sign',
        'sign_type',
        'header',
        'refund_info',
        'openType',
        'raw_request',
        'wallet_reference_data',
    ];

    public static function createTimeStamp(): string
    {
        return (string) time();
    }

    public static function createNonceStr(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Flatten top-level and biz_content fields, then sort by ASCII key.
     */
    public static function canonicalString(array $request): string
    {
        $fields = [];

        foreach ($request as $key => $value) {
            if ($key === 'biz_content' || in_array($key, self::EXCLUDED_FIELDS, true)) {
                continue;
            }

            if (is_scalar($value) && $value !== '') {
                $fields[(string) $key] = self::scalarToString($value);
            }
        }

        foreach ((array) ($request['biz_content'] ?? []) as $key => $value) {
            if (in_array($key, self::EXCLUDED_FIELDS, true)) {
                continue;
            }

            if (is_scalar($value) && $value !== '') {
                $fields[(string) $key] = self::scalarToString($value);
            }
        }

        ksort($fields, SORT_STRING);

        return implode('&', array_map(
            static fn (string $key, string $value): string => $key.'='.$value,
            array_keys($fields),
            array_values($fields)
        ));
    }

    public static function sign(array $request, ?string $privateKeyPath = null): string
    {
        $path = $privateKeyPath ?: (string) config('telebirr.private_key_path');
        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException('Telebirr private key is missing or unreadable: '.$path);
        }

        $keyContents = file_get_contents($path);
        if ($keyContents === false || trim($keyContents) === '') {
            throw new RuntimeException('Telebirr private key is empty.');
        }

        try {
            $privateKey = RSA::loadPrivateKey($keyContents)
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->withSaltLength(32)
                ->withPadding(RSA::SIGNATURE_PSS);

            return base64_encode($privateKey->sign(self::canonicalString($request)));
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Unable to sign the Telebirr request: '.$exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private static function scalarToString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException('Telebirr signing values must be scalar.');
        }

        return (string) $value;
    }
}
