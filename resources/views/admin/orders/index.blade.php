<x-admin.layout title="{{ __('admin.orders') }}">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
            <select name="status" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.status') }} —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 text-start">{{ __('admin.order_number_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.customer_name_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.phone') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.amount_label') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('admin.items') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.created_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-5 py-3 font-mono font-semibold text-brand-700" dir="ltr">
                                <a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $order->customer_name }}
                                @if ($order->user_id)
                                    <span class="ml-1 text-[10px] uppercase tracking-wide text-emerald-600">· {{ __('admin.account') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500" dir="ltr">{{ $order->phone }}</td>
                            <td class="px-5 py-3 text-end font-bold text-slate-900">{{ money($order->total) }}</td>
                            <td class="px-5 py-3 text-center text-slate-500">{{ $order->items_count }}</td>
                            <td class="px-5 py-3">
                                @include('admin.orders.partials.status-badge', ['status' => $order->status])
                            </td>
                            <td class="px-5 py-3 text-slate-400">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $orders->links() }}
        </div>
    </div>

</x-admin.layout>