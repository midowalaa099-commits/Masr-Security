<?php

namespace App\Services;

use App\Contracts\Purchasable;

/**
 * Immutable resolved cart line used by cart + checkout views.
 */
class CartItemValue
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly Purchasable $model,
        public readonly string $name,
        public readonly ?string $imageUrl,
        public readonly string $unitPrice,
        public readonly string $originalPrice,
        public readonly int $quantity,
        public readonly int $availableQuantity,
        public readonly bool $isAvailable,
        public readonly string $stockLabel,
    ) {}

    public function key(): string
    {
        return $this->type.':'.$this->id;
    }

    public function lineTotal(): string
    {
        return number_format($this->unitPrice * $this->quantity, 2, '.', '');
    }

    public function savingsPerUnit(): string
    {
        return number_format(max(0, $this->originalPrice - $this->unitPrice), 2, '.', '');
    }
}
