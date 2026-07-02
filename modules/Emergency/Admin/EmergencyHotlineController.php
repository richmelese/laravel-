<?php

namespace Modules\Emergency\Admin;

use Modules\Emergency\Models\EmergencyHotline;

class EmergencyHotlineController extends EmergencyBaseController
{
    protected function modelClass(): string   { return EmergencyHotline::class; }
    protected function singularName(): string { return 'Emergency Hotline'; }
    protected function searchFields(): array  { return ['title', 'number', 'description']; }
    protected function fillable(): array
    {
        return ['title', 'number', 'email', 'flag', 'description', 'status', 'sort_order'];
    }

    protected function validationRules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'number'      => 'nullable|string|max:100',
            'email'       => 'nullable|email|max:255',
            'flag'        => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:publish,draft',
            'sort_order'  => 'nullable|integer',
        ];
    }
}
