<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingPassenger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
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

        $hasTicketStatus = $this->ticketStatuses();
        
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

    /**
     * Return the tickets issued for one booking owned by the authenticated
     * customer. Ticket rows are created during checkout and issued once the
     * booking reaches a valid paid/confirmed state.
     */
    public function bookingTickets(Request $request, $booking_id)
    {
        $booking = Booking::query()
            ->where('id', $booking_id)
            ->where('customer_id', Auth::id())
            ->whereIn('status', $this->ticketStatuses())
            ->first();

        if (empty($booking)) {
            return response()->json([
                'success' => false,
                'message' => __('Paid booking not found'),
            ], 404);
        }

        $tickets = BookingPassenger::query()
            ->where('booking_id', $booking->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
                'booking_status' => $booking->status,
                'tickets' => TicketResource::collection($tickets, ['qr_code_url']),
            ],
            'total' => $tickets->count(),
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

    protected function ticketStatuses(): array
    {
        return [
            Booking::PAID,
            Booking::CONFIRMED,
            Booking::COMPLETED,
        ];
    }
}
