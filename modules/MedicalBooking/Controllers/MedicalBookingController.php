<?php

namespace Modules\MedicalBooking\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Hospital\Models\Hospital;
use Modules\MedicalBooking\Models\MedicalBooking;

class MedicalBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:20,1')->only('store');
        $this->middleware('throttle:10,1')->only('initiatePayment');
    }

    // =========================================================================
    // META & GATEWAYS
    // =========================================================================

    /**
     * GET /api/medical-booking/meta
     */
    public function meta()
    {
        return response()->json([
            'request_types'   => MedicalBooking::getRequestTypes(),
            'priority_levels' => MedicalBooking::getPriorityLevels(),
        ]);
    }

    /**
     * GET /api/medical-booking/gateways
     */
    public function gateways()
    {
        $result = [];
        foreach (get_available_gateways() as $key => $gw) {
            $result[] = [
                'id'         => $key,
                'name'       => $gw->getDisplayName(),
                'is_offline' => $gw->is_offline ?? false,
                'logo'       => $gw->getDisplayLogo() ?? null,
                'html'       => $gw->getApiDisplayHtml() ?? null,
            ];
        }
        return response()->json(['data' => $result]);
    }

    // =========================================================================
    // SUBMIT BOOKING
    // =========================================================================

    /**
     * POST /api/medical-booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'phone'               => 'required|string|max:50',
            'nationality'         => 'nullable|string|max:100',
            'passport_number'     => 'nullable|string|max:100',
            'date_of_birth'       => 'nullable|date',
            'request_type'        => 'required|in:medical_support,urgent_assistance,medical_centre_contact,travel_emergency',
            'priority'            => 'nullable|in:normal,urgent,emergency',
            'description'         => 'required|string',
            'preferred_date'      => 'nullable|date',
            'preferred_time'      => 'nullable|string|max:50',
            'location'            => 'nullable|string|max:255',
            'medical_centre_name' => 'nullable|string|max:255',
            'hospital_id'         => 'nullable|integer|exists:bc_hospitals,id',
        ]);

        $booking                 = new MedicalBooking();
        $booking->fill($validated);
        $booking->status         = 'pending';
        $booking->priority       = $validated['priority'] ?? 'normal';
        $booking->booking_code   = MedicalBooking::generateBookingCode();
        $booking->payment_status = 'unpaid';

        // Auto-fill fee and currency from the hospital's booking_amount if provided
        if (!empty($validated['hospital_id'])) {
            $hospital = Hospital::find($validated['hospital_id']);
            if ($hospital && $hospital->booking_amount > 0) {
                $booking->fee      = $hospital->booking_amount;
                $booking->currency = $hospital->currency ?? 'ETB';
            } else {
                $booking->fee      = 0;
                $booking->currency = 'ETB';
            }
        } else {
            $booking->fee      = 0;
            $booking->currency = 'ETB';
        }

        $booking->save();

        return response()->json([
            'status'  => 1,
            'message' => __('Your medical booking request has been received. We will contact you shortly.'),
            'data'    => [
                'id'             => $booking->id,
                'booking_code'   => $booking->booking_code,
                'request_type'   => $booking->request_type,
                'priority'       => $booking->priority,
                'status'         => $booking->status,
                'fee'            => $booking->fee,
                'currency'       => $booking->currency,
                'payment_status' => $booking->payment_status,
            ],
        ], 201);
    }

    // =========================================================================
    // PAYMENT STATUS CHECK
    // =========================================================================

    /**
     * GET /api/medical-booking/{id}/payment-status?email=xxx
     */
    public function paymentStatus(Request $request, $id)
    {
        $request->validate(['email' => 'required|email']);

        $booking = MedicalBooking::where('id', $id)
            ->where('email', $request->query('email'))
            ->first();

        if (!$booking) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        return response()->json([
            'data' => [
                'id'              => $booking->id,
                'booking_code'    => $booking->booking_code,
                'status'          => $booking->status,
                'fee'             => $booking->fee,
                'currency'        => $booking->currency,
                'payment_status'  => $booking->payment_status,
                'payment_gateway' => $booking->payment_gateway,
                'paid_at'         => $booking->paid_at,
            ],
        ]);
    }

    // =========================================================================
    // INITIATE PAYMENT
    // =========================================================================

    /**
     * POST /api/medical-booking/{id}/pay
     *
     * Body: { email, gateway }
     *   email   — verified against booking (no auth)
     *   gateway — e.g. "chapa", "offline_payment"
     */
    public function initiatePayment(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email',
            'gateway'  => 'required|string',
            'amount'   => 'nullable|numeric|min:1',
            'currency' => 'nullable|string|max:10',
        ]);

        $booking = MedicalBooking::where('id', $id)
            ->where('email', $request->input('email'))
            ->first();

        if (!$booking) {
            return response()->json(['message' => __('Booking not found or email mismatch')], 404);
        }
        if ($booking->payment_status === 'paid') {
            return response()->json(['message' => __('This booking is already paid')], 422);
        }

        // Allow caller to supply amount if admin hasn't set a fee yet
        if ($request->filled('amount')) {
            $booking->fee = (float) $request->input('amount');
        }
        if ($request->filled('currency')) {
            $booking->currency = strtoupper($request->input('currency'));
        }

        if (empty($booking->fee) || $booking->fee <= 0) {
            return response()->json(['message' => __('Payment amount is required. Pass "amount" in the request or ask admin to set a fee.')], 422);
        }

        $gatewayId   = $request->input('gateway');
        $gatewayInst = $this->findGateway($gatewayId);

        if (!$gatewayInst) {
            return response()->json(['message' => __('Payment gateway not available')], 422);
        }

        $booking->payment_gateway = $gatewayId;
        $booking->payment_status  = 'pending';
        $booking->save();

        // Chapa
        if ($gatewayId === 'chapa') {
            return $this->processChapa($gatewayInst, $booking);
        }

        // Offline payment
        if ($gatewayInst->is_offline) {
            return response()->json([
                'status'       => 1,
                'payment_type' => 'offline',
                'message'      => __('Payment request recorded. Please follow the instructions.'),
                'payment_note' => $gatewayInst->getOption('payment_note') ?: __('Please transfer the amount and send proof of payment.'),
                'gateway_html' => $gatewayInst->getOption('html') ?: null,
                'data'         => $this->paymentSummary($booking),
            ]);
        }

        return response()->json(['message' => __('Gateway not supported for medical bookings')], 422);
    }

    // =========================================================================
    // CHAPA — RETURN URL (user redirected back after paying)
    // =========================================================================

    /**
     * GET /api/medical-booking/payment/confirm/chapa
     *   ?booking_code=MB-XXXXXXXX&tx_ref=MB-XXXXXXXX-1234567890
     */
    public function confirmChapa(Request $request)
    {
        $bookingCode = $request->query('booking_code');
        $txRef       = $request->query('tx_ref') ?: $request->query('trx_ref');

        $booking = MedicalBooking::where('booking_code', $bookingCode)->first();
        if (!$booking) {
            return response()->json(['message' => __('Booking not found')], 404);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'status'  => 1,
                'message' => __('Already paid.'),
                'data'    => $this->paymentSummary($booking),
            ]);
        }

        if (empty($txRef)) {
            $txRef = $booking->payment_reference;
        }

        $gatewayInst = $this->findGateway('chapa');
        if (!$gatewayInst || empty($txRef)) {
            return response()->json(['message' => __('Cannot verify payment — missing reference.')], 422);
        }

        $verification = $this->chapaVerify($gatewayInst, $txRef);
        $success      = $this->chapaIsSuccess($verification);

        if ($success) {
            $booking->payment_status    = 'paid';
            $booking->paid_at           = Carbon::now();
            $booking->payment_reference = $txRef;
            $booking->save();

            return response()->json([
                'status'  => 1,
                'message' => __('Payment confirmed. Thank you!'),
                'data'    => $this->paymentSummary($booking),
            ]);
        }

        $booking->payment_status = 'failed';
        $booking->save();

        return response()->json([
            'status'  => 0,
            'message' => __('Payment verification failed. Please try again or contact support.'),
            'data'    => $this->paymentSummary($booking),
        ], 400);
    }

    // =========================================================================
    // CHAPA — WEBHOOK (Chapa POSTs here automatically after payment)
    // =========================================================================

    /**
     * POST /api/medical-booking/payment/webhook/chapa
     * Chapa webhook — no CSRF, no auth.
     */
    public function webhookChapa(Request $request)
    {
        $txRef = $this->extractTxRef($request);

        if (empty($txRef)) {
            Log::warning('Chapa medical webhook: missing tx_ref', $request->all());
            return response()->json(['status' => 'error', 'message' => 'tx_ref missing'], 400);
        }

        $booking = MedicalBooking::where('payment_reference', $txRef)->first();
        if (!$booking) {
            // Fallback: try booking_code from meta
            $bookingCode = $request->input('meta.booking_code')
                ?: $request->input('data.meta.booking_code')
                ?: $request->input('booking_code');
            if ($bookingCode) {
                $booking = MedicalBooking::where('booking_code', $bookingCode)->first();
            }
        }

        if (!$booking) {
            Log::warning('Chapa medical webhook: booking not found', ['tx_ref' => $txRef]);
            return response()->json(['status' => 'error', 'message' => 'Booking not found'], 404);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['status' => 'success', 'message' => 'Already processed']);
        }

        $gatewayInst  = $this->findGateway('chapa');
        $verification = $gatewayInst ? $this->chapaVerify($gatewayInst, $txRef) : [];
        $success      = $this->chapaIsSuccess($verification);

        Log::info('Chapa medical webhook', [
            'tx_ref'     => $txRef,
            'booking_id' => $booking->id,
            'success'    => $success,
        ]);

        if ($success) {
            $booking->payment_status    = 'paid';
            $booking->paid_at           = Carbon::now();
            $booking->payment_reference = $txRef;
            $booking->save();
            return response()->json(['status' => 'success', 'message' => 'Payment processed']);
        }

        $booking->payment_status = 'failed';
        $booking->save();
        return response()->json(['status' => 'error', 'message' => 'Verification failed'], 400);
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    /**
     * GET /api/medical-booking/payment/cancel/chapa?booking_code=MB-XXXXXXXX
     */
    public function cancelPayment(Request $request, $gateway)
    {
        $bookingCode = $request->query('booking_code');
        $booking     = MedicalBooking::where('booking_code', $bookingCode)->first();

        if ($booking && $booking->payment_status === 'pending') {
            $booking->payment_status = 'unpaid';
            $booking->save();
        }

        return response()->json([
            'status'  => 0,
            'message' => __('Payment was cancelled. You can try again.'),
            'data'    => ['booking_code' => $bookingCode],
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function processChapa($gatewayInst, MedicalBooking $booking)
    {
        $secretKey = trim((string) $gatewayInst->getOption('secret_key'));
        if (empty($secretKey)) {
            return response()->json(['message' => __('Chapa is not configured. Please contact support.')], 500);
        }

        $nameParts = explode(' ', trim($booking->name), 2);
        $firstName = $nameParts[0] ?? $booking->name;
        $lastName  = $nameParts[1] ?? '';

        $mainCurrency = strtoupper((string) setting_item('currency_main', 'ETB'));
        $currency = strtoupper((string) ($gatewayInst->getOption('currency') ?: $mainCurrency));
        $chargeAmount = (float) $booking->fee;
        if ($currency !== $mainCurrency && method_exists($gatewayInst, 'convertAmount')) {
            try {
                $chargeAmount = $gatewayInst->convertAmount($chargeAmount, $mainCurrency, $currency);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        }
        $txRef    = 'MB-' . $booking->booking_code . '-' . time();

        $returnUrl   = url('/api/medical-booking/payment/confirm/chapa?booking_code=' . $booking->booking_code . '&tx_ref=' . $txRef);
        $cancelUrl   = url('/api/medical-booking/payment/cancel/chapa?booking_code=' . $booking->booking_code);
        $webhookUrl  = url('/api/medical-booking/payment/webhook/chapa');

        $descRaw   = 'Medical Booking ' . $booking->booking_code;
        $descClean = mb_substr(preg_replace('/[^A-Za-z0-9\-_. ]+/', '', $descRaw), 0, 50);

        $payload = [
            'amount'       => number_format($chargeAmount, 2, '.', ''),
            'currency'     => $currency,
            'email'        => $booking->email,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone_number' => $booking->phone ?? '',
            'tx_ref'       => $txRef,
            'callback_url' => $webhookUrl,
            'return_url'   => $returnUrl,
            'customization' => [
                'title'       => 'Medical Booking',
                'description' => $descClean,
            ],
            'meta' => [
                'booking_code' => $booking->booking_code,
                'booking_id'   => (string) $booking->id,
            ],
        ];

        $baseUrl = $this->chapaBaseUrl($gatewayInst);
        $timeout = (int) max(5, (int) $gatewayInst->getOption('timeout', 30));

        $response = Http::timeout($timeout)
            ->withoutVerifying()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type'  => 'application/json',
            ])
            ->post($baseUrl . '/v1/transaction/initialize', $payload);

        $json        = $response->json();
        $checkoutUrl = data_get($json, 'data.checkout_url');

        if (!$response->successful() || empty($checkoutUrl)) {
            $booking->payment_status = 'failed';
            $booking->save();

            Log::error('Chapa medical booking init failed', ['booking_id' => $booking->id, 'response' => $json]);

            $errMsg = $this->flattenMessage(data_get($json, 'message', 'Unable to initialize Chapa payment'));
            return response()->json(['message' => $errMsg], 500);
        }

        $booking->payment_reference = $txRef;
        $booking->save();

        return response()->json([
            'status'       => 1,
            'payment_type' => 'redirect',
            'payment_url'  => $checkoutUrl,
            'tx_ref'       => $txRef,
            'data'         => $this->paymentSummary($booking),
        ]);
    }

    private function chapaVerify($gatewayInst, string $txRef): array
    {
        $secretKey = trim((string) $gatewayInst->getOption('secret_key'));
        $baseUrl   = $this->chapaBaseUrl($gatewayInst);
        $timeout   = (int) max(5, (int) $gatewayInst->getOption('timeout', 30));

        try {
            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type'  => 'application/json',
                ])
                ->get($baseUrl . '/v1/transaction/verify/' . urlencode($txRef));

            return (array) $response->json();
        } catch (\Throwable $e) {
            Log::warning('Chapa medical verify failed', ['tx_ref' => $txRef, 'error' => $e->getMessage()]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    private function chapaIsSuccess(array $verification): bool
    {
        $top     = strtolower((string) data_get($verification, 'status'));
        $data    = strtolower((string) data_get($verification, 'data.status'));
        $payment = strtolower((string) data_get($verification, 'data.payment_status'));
        $tx      = strtolower((string) data_get($verification, 'data.tx_status'));

        $ok     = in_array($top, ['success', 'successful'], true);
        $states = ['success', 'successful', 'completed', 'paid'];

        $nested    = in_array($data, $states, true) || in_array($payment, $states, true) || in_array($tx, $states, true);
        $noNested  = $data === '' && $payment === '' && $tx === '';

        return $ok && ($nested || $noNested);
    }

    private function chapaBaseUrl($gatewayInst): string
    {
        if ($gatewayInst->getOption('test')) {
            return rtrim((string) $gatewayInst->getOption('test_base_url', 'https://api.chapa.co'), '/');
        }
        return rtrim((string) $gatewayInst->getOption('live_base_url', 'https://api.chapa.co'), '/');
    }

    private function extractTxRef(Request $request): string
    {
        return trim((string) (
            $request->input('tx_ref')
            ?: $request->query('tx_ref')
            ?: $request->input('trx_ref')
            ?: $request->query('trx_ref')
            ?: $request->input('reference')
            ?: $request->input('data.tx_ref')
            ?: $request->input('data.trx_ref')
            ?: $request->input('meta.tx_ref')
            ?: ''
        ));
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

    private function paymentSummary(MedicalBooking $booking): array
    {
        return [
            'id'              => $booking->id,
            'booking_code'    => $booking->booking_code,
            'status'          => $booking->status,
            'fee'             => $booking->fee,
            'currency'        => $booking->currency,
            'payment_status'  => $booking->payment_status,
            'payment_gateway' => $booking->payment_gateway,
            'paid_at'         => $booking->paid_at,
        ];
    }

    private function flattenMessage($message): string
    {
        if (is_string($message) || is_numeric($message)) {
            return (string) $message;
        }
        if (is_array($message)) {
            $flat = [];
            array_walk_recursive($message, static fn($v) => $flat[] = (string) $v);
            return implode(' ', $flat) ?: __('Payment failed');
        }
        return __('Payment failed');
    }
}
