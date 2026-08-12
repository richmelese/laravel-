<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelebirrMiniAppLoginTest extends TestCase
{
    use DatabaseTransactions;

    private string $privateKeyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'telebirr-feature-key-');
        file_put_contents($this->privateKeyPath, <<<'PEM'
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

        config([
            'telebirr.miniapp_login_enabled' => true,
            'telebirr.sandbox' => true,
            'telebirr.sandbox_base_url' => 'https://telebirr.test/gateway',
            'telebirr.fabric_app_id' => 'fabric-app-id',
            'telebirr.app_secret' => 'app-secret',
            'telebirr.merchant_app_id' => 'merchant-app-id',
            'telebirr.merchant_code' => 'merchant-code',
            'telebirr.private_key_path' => $this->privateKeyPath,
            'telebirr.auth_method' => 'payment.authtoken',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->privateKeyPath) && is_file($this->privateKeyPath)) {
            unlink($this->privateKeyPath);
        }

        parent::tearDown();
    }

    public function test_verified_telebirr_identity_creates_a_customer_and_sanctum_session(): void
    {
        $this->fakeTelebirrIdentity('0911000000');

        $login = $this->postJson('/api/auth/telebirr-miniapp', [
            'access_token' => 'mini-app-access-token',
            'device_name' => 'feature-test-device',
        ]);

        $login->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('telebirr.open_id', 'feature-test-open-id')
            ->assertJsonPath('telebirr.phone', '+251911000000')
            ->assertJsonPath('telebirr.identity_type', 'CUSTOMER');

        $this->assertDatabaseHas('telebirr_accounts', [
            'open_id' => 'feature-test-open-id',
            'identifier' => '+251911000000',
            'identity_type' => 'CUSTOMER',
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+251911000000',
            'status' => 'publish',
        ]);

        $this->withToken($login->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_login_is_rejected_when_telebirr_does_not_return_a_valid_phone(): void
    {
        $this->fakeTelebirrIdentity('not-a-phone-number');

        $this->postJson('/api/auth/telebirr-miniapp', [
            'access_token' => 'mini-app-access-token',
        ])
            ->assertStatus(502)
            ->assertJsonPath('code', 'telebirr_phone_missing');

        $this->assertDatabaseMissing('telebirr_accounts', [
            'open_id' => 'feature-test-open-id',
        ]);
    }

    private function fakeTelebirrIdentity(string $identifier): void
    {
        Http::fake([
            'https://telebirr.test/gateway/payment/v1/token' => Http::response([
                'result' => 'SUCCESS',
                'code' => '0',
                'token' => 'fabric-token',
            ]),
            'https://telebirr.test/gateway/payment/v1/auth/authToken' => Http::response([
                'result' => 'SUCCESS',
                'code' => '0',
                'biz_content' => [
                    'open_id' => 'feature-test-open-id',
                    'identityId' => 'identity-id',
                    'identityType' => 'CUSTOMER',
                    'walletIdentityId' => 'wallet-id',
                    'identifier' => $identifier,
                    'nickName' => 'Mini App Customer',
                    'status' => 'ACTIVE',
                ],
            ]),
        ]);
    }
}
