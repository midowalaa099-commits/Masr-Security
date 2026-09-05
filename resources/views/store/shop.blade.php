<x-store.layout>

    <!-- Page header -->
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-8 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ __('store.shop') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('store.category_desc') }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $products->total() }} {{ $products->total() === 1 ? __('store.item') : __('store.items') }}
            </span>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_1fr]">

            <!-- Filters -->
            <aside>
                <form method="GET" action="{{ route('shop') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    @if (request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif

                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
                            {{ __('store.categories') }}
                        </h2>

                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                            <input type="radio" name="category" value="" {{ request('category') === null ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('store.all') }}
                        </label>

                        @foreach ($categories as $category)
                            <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                                <input type="radio" name="category" value="{{ $category->id }}"
                                    {{ (string) request('category') === (string) $category->id ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                {{ $category->trans('name') }}
                            </label>
                            @foreach ($category->children as $child)
                                <label class="ms-5 mt-1.5 flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                                    <input type="radio" name="category" value="{{ $child->id }}"
                                        {{ (string) request('category') === (string) $child->id ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    {{ $child->trans('name') }}
                                </label>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('store.price') }}</h2>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <input type="number" name="min_price" min="0" step="0.01" value="{{ request('min_price') }}" placeholder="{{ __('store.min_price') }}"
                                class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500" dir="ltr">
                            <input type="number" name="max_price" min="0" step="0.01" value="{{ request('max_price') }}" placeholder="{{ __('store.max_price') }}"
                                class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500" dir="ltr">
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800">
                        {{ __('store.filter') }}
                    </button>
                </form>
            </aside>

            <!-- Listing -->
            <div>
                <div class="flex items-center justify-between gap-4">
                    <div class="text-sm text-slate-500">
                        @if (request('q'))
                            <span class="font-semibold text-slate-800">"{{ request('q') }}"</span> —
                        @endif
                        {{ __('store.availability') }}:
                        <span class="font-semibold text-emerald-600">{{ __('store.in_stock') }}</span>
                    </div>

                    <div class="relative inline-flex">
                        <form method="GET" action="{{ route('shop') }}" x-data="{ value: '{{ request('sort', 'newest') }}' }">
                            @foreach (collect(request()->query())->except(['sort']) as $key => $val)
                                @if (is_array($val))
                                    @foreach ($val as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                @endif
                            @endforeach
                            <select name="sort" x-model="value" @change="$event.target.form.submit()"
                                class="rounded-lg border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 focus:border-brand-500 focus:ring-brand-500">
                                <option value="newest">{{ __('store.sort_newest') }}</option>
                                <option value="price_asc">{{ __('store.sort_price_low') }}</option>
                                <option value="price_desc">{{ __('store.sort_price_high') }}</option>
                                <option value="name">{{ __('store.sort_name') }}</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if ($products->isEmpty())
                    <div class="mt-10 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <svg class="h-14 w-14 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        <p class="mt-4 text-sm font-semibold text-slate-700">{{ __('store.no_products') }}</p>
                        <a href="{{ route('shop') }}" class="mt-3 text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('store.view_all') }}</a>
                    </div>
                @else
                    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-store.product-card :product="$product" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-store.layout>