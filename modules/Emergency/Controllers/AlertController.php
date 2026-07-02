<?php

namespace Modules\Emergency\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Emergency\Models\AlertRequest;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:10,1')->only('send');
    }

    /**
     * GET /api/alert/meta
     * Returns alert type options and statuses.
     */
    public function meta()
    {
        return response()->json([
            'alert_types' => AlertRequest::getAlertTypes(),
            'statuses'    => AlertRequest::getStatuses(),
        ]);
    }

    /**
     * GET /api/alert/check-booked
     * Check whether the authenticated user has any bookings in the system.
     * Returns { booked: true|false, count: N }
     */
    public function checkBooked(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => __('Unauthenticated')], 401);
        }

        $count = Booking::where('customer_id', $user->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->count();

        return response()->json([
            'booked' => $count > 0,
            'count'  => $count,
        ]);
    }

    /**
     * GET /api/alert/my-bookings
     * Return all services the authenticated user has booked.
     * Each item includes the service type (car, hotel, tour, etc.), title,
     * booking code, dates, and status.
     */
    public function myBookings(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => __('Unauthenticated')], 401);
        }

        $bookings = Booking::where('customer_id', $user->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('id')
            ->get([
                'id', 'code', 'object_model', 'object_id',
                'status', 'start_date', 'end_date', 'total', 'currency',
            ]);

        $bookableServices = get_bookable_services();

        $result = $bookings->map(function (Booking $booking) use ($bookableServices) {
            // object_model holds the service key (e.g. "hotel", "car"), not a class name
            $serviceType = $booking->object_model ?: 'unknown';

            // Resolve the real model class from the service key to load the title
            $serviceTitle = null;
            $serviceClass = $bookableServices[$booking->object_model] ?? null;
            if ($serviceClass && class_exists($serviceClass)) {
                $service = $serviceClass::find($booking->object_id);
                $serviceTitle = $service->title ?? ($service->name ?? null);
            }

            return [
                'booking_id'    => $booking->id,
                'booking_code'  => $booking->code,
                'service_type'  => $serviceType,
                'service_title' => $serviceTitle,
                'status'        => $booking->status,
                'start_date'    => $booking->start_date,
                'end_date'      => $booking->end_date,
                'total'         => $booking->total,
                'currency'      => $booking->currency,
            ];
        });

        return response()->json([
            'booked' => $result->isNotEmpty(),
            'count'  => $result->count(),
            'data'   => $result->values(),
        ]);
    }

    /**
     * POST /api/alert
     * Send an alert request.
     *
     * Accepts:
     *   - booked_service_ids[]  array of booking IDs to attach (optional, auth only)
     *   - description           alias for message
     *
     * Logged-in users  → name/email/phone auto-filled from account.
     * Guest with booking_code → name/email/phone pulled from the booking.
     * Pure guest       → must supply name, email, phone manually.
     */
    public function send(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        $request->validate([
            'alert_type'         => 'nullable|in:sos,medical,help,general',
            'message'            => 'nullable|string|max:2000',
            'description'        => 'nullable|string|max:2000',
            'location'           => 'nullable|string|max:255',
            'latitude'           => 'nullable|numeric|between:-90,90',
            'longitude'          => 'nullable|numeric|between:-180,180',
            'booking_code'       => 'nullable|string|max:50',
            'booked_service_ids' => 'nullable|array',
            'booked_service_ids.*' => 'integer',
            // Only required when not logged-in and no booking_code
            'name'               => 'nullable|string|max:255',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:50',
        ]);

        $alert = new AlertRequest();
        $alert->alert_type = $request->input('alert_type', 'general');
        // accept either "message" or "description"
        $alert->message    = $request->input('description') ?? $request->input('message');
        $alert->location   = $request->input('location');
        $alert->latitude   = $request->input('latitude');
        $alert->longitude  = $request->input('longitude');
        $alert->status     = 'new';

        // ── Attach booked services (auth required — verifies ownership) ──────
        if ($user && !empty($request->input('booked_service_ids'))) {
            $validIds = Booking::where('customer_id', $user->id)
                ->whereIn('id', $request->input('booked_service_ids'))
                ->pluck('id')
                ->toArray();

            if (!empty($validIds)) {
                $alert->booked_services = $this->buildBookedServicesSummary($validIds);
            }
        }

        // ── Logged-in user: pull profile info ────────────────────────────────
        if ($user) {
            $alert->user_id      = $user->id;
            $alert->name         = $user->name ?? ($user->first_name . ' ' . $user->last_name);
            $alert->email        = $user->email;
            $alert->phone        = $user->phone ?? $request->input('phone');
            $alert->booking_code = $request->input('booking_code');
        }

        // ── Guest with booking_code: pull info from booking ──────────────────
        elseif ($bookingCode = $request->input('booking_code')) {
            $booking = Booking::where('code', $bookingCode)->first();

            if ($booking) {
                $alert->booking_code = $bookingCode;
                $alert->name         = $booking->first_name . ' ' . $booking->last_name;
                $alert->email        = $booking->email;
                $alert->phone        = $booking->phone ?? $request->input('phone');
                $alert->user_id      = $booking->customer_id;
            } else {
                $alert->booking_code = $bookingCode;
                $alert->name         = $request->input('name');
                $alert->email        = $request->input('email');
                $alert->phone        = $request->input('phone');
            }
        }

        // ── Pure guest: must supply contact info manually ─────────────────────
        else {
            if (empty($request->input('name')) || empty($request->input('email'))) {
                return response()->json([
                    'message' => __('Please provide your name and email, or log in, or supply a booking_code.'),
                    'errors'  => [
                        'name'  => [__('Required when not logged in')],
                        'email' => [__('Required when not logged in')],
                    ],
                ], 422);
            }
            $alert->name  = $request->input('name');
            $alert->email = $request->input('email');
            $alert->phone = $request->input('phone');
        }

        $alert->save();

        return response()->json([
            'status'  => 1,
            'message' => __('Your alert has been received. Our team will respond shortly.'),
            'data'    => [
                'id'              => $alert->id,
                'alert_type'      => $alert->alert_type,
                'status'          => $alert->status,
                'name'            => $alert->name,
                'email'           => $alert->email,
                'booked_services' => $alert->booked_services ?? [],
            ],
        ], 201);
    }

    /**
     * GET /api/alert
     * List the current user's own alerts (auth required).
     */
    public function myAlerts(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => __('Unauthenticated')], 401);
        }

        $query = AlertRequest::where('user_id', $user->id)->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 10), 50);
        $rows    = $query->paginate($perPage);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    /**
     * Build a compact summary array from a list of verified booking IDs.
     * Stored in booked_services column so admin can see at a glance.
     */
    private function buildBookedServicesSummary(array $bookingIds): array
    {
        $bookings = Booking::whereIn('id', $bookingIds)
            ->get(['id', 'code', 'object_model', 'object_id', 'status', 'start_date', 'end_date']);

        $bookableServices = get_bookable_services();

        return $bookings->map(function (Booking $b) use ($bookableServices) {
            $serviceType  = $b->object_model ?: 'unknown';
            $serviceTitle = null;
            $serviceClass = $bookableServices[$b->object_model] ?? null;
            if ($serviceClass && class_exists($serviceClass)) {
                $svc = $serviceClass::find($b->object_id);
                $serviceTitle = $svc->title ?? ($svc->name ?? null);
            }

            return [
                'booking_id'    => $b->id,
                'booking_code'  => $b->code,
                'service_type'  => $serviceType,
                'service_title' => $serviceTitle,
                'status'        => $b->status,
                'start_date'    => $b->start_date,
                'end_date'      => $b->end_date,
            ];
        })->toArray();
    }
}
