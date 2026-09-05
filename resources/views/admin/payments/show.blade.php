<x-admin.layout title="{{ __('admin.payments') }}">

    @php
        $badges = [
            'pending' => 'bg-amber-100 text-amber-700',
            'success' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];
        $raw = $payment->raw_response;
    @endphp

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">← {{ __('admin.back') }}</a>
        <h1 class="text-lg font-extrabold text-slate-900">#{{ $payment->id }}</h1>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.payment_details') }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.method_label') }}</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $payment->method?->label() ?? $payment->method }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.provider') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $payment->provider }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.amount_label') }}</dt>
                        <dd class="mt-0.5 font-bold text-slate-900">{{ money($payment->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.status') }}</dt>
                        <dd class="mt-0.5">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $badges[$payment->status->value] ?? 'bg-slate-100 text-slate-500' }}">{{ $payment->status->label() }}</span>
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.order_number_label') }}</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-mono font-semibold text-brand-700 hover:text-brand-800" dir="ltr">{{ $payment->order->order_number }}</a>
                            <span class="block text-xs text-slate-400">{{ $payment->order->customer_name }} · {{ money($payment->order->total) }}</span>
                        </dd>
                    </div>
                    @if ($payment->transaction_reference)
                        <div class="col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.transaction_ref') }}</dt>
                            <dd class="mt-0.5 font-mono text-sm text-slate-700" dir="ltr">{{ $payment->transaction_reference }}</dd>
                        </div>
                    @endif
                    @if ($payment->paymob_transaction_id)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paymob TX</dt>
                            <dd class="mt-0.5 font-mono text-sm text-slate-700" dir="ltr">{{ $payment->paymob_transaction_id }}</dd>
                        </div>
                    @endif
                    @if ($payment->paid_at)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.paid_at') }}</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $payment->paid_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.raw_payload') }}</h2>
                @if ($raw)
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs leading-relaxed text-slate-100" dir="ltr">{{ json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="mt-4 text-sm text-slate-400">—</p>
                @endif
            </div>
        </div>
    </div>

</x-admin.layout>