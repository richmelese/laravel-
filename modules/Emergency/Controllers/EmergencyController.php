<?php

namespace Modules\Emergency\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Emergency\Models\EmergencyCentre;
use Modules\Emergency\Models\EmergencyCovidHealth;
use Modules\Emergency\Models\EmergencyContact;
use Modules\Emergency\Models\EmergencyHotline;
use Modules\Emergency\Models\EmergencyNumber;
use Modules\Emergency\Models\EmergencyTravelSupport;

class EmergencyController extends Controller
{
    /**
     * GET /api/emergency
     * Returns all published emergency data grouped by type in a single response.
     */
    public function index(Request $request)
    {
        return response()->json([
            'hotlines' => EmergencyHotline::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
            'numbers'  => EmergencyNumber::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
            'centres'  => EmergencyCentre::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
            'covid'    => EmergencyCovidHealth::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
            'travel'   => EmergencyTravelSupport::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
            'contacts' => EmergencyContact::where('status', 'publish')->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * GET /api/emergency/{type}
     * Returns published entries for a single type.
     */
    public function byType(Request $request, string $type)
    {
        $map = [
            'hotline'  => EmergencyHotline::class,
            'number'   => EmergencyNumber::class,
            'medical'  => EmergencyCentre::class,
            'health'   => EmergencyCovidHealth::class,
            'travel'   => EmergencyTravelSupport::class,
            'contacts' => EmergencyContact::class,
        ];

        if (!isset($map[$type])) {
            return response()->json(['message' => 'Unknown emergency type'], 404);
        }

        $class = $map[$type];
        $data  = $class::where('status', 'publish')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $data, 'total' => $data->count()]);
    }
}
