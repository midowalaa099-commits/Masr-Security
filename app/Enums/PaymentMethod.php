<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Visa / Mastercard',
            self::Wallet => 'Vodafone Cash',
        };
    }

    public function gatewayKey(): string
    {
        return match ($this) {
            self::Card => 'card',
            self::Wallet => 'wallet',
        };
    }
}
