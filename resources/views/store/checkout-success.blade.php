<x-store.layout>

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">

        @php
            $succeeded = $payment && $payment->isSuccessful();
            $pending = $payment && ! $payment->status->isFinal();
        @endphp

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="px-6 py-10 text-center sm:px-12">
                @if ($succeeded)
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-9 w-9 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                @elseif ($pending)
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                        <svg class="h-9 w-9 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                @else
                    <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-full bg-rose-100">
                        <svg class="h-9 w-9 text-rose-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </span>
                @endif

                <h1 class="mt-5 text-2xl font-bold text-slate-900">
                    {{ $succeeded ? __('store.order_success_title') : ($pending ? __('payments.payment_pending') : __('store.order_failed_text')) }}
                </h1>

                <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600">
                    {{ __('store.order_number') }}
                    <span class="font-mono font-bold text-slate-900" dir="ltr">{{ $order->order_number }}</span>
                </div>

                @if (! $succeeded && ! $pending)
                    <p class="mx-auto mt-4 max-w-md text-sm text-slate-500">{{ __('store.order_failed_text') }}</p>
                @endif
            </div>

            <!-- Order summary -->
            <div class="border-t border-slate-100 px-6 py-6 sm:px-12">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('store.cart') }}</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-4 text-sm">
                            <span class="min-w-0 flex-1 truncate text-slate-700">
                                <span class="font-semibold text-slate-900">{{ $item->name_snapshot }}</span>
                                <span class="text-slate-400"> × {{ $item->quantity }}</span>
                            </span>
                            <span class="font-semibold text-slate-900">{{ money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.subtotal') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ money($order->subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.shipping') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ money($order->shipping_fee) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="text-base font-bold text-slate-900">{{ __('store.total') }}</dt>
                        <dd class="text-xl font-extrabold text-brand-800">{{ money($order->total) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-col items-center gap-3 border-t border-slate-100 bg-slate-50 px-6 py-6 sm:flex-row sm:justify-center">
                <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-800 sm:w-auto">
                    {{ __('store.back_to_home') }}
                </a>
                <a href="{{ route('shop') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100 sm:w-auto">
                    {{ __('store.continue_shopping') }}
                </a>
                @auth
                    <a href="{{ route('account.orders.show', $order) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100 sm:w-auto">
                        {{ __('store.track_order') }}
                    </a>
                @endauth
            </div>
        </div>
    </div>

</x-store.layout>