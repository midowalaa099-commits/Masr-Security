<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountRequest;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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

    public function edit(): View
    {
        return view('account.edit');
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('account.edit')->with('success', __('auth_pages.account_updated'));
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
