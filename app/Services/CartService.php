<?php

namespace App\Services;

use App\Contracts\Purchasable;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Session + database backed cart.
 *
 * Guests keep their cart in the session. Authenticated customers get a
 * persistent cart (carts/cart_items) which is merged into their account on
 * login. Prices and totals are always recomputed from the database.
 */
class CartService
{
    public const SESSION_KEY = 'cart.items';

    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    public function isPersistent(): bool
    {
        return auth()->check();
    }

    public function cart(): ?Cart
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return $user->cart ?? Cart::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Raw entries as ["type" => "product"|"package", "id" => int, "quantity" => int].
     *
     * @return array<int, array{type: string, id: int, quantity: int}>
     */
    public function rawEntries(): array
    {
        if ($this->isPersistent()) {
            return $this->cart()
                ->items()
                ->get()
                ->map(fn ($item) => [
                    'type' => $item->cartable_type === Product::class ? 'product' : 'package',
                    'id' => $item->cartable_id,
                    'quantity' => $item->quantity,
                ])
                ->all();
        }

        return collect(session()->get(self::SESSION_KEY, []))
            ->values()
            ->all();
    }

    /**
     * Checkout callers must hold a database transaction for the requested row locks.
     *
     * @return Collection<int, CartItemValue>
     */
    public function items(bool $lockForCheckout = false): Collection
    {
        $entries = $this->rawEntries();

        $packageIds = collect($entries)->where('type', 'package')->pluck('id');
        $packages = Package::query()->whereKey($packageIds)->orderBy('id')
            ->when($lockForCheckout, fn ($query) => $query->lockForUpdate())->get()->keyBy('id');
        $packageItems = PackageItem::query()->whereIn('package_id', $packageIds)->orderBy('id')
            ->when($lockForCheckout, fn ($query) => $query->lockForUpdate())->get();
        $productIds = collect($entries)->where('type', 'product')->pluck('id')
            ->merge($packageItems->pluck('product_id'))->unique();
        $products = Product::query()->with('images')->whereKey($productIds)->orderBy('id')
            ->when($lockForCheckout, fn ($query) => $query->lockForUpdate())->get()->keyBy('id');

        foreach ($packageItems as $packageItem) {
            $packageItem->setRelation('product', $products->get($packageItem->product_id));
        }

        foreach ($packages as $package) {
            $package->setRelation('items', $packageItems->where('package_id', $package->id)->values());
        }

        $rows = collect($entries)->map(function (array $entry) use ($products, $packages, $lockForCheckout) {
            $purchasable = ($entry['type'] === 'package' ? $packages : $products)->get($entry['id']);

            if ($purchasable === null || $purchasable->status !== ProductStatus::Active
                || ($purchasable instanceof Package && $purchasable->items->contains(fn (PackageItem $item): bool => $item->product === null))) {
                if ($lockForCheckout) {
                    throw new InsufficientStockException(__('store.cart_unavailable_review'));
                }

                return null;
            }

            if ($lockForCheckout) {
                $this->inventory->assertSufficientStock($purchasable, $entry['quantity']);
            }

            $available = $this->inventory->availableQuantity($purchasable);

            return new CartItemValue(
                type: $entry['type'],
                id: $entry['id'],
                model: $purchasable,
                name: $purchasable->purchasableName(),
                imageUrl: $purchasable->firstImageUrl(),
                unitPrice: $purchasable->displayPrice(),
                originalPrice: $purchasable->originalPrice(),
                quantity: $entry['quantity'],
                availableQuantity: $available,
                isAvailable: $purchasable->isAvailable(),
                stockLabel: $purchasable->stockLabel(),
            );
        })->filter()->values();

        return new Collection($rows->all());
    }

    public function add(Purchasable $purchasable, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);
        session()->forget('cart.review_prices');
        $priceKey = 'cart.added_prices.'.$purchasable->cartTypeKey().':'.$purchasable->getKey();

        session()->put($priceKey, $purchasable->displayPrice());

        $entry = ['type' => $purchasable->cartTypeKey(), 'id' => $purchasable->getKey()];

        if ($this->isPersistent()) {
            DB::transaction(function () use ($entry, $quantity) {
                $cart = $this->cart();

                $item = $cart->items()
                    ->where('cartable_type', $this->cartableTypeFor($entry['type']))
                    ->where('cartable_id', $entry['id'])
                    ->first();

                if ($item === null) {
                    $cart->items()->create([
                        'cartable_type' => $this->cartableTypeFor($entry['type']),
                        'cartable_id' => $entry['id'],
                        'quantity' => $quantity,
                    ]);

                    return;
                }

                $this->updateQuantity($entry['type'], $entry['id'], $item->quantity + $quantity);
            });

            return;
        }

        $items = session()->get(self::SESSION_KEY, []);

        $found = false;

