<?php

namespace Tests\Unit;

use Modules\Booking\Gateways\ChapaGateway;
use Modules\Booking\Models\Payment;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ChapaGatewayTest extends TestCase
{
    public function test_mobile_numbers_are_normalized_and_validated(): void
    {
        $gateway = new TestableChapaGateway('chapa');

        $this->assertSame('0911234567', $gateway->normalize('+251 911 234 567'));
        $this->assertSame('0711234567', $gateway->normalize('711234567'));
        $this->assertTrue($gateway->valid('0911234567'));
        $this->assertTrue($gateway->valid('0711234567'));
        $this->assertFalse($gateway->valid('12345'));
    }

    public function test_direct_charge_acceptance_rejects_failure_and_reference_mismatch(): void
    {
        $gateway = new TestableChapaGateway('chapa');

        $this->assertTrue($gateway->accepted(200, ['status' => 'success'], 'TX-1'));
        $this->assertTrue($gateway->accepted(200, ['status' => 'success', 'data' => ['tx_ref' => 'TX-1']], 'TX-1'));
        $this->assertFalse($gateway->accepted(200, ['status' => 'failed'], 'TX-1'));
        $this->assertFalse($gateway->accepted(200, ['status' => 'success', 'data' => ['tx_ref' => 'TX-2']], 'TX-1'));
        $this->assertFalse($gateway->accepted(500, ['status' => 'success'], 'TX-1'));
    }

    public function test_terminal_verification_failures_are_distinguished_from_pending_statuses(): void
    {
        $gateway = new TestableChapaGateway('chapa');

        $this->assertTrue($gateway->terminalFailure(['data' => ['status' => 'failed']]));
        $this->assertTrue($gateway->terminalFailure(['status' => 'failed/cancelled']));
        $this->assertTrue($gateway->terminalFailure(['data' => ['payment_status' => 'declined']]));
        $this->assertFalse($gateway->terminalFailure(['status' => 'success', 'data' => ['status' => 'pending']]));
    }

    public function test_successful_verification_must_match_reference_amount_and_currency(): void
    {
        $gateway = new TestableChapaGateway('chapa');
        $payment = new Payment();
        $payment->amount = 100;
        $valid = [
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => 'TX-1',
                'amount' => '100.00',
                'currency' => 'ETB',
            ],
        ];

        $this->assertTrue($gateway->verified($valid, $payment, 'TX-1'));

        $wrongReference = $valid;
        $wrongReference['data']['tx_ref'] = 'TX-2';
        $this->assertFalse($gateway->verified($wrongReference, $payment, 'TX-1'));

        $wrongAmount = $valid;
        $wrongAmount['data']['amount'] = '99.00';
        $this->assertFalse($gateway->verified($wrongAmount, $payment, 'TX-1'));

        $wrongCurrency = $valid;
        $wrongCurrency['data']['currency'] = 'USD';
        $this->assertFalse($gateway->verified($wrongCurrency, $payment, 'TX-1'));
    }

    public function test_etb_is_divided_by_the_chapa_usd_rate(): void
    {
        $gateway = new TestableChapaGateway('chapa');

        $this->assertSame(1.0, $gateway->convertAmount(130, 'ETB', 'USD'));
        $this->assertSame(130.0, $gateway->convertAmount(1, 'USD', 'ETB'));
    }

    public function test_missing_currency_defaults_to_the_booking_main_currency(): void
    {
        $gateway = new TestableChapaGateway('chapa');

        $this->assertSame('ETB', $gateway->requestedCurrency(new Request()));
        $this->assertSame('USD', $gateway->requestedCurrency(new Request(['chapa_currency' => 'USD'])));
    }
}

class TestableChapaGateway extends ChapaGateway
{
    public function getUsdToEtbRate(): float
    {
        return 130.0;
    }

    public function normalize(string $mobile): string
    {
        return $this->normalizeMobile($mobile);
    }

    public function valid(string $mobile): bool
    {
        return $this->isValidMobile($mobile);
    }

    public function accepted(int $status, array $response, string $txRef): bool
    {
        return $this->isDirectChargeAccepted($status, $response, $txRef);
    }

    public function terminalFailure(array $response): bool
    {
        return $this->isVerificationTerminalFailure($response);
    }

    public function verified(array $response, Payment $payment, string $txRef): bool
    {
        return $this->isVerificationSuccess($response, $payment, $txRef);
    }

    public function requestedCurrency(Request $request): string
    {
        return $this->resolveRequestedCurrency($request);
    }

    public function getAvailableCurrencies(): array
    {
        return ['ETB', 'USD'];
    }

    protected function getMainCurrency(): string
    {
        return 'ETB';
    }

    protected function getCurrency(): string
    {
        return 'ETB';
    }
}
