<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $orders = auth()->user()
            ->orders()
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('account.dashboard', compact('orders'));
    }

    public function password(): View
    {
        return view('account.password');
    }

    public function orders(): View
    {
        $orders = auth()->user()
            ->orders()
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['items', 'payments']);

        return view('account.order', compact('order'));
    }
}
