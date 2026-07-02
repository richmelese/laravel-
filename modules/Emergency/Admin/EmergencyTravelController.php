<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyTravelSupport;

class EmergencyTravelController extends EmergencyBaseController
{
    protected function modelClass(): string    { return EmergencyTravelSupport::class; }
    protected function singularName(): string  { return 'Travel Support Entry'; }
    protected function searchFields(): array   { return ['scenario']; }
    protected function fillable(): array       { return ['scenario', 'steps', 'status', 'sort_order']; }

    protected function validationRules(): array
    {
        return [
            'scenario'   => 'required|string|max:255',
            'steps'      => 'nullable|array',
            'steps.*'    => 'string|max:500',
            'status'     => 'required|in:publish,draft',
            'sort_order' => 'nullable|integer',
        ];
    }
}
