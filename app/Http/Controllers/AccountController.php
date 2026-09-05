<?php

namespace App\Http\Controllers;

use App\Models\Order;

class AccountController extends Controller
{
    public function dashboard()
    {
        $orders = auth()->user()
            ->orders()
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('account.dashboard', compact('orders'));
    }

    public function orders()
    {
        $orders = auth()->user()
            ->orders()
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items', 'payments']);

        return view('account.order', compact('order'));
    }
}
