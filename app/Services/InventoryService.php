<?php

namespace App\Services;

use App\Contracts\Purchasable;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;

/**
 * Single source of truth for stock checks and mutations.
 *
 * Lifecycle of stock:
 *
 * 1. Products hold stock_quantity. A Package has no stock of its own;
 *    its availability is derived from the components in package_items.
 * 2. When an order is placed, each order item's components are snapshotted
 *    in `order_items.inventory_snapshot` (product_id => quantity).
 * 3. `decrementForOrder` is called inside the checkout transaction and locks
 *    product rows (`lockForUpdate`) to prevent double spending under
 *    concurrency. Stock is never allowed to go negative.
 * 4. `restoreForOrder` returns stock when an order is cancelled before
 *    fulfillment (status in pending/awaiting_payment/paid/processing).
 * 5. Once an order is shipped or delivered, stock is NOT restored on cancel.
 */
class InventoryService
{
    public function availableQuantity(Purchasable $purchasable): int
    {
        if ($purchasable instanceof Package) {
            // Package availability depends on its components.
            if (! $purchasable->relationLoaded('items')) {
                $purchasable->load('items.product');
            }

            return $purchasable->availableQuantity();
        }

        return max(0, (int) $purchasable->stock_quantity);
    }

    /**
     * @throws InsufficientStockException
     */
    public function assertSufficientStock(Purchasable $purchasable, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InsufficientStockException($purchasable->purchasableName().' '.__('store.invalid_quantity'));
        }

        $available = $this->availableQuantity($purchasable);

        if ($quantity > $available) {
            throw new InsufficientStockException(
                __('store.insufficient_stock_exception', [
                    'name' => $purchasable->purchasableName(),
                    'available' => $available,
                ]),
            );
        }
    }

    /**
     * Decrement stock for every physical component in the order.
     * Must be called inside a database transaction.
     */
    public function decrementForOrder(Order $order): void
    {
        foreach ($order->items as $orderItem) {
            $components = $this->lineComponents($orderItem->orderable_type, $orderItem->orderable_id, $orderItem->inventory_snapshot);

            foreach ($components as $productId => $lineQuantity) {
                // Lock the row so competing checkouts cannot oversell.
                $product = Product::query()
                    ->whereKey($productId)
                    ->lockForUpdate()
                    ->first();

                if ($product === null) {
                    continue;
                }

                $newStock = max(0, (int) $product->stock_quantity - (int) $lineQuantity);

                $product->update(['stock_quantity' => $newStock]);
            }
        }
    }

    /**
     * Restore stock for an order that is cancelled before fulfillment.
     */
    public function restoreForOrder(Order $order): void
    {
        foreach ($order->items as $orderItem) {
            $components = $this->lineComponents($orderItem->orderable_type, $orderItem->orderable_id, $orderItem->inventory_snapshot);

            foreach ($components as $productId => $lineQuantity) {
                Product::query()
                    ->whereKey($productId)
                    ->increment('stock_quantity', (int) $lineQuantity);
            }
        }
    }

    /**
     * @return array<int, int> product_id => quantity
     */
    private function lineComponents(string $orderableType, int $orderableId, ?array $snapshot): array
    {
        if ($snapshot !== null && $snapshot !== []) {
            return $snapshot;
        }

        // Fallback for legacy rows without a snapshot.
        if (is_a($orderableType, Package::class, true)) {
            $map = [];
            $package = Package::query()->with('items')->find($orderableId);

            if ($package !== null) {
                foreach ($package->items as $item) {
                    $map[$item->product_id] = $item->quantity;
                }
            }

            return $map;
        }

        return [];
    }
}
