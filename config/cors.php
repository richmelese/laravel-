<?php

    return [

        /*
        |--------------------------------------------------------------------------
        | Cross-Origin Resource Sharing (CORS) Configuration
        |--------------------------------------------------------------------------
        |
        | Here you may configure your settings for cross-origin resource sharing
        | or "CORS". This determines what cross-origin operations may execute
        | in web browsers. You are free to adjust these settings as needed.
        |
        | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        |
        */

        'paths' => [
            'api/*',
            'api-admin/*',
            'admin/*',
            'sanctum/csrf-cookie',
            'livewire/*',
        ],

        'allowed_methods' => ['*'],

        'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            ''
        ))))),

        // Allow common local dev frontends on any port (Vite/React/Next/etc).
        'allowed_origins_patterns' => [
            '/^https?:\/\/localhost(:\d+)?$/',
            '/^https?:\/\/127\.0\.0\.1(:\d+)?$/',
        ],

        'allowed_headers' => ['*'],

        'exposed_headers' => [],

        'max_age' => 0,

        'supports_credentials' => true,

    ];