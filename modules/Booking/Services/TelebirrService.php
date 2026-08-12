<?php

namespace Modules\Booking\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Booking\Utils\Tool;
use RuntimeException;

class TelebirrService
{
    public function authenticateMiniAppToken(string $accessToken): array
    {
        $request = [
            'timestamp' => Tool::createTimeStamp(),
            'nonce_str' => Tool::createNonceStr(),
            'method' => (string) config('telebirr.auth_method', 'payment.authtoken'),
            'version' => '1.0',
            'biz_content' => [
                'access_token' => $accessToken,
                'trade_type' => 'InApp',
                'appid' => $this->merchantAppId(),
                'resource_type' => 'OpenId',
            ],
        ];
        $request['sign'] = Tool::sign($request);
        $request['sign_type'] = 'SHA256WithRSA';

        $response = $this->authorizedClient()
            ->post($this->baseUrl().'/payment/v1/auth/authToken', $request);

        return $this->successfulBody($response, 'authenticate a Mini App user');
    }

    public function applyFabricToken(): string
    {
        $this->assertConfigured();

        $response = $this->client()
            ->withHeaders(['X-APP-Key' => $this->fabricAppId()])
            ->post($this->baseUrl().'/payment/v1/token', [
                'appSecret' => (string) config('telebirr.app_secret'),
            ]);

        $body = $this->successfulBody($response, 'apply for a fabric token');
        $token = (string) ($body['token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('Telebirr did not return a fabric token.');
        }

        return $token;
    }

    public function createOrder(
        string $merchantOrderId,
        string $title,
        string $amount,
        string $notifyUrl,
        string $redirectUrl
    ): array {
        $request = [
            'timestamp' => Tool::createTimeStamp(),
            'nonce_str' => Tool::createNonceStr(),
            'method' => 'payment.preorder',
            'version' => '1.0',
            'biz_content' => [
                'notify_url' => $notifyUrl,
                'redirect_url' => $redirectUrl,
                'appid' => $this->merchantAppId(),
                'merch_code' => $this->merchantCode(),
                'merch_order_id' => $merchantOrderId,
                'trade_type' => 'Checkout',
                'title' => $title,
                'total_amount' => $amount,
                'trans_currency' => (string) config('telebirr.currency', 'ETB'),
                'timeout_express' => (string) config('telebirr.timeout_express', '120m'),
                'business_type' => 'BuyGoods',
                'payee_identifier' => $this->merchantCode(),
                'payee_identifier_type' => '04',
                'payee_type' => (string) config('telebirr.payee_type', '5000'),
                'callback_info' => 'Booking '.$merchantOrderId,
            ],
        ];
        $request['sign'] = Tool::sign($request);
        $request['sign_type'] = 'SHA256WithRSA';

        $response = $this->authorizedClient()
            ->post($this->baseUrl().'/payment/v1/merchant/preOrder', $request);

        return $this->successfulBody($response, 'create an order');
    }

    public function queryOrder(string $merchantOrderId): array
    {
        $request = [
            'timestamp' => Tool::createTimeStamp(),
            'nonce_str' => Tool::createNonceStr(),
            'method' => 'payment.queryorder',
            'version' => '1.0',
            'biz_content' => [
                'appid' => $this->merchantAppId(),
                'merch_code' => $this->merchantCode(),
                'merch_order_id' => $merchantOrderId,
            ],
        ];
        $request['sign'] = Tool::sign($request);
        $request['sign_type'] = 'SHA256WithRSA';

        $response = $this->authorizedClient()
            ->post($this->baseUrl().'/payment/v1/merchant/queryOrder', $request);

        return $this->successfulBody($response, 'query an order');
    }

    public function checkoutUrl(string $prepayId): string
    {
        $request = [
            'appid' => $this->merchantAppId(),
            'merch_code' => $this->merchantCode(),
            'nonce_str' => Tool::createNonceStr(),
            'prepay_id' => $prepayId,
            'timestamp' => Tool::createTimeStamp(),
        ];

        $query = $request + [
            'sign' => Tool::sign($request),
            'sign_type' => 'SHA256WithRSA',
            'version' => '1.0',
            'trade_type' => 'Checkout',
        ];

        return rtrim($this->webUrl(), '?').'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function authorizedClient()
    {
        return $this->client()->withHeaders([
            'X-APP-Key' => $this->fabricAppId(),
            'Authorization' => $this->applyFabricToken(),
        ]);
    }

    private function client()
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(max(5, (int) config('telebirr.timeout', 30)))
            ->withOptions(['verify' => $this->sslVerifyOption()]);
    }

    private function sslVerifyOption()
    {
        if (! (bool) config('telebirr.verify_ssl', true)) {
            return false;
        }

        $caBundle = trim((string) config('telebirr.ca_bundle'));

        return $caBundle !== '' && is_file($caBundle) ? $caBundle : true;
    }

    private function successfulBody(Response $response, string $operation): array
    {
        $body = (array) $response->json();
        $result = strtoupper((string) ($body['result'] ?? ''));
        $code = (string) ($body['code'] ?? $body['errorCode'] ?? '');

        if (! $response->successful() || ($result !== '' && $result !== 'SUCCESS') || ($code !== '' && $code !== '0')) {
            $message = (string) ($body['msg'] ?? $body['errorMsg'] ?? $response->body());
            throw new RuntimeException("Unable to {$operation} with Telebirr: ".($message ?: 'Unknown error'));
        }

        return $body;
    }

    private function assertConfigured(): void
    {
        $required = [
            'fabric app ID' => $this->fabricAppId(),
            'app secret' => (string) config('telebirr.app_secret'),
            'merchant app ID' => $this->merchantAppId(),
            'merchant code' => $this->merchantCode(),
        ];

        foreach ($required as $name => $value) {
            if (trim($value) === '') {
                throw new RuntimeException("Telebirr {$name} is not configured.");
            }
        }
    }

    private function baseUrl(): string
    {
        $key = config('telebirr.sandbox', true) ? 'telebirr.sandbox_base_url' : 'telebirr.live_base_url';

        return rtrim((string) config($key), '/');
    }

    private function webUrl(): string
    {
        $key = config('telebirr.sandbox', true) ? 'telebirr.sandbox_web_url' : 'telebirr.live_web_url';

        return rtrim((string) config($key), '/');
    }

    private function fabricAppId(): string
    {
        return (string) config('telebirr.fabric_app_id');
    }

    private function merchantAppId(): string
    {
        return (string) config('telebirr.merchant_app_id');
    }

    private function merchantCode(): string
    {
        return (string) config('telebirr.merchant_code');
    }
}
