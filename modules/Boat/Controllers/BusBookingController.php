<?php

namespace Modules\Boat\Controllers;

use BC\QrCode\Facades\QrCode;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Boat\Models\Bus;
use Modules\Boat\Models\BusBooking;
use Modules\Boat\Models\BusPayment;
use Modules\Boat\Models\BusRoute;
use Modules\Boat\Models\BusSchedule;

class BusBookingController extends Controller
{
    private const MAX_PER_PAGE = 100;

    protected function resolvePerPage(Request $request, int $default = 20): int
    {
        $perPage = (int) $request->query('per_page', $default);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    public function routes(Request $request)
    {
        $query = BusRoute::query()
            ->select(['id', 'from_location', 'to_location', 'distance_km', 'status', 'created_at', 'updated_at'])
            ->where('status', 'active')
            ->orderBy('from_location');
        if ($from = $request->query('from_location')) {
            $query->where('from_location', 'like', '%' . $from . '%');
        }
        if ($to = $request->query('to_location')) {
            $query->where('to_location', 'like', '%' . $to . '%');
        }

        return response()->json(['data' => $query->paginate($this->resolvePerPage($request))]);
    }

    public function searchSchedules(Request $request)
    {
        // Accept both passengers and Passengers from clients.
        if ($request->filled('Passengers') && !$request->filled('passengers')) {
            $request->merge(['passengers' => $request->input('Passengers')]);
        }

        $request->validate([
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'date' => 'required|date_format:Y-m-d',
            'passengers' => 'nullable|integer|min:1|max:100',
        ]);

        $passengers = (int) $request->input('passengers', 1);

        $bookedSub = DB::table('bc_bus_bookings')
            ->select('schedule_id', DB::raw('COUNT(*) as booked_count'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->groupBy('schedule_id');

        $query = BusSchedule::query()
            ->with(['bus', 'route'])
            ->join('bc_buses', 'bc_buses.id', '=', 'bc_bus_schedules.bus_id')
            ->leftJoinSub($bookedSub, 'booked', function ($join) {
                $join->on('booked.schedule_id', '=', 'bc_bus_schedules.id');
            })
            ->where('status', 'scheduled')
            ->whereDate('departure_time', $request->input('date'))
            ->whereHas('route', function ($q) use ($request) {
                $q->where('from_location', 'like', '%' . $request->input('from_location') . '%')
                    ->where('to_location', 'like', '%' . $request->input('to_location') . '%')
                    ->where('status', 'active');
            })
            ->whereRaw('(COALESCE(bc_buses.seat_capacity, 0) - COALESCE(booked.booked_count, 0)) >= ?', [$passengers])
            ->select('bc_bus_schedules.*')
            ->selectRaw('COALESCE(booked.booked_count, 0) as booked_seats')
            ->selectRaw('(COALESCE(bc_buses.seat_capacity, 0) - COALESCE(booked.booked_count, 0)) as available_seats')
            ->orderBy('departure_time');

        $page = $query->paginate($this->resolvePerPage($request));
        $page->getCollection()->transform(function ($row) use ($passengers) {
            $item = $row->toArray();
            $item['booked_seats'] = (int) ($item['booked_seats'] ?? 0);
            $item['available_seats'] = (int) ($item['available_seats'] ?? 0);
            $item['passengers_requested'] = $passengers;
            return $item;
        });

        return response()->json(['data' => $page]);
    }

    public function scheduleSeats($id)
    {
        $schedule = BusSchedule::query()->with('bus')->find($id);
        if (!$schedule || !$schedule->bus) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        $capacity = (int) ($schedule->bus->seat_capacity ?? 0);
        if ($capacity < 1) {
            return response()->json(['message' => 'Seat capacity is not configured'], 422);
        }

        $booked = BusBooking::query()
            ->where('schedule_id', $schedule->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('seat_number')
            ->map(fn ($n) => (int) $n)
            ->values();

        $allSeats = [];
        for ($i = 1; $i <= $capacity; $i++) {
            $allSeats[] = [
                'seat_number' => $i,
                'available' => !$booked->contains($i),
            ];
        }

        return response()->json([
            'data' => [
                'schedule_id' => (int) $schedule->id,
                'bus_id' => (int) $schedule->bus_id,
                'total_seats' => $capacity,
                'booked_seats' => $booked,
                'seats' => $allSeats,
            ],
        ]);
    }

    public function busSeatAvailability(Request $request, $busId)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $scheduleQuery = BusSchedule::query()
            ->with(['route', 'bus'])
            ->where('bus_id', (int) $busId)
            ->where('status', 'scheduled')
            ->orderBy('departure_time');

        if ($date = $request->query('date')) {
            $scheduleQuery->whereDate('departure_time', $date);
        }

        $schedules = $scheduleQuery->get();
        if ($schedules->isEmpty()) {
            return response()->json([
                'data' => [
                    'bus_id' => (int) $busId,
                    'date' => $request->query('date'),
                    'schedules' => [],
                ],
            ]);
        }

        $scheduleIds = $schedules->pluck('id')->all();
        $bookings = BusBooking::query()
            ->whereIn('schedule_id', $scheduleIds)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['schedule_id', 'seat_number']);

        $bySchedule = $bookings->groupBy('schedule_id');
        $capacity = (int) ($schedules->first()->bus->seat_capacity ?? 0);

        $payload = $schedules->map(function (BusSchedule $schedule) use ($bySchedule, $capacity) {
            $rows = $bySchedule->get($schedule->id, collect());
            $bookedSeats = $rows->pluck('seat_number')->map(static fn ($n) => (int) $n)->values();
            $bookedCount = $bookedSeats->count();
            $available = max(0, $capacity - $bookedCount);
            $seatMap = [];
            for ($i = 1; $i <= $capacity; $i++) {
                $seatMap[] = [
                    'seat_number' => $i,
                    'available' => !$bookedSeats->contains($i),
                    'state' => $bookedSeats->contains($i) ? 'taken' : 'available',
                ];
            }

            return [
                'schedule_id' => (int) $schedule->id,
                'route_id' => (int) $schedule->route_id,
                'route' => $schedule->route,
                'departure_time' => $schedule->departure_time,
                'arrival_time' => $schedule->arrival_time,
                'total_seats' => $capacity,
                'booked_seats_count' => $bookedCount,
                'available_seats_count' => $available,
                'booked_seats' => $bookedSeats,
                'summary' => [
                    'total' => $capacity,
                    'free' => $available,
                    'taken' => $bookedCount,
                    'preview_seats' => min(40, $capacity),
                ],
                'seat_map' => $seatMap,
            ];
        })->values();

        return response()->json([
            'data' => [
                'bus_id' => (int) $busId,
                'date' => $request->query('date'),
                'schedules' => $payload,
            ],
        ]);
    }

    public function selectSeat(Request $request, $id)
    {
        $request->validate([
            'seat_number' => 'required|integer|min:1',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $result = DB::transaction(function () use ($id, $request, $user) {
            $schedule = BusSchedule::query()->with('bus')->lockForUpdate()->find((int) $id);
            if (!$schedule || !$schedule->bus) {
                return response()->json(['message' => 'Schedule not found'], 404);
            }

            $seatNumber = (int) $request->input('seat_number');
            $capacity = (int) ($schedule->bus->seat_capacity ?? 0);
            if ($capacity < 1) {
                return response()->json(['message' => 'Seat capacity is not configured'], 422);
            }
            if ($seatNumber > $capacity) {
                return response()->json(['message' => 'Seat number exceeds bus capacity'], 422);
            }

            $takenByOther = BusBooking::query()
                ->where('schedule_id', $schedule->id)
                ->where('seat_number', $seatNumber)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($takenByOther) {
                return response()->json(['message' => 'Seat already selected by another user'], 409);
            }

            $userBooking = BusBooking::query()
                ->where('schedule_id', $schedule->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            if ($userBooking) {
                if ($userBooking->seat_number !== $seatNumber) {
                    $userBooking->seat_number = $seatNumber;
                }
                if (!$userBooking->payment_status) {
                    $userBooking->payment_status = 'unpaid';
                }
                $userBooking->status = $userBooking->status ?: 'pending';
                $userBooking->save();

                return response()->json([
                    'message' => 'Seat selected',
                    'data' => [
                        'schedule_id' => (int) $schedule->id,
                        'seat_number' => (int) $userBooking->seat_number,
                        'booking_id' => (int) $userBooking->id,
                        'user_id' => (int) $user->id,
                        'status' => $userBooking->status,
                        'payment_status' => $userBooking->payment_status,
                    ],
                ]);
            }

            $booking = BusBooking::query()->create([
                'user_id' => $user->id,
                'schedule_id' => $schedule->id,
                'seat_number' => $seatNumber,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            return response()->json([
                'message' => 'Seat selected',
                'data' => [
                    'schedule_id' => (int) $schedule->id,
                    'seat_number' => (int) $booking->seat_number,
                    'booking_id' => (int) $booking->id,
                    'user_id' => (int) $user->id,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                ],
            ], 201);
        });

        return $result;
    }

    public function createBooking(Request $request)
    {
        // Empty-string values from the frontend (e.g. an unset date picker) should be
        // treated as "not provided" rather than failing format-specific validation rules.
        $request->merge(array_map(
            fn ($value) => $value === '' ? null : $value,
            $request->only(['departure_date', 'boarding_point', 'dropping_point'])
        ));

        $request->validate([
            'schedule_id' => 'required|integer|min:1',
            'seat_number' => 'required_without:seat_numbers|integer|min:1',
            'seat_numbers' => 'required_without:seat_number|array|min:1',
            'seat_numbers.*' => 'integer|min:1',
            'trip_type' => 'nullable|string|in:one-way,round-trip',
            'departure_date' => 'nullable|date',
            'passengers' => 'nullable|integer|min:1',
            'passenger_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:30',
            'boarding_point' => 'nullable|string|max:191',
            'dropping_point' => 'nullable|string|max:191',
            'paid_luggage' => 'nullable|integer|min:0',
            'special_luggage' => 'nullable|integer|min:0',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $seatNumbers = $request->filled('seat_numbers')
            ? array_values(array_unique(array_map('intval', $request->input('seat_numbers'))))
            : [$request->integer('seat_number')];

        $result = DB::transaction(function () use ($request, $user, $seatNumbers) {
            $schedule = BusSchedule::query()->with('bus')->lockForUpdate()->find($request->integer('schedule_id'));
            if (!$schedule || !$schedule->bus) {
                return response()->json(['message' => 'Schedule not found'], 404);
            }

            $overCapacity = array_filter($seatNumbers, fn ($seat) => $seat > (int) $schedule->bus->seat_capacity);
            if (!empty($overCapacity)) {
                return response()->json(['message' => 'Seat number exceeds bus capacity'], 422);
            }

            $alreadyBooked = BusBooking::query()
                ->where('schedule_id', $schedule->id)
                ->whereIn('seat_number', $seatNumbers)
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('seat_number');
            if ($alreadyBooked->isNotEmpty()) {
                return response()->json([
                    'message' => 'Seat already booked',
                    'seat_numbers' => $alreadyBooked->values(),
                ], 409);
            }

            $bookingGroup = (string) Str::uuid();
            $shared = [
                'user_id' => $user->id,
                'booking_group' => $bookingGroup,
                'schedule_id' => $schedule->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'trip_type' => $request->input('trip_type'),
                'departure_date' => $request->input('departure_date'),
                'passengers' => $request->input('passengers'),
                'passenger_name' => $request->input('passenger_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'boarding_point' => $request->input('boarding_point'),
                'dropping_point' => $request->input('dropping_point'),
                'paid_luggage' => $request->input('paid_luggage', 0),
                'special_luggage' => $request->input('special_luggage', 0),
            ];

            $bookings = collect($seatNumbers)->map(
                fn ($seatNumber) => BusBooking::query()->create($shared + ['seat_number' => $seatNumber])
            );

            return $bookings;
        });

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        // `data` is always a single object with `data.id` (the first seat's booking),
        // even when multiple seats were booked in one request. `bookings` carries every
        // row (each with its own id) so multi-seat clients can still access them all.
        $data = $result->first()->toArray();
        $data['seat_numbers'] = $result->pluck('seat_number')->values();
        $data['bookings'] = $result->values();

        return response()->json(['message' => 'Booking created', 'data' => $data], 201);
    }

    public function bookingDetail($id)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $booking = BusBooking::query()
            ->with(['schedule.bus', 'schedule.route'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $data = $booking->toArray();
        $data += $this->ticketLinks($booking);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/bus-bookings/ticket/{ticket_code}
     * Full ticket details for the paid booking (passenger, trip, and QR info), for
     * rendering a printable/scannable ticket like the one shown after payment success.
     * Public (no auth) — the customer lands here straight from the Chapa redirect with
     * no Sanctum token, so access is gated by the unguessable ticket_code instead,
     * same trust model as verifyTicket().
     */
    public function ticketByCode($ticketCode)
    {
        $booking = BusBooking::query()
            ->with(['schedule.bus', 'schedule.route'])
            ->where('ticket_code', $ticketCode)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json(['message' => __('Ticket is not available until payment is confirmed.')], 422);
        }

        return response()->json(['data' => $this->buildTicketData($booking)]);
    }

    /**
     * GET /api/bus-bookings/ticket/{ticket_code}/qr-code
     * Renders the scannable QR image for the booking's ticket, encoding a link back
     * to the public verify endpoint so staff/devices can validate it on scan.
     * Public (no auth), same trust model as ticketByCode() above.
     */
    public function qrCodeByCode(Request $request, $ticketCode)
    {
        $booking = BusBooking::query()->where('ticket_code', $ticketCode)->first();
        if (!$booking) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json(['message' => __('Ticket is not available until payment is confirmed.')], 422);
        }

        $size = max(100, min(1000, (int) $request->query('size', 300)));
        $verifyUrl = route('api.bus_bookings.verify', ['ticket_code' => $booking->ticket_code]);
        $svg = (string) QrCode::size($size)->generate($verifyUrl);

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }

    private function buildTicketData(BusBooking $booking): array
    {
        $schedule = $booking->schedule;
        $bus = $schedule->bus;
        $route = $schedule->route;

        $payment = BusPayment::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'success')
            ->latest('paid_at')
            ->first();
        $amountPaid = $payment ? (float) $payment->amount : (float) ($schedule->price ?? 0);

        return [
            'ticket_id' => $booking->ticket_code,
            'booking_id' => (int) $booking->id,
            'seat_number' => (int) $booking->seat_number,
            'passenger_name' => $booking->passenger_name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'from_location' => $route->from_location ?? null,
            'to_location' => $route->to_location ?? null,
            'boarding_point' => $booking->boarding_point,
            'dropping_point' => $booking->dropping_point,
            'departure_time' => $schedule->departure_time,
            'arrival_time' => $schedule->arrival_time,
            'bus_title' => $bus->bus_name ?? null,
            'bus_level' => $bus->bus_type ?? null,
            'bus_number' => $bus->bus_number ?? null,
            'bus_side_number' => $bus->side_number ?? null,
            'price_in_words' => $bus->price_in_words ?? null,
            'total_paid' => round($amountPaid, 2),
            'currency' => strtoupper((string) ($bus->currency ?? 'ETB')),
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'qr_code_url' => route('api.bus_bookings.qr_code', ['ticket_code' => $booking->ticket_code]),
        ];
    }

    /**
     * GET /api/bus-bookings/verify/{ticket_code}
     * What the ticket QR code links to — whoever scans it (staff at boarding) lands
     * here to confirm it's a real, paid, confirmed booking (no auth — the ticket_code
     * itself is the secret). Renders a plain-language "Valid/Invalid" result page by
     * default, same as the ticket page; add ?format=json for the raw payload.
     */
    public function verifyTicket(Request $request, $ticketCode)
    {
        $booking = BusBooking::query()
            ->with(['schedule.bus', 'schedule.route'])
            ->where('ticket_code', $ticketCode)
            ->first();

        if (!$booking) {
            return $this->verifyResponse($request, false, [
                'ticket_id' => $ticketCode,
            ], 404);
        }

        $valid = $booking->payment_status === 'paid' && $booking->status === 'confirmed';

        return $this->verifyResponse($request, $valid, [
            'ticket_id' => $booking->ticket_code,
            'passenger_name' => $booking->passenger_name,
            'seat_number' => (int) $booking->seat_number,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'from_location' => $booking->schedule->route->from_location ?? null,
            'to_location' => $booking->schedule->route->to_location ?? null,
            'departure_time' => $booking->schedule->departure_time ?? null,
        ]);
    }

    private function verifyResponse(Request $request, bool $valid, array $data, int $status = 200)
    {
        if ($request->query('format') === 'json') {
            return response()->json(['valid' => $valid, 'data' => $data], $valid ? 200 : $status);
        }

        return response()->view('Boat::frontend.verify', [
            'valid' => $valid,
            'ticket' => $data,
        ], $valid ? 200 : $status);
    }

    private function ticketLinks(BusBooking $booking): array
    {
        if (empty($booking->ticket_code)) {
            return [];
        }

        return [
            'ticket_url' => route('api.bus_bookings.ticket', ['ticket_code' => $booking->ticket_code]),
            'qr_code_url' => route('api.bus_bookings.qr_code', ['ticket_code' => $booking->ticket_code]),
        ];
    }

    public function payBooking(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string|max:50',
            'provider_reference' => 'nullable|string|max:100',
            'payload' => 'nullable|array',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $booking = BusBooking::query()->with(['schedule'])->where('id', $id)->where('user_id', $user->id)->first();
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if ($booking->payment_status === 'paid') {
            return response()->json(['message' => 'Booking already paid', 'data' => $booking]);
        }

        if (strtolower($request->input('payment_method')) === 'chapa') {
            return $this->initiateChapaPayment($booking, $request);
        }

        $amount = (float) ($booking->schedule->price ?? 0);
        $payment = BusPayment::query()->create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => 'success',
            'payment_method' => $request->input('payment_method'),
            'provider_reference' => $request->input('provider_reference'),
            'payload' => $request->input('payload'),
            'paid_at' => now(),
        ]);

        $booking->payment_status = 'paid';
        $booking->status = 'confirmed';
        $booking->ticket_code = $booking->ticket_code ?: 'BUS-' . strtoupper(Str::random(10));
        $booking->save();

        return response()->json([
            'message' => 'Payment processed and ticket generated',
            'data' => [
                'booking' => $booking->fresh(['schedule.bus', 'schedule.route']),
                'payment' => $payment,
            ] + $this->ticketLinks($booking),
        ]);
    }

    // =========================================================================
    // CHAPA
    // =========================================================================

    protected function initiateChapaPayment(BusBooking $booking, Request $request)
    {
        $gateway = $this->findGateway('chapa');
        $secretKey = $gateway ? trim((string) $gateway->getOption('secret_key')) : '';
        if (!$gateway || empty($secretKey)) {
            return response()->json(['message' => __('Chapa is not configured. Please contact support.')], 500);
        }

        $amount = (float) ($booking->schedule->price ?? 0);
        if ($amount <= 0) {
            return response()->json(['message' => __('Booking amount is invalid.')], 422);
        }

        $payment = BusPayment::query()->create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'chapa',
        ]);

        $nameParts = explode(' ', trim((string) $booking->passenger_name) ?: 'Guest User', 2);
        $mainCurrency = strtoupper((string) setting_item('currency_main', 'ETB'));
        $currency = strtoupper((string) ($gateway->getOption('currency') ?: 'ETB'));

        // Let the client choose ETB (local) or the configured alternate currency
        // (e.g. USD for card payments) via the "currency" request field, same
        // list ChapaGateway::getAvailableCurrencies() exposes to web checkout.
        $availableCurrencies = method_exists($gateway, 'getAvailableCurrencies')
            ? $gateway->getAvailableCurrencies()
            : [$mainCurrency];
        $requestedCurrency = strtoupper(trim((string) $request->input('currency')));
        if ($requestedCurrency !== '' && in_array($requestedCurrency, $availableCurrencies, true)) {
            $currency = $requestedCurrency;
        }

        $txRef = 'BUS-' . $booking->id . '-' . $payment->id . '-' . time();

        $conversion = null;
        $chargeAmount = $amount;
        if ($currency !== $mainCurrency) {
            // Delegate to ChapaGateway::convertAmount() so both booking flows use the
            // exact same ETB<->USD conversion direction — see that method for why the
            // rate can't just be blindly divided/multiplied based on main-vs-alternate.
            try {
                $chargeAmount = $gateway->convertAmount($amount, $mainCurrency, $currency);
            } catch (\Throwable $e) {
                $payment->status = 'fail';
                $payment->save();

                return response()->json(['message' => $e->getMessage()], 500);
            }

            $conversion = [
                'main_currency' => $mainCurrency,
                'main_amount' => $amount,
                'exchange_rate' => (float) $gateway->getOption('exchange_rate'),
                'converted_currency' => $currency,
                'converted_amount' => $chargeAmount,
            ];
        }

        $payload = [
            'amount' => number_format($chargeAmount, 2, '.', ''),
            'currency' => $currency,
            'email' => $booking->email ?: 'no-reply@example.com',
            'first_name' => $nameParts[0] ?? 'Guest',
            'last_name' => $nameParts[1] ?? '',
            'phone_number' => $booking->phone ?? '',
            'tx_ref' => $txRef,
            'callback_url' => url('/api/bus-bookings/payment/webhook/chapa'),
            'return_url' => url('/api/bus-bookings/payment/confirm/chapa') . '?tx_ref=' . $txRef,
            'customization' => [
                'title' => 'Bus Booking',
                'description' => mb_substr(preg_replace('/[^A-Za-z0-9\-_. ]+/', '', 'Bus Booking ' . $booking->id), 0, 50),
            ],
            'meta' => [
                'booking_id' => (string) $booking->id,
                'payment_id' => (string) $payment->id,
            ],
        ];

        $response = Http::timeout((int) max(5, (int) $gateway->getOption('timeout', 30)))
            ->withOptions(['verify' => $this->chapaSslVerifyOption()])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->chapaBaseUrl($gateway) . '/v1/transaction/initialize', $payload);

        $json = $response->json();
        $checkoutUrl = data_get($json, 'data.checkout_url');

        if (!$response->successful() || empty($checkoutUrl)) {
            $payment->status = 'fail';
            $payment->payload = $conversion ? ['response' => $json, 'conversion' => $conversion] : $json;
            $payment->save();

            Log::error('Chapa bus booking init failed', ['booking_id' => $booking->id, 'response' => $json]);

            return response()->json([
                'message' => $this->chapaFlattenMessage(data_get($json, 'message', __('Unable to initialize Chapa payment'))),
            ], 500);
        }

        $payment->provider_reference = $txRef;
        $payment->payload = $conversion ? ['response' => $json, 'conversion' => $conversion] : $json;
        $payment->save();

        return response()->json([
            'message' => __('Redirect the customer to payment_url to complete payment.'),
            'payment_type' => 'redirect',
            'payment_url' => $checkoutUrl,
            'tx_ref' => $txRef,
            'amount' => number_format($chargeAmount, 2, '.', ''),
            'currency' => $currency,
            'main_amount' => number_format($amount, 2, '.', ''),
            'main_currency' => $mainCurrency,
            'data' => [
                'booking' => $booking,
                'payment' => $payment,
            ],
        ]);
    }

    /**
     * GET /api/bus-bookings/payment/confirm/chapa?tx_ref=...
     * Chapa redirects the customer's browser here after they complete (or cancel) checkout.
     */
    public function confirmChapaPayment(Request $request)
    {
        $txRef = trim((string) $request->query('tx_ref', $request->query('trx_ref')));
        if (empty($txRef)) {
            return $this->paymentStatusResponse($request, 422, __('Missing payment reference'));
        }

        $payment = BusPayment::query()->where('provider_reference', $txRef)->first();
        if (!$payment) {
            return $this->paymentStatusResponse($request, 404, __('Payment not found'));
        }

        $booking = BusBooking::query()->with(['schedule.bus', 'schedule.route'])->find($payment->booking_id);
        if (!$booking) {
            return $this->paymentStatusResponse($request, 404, __('Booking not found'));
        }

        if ($booking->payment_status === 'paid') {
            return $this->ticketResponse($request, $booking, __('Already paid.'));
        }

        $gateway = $this->findGateway('chapa');
        $verification = $gateway ? $this->chapaVerify($gateway, $txRef) : [];
        $success = $this->chapaIsSuccess($verification);

        $payment->payload = $verification;

        if ($success) {
            $payment->status = 'success';
            $payment->paid_at = now();
            $payment->save();

            $booking->payment_status = 'paid';
            $booking->status = 'confirmed';
            $booking->ticket_code = $booking->ticket_code ?: 'BUS-' . strtoupper(Str::random(10));
            $booking->save();

            return $this->ticketResponse(
                $request,
                $booking->fresh(['schedule.bus', 'schedule.route']),
                __('Payment confirmed. Thank you!')
            );
        }

        $payment->status = 'fail';
        $payment->save();

        return $this->paymentStatusResponse($request, 400, __('Payment verification failed. Please try again.'));
    }

    /**
     * This is the Chapa return_url — a page the customer's browser lands on, so it
     * always renders the printable ticket UI. Some REST clients (Postman, Insomnia,
     * axios/fetch defaults) send "Accept: application/json" even for plain navigation,
     * so content negotiation via wantsJson() isn't reliable here; JSON is only returned
     * when explicitly asked for via ?format=json.
     */
    private function ticketResponse(Request $request, BusBooking $booking, string $message)
    {
        if ($request->query('format') === 'json') {
            $data = $booking->toArray();
            $data += $this->ticketLinks($booking);

            return response()->json(['message' => $message, 'data' => $data]);
        }

        return response()->view('Boat::frontend.ticket', [
            'message' => $message,
            'ticket' => $this->buildTicketData($booking),
        ]);
    }

    private function paymentStatusResponse(Request $request, int $status, string $message)
    {
        if ($request->query('format') === 'json') {
            return response()->json(['message' => $message], $status);
        }

        return response()->view('Boat::frontend.payment-status', [
            'message' => $message,
            'success' => $status < 300,
        ], $status);
    }

    /**
     * POST /api/bus-bookings/payment/webhook/chapa
     * Chapa's server calls this directly — no auth, no CSRF.
     */
    public function webhookChapaPayment(Request $request)
    {
        $txRef = trim((string) (
            $request->input('tx_ref')
            ?: $request->input('trx_ref')
            ?: $request->input('reference')
            ?: $request->input('data.tx_ref')
            ?: $request->input('meta.tx_ref')
        ));

        if (empty($txRef)) {
            Log::warning('Chapa bus webhook missing tx_ref', $request->all());
            return response()->json(['status' => 'error', 'message' => 'tx_ref missing'], 400);
        }

        $payment = BusPayment::query()->where('provider_reference', $txRef)->first();
        if (!$payment) {
            Log::warning('Chapa bus webhook: payment not found', ['tx_ref' => $txRef]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        $booking = BusBooking::query()->find($payment->booking_id);
        if (!$booking) {
            return response()->json(['status' => 'error', 'message' => 'Booking not found'], 404);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['status' => 'success', 'message' => 'Already processed']);
        }

        $gateway = $this->findGateway('chapa');
        $verification = $gateway ? $this->chapaVerify($gateway, $txRef) : [];
        $success = $this->chapaIsSuccess($verification);

        $payment->payload = $verification;

        if ($success) {
            $payment->status = 'success';
            $payment->paid_at = now();
            $payment->save();

            $booking->payment_status = 'paid';
            $booking->status = 'confirmed';
            $booking->ticket_code = $booking->ticket_code ?: 'BUS-' . strtoupper(Str::random(10));
            $booking->save();

            return response()->json(['status' => 'success', 'message' => 'Payment processed']);
        }

        $payment->status = 'fail';
        $payment->save();

        return response()->json(['status' => 'error', 'message' => 'Verification failed'], 400);
    }

    /**
     * GET /api/bus-bookings/payment/cancel/chapa?tx_ref=...
     */
    public function cancelChapaPayment(Request $request)
    {
        $txRef = trim((string) $request->query('tx_ref'));
        $payment = BusPayment::query()->where('provider_reference', $txRef)->first();
        if ($payment && $payment->status === 'pending') {
            $payment->status = 'cancel';
            $payment->save();
        }

        return response()->json(['message' => __('Payment was cancelled. You can try again.')]);
    }

    private function findGateway(string $id)
    {
        foreach (get_available_gateways() as $key => $gw) {
            if ($key == $id) {
                return $gw;
            }
        }
        return null;
    }

    private function chapaVerify($gateway, string $txRef): array
    {
        $secretKey = trim((string) $gateway->getOption('secret_key'));
        try {
            $response = Http::timeout((int) max(5, (int) $gateway->getOption('timeout', 30)))
                ->withOptions(['verify' => $this->chapaSslVerifyOption()])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($this->chapaBaseUrl($gateway) . '/v1/transaction/verify/' . urlencode($txRef));

            return (array) $response->json();
        } catch (\Throwable $e) {
            Log::warning('Chapa bus verify failed', ['tx_ref' => $txRef, 'error' => $e->getMessage()]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    private function chapaIsSuccess(array $verification): bool
    {
        $top = strtolower((string) data_get($verification, 'status'));
        $data = strtolower((string) data_get($verification, 'data.status'));
        $paymentStatus = strtolower((string) data_get($verification, 'data.payment_status'));
        $tx = strtolower((string) data_get($verification, 'data.tx_status'));

        $ok = in_array($top, ['success', 'successful'], true);
        $states = ['success', 'successful', 'completed', 'paid'];
        $nested = in_array($data, $states, true) || in_array($paymentStatus, $states, true) || in_array($tx, $states, true);
        $noNested = $data === '' && $paymentStatus === '' && $tx === '';

        return $ok && ($nested || $noNested);
    }

    private function chapaBaseUrl($gateway): string
    {
        if ($gateway->getOption('test')) {
            return rtrim((string) $gateway->getOption('test_base_url', 'https://api.chapa.co'), '/');
        }
        return rtrim((string) $gateway->getOption('live_base_url', 'https://api.chapa.co'), '/');
    }

    private function chapaSslVerifyOption()
    {
        $bundle = app_path('certs/cacert.pem');
        return is_file($bundle) ? $bundle : true;
    }

    private function chapaFlattenMessage($message): string
    {
        if (is_string($message) || is_numeric($message)) {
            return (string) $message;
        }
        if (is_array($message)) {
            $flat = [];
            array_walk_recursive($message, static function ($item) use (&$flat) {
                if (is_scalar($item) || $item === null) {
                    $flat[] = (string) $item;
                }
            });
            return implode(' ', $flat) ?: __('Payment failed');
        }
        return __('Payment failed');
    }
}
