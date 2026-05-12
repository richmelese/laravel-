<?php

namespace Modules\Boat\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Boat\Models\BusBooking;
use Modules\Boat\Models\BusSchedule;

class BusBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = BusBooking::query()->with(['schedule.bus', 'schedule.route'])->orderByDesc('id');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        return response()->json(['data' => $query->paginate((int) $request->query('per_page', 20))]);
    }

    public function show($id)
    {
        $booking = BusBooking::query()->with(['schedule.bus', 'schedule.route'])->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        return response()->json(['data' => $booking]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'nullable|string|max:20',
            'payment_status' => 'nullable|string|max:20',
        ]);
        $booking = BusBooking::query()->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }
        if (isset($data['status'])) {
            $booking->status = $data['status'];
        }
        if (isset($data['payment_status'])) {
            $booking->payment_status = $data['payment_status'];
        }
        $booking->save();
        return response()->json(['message' => 'Booking updated', 'data' => $booking]);
    }

    public function busSeatAvailability(Request $request, $busId)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string|max:20',
        ]);

        $scheduleQuery = BusSchedule::query()
            ->with(['route', 'bus'])
            ->where('bus_id', (int) $busId)
            ->orderBy('departure_time');

        if ($date = $request->query('date')) {
            $scheduleQuery->whereDate('departure_time', $date);
        }
        if ($status = $request->query('status')) {
            $scheduleQuery->where('status', $status);
        }

        $schedules = $scheduleQuery->get();
        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'No schedules found for this bus',
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
            ->get(['schedule_id', 'seat_number', 'status', 'payment_status']);

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
                'status' => $schedule->status,
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
}
