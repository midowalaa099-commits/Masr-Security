<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Package;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index()
    {
        $items = $this->cart->items();

        return view('store.cart', [
            'items' => $items,
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:product,package'],
            'cartable' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $purchasable = $validated['type'] === 'package'
            ? Package::query()->with('items.product')->where('status', 'active')->findOrFail($validated['cartable'])
            : Product::query()->where('status', 'active')->findOrFail($validated['cartable']);

        try {
            app(InventoryService::class)->assertSufficientStock($purchasable, (int) $validated['quantity']);
            $this->cart->add($purchasable, (int) $validated['quantity']);
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('cart.index')
            ->with('success', __('store.added_to_cart'));
    }

    public function update(Request $request, string $type, mixed $cartable)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->cart->updateQuantity($type, (int) $cartable, (int) $request->quantity);

        return back()->with('success', __('store.cart_updated'));
    }

    public function remove(string $type, mixed $cartable)
    {
        $this->cart->remove($type, (int) $cartable);

        return back()->with('success', __('store.cart_updated'));
    }

    public function clear()
    {
        $this->cart->clear();

        return back()->with('success', __('store.cart_cleared'));
    }
}
