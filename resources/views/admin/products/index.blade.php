<x-admin.layout title="{{ __('admin.products') }}">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
            <select name="status" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.status') }} —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                <option value="">— {{ __('admin.categories') }} —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name_en }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">{{ __('admin.search') }}</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800">
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
                        <th class="px-5 py-3 text-start">{{ __('admin.products') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.categories') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.sku') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.price') }}</th>
                        <th class="px-5 py-3 text-center">{{ __('admin.stock') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                @if ($product->images->isNotEmpty())
                                    <img src="{{ $product->images->first()->url }}" alt="" class="h-12 w-12 rounded-lg border border-slate-200 object-cover">
                                @else
                                    <span class="flex h-12 w-12 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" /></svg>
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-slate-800 hover:text-brand-700">{{ $product->name_en }}</a>
                                <span class="block text-xs text-slate-400">{{ $product->name_ar }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $product->category?->name_en ?? '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500" dir="ltr">{{ $product->sku }}</td>
                            <td class="px-5 py-3 text-end">
                                <span class="font-bold text-slate-900">{{ money($product->displayPrice()) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="{{ $product->stock_quantity <= 0 ? 'bg-rose-100 text-rose-700' : ($product->stock_quantity <= $product->low_stock_threshold ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }} inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $product->status->value === 'active' ? 'bg-emerald-100 text-emerald-700' : ($product->status->value === 'draft' ? 'bg-slate-100 text-slate-500' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $product->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.products.edit', $product) }}" title="{{ __('admin.edit') }}"
                                        class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
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
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $products->links() }}
        </div>
    </div>

</x-admin.layout>