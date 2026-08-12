<?php

namespace Modules\Booking\Gateways;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
use Modules\Booking\Models\PaymentMeta;

class ChapaGateway extends BaseGateway
{
    public $name = 'Chapa';

    public function getOptionsConfigs()
    {
        return [
            [
                'type' => 'checkbox',
                'id' => 'enable',
                'label' => __('Enable Chapa gateway?'),
            ],
            [
                'type' => 'input',
                'id' => 'name',
                'label' => __('Custom Name'),
                'std' => __('Chapa'),
                'multi_lang' => "1",
            ],
            [
                'type' => 'upload',
                'id' => 'logo_id',
                'label' => __('Custom Logo'),
            ],
            [
                'type' => 'editor',
                'id' => 'html',
                'label' => __('Custom HTML Description'),
                'multi_lang' => "1",
            ],
            [
                'type' => 'checkbox',
                'id' => 'test',
                'label' => __('Enable Sandbox Mode?'),
            ],
            [
                'type' => 'input',
                'id' => 'secret_key',
                'label' => __('Secret Key'),
                'desc' => __('Use CHAPA-TEST-... for sandbox or CHAPA-... for live'),
            ],
            [
                'type' => 'input',
                'id' => 'currency',
                'label' => __('Alternate Currency'),
                'std' => 'ETB',
                'desc' => __('Only ETB and USD are supported. Set this to whichever of ETB/USD is NOT your site\'s Main Currency, to let customers choose it at checkout in addition to the Main Currency. Leave equal to the Main Currency to only ever charge in the Main Currency.'),
            ],
            [
                'type' => 'input',
                'input_type' => 'number',
                'id' => 'exchange_rate',
                'label' => __('Fallback Exchange Rate (ETB per 1 USD)'),
                'desc' => __('Optional emergency fallback. The current rate is fetched automatically by swapping 1 USD to ETB through Chapa and cached for 24 hours. Each refresh performs a real, irreversible 1 USD swap.'),
            ],
            [
                'type' => 'input',
                'id' => 'test_base_url',
                'label' => __('Sandbox API URL'),
                'std' => 'https://api.chapa.co',
                'condition' => 'g_chapa_test:is(1)',
            ],
            [
                'type' => 'input',
                'id' => 'live_base_url',
                'label' => __('Live API URL'),
                'std' => 'https://api.chapa.co',
                'condition' => 'g_chapa_test:is()',
            ],
            [
                'type' => 'input',
                'id' => 'timeout',
                'label' => __('HTTP Timeout (seconds)'),
                'std' => 30,
            ],
            [
                'type' => 'input',
                'id' => 'webhook_secret',
                'label' => __('Webhook Secret (optional)'),
                'desc' => __('Webhook URL: <code>:url</code>', ['url' => $this->getWebhookUrl()]),
            ],
            [
                'type' => 'select',
                'id' => 'split_override_type',
                'label' => __('Default Platform Commission Type'),
                'desc' => __('Optional. Overrides the subaccount\'s own split when initializing a transaction. Leave empty to use the per-subaccount default.'),
                'options' => [
                    'percentage' => __('Percentage (e.g. 0.03 = 3%)'),
                    'flat'       => __('Flat amount (ETB)'),
                ],
                'std' => '',
            ],
            [
                'type' => 'input',
                'id' => 'split_override_value',
                'label' => __('Default Platform Commission Value'),
                'desc' => __('Used together with the type above. Example: <code>0.05</code> for 5% or <code>25</code> for 25 ETB.'),
                'std' => '',
            ],
        ];
    }

    /**
     * Resolve the gateway id, even when the gateway was instantiated without one
     * (helpers/admin pages may call new ChapaGateway()).
     */
    public function getId(): string
    {
        return (string) ($this->id ?: 'chapa');
    }

