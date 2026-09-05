<x-admin.layout title="{{ __('admin.payments') }}">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex items-center gap-2">
            <select name="status" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.status') }} —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="provider" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.provider') }} —</option>
                <option value="paymob" @selected(request('provider') === 'paymob')>Paymob</option>
                <option value="sandbox" @selected(request('provider') === 'sandbox')>Sandbox</option>
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
    </div>

    @php
        $badges = [
            'pending' => 'bg-amber-100 text-amber-700',
            'success' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 text-start">{{ __('admin.order_number_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.method_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.provider') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.amount_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.created_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.payments.show', $payment) }}'">
                            <td class="px-5 py-3 font-mono font-semibold text-brand-700" dir="ltr">
                                <a href="{{ route('admin.orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $payment->method?->label() ?? $payment->method }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $payment->provider }}</td>
                            <td class="px-5 py-3 text-end font-bold text-slate-900">
                                <a href="{{ route('admin.payments.show', $payment) }}">{{ money($payment->amount) }}</a>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $badges[$payment->status->value] ?? 'bg-slate-100 text-slate-500' }}">{{ $payment->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-400">{{ $payment->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $payments->links() }}
        </div>
    </div>

</x-admin.layout>