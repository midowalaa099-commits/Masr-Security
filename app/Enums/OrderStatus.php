<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingPayment => 'Awaiting Payment',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses that are considered still "open" (not paid or fulfilled).
     * Used to decide whether stock can be restored.
     */
    public function isBeforeFulfillment(): bool
    {
        return match ($this) {
            self::Pending, self::AwaitingPayment, self::Paid, self::Processing => true,
            self::Shipped, self::Delivered, self::Cancelled => false,
        };
    }
}
