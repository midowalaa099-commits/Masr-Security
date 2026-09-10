<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case Card = 'card';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
            self::Card => 'Visa / Mastercard',
            self::Wallet => 'Vodafone Cash',
        };
    }

    public function gatewayKey(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'cash_on_delivery',
            self::Card => 'card',
            self::Wallet => 'wallet',
        };
    }

    public function requiresGateway(): bool
    {
        return $this !== self::CashOnDelivery;
    }
}
