<?php

namespace Modules\Dashboard\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Booking\Models\Booking;
use Modules\Hotel\Models\Hotel;

class DashboardController extends AdminController
{
    public function index()
    {
        $f = strtotime('monday this week');
        $data = [
            'recent_bookings'    => Booking::getRecentBookings(),
            'top_cards'          => Booking::getTopCardsReport(),
            'earning_chart_data' => Booking::getDashboardChartData($f, time())
        ];
        return view('Dashboard::index', $data);
    }

    /**
     * JSON dashboard for Sanctum clients (GET /api-admin/dashboard).
     */
    public function apiDashboard(Request $request)
    {
        $this->checkPermission('dashboard_access');

        $f = strtotime('monday this week');
        $to = time();
        if ($request->filled('chart_from')) {
            $raw = $request->input('chart_from');
            $parsed = is_numeric($raw) ? (int) $raw : strtotime((string) $raw);
            if ($parsed !== false) {
                $f = $parsed;
            }
        }
        if ($request->filled('chart_to')) {
            $raw = $request->input('chart_to');
            $parsed = is_numeric($raw) ? (int) $raw : strtotime((string) $raw);
            if ($parsed !== false) {
                $to = $parsed;
            }
        }

        $limit = (int) $request->input('recent_limit', 10);
        $limit = min(100, max(1, $limit));
        $recentBookings = Booking::getRecentBookings($limit);
        $hotelIds = $recentBookings
            ->where('object_model', 'hotel')
            ->pluck('object_id')
            ->filter()
            ->unique()
            ->values();
        $hotelTitles = $hotelIds->isEmpty()
            ? collect()
            : Hotel::query()->whereIn('id', $hotelIds)->pluck('title', 'id');

        $recentBookings = $recentBookings
            ->map(function ($booking) use ($hotelTitles) {
                $booking->hotel_name = null;
                if ($booking->object_model === 'hotel') {
                    $booking->hotel_name = $hotelTitles->get($booking->object_id);
                }
                return $booking;
            })
            ->values();

        return response()->json([
            'recent_bookings'    => $recentBookings,
            'top_cards'          => Booking::getTopCardsReport(),
            'earning_chart_data' => Booking::getDashboardChartData($f, $to),
            'chart_range'        => ['from' => $f, 'to' => $to],
        ]);
    }

    public function reloadChart(Request $request)
    {
        $chart = $request->input('chart');
        switch ($chart) {
            case "earning":
                $from = $request->input('from');
                $to = $request->input('to');
                return $this->sendSuccess([
                    'data' => Booking::getDashboardChartData(strtotime($from), strtotime($to))
                ]);
                break;
        }
    }
}
