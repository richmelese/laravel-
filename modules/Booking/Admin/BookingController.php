<?php
namespace Modules\Booking\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\AdminController;
use Modules\Booking\Emails\NewBookingEmail;
use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Hotel\Models\HotelRoomBooking;

class BookingController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('booking.admin.booking'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    /**
     * Add the booked service title to each API booking without causing an
     * additional query for every row.
     */
    protected function appendServiceNames(Collection $bookings): Collection
    {
        $bookableServices = get_bookable_services();

        $bookings->each(function (Booking $booking) {
            $booking->setAttribute('service_name', null);
        });

        $bookings->groupBy('object_model')->each(function (Collection $group, string $objectModel) use ($bookableServices) {
            $serviceClass = $bookableServices[$objectModel] ?? null;
            $serviceIds = $group->pluck('object_id')->filter()->unique()->values();

            if (!$serviceClass || $serviceIds->isEmpty()) {
                return;
            }

            $serviceNames = $serviceClass::query()
                ->whereIn('id', $serviceIds)
                ->pluck('title', 'id');

            $group->each(function (Booking $booking) use ($serviceNames) {
                $booking->setAttribute('service_name', $serviceNames->get($booking->object_id));
            });
        });

        return $bookings;
    }

    /**
     * Build the sections used by the booking-detail modal. The raw booking is
     * still returned by the API, while this payload gives API clients stable,
     * display-ready labels and values without duplicating Blade-specific logic.
     */
    protected function bookingDetailUi(Booking $booking): array
    {
        $gateway = $booking->gateway ? get_payment_gateway_obj($booking->gateway) : null;
        $adults = $booking->getMeta('adults');
        $children = $booking->getMeta('children');

        $detailRows = [
            $this->uiRow('status', __('Booking Status'), $booking->status, $booking->status_name),
            $this->uiRow('booking_date', __('Booking Date'), optional($booking->created_at)->toIso8601String(), display_date($booking->created_at)),
            $this->uiRow('payment_method', __('Payment Method'), $booking->gateway, $gateway ? $gateway->name : $booking->gateway),
        ];

        if ($booking->start_date) {
            $detailRows[] = $this->uiRow('check_in', __('Check in:'), $booking->start_date, display_date($booking->start_date));
            $detailRows[] = $this->uiRow('check_out', __('Check out:'), $booking->end_date, display_date($booking->end_date));
            $detailRows[] = $this->uiRow('nights', __('Nights:'), $booking->duration_nights);
        }
        if ($adults !== '') {
            $detailRows[] = $this->uiRow('adults', __('Adults:'), (int) $adults);
        }
        if ($children !== '' && (int) $children > 0) {
            $detailRows[] = $this->uiRow('children', __('Children:'), (int) $children);
        }

        $lineItems = $this->bookingLineItems($booking);
        $paid = (float) $booking->paid;
        $total = (float) $booking->total;

        return [
            'title' => __('Booking ID: #') . ' ' . $booking->id,
            'booking_id' => $booking->id,
            'tabs' => [
                'booking_detail' => [
                    'label' => __('Booking Detail'),
                    'rows' => $detailRows,
                    'line_items' => $lineItems,
                    'totals' => [
                        'total' => $this->moneyValue($total),
                        'paid' => $this->moneyValue($paid),
                        'remain' => $this->moneyValue(max(0, $total - $paid)),
                    ],
                ],
                'personal_information' => [
                    'label' => __('Personal Information'),
                    'rows' => [
                        $this->uiRow('first_name', __('First name'), $booking->first_name),
                        $this->uiRow('last_name', __('Last name'), $booking->last_name),
                        $this->uiRow('email', __('Email'), $booking->email),
                        $this->uiRow('phone', __('Phone'), $booking->phone),
                        $this->uiRow('address', __('Address line 1'), $booking->address),
                        $this->uiRow('address2', __('Address line 2'), $booking->address2),
                        $this->uiRow('city', __('City'), $booking->city),
                        $this->uiRow('state', __('State/Province/Region'), $booking->state),
                        $this->uiRow('zip_code', __('ZIP code/Postal code'), $booking->zip_code),
                        $this->uiRow('country', __('Country'), $booking->country, get_country_name($booking->country)),
                        $this->uiRow('customer_notes', __('Special Requirements'), $booking->customer_notes),
                    ],
                ],
                'guests_information' => [
                    'label' => __('Guests Information'),
                    'guests' => $booking->passengers->map(function ($passenger) {
                        return [
                            'id' => $passenger->id,
                            'seat_type' => $passenger->seat_type,
                            'first_name' => $passenger->first_name,
                            'last_name' => $passenger->last_name,
                            'email' => $passenger->email,
                            'phone' => $passenger->phone,
                            'dob' => $passenger->dob,
                            'id_card' => $passenger->id_card,
                            'price' => $this->moneyValue((float) $passenger->price),
                            'meta' => $passenger->meta,
                        ];
                    })->values(),
                ],
                'booking_note' => [
                    'label' => __('Booking Note'),
                    'note' => $booking->getMeta('note_for_vendor'),
                ],
            ],
        ];
    }

    protected function bookingLineItems(Booking $booking): array
    {
        $items = [];

        if ($booking->object_model === 'hotel') {
            $rooms = HotelRoomBooking::query()
                ->where('booking_id', $booking->id)
                ->with('room')
                ->get();

            foreach ($rooms as $roomBooking) {
                $quantity = max(1, (int) $roomBooking->number);
                $amount = (float) $roomBooking->price * $quantity;
                $items[] = [
                    'type' => 'room',
                    'label' => trim(($roomBooking->room->title ?? __('Room')) . ' * ' . $quantity),
                    'quantity' => $quantity,
                    'unit_price' => $this->moneyValue((float) $roomBooking->price),
                    'amount' => $this->moneyValue($amount),
                ];
            }
        }

        foreach ((array) ($booking->getJsonMeta('extra_price') ?: []) as $extra) {
            $items[] = [
                'type' => 'extra',
                'label' => $extra['name_' . app()->getLocale()] ?? $extra['name'] ?? __('Extra price'),
                'amount' => $this->moneyValue((float) ($extra['total'] ?? 0)),
            ];
        }

        $buyerFees = json_decode($booking->buyer_fees ?: '[]', true);
        $fees = array_merge(is_array($buyerFees) ? $buyerFees : [], (array) ($booking->vendor_service_fee ?: []));
        foreach ($fees as $fee) {
            $unitAmount = (float) ($fee['price'] ?? 0);
            if (($fee['unit'] ?? null) === 'percent') {
                $unitAmount = ((float) $booking->total_before_fees / 100) * $unitAmount;
            }
            $quantity = (($fee['per_person'] ?? null) === 'on') ? max(1, (int) $booking->total_guests) : 1;
            $items[] = [
                'type' => 'fee',
                'label' => $fee['name_' . app()->getLocale()] ?? $fee['name'] ?? __('Service fee'),
                'description' => $fee['desc_' . app()->getLocale()] ?? $fee['desc'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $this->moneyValue($unitAmount),
                'amount' => $this->moneyValue($unitAmount * $quantity),
            ];
        }

        return $items;
    }

    protected function uiRow(string $key, string $label, $value, $displayValue = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'display_value' => $displayValue ?? $value,
        ];
    }

    protected function moneyValue(float $amount): array
    {
        return [
            'amount' => $amount,
            'formatted' => format_money_main($amount),
        ];
    }

    public function index(Request $request)
    {
        $this->checkPermission('booking_view');
        $query = Booking::where('status', '!=', 'draft');
        if (!empty($request->s)) {
            if( is_numeric($request->s) ){
                $query->Where('id', '=', $request->s);
            }else{
                $query->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', '%' . $request->s . '%')
                        ->orWhere('last_name', 'like', '%' . $request->s . '%')
                        ->orWhere('email', 'like', '%' . $request->s . '%')
                        ->orWhere('phone', 'like', '%' . $request->s . '%')
                        ->orWhere('address', 'like', '%' . $request->s . '%')
                        ->orWhere('address2', 'like', '%' . $request->s . '%');
                });
            }
        }
        if ($this->hasPermission('booking_manage_others')) {
            if (!empty($request->vendor_id)) {
                $query->where('vendor_id', $request->vendor_id);
            }
        } else {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $query->orderBy('id','desc');
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $data = [
            'rows'                  => $query->with(['vendor'])->paginate($perPage),
            'page_title'            => __("All Bookings"),
            'booking_manage_others' => $this->hasPermission('booking_manage_others'),
            'booking_update'        => $this->hasPermission('booking_update'),
            'statues'               => config('booking.statuses')
        ];
        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
            $bookings = $this->appendServiceNames($rows->getCollection());
            return response()->json([
                'data' => $bookings->values(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'booking_manage_others' => $data['booking_manage_others'],
                'booking_update'        => $data['booking_update'],
                'statuses'              => $data['statues'],
            ]);
        }
        return view('Booking::admin.booking.index', $data);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('booking_view');
        $query = Booking::query()->where('id', $id)->where('status', '!=', 'draft');
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $booking = $query->with(['vendor', 'passengers'])->first();
        if (empty($booking)) {
            return response()->json(['message' => __('Not found')], 404);
        }
        $this->appendServiceNames(collect([$booking]));
        return response()->json([
            'data' => $booking,
            'ui' => $this->bookingDetailUi($booking),
            'statuses'       => config('booking.statuses'),
            'booking_update' => $this->hasPermission('booking_update'),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->hasPermission('booking_manage_others')) {
            $this->checkPermission('booking_update');
        }
        $query = Booking::query()->where('id', $id)->where('status', '!=', 'draft');
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $booking = $query->first();
        if (empty($booking)) {
            return response()->json(['message' => __('Not found')], 404);
        }
        $previousStatus = $booking->status;
        $validated = $request->validate([
            'status'       => ['required', Rule::in(config('booking.statuses'))],
            'object_model' => ['required', Rule::in(array_keys(get_bookable_services()))],
            'object_id'    => ['required', 'integer'],
        ]);
        $booking->update([
            'status'       => $validated['status'],
            'object_model' => $validated['object_model'],
            'object_id'    => $validated['object_id'],
        ]);
        if ($validated['status'] == Booking::CANCELLED && $previousStatus !== Booking::CANCELLED) {
            $booking->tryRefundToWallet();
        }
        event(new BookingUpdatedEvent($booking));
        $booking = $booking->fresh(['vendor']);
        $this->appendServiceNames(collect([$booking]));
        return response()->json([
            'message' => __('Booking updated successfully'),
            'data'    => $booking,
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('No items selected')], 422);
            }
            return redirect()->back()->with('error', __('No items selected'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select action')], 422);
            }
            return redirect()->back()->with('error', __('Please select action'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Booking::where("id", $id);
                if (!$this->hasPermission('booking_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                }
                $row = $query->first();
                if(!empty($row)){
                    $row->delete();
                    event(new BookingUpdatedEvent($row));

                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Booking::where("id", $id);
                if (!$this->hasPermission('booking_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                    $this->checkPermission('booking_update');
                }
                $item = $query->first();
                if(!empty($item)){
                    $item->status = $action;
                    $item->save();

                    if($action == Booking::CANCELLED) $item->tryRefundToWallet();
                    event(new BookingUpdatedEvent($item));
                }
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success')]);
        }
        return redirect()->back()->with('success', __('Update success'));
    }

    public function email_preview(Request $request, $id)
    {
        $this->checkPermission('booking_view');
        $query = Booking::query()->where('id', $id);
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        }
        $booking = $query->first();

        if (empty($booking)) {
            if ($this->isApiRequest($request) || $request->wantsJson() || $request->query('format') === 'json') {
                return response()->json(['message' => __('Booking not found')], 404);
            }
            abort(404);
        }

        $to = $request->input('to', 'admin');
        $mailable = new NewBookingEmail($booking, $to);
        $mailable->build();
        $html = $mailable->render();
        $subject = $mailable->subject ?? __('[:site_name] Booking Email Preview', ['site_name' => setting_item('site_title')]);

        if ($this->isApiRequest($request) || $request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'data' => [
                    'id'      => (int) $booking->id,
                    'to'      => $to,
                    'subject' => $subject,
                    'html'    => $html,
                    'booking' => [
                        'id'         => (int) $booking->id,
                        'code'       => $booking->code,
                        'status'     => $booking->status,
                        'first_name' => $booking->first_name,
                        'last_name'  => $booking->last_name,
                        'email'      => $booking->email,
                        'phone'      => $booking->phone,
                        'total'      => (float) $booking->total,
                    ],
                ]
            ]);
        }

        return $html;
    }

    // =========================================================================
    // BOOKING HISTORY  –  GET /api-admin/booking/history
    // =========================================================================

    /**
     * Vendor's full booking history with rich filters.
     *
     * Query params:
     *   s            – search by name / email / phone / code
     *   status       – filter by booking status
     *   object_model – service type (hotel, car, tour …)
     *   date_from    – booking created_at >= YYYY-MM-DD
     *   date_to      – booking created_at <= YYYY-MM-DD
     *   start_date   – service start_date >= YYYY-MM-DD
     *   end_date     – service end_date   <= YYYY-MM-DD
     *   per_page     – items per page (max 100, default 20)
     */
    public function history(Request $request)
    {
        $this->checkPermission('booking_view');

        $query = Booking::where('status', '!=', 'draft')
            ->whereIn('object_model', array_keys(get_bookable_services()));

        // Vendor scope
        if (!$this->hasPermission('booking_manage_others')) {
            $query->where('vendor_id', Auth::id());
        } elseif ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->query('vendor_id'));
        }

        // Search
        if ($s = $request->query('s')) {
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', '%' . $s . '%')
                  ->orWhere('last_name',  'like', '%' . $s . '%')
                  ->orWhere('email',      'like', '%' . $s . '%')
                  ->orWhere('phone',      'like', '%' . $s . '%')
                  ->orWhere('code',       'like', '%' . $s . '%');
            });
        }

        // Filters
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($model = $request->query('object_model')) {
            $query->where('object_model', $model);
        }
        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($start = $request->query('start_date')) {
            $query->whereDate('start_date', '>=', $start);
        }
        if ($end = $request->query('end_date')) {
            $query->whereDate('end_date', '<=', $end);
        }

        $query->orderBy('id', 'desc');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $rows    = $query->with(['vendor'])->paginate($perPage);
        $bookings = $this->appendServiceNames($rows->getCollection());

        return response()->json([
            'data' => $bookings->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
            'filters' => [
                'statuses'       => config('booking.statuses'),
                'service_types'  => array_keys(get_bookable_services()),
            ],
        ]);
    }

    // =========================================================================
    // BOOKING REPORT  –  GET /api-admin/booking/report
    // =========================================================================

    /**
     * Booking summary / stats for the vendor (or all vendors for admin).
     *
     * Query params:
     *   date_from    – YYYY-MM-DD  (default: start of current month)
     *   date_to      – YYYY-MM-DD  (default: today)
     *   object_model – filter by service type
     *   group_by     – "day" | "month" (default: day)
     */
    public function report(Request $request)
    {
        $this->checkPermission('booking_view');

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->query('date_to',   now()->toDateString());

        $base = Booking::where('status', '!=', 'draft')
            ->whereIn('object_model', array_keys(get_bookable_services()))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        // Vendor scope
        if (!$this->hasPermission('booking_manage_others')) {
            $base->where('vendor_id', Auth::id());
        } elseif ($request->filled('vendor_id')) {
            $base->where('vendor_id', $request->query('vendor_id'));
        }

        if ($model = $request->query('object_model')) {
            $base->where('object_model', $model);
        }

        // ── Summary totals ────────────────────────────────────────────────────
        $summary = (clone $base)->selectRaw('
            COUNT(*)                                          AS total_bookings,
            SUM(CASE WHEN status = "completed"  THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = "confirmed"  THEN 1 ELSE 0 END) AS confirmed,
            SUM(CASE WHEN status = "cancelled"  THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = "pending"    THEN 1 ELSE 0 END) AS pending,
            COALESCE(SUM(total), 0)                           AS total_revenue,
            COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END), 0) AS confirmed_revenue
        ')->first();

        // ── By service type ───────────────────────────────────────────────────
        $byService = (clone $base)->selectRaw('
            object_model,
            COUNT(*)          AS total_bookings,
            COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END), 0) AS revenue
        ')->groupBy('object_model')->get();

        // ── By status ─────────────────────────────────────────────────────────
        $byStatus = (clone $base)->selectRaw('
            status,
            COUNT(*) AS total_bookings,
            COALESCE(SUM(total), 0) AS revenue
        ')->groupBy('status')->get();

        // ── Timeline (daily or monthly) ───────────────────────────────────────
        $groupBy  = $request->query('group_by', 'day');
        $dateExpr = $groupBy === 'month'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "DATE(created_at)";

        $timeline = (clone $base)->selectRaw("
            {$dateExpr}              AS period,
            COUNT(*)                 AS total_bookings,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END), 0) AS revenue
        ")->groupByRaw($dateExpr)->orderByRaw($dateExpr)->get();

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_bookings'    => (int) $summary->total_bookings,
                'completed'         => (int) $summary->completed,
                'confirmed'         => (int) $summary->confirmed,
                'cancelled'         => (int) $summary->cancelled,
                'pending'           => (int) $summary->pending,
                'total_revenue'     => (float) $summary->total_revenue,
                'confirmed_revenue' => (float) $summary->confirmed_revenue,
            ],
            'by_service' => $byService,
            'by_status'  => $byStatus,
            'timeline'   => $timeline,
        ]);
    }
}
