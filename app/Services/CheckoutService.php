<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\CartEmptyException;
use App\Exceptions\CartPriceChangedException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates orders from the cart inside a single database transaction.
 *
 * Money safety: every total is recomputed from rows locked in this
 * transaction — never from values submitted by the browser.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array{customer_name: string, phone: string, email: ?string, governorate: ?string, city: ?string, address_line: ?string, notes: ?string, payment_method: string}  $customerData
     *
     * @throws CartEmptyException
     * @throws CartPriceChangedException
     * @throws InsufficientStockException
     */
    public function placeOrder(array $customerData): Order
    {
        return DB::transaction(function () use ($customerData) {
            /** @var Collection<int, CartItemValue> $cartItems */
            $cartItems = $this->cart->items(lockForCheckout: true);

            if ($cartItems->isEmpty()) {
                throw new CartEmptyException;
            }

            if ($this->cart->pricesNeedReview($cartItems)) {
                throw new CartPriceChangedException;
            }

            $subtotal = round($cartItems->sum(fn (CartItemValue $item) => (float) $item->lineTotal()), 2);
            $shippingFee = 0.0;

            $order = new Order($customerData + [
                'user_id' => auth()->id(),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'shipping_fee' => number_format($shippingFee, 2, '.', ''),
                'total' => number_format(round($subtotal + $shippingFee, 2), 2, '.', ''),
                'status' => OrderStatus::Pending,
            ]);

            $order->order_number = $this->generateOrderNumber();
            $order->save();

            foreach ($cartItems as $item) {
                $order->items()->create([
                    'orderable_type' => $item->type === 'package' ? Package::class : Product::class,
                    'orderable_id' => $item->id,
                    'name_snapshot' => $item->name,
                    'sku_snapshot' => $item->type === 'product' ? $item->model->sku : null,
                    'inventory_snapshot' => $this->inventorySnapshot($item),
                    'unit_price' => $item->unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $item->lineTotal(),
                ]);
            }

            $this->inventory->decrementForOrder($order);

            $this->cart->clear();

            return $order->fresh(['items']);
        });
    }

    /**
     * @return array<int, int> product_id => total quantity
     */
    private function inventorySnapshot(CartItemValue $item): array
    {
        if ($item->type === 'product') {
            return [$item->id => $item->quantity];
        }

        $map = [];

        /** @var Package $package */
        $package = $item->model;

        foreach ($package->items as $packageItem) {
            $map[$packageItem->product_id] = $packageItem->quantity * $item->quantity;
        }

        return $map;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'MSR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    public function calculateShipping(float $subtotal): float
    {
        return 0.0;
    }
}
