<x-admin.layout title="{{ __('admin.orders') }}">

    @php
        $paymentBadges = [
            'pending' => 'bg-amber-100 text-amber-700',
            'success' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">← {{ __('admin.back') }}</a>
            <h1 class="font-mono text-lg font-extrabold text-slate-900" dir="ltr">{{ $order->order_number }}</h1>
        </div>
        @include('admin.orders.partials.status-badge', ['status' => $order->status])
    </div>

    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.order_items') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-3 py-2 text-start">{{ __('admin.products') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('admin.unit_price') }}</th>
                                <th class="px-3 py-2 text-center">{{ __('admin.quantity') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('admin.line_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-3 py-3">
                                        <span class="font-semibold text-slate-800">{{ $item->name_snapshot }}</span>
                                        <span class="block text-xs text-slate-400" dir="ltr">{{ $item->sku_snapshot }} · {{ $item->orderable_type === \App\Models\Package::class ? __('admin.packages') : __('admin.products') }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center text-slate-600">{{ money($item->unit_price) }}</td>
                                    <td class="px-3 py-3 text-center font-semibold text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-3 py-3 text-end font-bold text-slate-900">{{ money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200">
                            <tr><td colspan="3" class="px-3 py-2 text-start text-sm text-slate-500">{{ __('admin.subtotal') }}</td><td class="px-3 py-2 text-end font-bold text-slate-800">{{ money($order->subtotal) }}</td></tr>
                            <tr><td colspan="3" class="px-3 py-2 text-start text-sm text-slate-500">{{ __('admin.shipping_fee') }}</td><td class="px-3 py-2 text-end font-bold text-slate-800">{{ money($order->shipping_fee) }}</td></tr>
                            <tr><td colspan="3" class="px-3 py-2 text-start text-sm font-bold text-slate-900">{{ __('admin.total') }}</td><td class="px-3 py-2 text-end text-lg font-extrabold text-slate-900">{{ money($order->total) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.payments') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($order->payments as $payment)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 p-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.368A9.28 9.28 0 017.36 9h9.28c1.55 0 3.023.443 4.23 1.176M3 10.368V7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v2.868M3 10.368v6.132A1.5 1.5 0 004.5 18h15a1.5 1.5 0 001.5-1.5v-6.132" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $payment->method?->label() ?? $payment->method }}</p>
                                    <p class="text-xs text-slate-400">{{ $payment->provider }} · {{ $payment->paid_at?->diffForHumans() ?? $payment->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-slate-900">{{ money($payment->amount) }}</span>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $paymentBadges[$payment->status->value] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $payment->status->label() }}
                                </span>
                            </div>
                        </div>
                        @if ($payment->transaction_reference)
                            <p class="text-xs text-slate-400" dir="ltr">Ref: {{ $payment->transaction_reference }}</p>
                        @endif
                    @empty
                        <p class="py-4 text-center text-sm text-slate-400">{{ __('admin.no_payments') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.customer_info') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.customer_name_label') }}</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.phone') }}</dt>
                        <dd class="mt-0.5 text-slate-700" dir="ltr">{{ $order->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.email') }}</dt>
                        <dd class="mt-0.5 text-slate-700" dir="ltr">{{ $order->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.shipping_address') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $order->governorate }} · {{ $order->city }}<br>{{ $order->address_line }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.notes') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $order->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.update_status') }}</h2>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <select name="status" class="block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tracking_number" :value="__('admin.tracking_number')" />
                        <x-text-input id="tracking_number" name="tracking_number" class="mt-1 block w-full" :value="old('tracking_number', $order->tracking_number)" dir="ltr" />
                        <x-input-error :messages="$errors->get('tracking_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="admin_note" :value="__('admin.admin_note')" />
                        <textarea id="admin_note" name="admin_note" rows="2"
                            class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">{{ old('admin_note', $order->admin_note) }}</textarea>
                        <x-input-error :messages="$errors->get('admin_note')" class="mt-2" />
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.save') }}</button>
                </form>
            </div>
        </div>
    </div>

</x-admin.layout>