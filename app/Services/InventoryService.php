<?php

namespace App\Services;

use App\Contracts\Purchasable;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;

/**
 * Maintains optional, internal stock counts without limiting customer orders.
 *
 * Lifecycle of stock:
 *
 * 1. Products may have stock_quantity. A Package has no stock of its own.
 * 2. When an order is placed, each order item's components are snapshotted
 *    in `order_items.inventory_snapshot` (product_id => quantity).
 * 3. `decrementForOrder` is called inside the checkout transaction and locks
 *    product rows. Negative counts represent items to procure for open orders.
 * 4. `restoreForOrder` returns stock when an order is cancelled before
 *    fulfillment (status in pending/awaiting_payment/paid/processing).
 * 5. Once an order is shipped or delivered, stock is NOT restored on cancel.
 */
class InventoryService
{
    public function availableQuantity(Purchasable $purchasable): ?int
    {
        if ($purchasable instanceof Package) {
            // Package availability depends on its components.
            if (! $purchasable->relationLoaded('items')) {
                $purchasable->load('items.product');
            }

            return $purchasable->availableQuantity();
        }

        return $purchasable->availableQuantity();
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

        if ($available === 0 || ! $purchasable->isAvailable()) {
            throw new InsufficientStockException(
                __('store.insufficient_stock_exception', [
                    'name' => $purchasable->purchasableName(),
                    'available' => $available ?? 0,
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
                // Lock the row so internal counts remain consistent under concurrent orders.
                $product = Product::query()
                    ->whereKey($productId)
                    ->lockForUpdate()
                    ->first();

                if ($product === null) {
                    continue;
                }

                if ($product->stock_quantity === null) {
                    continue;
                }

                $newStock = (int) $product->stock_quantity - (int) $lineQuantity;

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
                    ->whereNotNull('stock_quantity')
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
