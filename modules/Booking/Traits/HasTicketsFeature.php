<?php
namespace Modules\Booking\Traits;
use Illuminate\Support\Facades\Hash;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingPassenger;
use Carbon\Carbon;

trait HasTicketsFeature
{
    /**
     * Vendor only scan QR Code
     *
     * @param $code
     */
    public function scanTicket($booking_id, $ticket_id, $code)
    {
        try {
            if(!Hash::check($booking_id . '.' . $ticket_id, $code)){
                throw new \Exception('Ticket not found.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Ticket not found');
        }

        $bookingClass = app(Booking::class);
        $ticketClass = app(BookingPassenger::class);
        $booking = $bookingClass::find($booking_id);
        $ticket = $ticketClass::query()->where([
            'booking_id' => $booking_id,
            'id'         => $ticket_id
        ])->first();

        $user_id = auth()->id();

        $allowStatus = [Booking::COMPLETED, Booking::PAID];

        if (empty($booking) or !in_array($booking->status, $allowStatus)) {
            throw new \Exception('Booking status not valid');
        }
        if (empty($ticket)) {
            throw new \Exception('Ticket not found.');
        }
        if ($booking->vendor_id != $user_id and !is_admin()) {
            throw new \Exception('This ticket does not belong to your events');
        }
        if ($ticket->is_scanned) {
            throw new \Exception('Ticket already scanned at :time', display_datetime($ticket->scanned_at));
        }

        $ticket->is_scanned = 1;
        $ticket->scanned_at = Carbon::now();
        $ticket->scanned_by = $user_id;

        $ticket->save();

        return [
            'booking' => $booking,
            'ticket' => $ticket
        ];
    }
}
