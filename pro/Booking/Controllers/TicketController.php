<?php

namespace Pro\Booking\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingPassenger;
use Modules\FrontendController;

class TicketController extends FrontendController
{
    private Booking $booking;
    private BookingPassenger $ticket;

    public function __construct(Booking $booking, BookingPassenger $ticket)
    {
        parent::__construct();
        $this->booking = $booking;
        $this->ticket = $ticket;
    }

    public function index($code = '')
    {
        $booking = $this->booking->where('code', $code)->first();
        $user_id = Auth::id();

        $allowStatus = [$this->booking::COMPLETED, $this->booking::PAID];

        if (empty($booking) or !in_array($booking->status, $allowStatus)) {
            return redirect('user/booking-history');
        }
        if ($booking->customer_id != $user_id and $booking->vendor_id != $user_id) {
            return redirect('user/booking-history');
        }
        if ($ticket_id = request('ticket_id')) {
            $tickets = $booking->passengers()->where('id', $ticket_id)->get();
        } else {
            $tickets = $booking->passengers;
        }
        $data = [
            'booking'    => $booking,
            'service'    => $booking->service,
            'page_title' => __("Tickets"),
            'tickets'    => $tickets
        ];
        return view('Booking::frontend.user.ticket.tickets', $data);
    }

    /**
     * Vendor only scan QR Code
     *
     * @param $code
     */
    public function scan(\Illuminate\Http\Request $request, $booking_id, $ticket_id)
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

        try {
            $bookingObj = app(Booking::class);
            $result = $bookingObj->scanTicket($request->booking_id, $request->ticket_id, $request->code);
            return redirect(route('user.booking.ticket', ['code'      => $result['booking']->code,
                                                          'ticket_id' => $result['ticket']->id]))->with('success', __('Ticket scan success'));
        } catch (\Exception $e) {
            return redirect(route('vendor.dashboard'))->with('error', $e->getMessage());
        }
    }

}
