<?php

namespace App\Contracts;

use App\Models\Product;

/**
 * Anything a customer can add to the cart: a standalone Product or a Package.
 *
 * The cart and checkout services rely on this contract to read fresh
 * prices and stock from the database instead of trusting the browser.
 */
interface Purchasable
{
    /**
     * Discriminator stored on the cart / order item ("product" | "package").
     */
    public function cartTypeKey(): string;

    public function purchasableName(): string;

    /**
     * The price the customer effectively pays (already discounted).
     *
     * Always an exact two-decimal monetary string (e.g. "2600.00"), matching
     * the decimal(10,2) columns used through cart, order and payment storage.
     */
    public function displayPrice(): string;

    /**
     * The price before discounts, used to show savings.
     *
     * Always an exact two-decimal monetary string (e.g. "3000.00").
     */
    public function originalPrice(): string;

    /**
     * Whether the item can currently be added to the cart
     * (for a package this depends on its components).
     */
    public function isAvailable(): bool;

    public function isOutOfStock(): bool;

    public function stockLabel(): string;

    public function firstImageUrl(): ?string;
}
