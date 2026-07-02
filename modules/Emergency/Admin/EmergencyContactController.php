<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyContact;

class EmergencyContactController extends EmergencyBaseController
{
    protected function modelClass(): string    { return EmergencyContact::class; }
    protected function singularName(): string  { return 'Quick Contact'; }
    protected function searchFields(): array   { return ['label', 'value']; }
    protected function fillable(): array       { return ['label', 'value', 'href', 'status', 'sort_order']; }

    protected function validationRules(): array
    {
        return [
            'label'      => 'required|string|max:255',
            'value'      => 'nullable|string|max:255',
            'href'       => 'nullable|string|max:500',
            'status'     => 'required|in:publish,draft',
            'sort_order' => 'nullable|integer',
        ];
    }
}
