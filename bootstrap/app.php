<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $csrfExcept = [
            '*/gateway_callback/*',
            '*/callback/*',
            '*/order/confirm/*',
            // Login modal/API-like submissions can come without a stable first-party session cookie.
            'login',
            '*/login',
            'hotel/checkAvailability',
            // SPA JSON POSTs to web booking URLs (CORS + no session cookie); prefer POST /api/booking/* when possible.
            'booking/addEnquiry',
            'booking/addToCart',
            'booking/doCheckout',
            // SPA newsletter subscribe from another origin.
            'newsletter/subscribe',
            // Public contact JSON POST without first-party session (SPA / alternate origin).
            // Rate-limited on the controller.
            'contact/store',
            '*/contact/store',
            // Public vendor registration from SPA/Postman without first-party CSRF cookie.
            'vendor/register',
            '*/vendor/register',
        ];

        // Optional dev-only bypass for cross-origin Livewire requests.
        // Enable with LIVEWIRE_CSRF_EXEMPT=true in .env when needed.
        if (filter_var(env('LIVEWIRE_CSRF_EXEMPT', false), FILTER_VALIDATE_BOOLEAN)) {
            $csrfExcept[] = 'livewire/update';
        }

        $middleware->validateCsrfTokens(except: $csrfExcept);

        // Redirect to installer if not installed
        $middleware->append(\App\Http\Middleware\RedirectToInstaller::class);

        // CORS for web routes (e.g. /user/hotel) when requested cross-origin from a local SPA.
        $middleware->web(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Explicit Referrer-Policy (avoids "missing referrer policy" scanner noise; matches common browser defaults).
        $middleware->web(append: [
            \App\Http\Middleware\SetReferrerPolicy::class,
        ]);

        $middleware->web([
            \App\Http\Middleware\RedirectForMultiLanguage::class,
            \App\Http\Middleware\SetLanguageForAdmin::class,
            \App\Http\Middleware\SetCurrentCurrency::class,
            \App\Http\Middleware\RequireChangePassword::class,
        ]);
        $middleware->api([
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\MayAuthenticateWithSanctum::class,
        ], [
            \App\Http\Middleware\SetLanguageForApi::class,
            \App\Http\Middleware\SetReferrerPolicy::class,
            \App\Http\Middleware\RequireChangePassword::class,
        ]);

        $middleware->alias([
            "dashboard" => \App\Http\Middleware\Dashboard::class,
            "translation_manager" => \App\Http\Middleware\TranslationManager::class,
            "system_log_view" => \App\Http\Middleware\CheckForLogPermission::class,
            "set_language_for_api" => \App\Http\Middleware\SetLanguageForApi::class,
            "pro_plan" => \App\Pro\Middlewares\ProPlan::class,
        ]);

        // Sanctum Middleware
        $middleware->statefulApi();
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api-admin/*') || $request->expectsJson() || $request->bearerToken()) {
                return response()->json([
                    'message' => 'You have to login',
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            $wantsHttpJson = $request->expectsJson()
                || $request->ajax()
                || Str::contains((string) $request->header('Accept'), 'application/json');

            if (! $request->is('api-admin/*') && ! $request->is('api/*') && ! $wantsHttpJson) {
                return null;
            }

            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if (
                ! $request->expectsJson()
                && ! $request->ajax()
                && ! Str::contains((string) $request->header('Accept'), 'application/json')
            ) {
                return null;
            }

            return response()->json([
                'message' => __('Page expired or invalid CSRF token. Refresh the page and try again.'),
                'errors' => [
                    '_token' => [
                        __('Session token mismatch. Reload the page, then submit again.'),
                    ],
                ],
            ], 419);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api-admin/*') && ! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            $message = $exception->getMessage();
            if ($message === '') {
                $message = match ($status) {
                    400 => 'Bad Request',
                    401 => 'Unauthorized',
                    403 => 'Forbidden',
                    404 => 'Not Found',
                    405 => 'Method Not Allowed',
                    419 => 'Page Expired',
                    429 => 'Too Many Requests',
                    500 => 'Server Error',
                    503 => 'Service Unavailable',
                    default => 'Error',
                };
            }

            return response()->json([
                'message' => $message,
            ], $status);
        });
    })->create();
