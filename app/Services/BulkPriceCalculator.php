<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class BulkPriceCalculator
{
    /** @param array{operation: string, direction: string, amount: string, rounding: string, precision?: int|string} $options */
    public function calculate(string $price, array $options): string
    {
        $original = BigDecimal::of($price);
        $amount = BigDecimal::of($options['amount']);
        $adjustment = $options['operation'] === 'percentage'
            ? $original->multipliedBy($amount)->dividedByExact('100')
            : $amount;
        $result = $options['direction'] === 'increase' ? $original->plus($adjustment) : $original->minus($adjustment);

        if ($options['rounding'] === 'precision') {
            return (string) $result->toScale(2, RoundingMode::HalfUp);
        }

        return (string) $result->dividedBy($options['rounding'], 0, RoundingMode::HalfUp)
            ->multipliedBy($options['rounding'])->toScale(2);
    }

    /** @param array{price: string, sale_price: ?string} $prices */
    public function invalidReason(array $prices): ?string
    {
        $regular = BigDecimal::of($prices['price']);
        if ($regular->isLessThan('0.01') || $regular->isGreaterThan('999999999')) {
            return 'regular_out_of_range';
        }
        if ($prices['sale_price'] !== null) {
            $sale = BigDecimal::of($prices['sale_price']);
            if ($sale->isLessThan('0.01') || $sale->isGreaterThan('999999999')) {
                return 'sale_out_of_range';
            }
            if ($sale->isGreaterThanOrEqualTo($regular)) {
                return 'sale_not_below_regular';
            }
        }

        return null;
    }
}
