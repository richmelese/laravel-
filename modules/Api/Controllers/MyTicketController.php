<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingPassenger;
use Illuminate\Support\Facades\Auth;
use Modules\Api\Resources\TicketResource;
use \BC\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;

class MyTicketController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $passenger = app(BookingPassenger::class);
        $booking = app(Booking::class);

        $hasTicketStatus = [
            Booking::COMPLETED,
            Booking::PAID
        ];
        
        $query = $passenger->query()
            ->select($passenger->getTable() . ".*")
            ->join($booking->getTable(), $booking->qualifyColumn("id"), $passenger->qualifyColumn("booking_id"))
            ->where($booking->qualifyColumn("customer_id"),Auth::id())
            ->whereIn($booking->qualifyColumn("status"), $hasTicketStatus)
            ->orderBy($passenger->qualifyColumn("id"), 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => TicketResource::collection($query->getCollection(),['qr_code_url']),
            'total'   => $query->total(),
            'max_pages' => $query->lastPage()
        ]);
    }

    public function qrImage($ticket_id)
    {
        $passenger = app(BookingPassenger::class);
        $booking = app(Booking::class);
        
        $query = $passenger->query()
            ->select($passenger->getTable() . ".*")
            ->join($booking->getTable(), $booking->qualifyColumn("id"), $passenger->qualifyColumn("booking_id"))
            ->where($booking->qualifyColumn("customer_id"),Auth::id())
            ->where($passenger->qualifyColumn("id"), $ticket_id)
            ->first();

        if (empty($query)) {
            abort(404);
        }

        $dataForHash = ['booking_id' => $query->booking_id, 'ticket_id' => $query->id];
        $code = Hash::make($query->booking_id . '.' . $query->id);
        $qr_data = route('api.user.tickets.scan', array_merge($dataForHash, ['code' => $code]));
        $qr_size = 200;

        return QrCode::size($qr_size)->generate($qr_data);
    }
}