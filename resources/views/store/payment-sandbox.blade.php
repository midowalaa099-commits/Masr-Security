<x-store.layout>

    <div class="mx-auto max-w-xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="px-6 py-8 text-center sm:px-10">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                    <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.36 3.279-1.45 3.402H16.5c0 1.5-.75 2.25-2.25 2.25s-2.25-.75-2.25-2.25H9.75c0 1.5-.75 2.25-2.25 2.25s-2.25-.75-2.25-2.25H7.5c-1.81-.123-2.682-2.17-1.45-3.402L7.5 14.5m-2.5 0H3v-3a2.25 2.25 0 012.25-2.25h5.25" /></svg>
                </span>

                <h1 class="mt-5 text-xl font-extrabold text-slate-900">{{ __('payments.sandbox_title') }}</h1>
                <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500">{{ __('payments.sandbox_intro') }}</p>
            </div>

            <div class="border-t border-slate-100 bg-slate-50 px-6 py-6 sm:px-10">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.order_number') }}</dt>
                        <dd class="font-mono font-bold text-slate-900" dir="ltr">{{ $order->order_number }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('admin.amount_label') }}</dt>
                        <dd class="font-bold text-slate-900">{{ money($payment->amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.payment_method') }}</dt>
                        <dd class="font-semibold text-slate-700">{{ $payment->method === \App\Enums\PaymentMethod::Wallet ? __('payments.method_wallet') : __('payments.method_card') }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <form method="POST" action="{{ route('payments.sandbox.complete', $payment) }}">
                        @csrf
                        <input type="hidden" name="result" value="success">
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                            {{ __('payments.sandbox_pay') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('payments.sandbox.complete', $payment) }}">
                        @csrf
                        <input type="hidden" name="result" value="failed">
                        <button type="submit" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                            {{ __('payments.sandbox_failed') }}
                        </button>
                    </form>
                </div>

                <p class="mt-4 flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    {{ __('payments.sandbox_note') }}
                </p>
            </div>
        </div>
    </div>

</x-store.layout>