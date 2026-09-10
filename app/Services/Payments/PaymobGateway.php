<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentGatewayException;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentCallbackResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paymob gateway implemented against the current official documentation:
 *
 *   - Payment Intention API:  POST {base_url}/v1/intention/
 *       Authorization: `Token {secret_key}`
 *   - Unified Checkout:       {base_url}/unifiedcheckout/?publicKey=...&clientSecret=...
 *   - Callback security:      HMAC-SHA512 over the documented 20 transaction
 *       fields concatenated in order (see https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-transaction-callback)
 *
 * The local simulator is available only when explicitly enabled outside
 * production. Missing live credentials never expose simulated payments to
 * customers.
 */
class PaymobGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'paymob';
    }

    public function isConfigured(): bool
    {
        return filled(config('paymob.secret_key'))
            && filled(config('paymob.public_key'))
            && (config('paymob.sandbox_mode') !== true);
    }

    public function isSandboxMode(): bool
    {
        return (bool) config('paymob.sandbox_mode') && ! app()->environment('production');
    }

    public function supportsMethod(PaymentMethod $method): bool
    {
        if (! $method->requiresGateway()) {
            return false;
        }

        if ($this->isSandboxMode()) {
            return true;
        }

        if (! $this->isConfigured()) {
            return false;
        }

        return $this->integrationId($method) > 0;
    }

    public function createPayment(Payment $payment, PaymentMethod $method, string $returnUrl): array
    {
        if ($this->isSandboxMode()) {
            return [
                'redirect_url' => route('payments.sandbox', ['payment' => $payment->id]),
                'sandbox_mode' => true,
            ];
        }

        if (! $this->isConfigured() || ! $this->supportsMethod($method)) {
            throw new PaymentGatewayException(__('payments.gateway_unavailable'));
        }

        $order = $payment->order;
        $amountCents = (int) round(((float) $payment->amount) * 100);

        $items = $order->items->map(fn ($item) => [
            'name' => mb_substr($item->name_snapshot, 0, 255),
            'amount_cents' => (int) round(((float) $item->line_total) * 100),
            'description' => $item->sku_snapshot ?? $item->name_snapshot,
            'quantity' => $item->quantity,
        ])->all();

        $billing = $this->billingData($order);
        $customer = $this->customerData($order);

        $payload = [
            'amount' => $amountCents,
            'currency' => config('paymob.currency'),
            'payment_methods' => [$this->integrationId($method)],
            'items' => $items,
            'billing_data' => $billing,
            'customer' => $customer,
            'special_reference' => $order->order_number,
            'notification_url' => route('payments.webhook'),
            'redirection_url' => $returnUrl,
            'expiration' => config('paymob.expiration', 3600),
        ];

        $response = Http::withToken((string) config('paymob.secret_key'), 'Token')
            ->acceptJson()
            ->timeout(20)
            ->post(rtrim((string) config('paymob.base_url'), '/').'/v1/intention/', $payload);

        if ($response->failed()) {
            Log::warning('paymob.create_intention_failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $this->sanitizeForLogs($response->body()),
            ]);

            throw new PaymentGatewayException(
                __('payments.gateway_unavailable'),
                ['status' => $response->status()],
            );
        }

        $intention = $response->json() ?? [];

        $clientSecret = data_get($intention, 'client_secret');

        if (! $clientSecret) {
            Log::error('paymob.create_intention_missing_client_secret', [
                'order_id' => $order->id,
                'intention' => collect($intention)->except(['client_secret'])->toArray(),
            ]);

            throw new PaymentGatewayException(__('payments.gateway_unavailable'));
        }

        $paymobOrderId = data_get($intention, 'intention_order_id') ?? data_get($intention, 'id');

        $payment->forceFill([
            'provider' => $this->name(),
            'paymob_order_id' => $paymobOrderId,
            'transaction_reference' => $clientSecret,
            'raw_response' => $this->sanitizePayload($intention),
        ])->save();

        $url = rtrim((string) config('paymob.base_url'), '/')
            .'/unifiedcheckout/?publicKey='.urlencode((string) config('paymob.public_key'))
            .'&clientSecret='.urlencode($clientSecret);

        return ['redirect_url' => $url, 'sandbox_mode' => false];
    }

    public function resolveWebhook(array $payload, array $query): PaymentCallbackResult
    {
        $obj = $payload['obj'] ?? $payload;
        $receivedHmac = $query['hmac'] ?? ($payload['hmac'] ?? null);

        if (! $this->verifyWebhookSignature($obj, $receivedHmac)) {
            throw new InvalidPaymentSignatureException;
        }

        $payment = $this->findPayment($obj);

        if ($payment === null) {
            throw new PaymentNotFoundException;
        }

        return $this->buildResult($payment, $obj);
    }

    public function resolveRedirect(array $query): ?PaymentCallbackResult
    {
        $receivedHmac = $query['hmac'] ?? null;

        if (! $this->verifyRedirectSignature($query, $receivedHmac)) {
            throw new InvalidPaymentSignatureException;
        }

        $payment = $this->findPayment($query);

        if ($payment === null) {
            throw new PaymentNotFoundException;
        }

        return $this->buildResult($payment, $query);
    }

    // ---------------------------------------------------------------- HMAC

    /**
     * The 20 fields used for the POST "transaction processed" callback HMAC.
     * See https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-transaction-callback
     *
     * @var array<int, string>
     */
    private const WEBHOOK_HMAC_FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
        'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture',
        'is_refunded', 'is_standalone_payment', 'is_voided', 'order.id', 'owner',
        'pending', 'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
    ];

    /**
     * Same fields but using the flattened query-parameter keys of the GET redirect.
     *
     * @var array<int, string>
     */
    private const REDIRECT_HMAC_FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
        'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture',
        'is_refunded', 'is_standalone_payment', 'is_voided', 'order_id', 'owner',
        'pending', 'source_data_pan', 'source_data_sub_type', 'source_data_type', 'success',
    ];

    public function verifyWebhookSignature(array $obj, ?string $receivedHmac): bool
    {
        return $this->verifySignature($obj, self::WEBHOOK_HMAC_FIELDS, $receivedHmac);
    }

    public function verifyRedirectSignature(array $query, ?string $receivedHmac): bool
    {
        return $this->verifySignature($query, self::REDIRECT_HMAC_FIELDS, $receivedHmac);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    private function verifySignature(array $data, array $fields, ?string $receivedHmac): bool
    {
        $secret = (string) config('paymob.hmac_secret');

        if ($secret === '' || $receivedHmac === null || $receivedHmac === '') {
            return false;
        }

        $concatenated = '';

        foreach ($fields as $field) {
            $concatenated .= $this->stringify(data_get($data, $field));
        }

        $computed = hash_hmac('sha512', $concatenated, $secret);

        return hash_equals($computed, strtolower((string) $receivedHmac));
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

    // ------------------------------------------------------------ matching

    /**
     * Locate the local Payment referenced by a webhook or redirect payload.
     *
     * @param  array<string, mixed>  $data
     */
    private function findPayment(array $data): ?Payment
    {
        $amountCents = isset($data['amount_cents']) ? (int) $data['amount_cents'] : null;

        // 1. Directly by Paymob transaction id (duplicate delivery safety).
        $transactionId = $data['id'] ?? null;
        if ($transactionId !== null) {
            $payment = Payment::query()
                ->where('paymob_transaction_id', (string) $transactionId)
                ->first();

            if ($payment !== null) {
                return $this->amountMatches($payment, $amountCents) ? $payment : null;
            }
        }

        // 2. By Paymob order id captured during intention creation.
        $paymobOrderId = $data['order_id'] ?? (is_array($data['order'] ?? null) ? ($data['order']['id'] ?? null) : null);
        if ($paymobOrderId !== null) {
            $payment = Payment::query()
                ->where('paymob_order_id', (string) $paymobOrderId)
                ->first();

            if ($payment !== null) {
                return $this->amountMatches($payment, $amountCents) ? $payment : null;
            }
        }

        // 3. By our own order number echoed back as merchant_order_id.
        $merchantOrderId = $data['merchant_order_id']
            ?? (is_array($data['order'] ?? null) ? ($data['order']['merchant_order_id'] ?? null) : null);

        if ($merchantOrderId !== null) {
            $order = Order::query()
                ->whereIn('status', ['pending', 'awaiting_payment'])
                ->where('order_number', (string) $merchantOrderId)
                ->first();

            if ($order !== null) {
                $payment = $order->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->latest()
                    ->first() ?? $order->payments()->latest()->first();

                if ($payment !== null) {
                    return $this->amountMatches($payment, $amountCents) ? $payment : null;
                }
            }
        }

        return null;
    }

    private function amountMatches(Payment $payment, ?int $amountCents): bool
    {
        if ($amountCents === null) {
            return true;
        }

        $expected = (int) round(((float) $payment->amount) * 100);

        return $amountCents === $expected;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildResult(Payment $payment, array $data): PaymentCallbackResult
    {
        $success = filter_var($data['success'] ?? false, FILTER_VALIDATE_BOOL);
        $pending = filter_var($data['pending'] ?? false, FILTER_VALIDATE_BOOL);

        $status = $success && ! $pending
            ? PaymentStatus::Success
            : (! $success ? PaymentStatus::Failed : PaymentStatus::Pending);

        $transactionId = $data['id'] ?? $data['transaction_id'] ?? null;
        $orderId = $data['order_id'] ?? ($data['order']['id'] ?? null);

        return new PaymentCallbackResult(
            payment: $payment,
            status: $status,
            transactionReference: $transactionId !== null ? (string) $transactionId : null,
            providerTransactionId: $transactionId !== null ? (string) $transactionId : null,
            providerOrderId: $orderId !== null ? (string) $orderId : null,
            raw: $this->sanitizePayload($data),
        );
    }

    private function integrationId(PaymentMethod $method): int
    {
        return match ($method) {
            PaymentMethod::Card => (int) config('paymob.card_integration_id'),
            PaymentMethod::Wallet => (int) config('paymob.wallet_integration_id'),
            PaymentMethod::CashOnDelivery => 0,
        };
    }

    /**
     * @return array<string, string>
     */
    private function billingData(Order $order): array
    {
        $nameParts = array_values(array_filter(explode(' ', trim((string) $order->customer_name))));

        return [
            'first_name' => $nameParts[0] ?? 'NA',
            'last_name' => $nameParts[1] ?? 'NA',
            'email' => $order->email ?? 'no-email@example.com',
            'phone_number' => (string) $order->phone,
            'apartment' => 'NA',
            'floor' => 'NA',
            'street' => $order->address_line ?? 'NA',
            'building' => 'NA',
            'shipping_method' => 'NA',
            'postal_code' => 'NA',
            'city' => $order->city ?? 'NA',
            'country' => 'EG',
            'state' => $order->governorate ?? 'NA',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function customerData(Order $order): array
    {
        return [
            'first_name' => $order->customer_name,
            'last_name' => '',
            'email' => $order->email ?? 'no-email@example.com',
            'phone_number' => (string) $order->phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $payload['pan'] = isset($payload['source_data']['pan']) ? '***' : null;

        if ($payload['pan'] === null) {
            unset($payload['pan']);
        }

        $payload['source_data'] = $payload['source_data'] ?? [];

        unset($payload['source_data']['pan']);

        return $payload;
    }

    private function sanitizeForLogs(string $body): string
    {
        return mb_substr($body, 0, 500);
    }
}
