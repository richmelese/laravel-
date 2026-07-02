<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyNumber;

class EmergencyNumberController extends EmergencyBaseController
{
    protected function modelClass(): string    { return EmergencyNumber::class; }
    protected function singularName(): string  { return 'Emergency Number'; }
    protected function searchFields(): array   { return ['label', 'number']; }
    protected function fillable(): array
    {
        return ['label', 'number', 'href', 'color', 'icon_name', 'status', 'sort_order'];
    }

    protected function validationRules(): array
    {
        return [
            'label'      => 'required|string|max:255',
            'number'     => 'required|string|max:100',
            'href'       => 'nullable|string|max:500',
            'color'      => 'nullable|string|max:100',
            'icon_name'  => 'nullable|string|max:100',
            'status'     => 'required|in:publish,draft',
            'sort_order' => 'nullable|integer',
        ];
    }
}
