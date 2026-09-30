<x-admin.layout title="{{ __('admin.quotes') }}">

    @php
        $badges = [
            'new' => 'bg-brand-100 text-brand-700',
            'contacted' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <x-admin.page-heading :title="__('admin.quotes')" :description="__('admin.quotes_page_intro')" :count="$quotes->total()" :count-label="__('admin.quotes')" />

    <div class="ui-panel mb-5 p-3 sm:p-4">
        <form method="GET" action="{{ route('admin.quotes.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="min-w-0 flex-1 rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-xs">
            <select name="status" class="rounded-xl border-slate-200 px-3 py-2.5 text-sm">
                <option value="">— {{ __('admin.status') }} —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">{{ __('admin.search') }}</button>
        </form>
    </div>

    <div class="ui-panel overflow-hidden">
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