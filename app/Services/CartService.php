<?php

namespace App\Services;

use App\Contracts\Purchasable;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Session + database backed cart.
 *
 * Guests keep their cart in the session. Authenticated customers get a
 * persistent cart (carts/cart_items) which is merged into their account on
 * login. Prices, stock and totals are always recomputed from the database.
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
     * @return Collection<int, CartItemValue>
     */
    public function items(): Collection
    {
        $entries = $this->rawEntries();

        $rows = collect($entries)->map(function (array $entry) {
            $purchasable = $this->resolve($entry['type'], $entry['id']);

            if ($purchasable === null) {
                return null;
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
                isAvailable: $purchasable->isAvailable() && $entry['quantity'] <= $available,
                stockLabel: $purchasable->stockLabel(),
            );
        })->filter()->values();

        return new Collection($rows->all());
    }

    public function add(Purchasable $purchasable, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);

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
        $purchasable = $this->resolve($type, $id);

        if (! $purchasable) {
            return;
        }

        $available = $this->inventory->availableQuantity($purchasable);
        $quantity = min($quantity, $available);

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

    public function subtotal(): float
    {
        return round($this->items()->sum(fn (CartItemValue $item) => $item->lineTotal()), 2);
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

            $available = $this->inventory->availableQuantity($purchasable);

            $existing = $cart->items()
                ->where('cartable_type', $cartableType)
                ->where('cartable_id', $entry['id'])
                ->first();

            $newQuantity = min($entry['quantity'], $available);

            if ($newQuantity <= 0) {
                continue;
            }

            if ($existing) {
                $existing->update(['quantity' => min($existing->quantity + $newQuantity, $available)]);
            } else {
                $cart->items()->create([
                    'cartable_type' => $cartableType,
                    'cartable_id' => $entry['id'],
                    'quantity' => $newQuantity,
                ]);
            }
        }
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