    /**
     * Currencies a customer may choose at checkout: always the site's Main
     * Currency, plus the configured Alternate Currency when it differs from
     * the Main Currency and an exchange rate has been set for it.
     *
     * @return array<int,string>
     */
    public function getAvailableCurrencies(): array
    {
        $main = $this->getMainCurrency();
        $alternate = strtoupper(trim((string) $this->getOption('currency')));
        $canRetrieveRate = $this->getSecretKey() !== '' || (float) $this->getOption('exchange_rate') > 0;

        // Conversion (see convertAmount()) only understands the ETB/USD pair,
        // regardless of which of the two is configured as Main Currency — so
        // only offer the alternate when both currencies are actually ETB/USD.
        $isSupportedPair = in_array($main, ['ETB', 'USD'], true) && in_array($alternate, ['ETB', 'USD'], true);

        $currencies = [$main];
        if ($alternate !== '' && $alternate !== $main && $canRetrieveRate && $isSupportedPair) {
            $currencies[] = $alternate;
        }

        return $currencies;
    }

    /**
     * Convert an amount between ETB and USD. The configured Exchange Rate
     * always means "how many ETB equal 1 USD" — a fixed, real-world
     * convention that does NOT depend on which currency is the site's Main
     * Currency. Direction (multiply vs divide) is chosen from the actual
     * currency codes involved, never from their main/alternate role, so this
     * gives the correct result whether Main Currency is ETB or USD.
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if (!in_array($fromCurrency, ['ETB', 'USD'], true) || !in_array($toCurrency, ['ETB', 'USD'], true)) {
            throw new Exception(__("Chapa currency conversion only supports ETB and USD"));
        }

        $etbPerUsd = $this->getUsdToEtbRate();
        if ($etbPerUsd <= 0) {
            throw new Exception(__(
                "Unable to retrieve the Chapa exchange rate from :from to :to. Please contact site owner",
                ['from' => $fromCurrency, 'to' => $toCurrency]
            ));
        }

        if ($fromCurrency === 'USD' && $toCurrency === 'ETB') {
            return $amount * $etbPerUsd;
        }

        // ETB -> USD
        return $amount / $etbPerUsd;
    }

    /**
     * Return Chapa's ETB value for 1 USD.
     *
     * POST /v1/swap performs a real, irreversible conversion. Cache the rate
     * for 24 hours so checkout does not swap 1 USD on every request.
     */
    public function getUsdToEtbRate(): float
    {
        $fallbackRate = (float) $this->getOption('exchange_rate');
        $secretKey = $this->getSecretKey();

        if ($secretKey === '') {
            if ($fallbackRate > 0) {
                return $fallbackRate;
            }

            throw new Exception(__('Chapa secret key is required to retrieve the exchange rate.'));
        }

        $cacheKey = 'chapa_usd_etb_rate_' . md5($this->getBaseUrl() . '|' . $secretKey);

        try {
            return (float) Cache::remember($cacheKey, 86400, function () {
                $response = Http::timeout($this->getTimeout())
                    ->withOptions(['verify' => $this->getSslVerifyOption()])
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->getSecretKey(),
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->getBaseUrl() . '/v1/swap', [
                        'amount' => 1,
                        'from' => 'USD',
                        'to' => 'ETB',
                    ]);

                $body = (array) $response->json();
                $rate = (float) (
                    data_get($body, 'data.rate')
                    ?: data_get($body, 'data.exchange_rate')
                    ?: data_get($body, 'rate')
                );

                if (
                    !$response->successful()
                    || strtolower((string) data_get($body, 'status')) !== 'success'
                    || $rate <= 0
                ) {
                    throw new Exception($this->normalizeGatewayMessage(
                        data_get($body, 'message', __('Chapa did not return a valid USD to ETB rate.'))
                    ));
                }

                return $rate;
            });
        } catch (\Throwable $e) {
            Log::warning('Chapa swap rate request failed', ['error' => $e->getMessage()]);

            if ($fallbackRate > 0) {
                return $fallbackRate;
            }

            throw new Exception(__('Unable to retrieve the Chapa USD to ETB rate: :message', [
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Exposed to the frontend/API (see BaseGateway::getForm / Api\Controllers\BookingController::getGatewaysForApi)
     * so the checkout UI can render a currency picker when more than one
     * currency is available. Returns null when there's nothing to choose.
     */
    public function getForm()
    {
        $currencies = $this->getAvailableCurrencies();
        if (count($currencies) < 2) {
            return null;
        }

        $mainCurrency = $this->getMainCurrency();

        // "rates" lets the frontend compute a live preview per currency
        // without another request: displayed_amount = booking_main_amount * rates[currency].
        // The Main Currency always has rate 1 (no conversion).
        $rates = [];
        foreach ($currencies as $currency) {
            $rates[$currency] = $currency === $mainCurrency ? 1 : $this->convertAmount(1, $mainCurrency, $currency);
        }

        return [
            [
                'type' => 'radio',
                'id' => 'chapa_currency',
                'label' => __('Pay with'),
                'options' => array_combine($currencies, $currencies),
                'std' => $mainCurrency,
                'main_currency' => $mainCurrency,
                'rates' => $rates,
            ],
        ];
    }

    /**
     * Resolve the currency to actually charge for this transaction: the
     * customer's choice (if it's one of the currencies configured as
     * available), otherwise the gateway's configured default.
     */
    protected function resolveRequestedCurrency(Request $request): string
    {
        $requested = strtoupper(trim((string) $request->input('chapa_currency')));
        if ($requested !== '' && in_array($requested, $this->getAvailableCurrencies(), true)) {
            return $requested;
        }

        // Booking totals are stored in the site's main currency. API clients
        // are not required to send chapa_currency, so an omitted choice must
        // not silently switch the charge to the configured alternate currency.
        return $this->getMainCurrency();
    }

    public function process(Request $request, $booking, $service)
    {
        if (in_array($booking->status, [$booking::PAID, $booking::COMPLETED, $booking::CANCELLED], true)) {
            throw new Exception(__("Booking status does need to be paid"));
        }
        if (!$booking->pay_now) {
            throw new Exception(__("Booking total is zero. Can not process payment gateway!"));
        }

        $payment = new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id;
        $payment->status = 'draft';
        $payment->amount = (float) $booking->pay_now;
        $payment->save();

        $txRef = $this->buildBookingTxRef($booking, $payment);
        $currency = $this->resolveRequestedCurrency($request);
        $chargeAmount = $this->resolveChargeAmount((float) $booking->pay_now, $currency, $payment);

        $payload = [
            'amount' => $this->formatAmount($chargeAmount),
            'currency' => $currency,
            'email' => $booking->email,
            'first_name' => $booking->first_name ?: __('Guest'),
            'last_name' => $booking->last_name ?: __('User'),
            'tx_ref' => $txRef,
            'callback_url' => $this->getWebhookUrl(),
            'return_url' => $this->getReturnUrl() . '?c=' . $booking->code . '&tx_ref=' . $txRef,
            'customization' => [
                'title' => 'TONETOR',
                'description' => $this->buildCustomizationDescription((string) $booking->code),
            ],
            'meta' => [
                'booking_code' => $booking->code,
                'booking_id' => (string) $booking->id,
                'payment_code' => $payment->code,
            ],
        ];

        $split = $this->buildSubaccountPayload($booking);
        if (!empty($split)) {
            // Chapa documents the split fields with bracket-style keys
            // (e.g. "subaccounts[id]"), so flatten them into the JSON body.
            foreach ($split as $key => $value) {
                $payload['subaccounts[' . $key . ']'] = $value;
            }
            $payment->addMeta('chapa_subaccount_id', $split['id']);
        }

        $response = $this->initializeTransaction($payload);
        $json = $response->json();
        $checkoutUrl = data_get($json, 'data.checkout_url');

        if (!$response->successful() || empty($checkoutUrl)) {
            $payment->markAsFailed($json);
            Log::error('Chapa initialize payment failed', ['response' => $json, 'booking_id' => $booking->id]);
            $message = $this->normalizeGatewayMessage(
                data_get($json, 'message', __('Unable to initialize Chapa payment'))
            );
            throw new Exception('Chapa Gateway: ' . $message);
        }

        $payment->addMeta('chapa_tx_ref', $txRef);
        $payment->addMeta('chapa_init_response', $json);

        $booking->status = $booking::UNPAID;
        $booking->payment_id = $payment->id;
        $booking->save();

        try {
            event(new BookingCreatedEvent($booking));
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
        }

        return response()->json([
            'url' => $checkoutUrl,
            'payment_url' => $checkoutUrl,
            'checkout_url' => $checkoutUrl,
            'amount' => $this->formatAmount($chargeAmount),
            'currency' => $currency,
            'main_amount' => $this->formatAmount((float) $booking->pay_now),
            'main_currency' => $this->getMainCurrency(),
        ]);
    }

    public function confirmPayment(Request $request)
    {
        $booking = Booking::where('code', $request->query('c'))->first();
        if (!$booking) {
            return redirect(url('/'))->with("error", __("Booking not found"));
        }
        if (!$booking->payment) {
            return redirect($booking->getDetailUrl(false))->with("error", __("Payment not found"));
        }

        $txRef = $this->extractTxRef($request) ?: $booking->payment->getMeta('chapa_tx_ref');
        if (empty($txRef)) {
            return redirect($booking->getDetailUrl(false))->with("error", __("Invalid payment reference"));
        }

        $verification = $this->verifyByTxRef($txRef);
        $isSuccess = $this->isVerificationSuccess($verification);
        $payment = $booking->payment;

        if ($isSuccess && in_array($booking->status, [$booking::UNPAID, $booking::DRAFT], true)) {
            $payment->status = 'completed';
            $payment->logs = json_encode($verification);
            $payment->save();

            try {
                $booking->paid += (float) $booking->pay_now;
                $booking->markAsPaid();
            } catch (\Exception $e) {
                Log::warning($e->getMessage());
            }

            return redirect($booking->getDetailUrl())->with("success", __("You payment has been processed successfully"));
        }

        if (!$isSuccess && in_array($booking->status, [$booking::UNPAID, $booking::DRAFT], true)) {
            $payment->status = 'fail';
            $payment->logs = json_encode($verification);
            $payment->save();
            try {
                $booking->markAsPaymentFailed();
            } catch (\Exception $e) {
                Log::warning($e->getMessage());
            }
        }

        return redirect($booking->getDetailUrl(false));
    }

    public function cancelPayment(Request $request)
    {
        $booking = Booking::where('code', $request->query('c'))->first();
        if (!empty($booking) && in_array($booking->status, [$booking::UNPAID], true)) {
            $payment = $booking->payment;
            if ($payment) {
                $payment->status = 'cancel';
                $payment->logs = json_encode(['customer_cancel' => 1]);
                $payment->save();
            }
            $booking->tryRefundToWallet(false);
            return redirect($booking->getDetailUrl())->with("error", __("You cancelled the payment"));
        }

        if (!empty($booking)) {
            return redirect($booking->getDetailUrl(false));
        }
        return redirect(url('/'));
    }

    public function callbackPayment(Request $request)
    {
        $txRef = $this->extractTxRef($request);
        $payment = $txRef ? $this->findPaymentByTxRef($txRef) : null;
        if (!$payment) {
            $payment = $this->findPaymentFromPayload($request);
            if ($payment) {
                $txRef = (string) $payment->getMeta('chapa_tx_ref');
            }
        }

        if (empty($txRef) || !$payment) {
            Log::warning('Chapa callback missing payment reference', [
                'payload' => $request->all(),
                'query' => $request->query(),
            ]);
            return response()->json(['status' => 'error', 'message' => __('Payment reference is required')], 400);
        }

        if (!$payment || !$payment->booking) {
            return response()->json(['status' => 'error', 'message' => __('Payment not found')], 404);
        }

        $booking = $payment->booking;
        if (in_array($booking->status, [$booking::PAID, $booking::COMPLETED, $booking::CANCELLED], true)) {
            return response()->json(['status' => 'success', 'message' => __('Already processed')]);
        }

        $verification = $this->verifyByTxRef($txRef);
        $isSuccess = $this->isVerificationSuccess($verification);
        Log::info('Chapa callback verification', [
            'tx_ref' => $txRef,
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'is_success' => $isSuccess,
            'verification' => $verification,
        ]);

        if ($isSuccess) {
            $payment->status = 'completed';
            $payment->logs = json_encode($verification);
            $payment->save();
            try {
                $booking->paid = (float) $booking->paid + (float) ($payment->amount ?: $booking->pay_now);
                $booking->markAsPaid();
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return response()->json(['status' => 'success', 'message' => __('Payment processed')]);
        }

        $payment->status = 'fail';
        $payment->logs = json_encode($verification);
        $payment->save();
        try {
            $booking->markAsPaymentFailed();
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
        }

        return response()->json(['status' => 'error', 'message' => __('Payment verification failed')], 400);
    }

    protected function initializeTransaction(array $payload)
    {
        return Http::timeout($this->getTimeout())
            ->withOptions(['verify' => $this->getSslVerifyOption()])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getSecretKey(),
                'Content-Type' => 'application/json',
            ])
            ->post($this->getBaseUrl() . '/v1/transaction/initialize', $payload);
    }

    protected function verifyByTxRef(string $txRef): array
    {
        try {
            $response = Http::timeout($this->getTimeout())
                ->withOptions(['verify' => $this->getSslVerifyOption()])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getSecretKey(),
                    'Content-Type' => 'application/json',
                ])
                ->get($this->getBaseUrl() . '/v1/transaction/verify/' . urlencode($txRef));

            return (array) $response->json();
        } catch (\Throwable $e) {
            Log::warning('Chapa verify request failed', ['tx_ref' => $txRef, 'error' => $e->getMessage()]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    protected function isVerificationSuccess(array $verification): bool
    {
        $topStatus = strtolower((string) data_get($verification, 'status'));
        $dataStatus = strtolower((string) data_get($verification, 'data.status'));
        $paymentStatus = strtolower((string) data_get($verification, 'data.payment_status'));
        $txStatus = strtolower((string) data_get($verification, 'data.tx_status'));
        $processorStatus = strtolower((string) data_get($verification, 'data.processor_response.status'));

        $isTopOk = in_array($topStatus, ['success', 'successful'], true);
        $successStates = ['success', 'successful', 'completed', 'paid'];
        $hasNestedSuccess = in_array($dataStatus, $successStates, true)
            || in_array($paymentStatus, $successStates, true)
            || in_array($txStatus, $successStates, true)
            || in_array($processorStatus, $successStates, true);

        // Some Chapa verify responses only set top-level status; accept that when no nested status exists.
        $hasNoNestedState = $dataStatus === '' && $paymentStatus === '' && $txStatus === '' && $processorStatus === '';

        return $isTopOk && ($hasNestedSuccess || $hasNoNestedState);
    }

    protected function findPaymentByTxRef(string $txRef): ?Payment
    {
        $meta = PaymentMeta::where('name', 'chapa_tx_ref')->where('val', $txRef)->first();
        if (!$meta) {
            return null;
        }
        return Payment::find($meta->payment_id);
    }

    protected function findPaymentFromPayload(Request $request): ?Payment
    {
        $paymentCode = (string) (
            $request->input('payment_code')
            ?: $request->input('meta.payment_code')
            ?: $request->input('data.payment_code')
            ?: $request->input('data.meta.payment_code')
        );
        if ($paymentCode !== '') {
            return Payment::query()->where('code', $paymentCode)->first();
        }

        $bookingCode = (string) (
            $request->input('booking_code')
            ?: $request->input('meta.booking_code')
            ?: $request->input('data.booking_code')
            ?: $request->input('data.meta.booking_code')
        );
        if ($bookingCode !== '') {
            $booking = Booking::query()->where('code', $bookingCode)->first();
            if ($booking && $booking->payment) {
                return $booking->payment;
            }
        }

        return null;
    }

    protected function extractTxRef(Request $request): string
    {
        $txRef = (string) (
            $request->input('tx_ref')
            ?: $request->query('tx_ref')
            ?: $request->input('trx_ref')
            ?: $request->query('trx_ref')
            ?: $request->input('reference')
            ?: $request->query('reference')
            ?: $request->input('data.tx_ref')
            ?: $request->input('data.trx_ref')
            ?: $request->input('data.reference')
            ?: $request->input('data.meta.tx_ref')
            ?: $request->input('meta.tx_ref')
        );

        return trim($txRef);
    }

    protected function buildBookingTxRef(Booking $booking, Payment $payment): string
    {
        return sprintf('BOOKING-%s-%s-%s', $booking->id, $payment->id, time());
    }

    protected function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    protected function getSecretKey(): string
    {
        return trim((string) $this->getOption('secret_key'));
    }

    protected function getCurrency(): string
    {
        return strtoupper((string) ($this->getOption('currency') ?: setting_item('currency_main', 'ETB')));
    }

    protected function getMainCurrency(): string
    {
        return strtoupper((string) setting_item('currency_main', 'ETB'));
    }

    /**
     * Booking totals are always stored in the site's Main Currency. When the
     * Chapa gateway is configured to charge in a different currency (e.g. the
     * site runs on ETB but Chapa should charge USD cards), the stored amount
     * must be converted using the gateway's configured exchange rate before
     * being sent to Chapa — otherwise the ETB-denominated number would be
     * sent as-is under the USD label, wildly overcharging the customer.
     */
    protected function resolveChargeAmount(float $mainAmount, string $chargeCurrency, Payment $payment): float
    {
        $mainCurrency = $this->getMainCurrency();
        if ($chargeCurrency === $mainCurrency) {
            return $mainAmount;
        }

        $convertedAmount = $this->convertAmount($mainAmount, $mainCurrency, $chargeCurrency);

        $payment->addMeta('chapa_main_currency', $mainCurrency);
        $payment->addMeta('chapa_main_amount', $mainAmount);
        $payment->addMeta('chapa_exchange_rate', $this->getUsdToEtbRate());
        $payment->addMeta('chapa_converted_currency', $chargeCurrency);
        $payment->addMeta('chapa_converted_amount', $convertedAmount);

        return $convertedAmount;
    }

    protected function getBaseUrl(): string
    {
        if ($this->getOption('test')) {
            return rtrim((string) $this->getOption('test_base_url', 'https://api.chapa.co'), '/');
        }
        return rtrim((string) $this->getOption('live_base_url', 'https://api.chapa.co'), '/');
    }

    protected function getTimeout(): int
    {
        return (int) max(5, (int) $this->getOption('timeout', 30));
    }

    protected function getSslVerifyOption()
    {
        $bundle = app_path('certs/cacert.pem');

        return is_file($bundle) ? $bundle : true;
    }

    protected function normalizeGatewayMessage($message): string
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

            if (!empty($flat)) {
                return implode(' ', $flat);
            }

            return __('Unable to initialize Chapa payment');
        }

        if ($message instanceof \Stringable) {
            return (string) $message;
        }

        return __('Unable to initialize Chapa payment');
    }

    protected function buildCustomizationDescription(string $bookingCode): string
    {
        // Chapa rule: max 50 chars and only letters, numbers, hyphens, underscores, spaces, dots.
        $raw = 'Booking ' . $bookingCode;
        $clean = preg_replace('/[^A-Za-z0-9\-_. ]+/', '', $raw) ?? 'Booking Payment';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? 'Booking Payment');

        if ($clean === '') {
            $clean = 'Booking Payment';
        }

        return mb_substr($clean, 0, 50);
    }

    /**
     * Build the `subaccounts` portion of the initialize-transaction payload by
     * resolving the booking's vendor and reading the vendor's stored Chapa
     * subaccount id. Returns an empty array when no subaccount is configured.
     *
     * @param Booking $booking
     * @return array<string,mixed>
     */
    protected function buildSubaccountPayload(Booking $booking): array
    {
        $vendorId = (int) ($booking->vendor_id ?? 0);
        if ($vendorId <= 0) {
            return [];
        }

        $subaccountId = $this->getVendorSubaccountId($vendorId);
        if ($subaccountId === null || $subaccountId === '') {
            return [];
        }

        $payload = ['id' => $subaccountId];

        $overrideType = strtolower(trim((string) $this->getOption('split_override_type')));
        $overrideValue = trim((string) $this->getOption('split_override_value'));
        if (in_array($overrideType, ['flat', 'percentage'], true) && $overrideValue !== '') {
            $payload['split_type'] = $overrideType;
            $payload['split_value'] = is_numeric($overrideValue) ? (float) $overrideValue : $overrideValue;
        }

        return $payload;
    }

    /**
     * Resolve the Chapa subaccount id stored against a vendor user.
     */
    public function getVendorSubaccountId(int $vendorId): ?string
    {
        $vendor = \App\User::find($vendorId);
        if (!$vendor) {
            return null;
        }
        $id = trim((string) $vendor->getMeta('chapa_subaccount_id'));
        return $id === '' ? null : $id;
    }

    /**
     * Create a Chapa subaccount via the Chapa API.
     *
     * @see https://developer.chapa.co/docs/split-payment
     *
     * @param array<string,mixed> $payload Required keys: business_name, account_name,
     *                                     bank_code, account_number, split_type, split_value.
     * @return array{ok:bool,status:int,body:array<string,mixed>,subaccount_id:?string,message:string}
     */
    public function createSubaccount(array $payload): array
    {
        try {
            $response = Http::timeout($this->getTimeout())
                ->withOptions(['verify' => $this->getSslVerifyOption()])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getSecretKey(),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->getBaseUrl() . '/v1/subaccount', $payload);

            $body = (array) $response->json();
            $ok = $response->successful() && strtolower((string) data_get($body, 'status')) === 'success';

            // The API returns the new id as the literal key "subaccounts[id]" in the data object.
            $subaccountId = (string) (
                data_get($body, 'data.subaccounts[id]')
                ?: data_get($body, 'data.subaccount_id')
                ?: data_get($body, 'data.id')
            );

            return [
                'ok' => $ok && $subaccountId !== '',
                'status' => $response->status(),
                'body' => $body,
                'subaccount_id' => $subaccountId !== '' ? $subaccountId : null,
                'message' => $this->normalizeGatewayMessage(
                    data_get($body, 'message', __('Unable to create Chapa subaccount'))
                ),
            ];
        } catch (\Throwable $e) {
            Log::warning('Chapa createSubaccount failed', ['error' => $e->getMessage()]);
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'subaccount_id' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch the list of supported banks from Chapa. Cached for 1 hour to avoid
     * hammering the API on every render of the vendor settings page.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getBanks(): array
    {
        $cacheKey = 'chapa_banks_' . md5($this->getBaseUrl() . '|' . $this->getSecretKey());

        try {
            return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () {
                $response = Http::timeout($this->getTimeout())
                    ->withOptions(['verify' => $this->getSslVerifyOption()])
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->getSecretKey(),
                        'Content-Type' => 'application/json',
                    ])
                    ->get($this->getBaseUrl() . '/v1/banks');

                if (!$response->successful()) {
                    return [];
                }
                $body = (array) $response->json();
                $data = data_get($body, 'data', []);
                return is_array($data) ? $data : [];
            });
        } catch (\Throwable $e) {
            Log::warning('Chapa getBanks failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}

