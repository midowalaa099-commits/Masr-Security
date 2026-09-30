<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentGatewayException;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Payments\PaymobGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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
        config(['paymob.secret_key' => null, 'paymob.sandbox_mode' => true]);

        $gateway = $this->gateway();

        $this->assertTrue($gateway->isSandboxMode());
        $this->assertTrue($gateway->supportsMethod(PaymentMethod::Card));
        $this->assertTrue($gateway->supportsMethod(PaymentMethod::Wallet));
    }

    public function test_sandbox_mode_is_never_available_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        config([
            'paymob.secret_key' => null,
            'paymob.public_key' => null,
            'paymob.sandbox_mode' => true,
        ]);

        $gateway = $this->gateway();

        $this->assertFalse($gateway->isSandboxMode());
        $this->assertFalse($gateway->supportsMethod(PaymentMethod::Card));
        $this->assertFalse($gateway->supportsMethod(PaymentMethod::Wallet));
    }

    public function test_configured_gateway_requires_integration_ids_otherwise(): void
    {
        config(['paymob.secret_key' => 'secret', 'paymob.public_key' => 'public', 'paymob.sandbox_mode' => false]);
        config(['paymob.card_integration_id' => 0, 'paymob.wallet_integration_id' => 0]);

        $this->assertFalse($this->gateway()->isSandboxMode());
        $this->assertFalse($this->gateway()->supportsMethod(PaymentMethod::Card));
    }

    public function test_live_intention_uses_documented_item_amounts_and_exact_cents(): void
    {
        config([
            'paymob.secret_key' => 'test-secret', 'paymob.public_key' => 'test-public',
            'paymob.sandbox_mode' => false, 'paymob.card_integration_id' => 123,
            'paymob.base_url' => 'https://paymob.example.test',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://paymob.example.test/v1/intention/' => Http::response([
            'client_secret' => 'test-client-secret', 'intention_order_id' => 456,
        ])]);
        $payment = Payment::factory()->create(['amount' => '0.30']);
        OrderItem::factory()->create([
            'order_id' => $payment->order_id, 'name_snapshot' => 'Test camera',
            'unit_price' => '0.10', 'quantity' => 3, 'line_total' => '0.30',
        ]);

        $result = $this->gateway()->createPayment($payment, PaymentMethod::Card, 'https://store.example.test/return');

        Http::assertSent(fn (Request $request): bool => $request['amount'] === 30
            && $request['items'][0]['amount'] === 30 && $request['items'][0]['quantity'] === 3
            && ! array_key_exists('amount_cents', $request['items'][0]));
        Http::assertSentCount(1);
        $this->assertFalse($result['sandbox_mode']);
        $this->assertSame('456', $payment->fresh()->paymob_order_id);
    }

    public function test_intention_connection_failure_is_handled_without_retrying_an_ambiguous_payment(): void
    {
        config([
            'paymob.secret_key' => 'test-secret', 'paymob.public_key' => 'test-public',
            'paymob.sandbox_mode' => false, 'paymob.card_integration_id' => 123,
        ]);
        Http::fake(['*' => Http::failedConnection()]);
        $payment = Payment::factory()->create();

        try {
            $this->gateway()->createPayment($payment, PaymentMethod::Card, 'https://store.example.test/return');
            $this->fail('Connection failure must be a handled gateway error.');
        } catch (PaymentGatewayException $exception) {
            $this->assertSame(__('payments.gateway_unavailable'), $exception->getMessage());
        }

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertNull($payment->fresh()->transaction_reference);
        Http::assertSentCount(1);
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
