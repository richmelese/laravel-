<?php

return [
    'enabled' => env('TELEBIRR_ENABLED', false),
    'miniapp_login_enabled' => env(
        'TELEBIRR_MINIAPP_LOGIN_ENABLED',
        env('TELEBIRR_ENABLED', false)
    ),
    'sandbox' => env('TELEBIRR_SANDBOX', true),

    'sandbox_base_url' => env(
        'TELEBIRR_SANDBOX_BASE_URL',
        'https://196.188.120.3:38443/apiaccess/payment/gateway'
    ),
    'live_base_url' => env(
        'TELEBIRR_LIVE_BASE_URL',
        'https://superapp.ethiomobilemoney.et:38443/apiaccess/payment/gateway'
    ),
    'sandbox_web_url' => env(
        'TELEBIRR_SANDBOX_WEB_URL',
        'https://196.188.120.3:38443/payment/web/paygate'
    ),
    'live_web_url' => env(
        'TELEBIRR_LIVE_WEB_URL',
        'https://superapp.ethiomobilemoney.et:38443/apiaccess/payment/gateway'
    ),

    'fabric_app_id' => env('TELEBIRR_FABRIC_APP_ID', config('env.fabricAppId')),
    'app_secret' => env('TELEBIRR_APP_SECRET', config('env.appSecret')),
    'merchant_app_id' => env('TELEBIRR_MERCHANT_APP_ID', config('env.merchantAppId')),
    'merchant_code' => env('TELEBIRR_MERCHANT_CODE', config('env.merchantCode')),

    'private_key_path' => env('TELEBIRR_PRIVATE_KEY_PATH', config_path('private_key.pem')),
    'public_key_path' => env('TELEBIRR_PUBLIC_KEY_PATH', config_path('public_key.pem')),
    'currency' => 'ETB',
    'timeout_express' => env('TELEBIRR_TIMEOUT_EXPRESS', '120m'),
    'payee_type' => env('TELEBIRR_PAYEE_TYPE', '5000'),
    'auth_method' => env('TELEBIRR_AUTH_METHOD', 'payment.authtoken'),
    'timeout' => (int) env('TELEBIRR_HTTP_TIMEOUT', 30),
    'verify_ssl' => env('TELEBIRR_VERIFY_SSL', true),
    'ca_bundle' => env('TELEBIRR_CA_BUNDLE') ?: app_path('certs/cacert.pem'),
];
