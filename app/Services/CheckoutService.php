<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\CartEmptyException;
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
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{customer_name: string, phone: string, email: ?string, governorate: ?string, city: ?string, address_line: ?string, notes: ?string, payment_method: string}  $customerData
     *
     * @throws CartEmptyException
     * @throws InsufficientStockException
     */
    public function placeOrder(array $customerData): Order
    {
        return DB::transaction(function () use ($customerData) {
            /** @var Collection<int, CartItemValue> $cartItems */
            $cartItems = $this->cart->items();

            if ($cartItems->isEmpty()) {
                throw new CartEmptyException;
            }

            $this->ensureStockUnderLock($cartItems);

            $subtotal = round($cartItems->sum(fn (CartItemValue $item) => (float) $item->lineTotal()), 2);
            $shippingFee = $subtotal > 0 ? (float) $this->settings->get('shipping_fee', 0) : 0.0;

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

    /**
     * Lock the physical product rows involved in this cart and re-validate
     * availability against the locked (most current) values.
     *
     * @param  Collection<int, CartItemValue>  $cartItems
     *
     * @throws InsufficientStockException
     */
    private function ensureStockUnderLock(Collection $cartItems): void
    {
        $productIds = collect($cartItems)->flatMap(function (CartItemValue $item) {
            if ($item->type === 'product') {
                return [$item->id];
            }

            return $item->model->items->pluck('product_id');
        })->unique()->values()->all();

        /** @var Collection<int, Product> $lockedProducts */
        $lockedProducts = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($cartItems as $item) {
            $available = $item->type === 'package'
                ? $this->lockedPackageAvailability($item->model, $lockedProducts)
                : (int) ($lockedProducts->get($item->id)?->stock_quantity ?? 0);

            if ($item->quantity > $available) {
                throw new InsufficientStockException(
                    __('store.insufficient_stock_exception', [
                        'name' => $item->name,
                        'available' => $available,
                    ]),
                );
            }
        }
    }

    /**
     * @param  Collection<int, Product>  $lockedProducts
     */
    private function lockedPackageAvailability(Package $package, Collection $lockedProducts): int
    {
        if ($package->items->isEmpty()) {
            return 0;
        }

        $min = null;

        foreach ($package->items as $packageItem) {
            $product = $lockedProducts->get($packageItem->product_id);

            if ($product === null) {
                return 0;
            }

            $possible = intdiv((int) $product->stock_quantity, max(1, (int) $packageItem->quantity));

            $min = $min === null ? $possible : min($min, $possible);
        }

        return $min ?? 0;
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
        if ($subtotal <= 0) {
            return 0.0;
        }

        return (float) $this->settings->get('shipping_fee', 0);
    }
}
