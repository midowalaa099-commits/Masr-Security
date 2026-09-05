<x-store.layout>

    @php
        $badges = [
            'pending' => 'bg-amber-100 text-amber-700',
            'awaiting_payment' => 'bg-amber-100 text-amber-700',
            'paid' => 'bg-emerald-100 text-emerald-700',
            'processing' => 'bg-sky-100 text-sky-700',
            'shipped' => 'bg-indigo-100 text-indigo-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('account.partials.nav')

            <div class="min-w-0 flex-1">
                <nav class="text-xs text-slate-400">
                    <a href="{{ route('account.orders') }}" class="hover:text-brand-600">{{ __('store.orders') }}</a>
                    <span class="mx-1.5">/</span>
                    <span class="text-slate-600" dir="ltr">{{ $order->order_number }}</span>
                </nav>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-xl font-bold text-slate-900" dir="ltr">{{ $order->order_number }}</h1>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $badges[$order->status->value] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ __('store.status_'.$order->status->value) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $order->created_at->translatedFormat('j F Y · g:i A') }}</p>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Items -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <h2 class="border-b border-slate-100 px-5 py-4 text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.items') }}</h2>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($order->items as $item)
                                <li class="flex items-center justify-between gap-4 px-5 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $item->name_snapshot }}</p>
                                        <p class="text-xs text-slate-400" dir="ltr">{{ $item->sku_snapshot ?? '' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <p class="text-sm font-bold text-slate-900">{{ money($item->line_total) }}</p>
                                        <p class="text-xs text-slate-400">{{ money($item->unit_price) }} × {{ $item->quantity }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <dl class="space-y-2 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">{{ __('store.subtotal') }}</dt>
                                <dd class="font-semibold text-slate-900">{{ money($order->subtotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">{{ __('store.shipping') }}</dt>
                                <dd class="font-semibold text-slate-900">{{ money($order->shipping_fee) }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                                <dt class="text-base font-bold text-slate-900">{{ __('store.total') }}</dt>
                                <dd class="text-lg font-extrabold text-brand-800">{{ money($order->total) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Details -->
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('admin.shipping_address') }}</h2>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.customer_name') }}</dt><dd class="font-semibold text-slate-800">{{ $order->customer_name }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.phone') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $order->phone }}</dd></div>
                                @if ($order->email)
                                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.email') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $order->email }}</dd></div>
                                @endif
                                @if ($order->governorate)
                                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.governorate') }}</dt><dd class="font-semibold text-slate-800">{{ $order->governorate }}</dd></div>
                                @endif
                                @if ($order->city)
                                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.city') }}</dt><dd class="font-semibold text-slate-800">{{ $order->city }}</dd></div>
                                @endif
                                @if ($order->address_line)
                                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('store.address_line') }}</dt><dd class="font-semibold text-slate-800">{{ $order->address_line }}</dd></div>
                                @endif
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('store.payment_method') }}</h2>
                            <ul class="mt-3 space-y-3">
                                @forelse ($order->payments as $payment)
                                    <li class="flex items-center justify-between gap-4 text-sm">
                                        <span>
                                            <span class="font-semibold text-slate-800">
                                                {{ $payment->method === 'wallet' ? __('payments.method_wallet') : __('payments.method_card') }}
                                            </span>
                                            <span class="block text-xs text-slate-400">{{ $payment->provider }} · {{ $payment->created_at->translatedFormat('j M Y') }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-2">
                                            <span class="font-bold text-slate-900">{{ money($payment->amount) }}</span>
                                            @if ($payment->isSuccessful())
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">{{ __('admin.paid') }}</span>
                                            @elseif ($payment->status->isFinal())
                                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">{{ __('store.status_cancelled') }}</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">{{ __('payments.payment_pending') }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @empty
                                    <li class="text-sm text-slate-400">{{ __('admin.order_no_payment') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-store.layout>