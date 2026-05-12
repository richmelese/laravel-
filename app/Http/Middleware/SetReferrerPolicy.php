<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetReferrerPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $response->headers->has('Referrer-Policy')) {
            $policy = env('HTTP_REFERRER_POLICY', 'strict-origin-when-cross-origin');
            if (is_string($policy) && $policy !== '') {
                $response->headers->set('Referrer-Policy', $policy);
            }
        }

        return $response;
    }
}
