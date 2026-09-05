<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->with('order:id,order_number,customer_name,total')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('provider'), fn ($q, $provider) => $q->where('provider', $provider))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $statuses = PaymentStatus::cases();

        return view('admin.payments.index', compact('payments', 'statuses'));
    }

    public function show(Payment $payment)
    {
        $payment->load('order');

        return view('admin.payments.show', compact('payment'));
    }
}
