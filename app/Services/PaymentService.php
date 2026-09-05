<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentCallbackResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates payments: creates payment records, hands off to the gateway,
 * and applies idempotent state transitions from webhooks/redirects.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly AuditLogger $audit,
    ) {}

    public function gateway(): PaymentGatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Create (or reuse) a pending payment for an order and move the order to
     * awaiting_payment so it no longer appears as an unpaid pending order.
     */
    public function startPayment(Order $order, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($order, $method) {
            $payment = $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->where('method', $method->value)
                ->latest()
                ->first();

            if ($payment === null) {
                $payment = $order->payments()->create([
                    'provider' => $this->gateway->name(),
                    'method' => $method->value,
                    'amount' => $order->total,
                    'status' => PaymentStatus::Pending,
                ]);
            }

            $order->markAwaitingPayment();

            return $payment;
        });
    }

    /**
     * Ask the gateway to create the remote payment and return a redirect URL.
     *
     * @return array{redirect_url: string, sandbox_mode?: bool}
     */
    public function redirect(Payment $payment, PaymentMethod $method): array
    {
        return $this->gateway->createPayment(
            $payment,
            $method,
            route('checkout.return', ['payment' => $payment->id]),
        );
    }

    /**
     * Handle an authenticated POST webhook from the gateway.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    public function handleWebhook(array $payload, array $query): void
    {
        $result = $this->gateway->resolveWebhook($payload, $query);

        $this->applyResult($result);
    }

    /**
     * Handle an authenticated GET redirect from the gateway checkout.
     *
     * @param  array<string, mixed>  $query
     */
    public function handleRedirect(array $query): void
    {
        try {
            $result = $this->gateway->resolveRedirect($query);

            if ($result === null) {
                return;
            }

            $this->applyResult($result);
        } catch (InvalidPaymentSignatureException) {
            // Ignore unauthenticated redirects; payment stays pending.
            Log::warning('payments.redirect_signature_invalid');
        } catch (PaymentNotFoundException $e) {
            Log::warning('payments.redirect_no_payment', ['query' => array_keys($query)]);
        }
    }

    /**
     * Apply a normalized callback result. Idempotent: a finalized payment is
     * never processed twice and never downgraded (e.g. a "failed" callback
     * arriving after a success is ignored).
     */
    public function applyResult(PaymentCallbackResult $result): void
    {
        DB::transaction(function () use ($result) {
            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->whereKey($result->payment->id)
                ->lockForUpdate()
                ->first();

            if ($payment === null || $payment->status->isFinal()) {
                return;
            }

            $oldStatus = $payment->status;

            $payment->forceFill([
                'paymob_transaction_id' => $result->providerTransactionId ?? $payment->paymob_transaction_id,
                'paymob_order_id' => $result->providerOrderId ?? $payment->paymob_order_id,
                'status' => $result->status,
                'raw_response' => $result->raw,
                'paid_at' => $result->status === PaymentStatus::Success ? now() : $payment->paid_at,
            ])->save();

            $this->synchronizeOrder($payment, $oldStatus);
        });
    }

    private function synchronizeOrder(Payment $payment, PaymentStatus $oldStatus): void
    {
        $order = $payment->order;

        if ($order === null) {
            return;
        }

        $oldOrderStatus = $order->status;

        if ($payment->status === PaymentStatus::Success) {
            if (in_array($order->status, [OrderStatus::Pending, OrderStatus::AwaitingPayment], true)) {
                $order->update(['status' => OrderStatus::Paid]);

                $this->audit->log(
                    'payment_succeeded',
                    $order,
                    ['payment_status' => $oldStatus->value],
                    ['payment_status' => $payment->status->value, 'order_status' => $order->status->value],
                );
            }
        }

        if ($payment->status === PaymentStatus::Failed && $order->status === OrderStatus::Pending) {
            $order->update(['status' => OrderStatus::AwaitingPayment]);

            $this->audit->log(
                'payment_failed',
                $order,
                ['order_status' => $oldOrderStatus->value],
                ['order_status' => $order->status->value],
            );
        }

        $this->audit->log(
            'payment_status_changed',
            $payment,
            ['status' => $oldStatus->value],
            ['status' => $payment->status->value],
        );
    }
}
