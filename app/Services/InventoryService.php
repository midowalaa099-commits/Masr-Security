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
        $components = $this->orderComponents($order);
        $products = Product::query()->whereKey(array_keys($components))->orderBy('id')->lockForUpdate()->get();

        foreach ($products as $product) {
            if ($product->stock_quantity !== null) {
                $product->update(['stock_quantity' => (int) $product->stock_quantity - $components[$product->id]]);
            }
        }
    }

    /**
     * Restore stock for an order that is cancelled before fulfillment.
     */
    public function restoreForOrder(Order $order): void
    {
        foreach ($this->orderComponents($order) as $productId => $quantity) {
            Product::query()->whereKey($productId)->whereNotNull('stock_quantity')->increment('stock_quantity', $quantity);
        }
    }

    /** @return array<int, int> */
    private function orderComponents(Order $order): array
    {
        $totals = [];
        foreach ($order->items as $item) {
            foreach ($this->lineComponents($item->orderable_type, $item->orderable_id, $item->inventory_snapshot, $item->quantity) as $id => $quantity) {
                $totals[$id] = ($totals[$id] ?? 0) + $quantity;
            }
        }

        return $totals;
    }

    /**
     * @return array<int, int> product_id => quantity
     */
    private function lineComponents(string $orderableType, int $orderableId, ?array $snapshot, int $quantity): array
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
                    $map[$item->product_id] = ($map[$item->product_id] ?? 0) + $item->quantity * $quantity;
                }
            }

            return $map;
        }

        return is_a($orderableType, Product::class, true) ? [$orderableId => $quantity] : [];
    }
}
