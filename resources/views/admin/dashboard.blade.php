<x-admin.layout title="{{ __('admin.dashboard') }}">

    @php
        $statCards = [
            ['label' => __('admin.total_revenue'), 'value' => money($revenue), 'icon' => 'money', 'color' => 'bg-emerald-100 text-emerald-600'],
            ['label' => __('admin.total_orders'), 'value' => number_format($totalOrders), 'icon' => 'cart', 'color' => 'bg-brand-100 text-brand-700'],
            ['label' => __('admin.pending_orders'), 'value' => number_format($pendingOrders), 'icon' => 'clock', 'color' => 'bg-amber-100 text-amber-600'],
            ['label' => __('admin.paid'), 'value' => number_format($paidOrders), 'icon' => 'check', 'color' => 'bg-sky-100 text-sky-600'],
        ];
        $icons = [
            'money' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'cart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />',
            'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $card['color'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $icons[$card['icon']] !!}</svg>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.recent_orders') }}</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-brand-700 hover:text-brand-800">{{ __('admin.view_all') }} →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-2.5 text-start">{{ __('admin.order_number_label') }}</th>
                            <th class="px-5 py-2.5 text-start">{{ __('admin.customer_name_label') }}</th>
                            <th class="px-5 py-2.5 text-end">{{ __('admin.amount_label') }}</th>
                            <th class="px-5 py-2.5 text-start">{{ __('admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono font-semibold text-brand-700" dir="ltr">
                                    <a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $order->customer_name }}</td>
                                <td class="px-5 py-3 text-end font-bold text-slate-900">{{ money($order->total) }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $order->status->label() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.products') }} · {{ __('admin.low_stock') }}</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($lowStockProducts as $product)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <a href="{{ route('admin.products.edit', $product) }}" class="block truncate text-sm font-semibold text-slate-800 hover:text-brand-700">{{ $product->trans('name') }}</a>
                                <span class="text-xs text-slate-400" dir="ltr">{{ $product->sku }}</span>
                            </div>
                            <span class="{{ $product->stock_quantity <= 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }} inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold">
                                {{ $product->stock_quantity }}
                            </span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-400">{{ __('admin.no_low_stock') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">{{ __('admin.top_selling') }}</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($topSelling as $row)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="min-w-0 truncate text-sm font-semibold text-slate-700">{{ $row->name_snapshot }}</span>
                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">{{ $row->sold_qty }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-400">{{ __('admin.no_records') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

</x-admin.layout>