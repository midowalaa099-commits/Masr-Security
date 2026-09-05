<x-admin.layout title="{{ __('admin.packages') }}">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.packages.index') }}" class="flex items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
        <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.add_new') }}
        </a>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3"></th>
                        <th class="px-5 py-3 text-start">{{ __('admin.packages') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('admin.component_count') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.price') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('store.featured_products') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($packages as $package)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                @if ($package->firstImageUrl())
                                    <img src="{{ $package->firstImageUrl() }}" alt="" class="h-12 w-12 rounded-lg border border-slate-200 object-cover">
                                @else
                                    <span class="flex h-12 w-12 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.packages.edit', $package) }}" class="font-semibold text-slate-800 hover:text-brand-700">{{ $package->name_en }}</a>
                                <span class="block text-xs text-slate-400">{{ $package->name_ar }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $package->items_count }}</td>
                            <td class="px-5 py-3 text-end font-bold text-slate-900">{{ money($package->displayPrice()) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $package->status->value === 'active' ? 'bg-emerald-100 text-emerald-700' : ($package->status->value === 'draft' ? 'bg-slate-100 text-slate-500' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $package->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if ($package->featured)
                                    <span class="text-brand-700">★</span>
                                @else
                                    <span class="text-slate-200">★</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.packages.edit', $package) }}" title="{{ __('admin.edit') }}"
                                        class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.packages.destroy', $package) }}"
                                        onsubmit="return confirm('{{ __('store.are_you_sure') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="{{ __('admin.delete') }}"
                                            class="rounded-lg border border-slate-200 p-1.5 text-rose-500 hover:bg-rose-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
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
            {{ $packages->links() }}
        </div>
    </div>

</x-admin.layout>