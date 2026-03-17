<?php

namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingPassenger;
use Illuminate\Http\Request;
use Modules\Api\Resources\TicketResource;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
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

    public function index(){
        $passenger = app(BookingPassenger::class);
        $booking = app(Booking::class);
        
        $query = $passenger->query()
            ->select($passenger->getTable() . ".*")
            ->join($booking->getTable(), $booking->qualifyColumn("id"), $passenger->qualifyColumn("booking_id"))
            ->where($booking->qualifyColumn("customer_id"),Auth::id())
            ->where($passenger->qualifyColumn("is_scanned"),1)
            ->orderBy($passenger->qualifyColumn("id"), 'desc');
            
        if(!is_admin()){
            $query->where($passenger->qualifyColumn("author_id"),Auth::id());
        }

        $query = $query->paginate(20);
        return response()->json([
            'success' => true,
            'data'    => TicketResource::collection($query->getCollection()),
            'total'   => $query->total(),
            'max_pages' => $query->lastPage()
        ]);
    }

    public function scan(Request $request, $booking_id, $ticket_id)
    {
        $request->merge([
            'booking_id' => $booking_id,
            'ticket_id' => $ticket_id,
        ]);

        $this->validate($request, [
            'code' => 'required',
            'booking_id' => 'required',
            'ticket_id' => 'required',
        ]);

        $booking = app(Booking::class);

        try {
            $booking->scanTicket($request->booking_id, $request->ticket_id, $request->code);
            return $this->sendSuccess(__('Ticket scan success'));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}