        foreach ($items as &$row) {
            if ($row['type'] === $entry['type'] && $row['id'] === $entry['id']) {
                $row['quantity'] += $quantity;
                $found = true;

                break;
            }
        }

        if (! $found) {
            $items[] = ['type' => $entry['type'], 'id' => $entry['id'], 'quantity' => $quantity];
        }

        session()->put(self::SESSION_KEY, array_values($items));
    }

    public function updateQuantity(string $type, int $id, int $quantity): void
    {
        session()->forget('cart.review_prices');

        if ($quantity <= 0) {
            $this->remove($type, $id);

            return;
        }

        $purchasable = $this->resolve($type, $id);

        if (! $purchasable) {
            return;
        }

        if ($this->isPersistent()) {
            $query = $this->cart()->items()
                ->where('cartable_type', $this->cartableTypeFor($type))
                ->where('cartable_id', $id);

            if ($quantity <= 0) {
                $query->delete();
            } else {
                $query->update(['quantity' => $quantity]);
            }

            return;
        }

        $items = collect(session()->get(self::SESSION_KEY, []))
            ->map(function ($row) use ($type, $id, $quantity) {
                if ($row['type'] === $type && $row['id'] === $id) {
                    $row['quantity'] = $quantity;
                }

                return $row;
            })
            ->filter(fn ($row) => $row['quantity'] > 0)
            ->values()
            ->all();

        session()->put(self::SESSION_KEY, $items);
    }

    public function remove(string $type, int $id): void
    {
        session()->forget(['cart.added_prices.'.$type.':'.$id, 'cart.review_prices']);
        if ($this->isPersistent()) {
            $this->cart()->items()
                ->where('cartable_type', $this->cartableTypeFor($type))
                ->where('cartable_id', $id)
                ->delete();

            return;
        }

        $items = collect(session()->get(self::SESSION_KEY, []))
            ->reject(fn ($row) => $row['type'] === $type && $row['id'] === $id)
            ->values()
            ->all();

        session()->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        session()->forget(['cart.added_prices', 'cart.review_prices']);
        if ($this->isPersistent()) {
            $this->cart()?->items()?->delete();

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function count(): int
    {
        if ($this->isPersistent()) {
            return (int) $this->cart()?->items()->sum('quantity') ?? 0;
        }

        return (int) collect(session()->get(self::SESSION_KEY, []))->sum('quantity');
    }

    /** @param Collection<int, CartItemValue>|null $items */
    public function subtotal(?Collection $items = null): string
    {
        $total = BigDecimal::of('0.00');
        foreach ($items ?? $this->items() as $item) {
            $total = $total->plus($item->lineTotal());
        }

        return (string) $total->toScale(2);
    }

    /**
     * Move a guest session cart into the authenticated user's persistent cart.
     */
    public function mergeSessionIntoDatabase(User $user): void
    {
        $sessionEntries = session()->pull(self::SESSION_KEY, []);

        if ($sessionEntries === []) {
            return;
        }

        $cart = $user->cart ?? Cart::create(['user_id' => $user->id]);

        foreach ($sessionEntries as $entry) {
            $cartableType = $this->cartableTypeFor($entry['type']);
            $purchasable = $this->resolve($entry['type'], $entry['id']);

            if (! $purchasable) {
                continue;
            }

            if ($this->inventory->availableQuantity($purchasable) === 0) {
                continue;
            }

            $existing = $cart->items()
                ->where('cartable_type', $cartableType)
                ->where('cartable_id', $entry['id'])
                ->first();

            $newQuantity = $entry['quantity'];

            if ($newQuantity <= 0) {
                continue;
            }

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $newQuantity]);
            } else {
                $cart->items()->create([
                    'cartable_type' => $cartableType,
                    'cartable_id' => $entry['id'],
                    'quantity' => $newQuantity,
                ]);
            }
        }
    }

    /** @param Collection<int, CartItemValue> $items */
    public function pricesNeedReview(Collection $items): bool
    {
        $prices = session('cart.review_prices', session('cart.added_prices', []));

        foreach ($items as $item) {
            if (($prices[$item->key()] ?? null) !== $item->unitPrice) {
                return true;
            }
        }

        return false;
    }

    /** @param Collection<int, CartItemValue> $items */
    public function rememberReviewedPrices(Collection $items): void
    {
        session()->put('cart.review_prices', $items->mapWithKeys(
            fn (CartItemValue $item): array => [$item->key() => $item->unitPrice],
        )->all());
    }

    private function resolve(string $type, int $id): ?Purchasable
    {
        if ($type === 'package') {
            return Package::query()
                ->with('items.product')
                ->whereKey($id)
                ->where('status', ProductStatus::Active->value)
                ->first();
        }

        return Product::query()
            ->with('images')
            ->whereKey($id)
            ->where('status', ProductStatus::Active->value)
            ->first();
    }

    private function cartableTypeFor(string $type): string
    {
        return $type === 'package' ? Package::class : Product::class;
    }
}
