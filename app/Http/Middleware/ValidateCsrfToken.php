<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
    /**
     * Allow Sanctum personal access tokens on web routes (no CSRF cookie).
     * Other exceptions still come from bootstrap/app.php validateCsrfTokens().
     */
    public function shouldPassThrough($request): bool
    {
        if ($request->bearerToken()) {
            return true;
        }

        return parent::shouldPassThrough($request);
    }
}
