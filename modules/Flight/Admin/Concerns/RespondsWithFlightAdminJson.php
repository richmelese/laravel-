<?php

namespace Modules\Flight\Admin\Concerns;

use Illuminate\Http\Request;

trait RespondsWithFlightAdminJson
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }
}
