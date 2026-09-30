<?php

namespace App\Services;

use App\Contracts\Purchasable;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

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
        public readonly ?int $availableQuantity,
        public readonly bool $isAvailable,
        public readonly string $stockLabel,
    ) {}

    public function key(): string
    {
        return $this->type.':'.$this->id;
    }

    public function lineTotal(): string
    {
        return (string) BigDecimal::of($this->unitPrice)->multipliedBy($this->quantity)->toScale(2, RoundingMode::HalfUp);
    }

    public function savingsPerUnit(): string
    {
        return (string) BigDecimal::max('0', BigDecimal::of($this->originalPrice)->minus($this->unitPrice))->toScale(2, RoundingMode::HalfUp);
    }
}
