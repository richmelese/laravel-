<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Modules\Booking\Services\TelebirrService;
use PHPUnit\Framework\TestCase;

class TelebirrMiniAppServiceTest extends TestCase
{
    private Container $app;
    private string $privateKeyPath;
    private ?Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'telebirr-test-key-');
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

        $this->app = new Container();
        $this->app->instance('config', new Repository([
            'telebirr' => [
                'sandbox' => true,
                'sandbox_base_url' => 'https://telebirr.test/gateway',
                'fabric_app_id' => 'fabric-app-id',
                'app_secret' => 'app-secret',
                'merchant_app_id' => 'merchant-app-id',
                'merchant_code' => 'merchant-code',
                'private_key_path' => $this->privateKeyPath,
                'auth_method' => 'payment.authtoken',
                'timeout' => 30,
                'verify_ssl' => true,
            ],
        ]));
        $this->app->instance(Factory::class, new Factory());

        Container::setInstance($this->app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousContainer);
        Container::setInstance($this->previousContainer);

        if (isset($this->privateKeyPath) && is_file($this->privateKeyPath)) {
            unlink($this->privateKeyPath);
        }

        parent::tearDown();
    }

    public function test_it_exchanges_a_mini_app_token_for_a_telebirr_identity(): void
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
                    'open_id' => 'customer-open-id',
                    'identityType' => 'CUSTOMER',
                ],
            ]),
        ]);

        $response = (new TelebirrService())
            ->authenticateMiniAppToken('mini-app-access-token');

        self::assertSame(
            'customer-open-id',
            data_get($response, 'biz_content.open_id')
        );

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/payment/v1/auth/authToken')) {
                return false;
            }

            $payload = $request->data();

            return $request->hasHeader('X-APP-Key', 'fabric-app-id')
                && $request->hasHeader('Authorization', 'fabric-token')
                && data_get($payload, 'method') === 'payment.authtoken'
                && data_get($payload, 'biz_content.access_token') === 'mini-app-access-token'
                && data_get($payload, 'biz_content.trade_type') === 'InApp'
                && data_get($payload, 'biz_content.appid') === 'merchant-app-id'
                && data_get($payload, 'biz_content.resource_type') === 'OpenId'
                && ! empty($payload['sign'])
                && data_get($payload, 'sign_type') === 'SHA256WithRSA';
        });
    }
}
