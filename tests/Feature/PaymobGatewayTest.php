<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Models\Payment;
use App\Services\Payments\PaymobGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymobGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): PaymobGateway
    {
        return new PaymobGateway;
    }

    public function test_webhook_signature_is_verified_with_the_documented_fields(): void
    {
        config(['paymob.hmac_secret' => 'test-secret']);

        $obj = [
            'amount_cents' => 1000,
            'created_at' => '2026-09-05T10:00:00.000000',
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => 12345678,
            'integration_id' => 456,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => false,
            'is_voided' => false,
            'order' => ['id' => 987654],
            'owner' => 0,
            'pending' => false,
            'source_data' => ['pan' => '4485********5279', 'sub_type' => 'MasterCard', 'type' => 'card'],
            'success' => true,
        ];

        $concatenated = '';
        foreach ($this->webhookFields() as $field) {
            $concatenated .= $this->stringify(data_get($obj, $field));
        }

        $hmac = hash_hmac('sha512', $concatenated, 'test-secret');

        $this->assertTrue($this->gateway()->verifyWebhookSignature($obj, $hmac));
        $this->assertFalse($this->gateway()->verifyWebhookSignature($obj, 'tampered'));
    }

    public function test_webhook_with_an_invalid_signature_is_rejected(): void
    {
        config(['paymob.hmac_secret' => 'test-secret']);

        $payment = Payment::factory()->create(['paymob_transaction_id' => '12345678']);

        $obj = ['id' => $payment->paymob_transaction_id, 'success' => true];

        $this->expectException(InvalidPaymentSignatureException::class);
        $this->gateway()->resolveWebhook($obj, ['hmac' => 'invalid']);
    }

    public function test_authenticated_webhook_resolves_to_a_successful_callback(): void
    {
        config(['paymob.hmac_secret' => 'test-secret']);

        $payment = Payment::factory()->create([
            'paymob_transaction_id' => '12345678',
            'amount' => 100.00,
            'status' => PaymentStatus::Pending,
        ]);

        $obj = [
            'amount_cents' => 10000,
            'id' => 12345678,
            'success' => true,
            'pending' => false,
        ];

        $hmac = $this->hmacFor($obj);

        $result = $this->gateway()->resolveWebhook($obj, ['hmac' => $hmac]);

        $this->assertSame(PaymentStatus::Success, $result->status);
        $this->assertSame('12345678', $result->providerTransactionId);
    }

    public function test_sandbox_mode_supports_every_payment_method(): void
    {
        config(['paymob.secret_key' => null]);

        $gateway = $this->gateway();

        $this->assertTrue($gateway->isSandboxMode());
        $this->assertTrue($gateway->supportsMethod(PaymentMethod::Card));
        $this->assertTrue($gateway->supportsMethod(PaymentMethod::Wallet));
    }

    public function test_configured_gateway_requires_integration_ids_otherwise(): void
    {
        config(['paymob.secret_key' => 'secret', 'paymob.public_key' => 'public', 'paymob.sandbox_mode' => false]);
        config(['paymob.card_integration_id' => 0, 'paymob.wallet_integration_id' => 0]);

        $this->assertFalse($this->gateway()->isSandboxMode());
        $this->assertFalse($this->gateway()->supportsMethod(PaymentMethod::Card));
    }

    /**
     * @return array<int, string>
     */
    private function webhookFields(): array
    {
        return [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture',
            'is_refunded', 'is_standalone_payment', 'is_voided', 'order.id', 'owner',
            'pending', 'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ];
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    private function hmacFor(array $obj): string
    {
        $concatenated = '';

        foreach ($this->webhookFields() as $field) {
            $concatenated .= $this->stringify(data_get($obj, $field));
        }

        return hash_hmac('sha512', $concatenated, 'test-secret');
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
