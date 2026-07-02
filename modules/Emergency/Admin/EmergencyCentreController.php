<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyCentre;

class EmergencyCentreController extends EmergencyBaseController
{
    protected function modelClass(): string    { return EmergencyCentre::class; }
    protected function singularName(): string  { return 'Medical Centre'; }
    protected function searchFields(): array   { return ['name', 'alias', 'address', 'phone']; }
    protected function fillable(): array
    {
        return ['name', 'alias', 'type', 'address', 'phone', 'hours', 'services', 'note', 'dot_color', 'href', 'status', 'sort_order'];
    }

    protected function validationRules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'alias'      => 'nullable|string|max:255',
            'type'       => 'nullable|string|max:100',
            'address'    => 'nullable|string|max:500',
            'phone'      => 'nullable|string|max:100',
            'hours'      => 'nullable|string|max:255',
            'services'   => 'nullable|array',
            'services.*' => 'string|max:255',
            'note'       => 'nullable|string',
            'dot_color'     => 'nullable|string|max:100',
            'href' => 'nullable|string|max:500',
            'status'        => 'required|in:publish,draft',
            'sort_order' => 'nullable|integer',
        ];
    }
}
