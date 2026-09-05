@props(['orders'])

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

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-start text-xs font-bold uppercase tracking-wider text-slate-400">
                <th class="px-4 py-3 text-start">{{ __('store.order_number') }}</th>
                <th class="px-4 py-3 text-start">{{ __('store.date') }}</th>
                <th class="px-4 py-3 text-center">{{ __('admin.items') }}</th>
                <th class="px-4 py-3 text-start">{{ __('store.total') }}</th>
                <th class="px-4 py-3 text-start">{{ __('store.status') }}</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono font-semibold text-brand-700" dir="ltr">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $order->created_at->translatedFormat('j M Y') }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $order->items_count }}</td>
                    <td class="px-4 py-3 font-bold text-slate-900">{{ money($order->total) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $badges[$order->status->value] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ __('store.status_'.$order->status->value) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-end">
                        <a href="{{ route('account.orders.show', $order) }}" class="text-xs font-bold text-brand-700 hover:text-brand-800">{{ __('store.track_order') }}</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>