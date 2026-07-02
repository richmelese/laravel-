<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;

/**
 * Vendor "booking report" JSON API for SPA clients using Sanctum Bearer tokens.
 * Mirrors Modules\Vendor\Controllers\VendorController::bookingReport scope (vendor_id = current user), but returns JSON.
 */
class VendorReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function bookingReport(Request $request)
    {
        $user = Auth::user();

        $rows = Booking::getBookingHistory(
            $request->input('status'),
            $request->input('customer_name'),
            $user->id,
            false,
            $request->input('from'),
            $request->input('to')
        );

        return $this->sendSuccess([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
            'statuses' => config('booking.statuses'),
        ]);
    }
}
