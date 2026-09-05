<x-admin.layout title="{{ __('admin.categories') }}">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
        <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.add_new') }}
        </a>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 text-start">{{ __('admin.name_en') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.parent_category') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('admin.products') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('admin.sort_order') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span class="font-semibold text-slate-800">{{ $category->name_en }}</span>
                                <span class="block text-xs text-slate-400">{{ $category->name_ar }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $category->parent?->name_en ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $category->products_count }}</td>
                            <td class="px-5 py-3">
                                @if ($category->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">{{ __('admin.status_active') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500">{{ __('admin.status_inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-slate-500">{{ $category->sort_order }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="{{ __('admin.status') }}"
                                            class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-100">
                                            @if ($category->is_active)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            @endif
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.categories.edit', $category) }}" title="{{ __('admin.edit') }}"
                                        class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
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
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $categories->links() }}
        </div>
    </div>

</x-admin.layout>