<?php

namespace Modules\Announcement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Announcement\Models\Announcement;

class AnnouncementController extends Controller
{
    /**
     * GET /api/announcements
     * Returns active announcements for the homepage.
     * Optional ?type=maintenance|promotion|etc to filter by type.
     */
    public function index(Request $request)
    {
        // Default: return all published announcements.
        // Use ?active=1 to return only "currently active" (within date range).
        $activeOnly = $request->boolean('active', false);

        $announcements = $activeOnly
            ? Announcement::getActive()
            : Announcement::query()
                ->where('status', 'publish')
                ->orderByDesc('id')
                ->get();

        if ($type = $request->query('type')) {
            $announcements = $announcements->filter(fn($a) => $a->type === $type)->values();
        }

        $typeLabels = Announcement::getTypes();
        $data = $announcements->map(fn($a) => [
            'id'         => $a->id,
            'title'      => $a->title,
            'content'    => $a->content,
            'type'       => $a->type,
            'type_label' => $typeLabels[$a->type] ?? $a->type,
            'start_date' => $a->start_date,
            'end_date'   => $a->end_date,
            'url'        => $a->url,
            'button_label' => $a->button_label,
        ])->values();

        return response()->json([
            'data'  => $data,
            'total' => $data->count(),
        ]);
    }
}
