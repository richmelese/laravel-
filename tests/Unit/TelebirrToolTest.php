<?php

namespace Tests\Unit;

use Modules\Booking\Utils\Tool;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;

class TelebirrToolTest extends TestCase
{
    public function test_it_builds_the_official_flat_sorted_signing_string(): void
    {
        $request = [
            'timestamp' => '1755866911',
            'nonce_str' => 'NONCE',
            'method' => 'payment.preorder',
            'version' => '1.0',
            'biz_content' => [
                'title' => 'Booking',
                'appid' => '123',
                'merch_code' => '456',
            ],
            'sign' => 'excluded',
            'sign_type' => 'SHA256WithRSA',
        ];

        self::assertSame(
            'appid=123&merch_code=456&method=payment.preorder&nonce_str=NONCE&timestamp=1755866911&title=Booking&version=1.0',
            Tool::canonicalString($request)
        );
    }

    public function test_it_creates_a_verifiable_sha256_rsa_pss_signature(): void
    {
        $privateKey = RSA::loadPrivateKey(<<<'PEM'
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMTExL4SSFJeawYx
c3t7pfIANEgHJF8yNHj8xPqjyenA0AdindqdfHx7a+JXG1cvy7ZqM5vDUkwEyWGT
lchDih0Jkr/3P4Ldimhf/qKhnB14e9b2PqZU68U1Sxp4lCdxIO/8sLPx43LQVhrY
/kgfvHB9Pa5UmhbZv/P8ppmkyX6BAgMBAAECgYA91N9GIxSa3ZSgA5YYbYh9/VZw
c94YE/ytMDDt2d4vGCnGyFR2SBrAO0BxhZHP2fMXxVOmVMBdpvtpMClXHvIdFKvu
BpIJHFD4LGg1XQq0TySEHCOwvJ6rJO3DMViJpGp7AzORLDhZDk3Tp0858ooMQZct
6TsJVwFLd9EdwwsYSQJBAOg6SIMUMV/ooSigS+1yii1k0t+eAoCsIsJTs40yBBk2
7jS8mHfZIScQWKklS7SfUIckC6QEDhalho+KWFqDtmcCQQDY6UCA32t3fXlh4Iqy
3mbkc2+U86q2P30DM8O2zez77LL5RZL9N1PXR8XaMDwRKh4Z6GpsQSypac3/748h
PYLXAkBAhdkZ2mVxkXAdmpQeEEIGJMpWaU+msq0hsyHjLC9pVhLPQktWmVSVxvvr
WzpyoAU+1ywI0Tuc3TbK8RRlac0nAkEAoYn8fr1kxGVOi4T05kbZK9OISs642Ocp
S8Q2QiLUFb3uf9O/pxKYPuB1yYtYgJP0POkosJxNDZH9V1hqKKAtmwJAPc2qPCw0
iQ3hbYYsQwn/T13bQ7/BWoC7z9Q13bRrT55RB6zEUERg5qj4+eVgGoqlD48tSpah
vhqKgzclGRJtOw==
-----END PRIVATE KEY-----
PEM);
        $path = tempnam(sys_get_temp_dir(), 'telebirr-key-');
        file_put_contents($path, (string) $privateKey);

        try {
            $request = ['appid' => '123', 'timestamp' => '1755866911'];
            $signature = base64_decode(Tool::sign($request, $path), true);

            $publicKey = $privateKey->getPublicKey()
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->withSaltLength(32)
                ->withPadding(RSA::SIGNATURE_PSS);

            self::assertTrue($publicKey->verify(Tool::canonicalString($request), $signature));
        } finally {
            @unlink($path);
        }
    }
}
