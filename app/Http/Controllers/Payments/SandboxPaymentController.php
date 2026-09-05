<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentCallbackResult;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/**
 * Local simulator used while the gateway runs without real Paymob credentials.
 *
 * It does NOT fake a payment: it presents an explicit "sandbox" page where a
 * human operator picks an outcome, and then feeds that outcome through the
 * exact same idempotent state transition used for real webhooks. This route
 * is disabled when real Paymob credentials are configured.
 */
class SandboxPaymentController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function show(Payment $payment)
    {
        abort_unless($this->payments->gateway()->isSandboxMode(), 404);

        $order = $payment->order()->with('items')->firstOrFail();

        return view('store.payment-sandbox', compact('payment', 'order'));
    }

    public function complete(Request $request, Payment $payment)
    {
        abort_unless($this->payments->gateway()->isSandboxMode(), 404);

        $order = $payment->order()->firstOrFail();

        $isOwner = $request->user() !== null && $request->user()->id === $order->user_id;
        $isRecentOrder = in_array($order->order_number, (array) $request->session()->get('recent_order_numbers', []), true);

        abort_unless($isOwner || $isRecentOrder, 403);

        abort_unless($payment->status === PaymentStatus::Pending || ! $payment->status->isFinal(), 422);

        $result = $request->validate(['result' => ['required', 'in:success,failed']])['result'];

        $status = $result === 'success' ? PaymentStatus::Success : PaymentStatus::Failed;

        $reference = 'SANDBOX-'.now()->format('YmdHis').'-'.$payment->id;

        $callbackResult = new PaymentCallbackResult(
            payment: $payment->fresh(),
            status: $status,
            transactionReference: $reference,
            providerTransactionId: $reference,
            providerOrderId: null,
            raw: ['sandbox' => true, 'simulated' => true, 'at' => now()->toIso8601String()],
        );

        $this->payments->applyResult($callbackResult);

        if ($status === PaymentStatus::Failed) {
            return redirect()->route('payments.sandbox', $payment)
                ->with('error', __('payments.simulated_failed'));
        }

        return redirect()->route('checkout.success', $payment->order)
            ->with('success', __('payments.simulated_success'));
    }
}
