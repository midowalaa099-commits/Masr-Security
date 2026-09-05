<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Normalized result of a gateway callback/webhook, used to apply idempotent
 * payment state transitions.
 */
class PaymentCallbackResult implements Arrayable
{
    public function __construct(
        public readonly Payment $payment,
        public readonly PaymentStatus $status,
        public readonly ?string $transactionReference,
        public readonly ?string $providerTransactionId,
        public readonly ?string $providerOrderId,
        public readonly array $raw,
    ) {}

    public function toArray(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'status' => $this->status->value,
            'transaction_reference' => $this->transactionReference,
            'provider_transaction_id' => $this->providerTransactionId,
            'provider_order_id' => $this->providerOrderId,
        ];
    }
}
