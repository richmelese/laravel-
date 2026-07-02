<?php

namespace Modules\Hospital\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Hospital\Models\Hospital;

class HospitalController extends Controller
{
    /**
     * GET /api/hospitals
     * List all published hospitals (public).
     */
    public function index(Request $request)
    {
        $query = Hospital::where('status', 'publish')->orderBy('name');

        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')
                  ->orWhere('city', 'LIKE', '%' . $s . '%')
                  ->orWhere('country', 'LIKE', '%' . $s . '%');
            });
        }
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }
        if ($country = $request->query('country')) {
            $query->where('country', $country);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'data' => collect($rows->items())->map(fn($h) => $this->publicFormat($h)),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/hospitals/{id}
     * Single published hospital detail (public).
     */
    public function show(Request $request, $id)
    {
        $hospital = Hospital::where('status', 'publish')->find($id);
        if (!$hospital) {
            return response()->json(['message' => __('Hospital not found')], 404);
        }

        return response()->json(['data' => $this->publicFormat($hospital)]);
    }

    private function publicFormat(Hospital $h): array
    {
        return [
            'id'             => $h->id,
            'name'           => $h->name,
            'description'    => $h->description,
            'address'        => $h->address,
            'city'           => $h->city,
            'country'        => $h->country,
            'phone'          => $h->phone,
            'email'          => $h->email,
            'website'        => $h->website,
            'latitude'       => $h->latitude,
            'longitude'      => $h->longitude,
            'booking_amount' => $h->booking_amount,
            'currency'       => $h->currency ?? 'ETB',
            'image'          => $h->image_id ? get_file_url($h->image_id, 'full') : null,
        ];
    }
}
