<x-admin.layout title="{{ __('admin.quotes') }}">

    @php
        $badges = [
            'new' => 'bg-brand-100 text-brand-700',
            'contacted' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.quotes.index') }}" class="flex items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
            <select name="status" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.status') }} —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 text-start">{{ __('admin.customer_name_label') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.company') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.phone') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.created_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quotes as $quote)
                        <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.quotes.show', $quote) }}'">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $quote->name }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $quote->company ?: '—' }}</td>
                            <td class="px-5 py-3 text-slate-500" dir="ltr">{{ $quote->phone }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $badges[$quote->status->value] ?? 'bg-slate-100 text-slate-500' }}">{{ $quote->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-400">{{ $quote->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $quotes->links() }}
        </div>
    </div>

</x-admin.layout>