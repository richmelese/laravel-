<?php

namespace Modules\Boat\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        $request->validate([
            'schedule_id' => 'required|integer|min:1',
            'seat_number' => 'required|integer|min:1',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $booking = DB::transaction(function () use ($request, $user) {
            $schedule = BusSchedule::query()->with('bus')->lockForUpdate()->find($request->integer('schedule_id'));
            if (!$schedule || !$schedule->bus) {
                return response()->json(['message' => 'Schedule not found'], 404);
            }
            $seatNumber = $request->integer('seat_number');
            if ($seatNumber > (int) $schedule->bus->seat_capacity) {
                return response()->json(['message' => 'Seat number exceeds bus capacity'], 422);
            }

            $alreadyBooked = BusBooking::query()
                ->where('schedule_id', $schedule->id)
                ->where('seat_number', $seatNumber)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();
            if ($alreadyBooked) {
                return response()->json(['message' => 'Seat already booked'], 409);
            }

            return BusBooking::query()->create([
                'user_id' => $user->id,
                'schedule_id' => $schedule->id,
                'seat_number' => $seatNumber,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);
        });

        if ($booking instanceof \Illuminate\Http\JsonResponse) {
            return $booking;
        }

        return response()->json(['message' => 'Booking created', 'data' => $booking], 201);
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

        return response()->json(['data' => $booking]);
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
            ],
        ]);
    }
}
