<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
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
        ];

        // Optional dev-only bypass for cross-origin Livewire requests.
        // Enable with LIVEWIRE_CSRF_EXEMPT=true in .env when needed.
        if (filter_var(env('LIVEWIRE_CSRF_EXEMPT', false), FILTER_VALIDATE_BOOLEAN)) {
            $csrfExcept[] = 'livewire/update';
        }

        $middleware->validateCsrfTokens(except: $csrfExcept);

        // Redirect to installer if not installed
        $middleware->append(\App\Http\Middleware\RedirectToInstaller::class);

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
            if ($request->is('api-admin/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'You have to login',
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api-admin/*') || $request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            return null;
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
