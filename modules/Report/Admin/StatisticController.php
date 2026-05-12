<?php
namespace Modules\Report\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Booking\Emails\NewBookingEmail;
use Modules\Booking\Models\Booking;

class StatisticController extends AdminController
{
    public function __construct()
    {

    }

    public function index()
    {
        $f = strtotime('monday this week');
        $status = config('booking.statuses');
        $data = [
            'earning_chart_data'  => Booking::getStatisticChartData($f, time(), $status)['chart'],
            'earning_detail_data' => Booking::getStatisticChartData($f, time(), $status)['detail']
        ];
        return view('Report::admin.statistic.index', $data);
    }

    public function apiIndex()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('report_view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $from = strtotime('monday this week');
        $to = time();
        $status = config('booking.statuses');
        $stats = Booking::getStatisticChartData($from, $to, $status);

        return response()->json([
            'data' => [
                'chart_data' => $stats['chart'] ?? [],
                'detail_data' => $stats['detail'] ?? [],
                'from' => date('Y-m-d', $from),
                'to' => date('Y-m-d', $to),
            ],
        ]);
    }

    public function reloadChart(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $status = config('booking.statuses');
        $customer_id = false;
        $vendor_id = false;
        $user_type = $request->input('user_type');
        if ($user_type == 'customer') {
            $customer_id = $request->input('user_id');
        }
        if ($user_type == 'vendor') {
            $vendor_id = $request->input('user_id');
        }
        return $this->sendSuccess([
            'chart_data'  => Booking::getStatisticChartData(strtotime($from), strtotime($to), $status, $customer_id, $vendor_id)['chart'],
            'detail_data' => Booking::getStatisticChartData(strtotime($from), strtotime($to), $status, $customer_id, $vendor_id)['detail']
        ]);
    }

    public function apiReloadChart(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('report_view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
            'user_type' => 'nullable|string|in:customer,vendor',
            'user_id' => 'nullable|integer|min:1',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');
        $status = config('booking.statuses');
        $customer_id = false;
        $vendor_id = false;

        $userType = $request->input('user_type');
        if ($userType === 'customer') {
            $customer_id = (int) $request->input('user_id');
        } elseif ($userType === 'vendor') {
            $vendor_id = (int) $request->input('user_id');
        }

        $stats = Booking::getStatisticChartData(strtotime($from), strtotime($to), $status, $customer_id, $vendor_id);

        return response()->json([
            'data' => [
                'chart_data' => $stats['chart'] ?? [],
                'detail_data' => $stats['detail'] ?? [],
                'from' => $from,
                'to' => $to,
                'user_type' => $userType,
                'user_id' => $request->input('user_id'),
            ],
        ]);
    }
}
