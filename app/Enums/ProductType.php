<?php

namespace App\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Component = 'component';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Component => 'Component',
        };
    }
}
