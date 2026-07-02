<?php
namespace Modules\Api\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Enquiry;
use Modules\Template\Models\Template;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class BookingController extends \Modules\Booking\Controllers\BookingController
{
    public function __construct(Booking $booking, Enquiry $enquiryClass)
    {
        parent::__construct($booking, $enquiryClass);
        $this->middleware('auth:sanctum')->except([
            'detail',
            'getConfigs',
            'getMapConfig',
            'getHomeLayout',
            'getTypes',
            'cancelPayment',
            'thankyou',
            // Guest booking / enquiry (no login; JSON from SPA / mobile)
            'addEnquiry',
            'addToCart',
            'doCheckout',
            'confirmPayment',
            'checkout',
            'checkStatusCheckout',
            'getGatewaysForApi',
            // Guest booking tracking (no auth — only code+email required)
            'guestLookup',
        ]);
    }
    public function getMapConfig(Request $request)
    {
        $data = [
            'map_provider' => setting_item('map_provider', 'google'),
            'map_gmap_key' => setting_item('map_gmap_key'),
        ];

        $etag = md5(serialize($data));

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        return $this->sendSuccess($data)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('ETag', $etag);
    }

    public function getTypes(){
        $types = get_bookable_services();

        $res = [];
        foreach ($types as $type=>$class) {
            $obj = new $class();
            $res[$type] = [
                'icon'=>call_user_func([$obj,'getServiceIconFeatured']),
                'name'=>call_user_func([$obj,'getModelName']),
                'search_fields'=>[

                ],
            ];
        }
        return $res;
    }

    public function getConfigs(){
        $cacheKey = sprintf(
            'api:configs:%s:%s',
            session('website_locale', app()->getLocale()),
            \App\Currency::getCurrent('currency_main')
        );

        $res = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $languages = \Modules\Language\Models\Language::getActive();

            $socialLogins = [];
            if (setting_item('facebook_client_id') && setting_item('facebook_client_secret')) {
                $socialLogins['facebook'] = [
                    'client_id' => setting_item('facebook_client_id'),
                ];
            }
            if (setting_item('google_client_id') && setting_item('google_client_secret')) {
                $socialLogins['google'] = [
                    'client_id' => setting_item('google_client_id'),
                ];
            }

            // x
            if (setting_item('twitter_client_id') && setting_item('twitter_client_secret')) {
                $socialLogins['twitter'] = [
                    'client_id' => setting_item('twitter_client_id'),
                ];
            }
            // apple
            if (setting_item('apple_client_id') && setting_item('apple_client_secret')) {
                $socialLogins['apple'] = [
                    'client_id' => setting_item('apple_client_id'),
                ];
            }

            $res = [
                'languages' => $languages->map(function ($lang) {
                    return $lang->only(['locale', 'name']);
                }),
                'booking_types' => $this->getTypes(),
                'is_enable_guest_checkout' => (int) is_enable_guest_checkout(),
                'service_search_forms' => [],
                'locale' => session('website_locale', app()->getLocale()),
                'currency_main' => \App\Currency::getCurrent('currency_main'),
                'currency' => $this->getCurrency(),
                'social_login' => $socialLogins,
            ];
            $all_service = get_bookable_services();
            foreach ($all_service as $key => $class) {
                $res['service_search_forms'][$key] = call_user_func([$class, 'getFormSearch'], request());
            }

            return $res;
        });

        return $this->sendSuccess($res);
    }

    protected function validateCheckout($code){

        $booking = $this->booking::where('code', $code)->first();

        $this->bookingInst = $booking;

        if (empty($booking)) {
            abort(404);
        }

        return true;
    }

    public function detail(Request $request, $code)
    {

        $booking = Booking::where('code', $code)->first();
        if (empty($booking)) {
            return $this->sendError(__("Booking not found!"))->setStatusCode(404);
        }

        if ($booking->status == 'draft') {
            return $this->sendError(__("You do not have permission to access"))->setStatusCode(404);
        }
        $data = [
            'booking'    => $booking,
            'service'    => $booking->service,
        ];
        if ($booking->gateway) {
            $data['gateway'] = get_payment_gateway_obj($booking->gateway);
        }
        return $this->sendSuccess(
            $data
        );
    }

    protected function validateDoCheckout(){

        $request = \request();
        /**
         * @param Booking $booking
         */
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('', ['errors' => $validator->errors()]);
        }
        $code = $request->input('code');
        $booking = $this->booking::where('code', $code)->first();
        $this->bookingInst = $booking;

        if (empty($booking)) {
            abort(404);
        }

        return true;
    }

    public function checkStatusCheckout($code)
    {
        $booking = $this->booking::where('code', $code)->first();
        $data = [
            'error'    => false,
            'message'  => '',
            'redirect' => ''
        ];
        if (empty($booking)) {
            $data = [
                'error'    => true,
                'redirect' => url('/')
            ];
        }

        if ($booking->status != 'draft') {
            $data = [
                'error'    => true,
                'redirect' => url('/')
            ];
        }
        return response()->json($data, 200);
    }

    public function getGatewaysForApi(){
        $res = [];
        $gateways = get_available_gateways();
        foreach ($gateways as $gateway=>$obj){
            $displayName = $obj->getDisplayName();
            if (is_string($displayName)) {
                $decodedName = json_decode($displayName, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedName)) {
                    $displayName = (string) ($decodedName[app()->getLocale()] ?? $decodedName['en'] ?? reset($decodedName) ?? $displayName);
                }
            }

            $res[$gateway] = [
                'logo'=>$obj->getDisplayLogo(),
                'name'=>$displayName,
                'desc'=>$obj->getApiDisplayHtml(),
            ];
            if($option = $obj->getForm()){
                $res[$gateway]['form'] = $option;
            }
            if($options = $obj->getApiOptions()){
                $res[$gateway]['options'] = $options;
            }
        }

        return $this->sendSuccess($res);
    }

    public function thankyou(Request $request, $code)
    {

        $booking = Booking::where('code', $code)->first();
        if (empty($booking)) {
            abort(404);
        }

        if ($booking->status == 'draft') {
            return redirect($booking->getCheckoutUrl());
        }

        $data = [
            'page_title' => __('Booking Details'),
            'booking'    => $booking,
            'service'    => $booking->service,
        ];
        if ($booking->gateway) {
            $data['gateway'] = get_payment_gateway_obj($booking->gateway);
        }
        return view('Booking::frontend/detail', $data);
    }

    public function getCurrency(){
        $list = \App\Currency::getActiveCurrency();
        foreach ($list as &$item)
        {
            $currency = \App\Currency::getCurrency($item['currency_main']);
            $item['symbol'] = $currency['symbol'];
        }
        return $list;
    }

    /**
     * GET /api/booking/guest-lookup?code=XXX&email=guest@example.com
     * Allow a guest to retrieve their booking status using the booking code + email.
     * No authentication required.
     */
    public function guestLookup(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'code'  => 'required|string',
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return $this->sendError('', ['errors' => $validator->errors()]);
        }

        $booking = Booking::where('code', $request->input('code'))
            ->where('email', $request->input('email'))
            ->whereNotIn('status', ['draft'])
            ->first();

        if (empty($booking)) {
            return $this->sendError(__('Booking not found. Please check your booking code and email.'))->setStatusCode(404);
        }

        return $this->sendSuccess([
            'booking' => $booking->only([
                'code', 'status', 'first_name', 'last_name', 'email',
                'total', 'currency', 'start_date', 'end_date',
                'object_model', 'created_at',
            ]),
            'service' => $booking->service ? [
                'title' => $booking->service->title ?? null,
                'id'    => $booking->service->id ?? null,
            ] : null,
        ]);
    }

    /**
     * POST /api/booking/claim-guest-bookings
     * Assign all previous guest bookings (matching the authenticated user's email) to the account.
     * Requires auth. Call this after a guest registers or logs in.
     */
    public function claimGuestBookings(Request $request)
    {
        $user  = auth()->user();
        $count = $this->claimGuestBookingsByEmail($user->email, $user->id);

        return $this->sendSuccess([
            'claimed' => $count,
            'message' => $count > 0
                ? __(':count booking(s) have been linked to your account.', ['count' => $count])
                : __('No unassigned bookings found for your email address.'),
        ]);
    }
}
