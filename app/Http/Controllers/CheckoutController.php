<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Exceptions\CartEmptyException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PaymentGatewayException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly SettingsService $settings,
    ) {}

    public function index()
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('store.cart_empty'));
        }

        $subtotal = $this->cart->subtotal();
        $shippingFee = $this->checkout->calculateShipping($subtotal);

        return view('store.checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'total' => round($subtotal + $shippingFee, 2),
            'paymentMethods' => PaymentMethod::cases(),
            'preset' => [
                'name' => auth()->user()?->name,
                'email' => auth()->user()?->email,
                'phone' => auth()->user()?->phone,
            ],
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        try {
            $order = $this->checkout->placeOrder($request->safe([
                'customer_name',
                'phone',
                'email',
                'governorate',
                'city',
                'address_line',
                'notes',
            ]));
        } catch (CartEmptyException) {
            return redirect()->route('cart.index')->with('error', __('store.cart_empty'));
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        session([
            'recent_order_numbers' => collect((array) session('recent_order_numbers', []))
                ->push($order->order_number)
                ->unique()
                ->take(5)
                ->values()
                ->all(),
        ]);

        $method = PaymentMethod::from($request->validated('payment_method'));
        $payment = $this->payments->startPayment($order, $method);

        if (! $this->payments->gateway()->supportsMethod($method)) {
            return redirect()->route('checkout.return', $payment)->with('error', __('payments.method_unavailable'));
        }

        try {
            $result = $this->payments->redirect($payment, $method);
        } catch (PaymentGatewayException $e) {
            Log::warning('checkout.gateway_redirect_failed', ['order' => $order->id]);

            return redirect()->route('checkout.return', $payment)->with('error', __('payments.gateway_unavailable'));
        }

        return redirect()->away($result['redirect_url']);
    }

    public function return(Payment $payment)
    {
        // Let the (HMAC authenticated) redirect or webhook update payment state.
        if (request()->query->count() > 0) {
            $this->payments->handleRedirect(request()->query());
        }

        $order = $payment->order;

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order)
    {
        $order->load(['items', 'payments']);

        $allowed = auth()->check() && auth()->id() === $order->user_id
            || in_array($order->order_number, (array) session('recent_order_numbers', []), true);

        abort_unless($allowed, 404);

        $payment = $order->payments()->latest()->first();

        return view('store.checkout-success', compact('order', 'payment'));
    }
}
