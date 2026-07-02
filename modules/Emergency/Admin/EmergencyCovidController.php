<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyCovidHealth;

class EmergencyCovidController extends EmergencyBaseController
{
    protected function modelClass(): string    { return EmergencyCovidHealth::class; }
    protected function singularName(): string  { return 'COVID & Health Entry'; }
    protected function searchFields(): array   { return ['title']; }
    protected function fillable(): array       { return ['title', 'points', 'status', 'sort_order']; }

    protected function validationRules(): array
    {
        return [
            'title'      => 'required|string|max:255',
            'points'     => 'nullable|array',
            'points.*'   => 'string|max:500',
            'status'     => 'required|in:publish,draft',
            'sort_order' => 'nullable|integer',
        ];
    }
}
