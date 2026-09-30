<x-admin.layout :title="__('pricing.title')">
    <div class="mx-auto grid max-w-7xl gap-8">
        <section class="relative isolate overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-navy-950 via-brand-950 to-brand-800 px-6 py-8 text-white shadow-xl shadow-brand-950/10 sm:px-9 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute -end-14 -top-28 -z-10 h-72 w-72 rounded-full border-[36px] border-white/5"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-36 start-1/3 -z-10 h-72 w-72 rounded-full bg-brand-400/10 blur-3xl"></div>

            <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="max-w-3xl">
                    <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold tracking-wide text-brand-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        {{ __('pricing.workspace_eyebrow') }}
                    </div>
                    <h1 class="text-3xl font-black tracking-tight sm:text-4xl">{{ __('pricing.title') }}</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-brand-100/85 sm:text-base">{{ __('pricing.help') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:min-w-72">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.951 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </span>
                        <p class="mt-4 text-2xl font-black tabular-nums">{{ number_format($products->count()) }}</p>
                        <p class="mt-1 text-xs font-medium text-brand-100/75">{{ __('pricing.catalog_products') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" /></svg>
                        </span>
                        <p class="mt-4 text-2xl font-black tabular-nums">{{ number_format($batches->total()) }}</p>
                        <p class="mt-1 text-xs font-medium text-brand-100/75">{{ __('pricing.saved_previews') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.pricing.store') }}"
            x-data="{
                scope: @js(old('scope', 'all')),
                operation: @js(old('operation', 'percentage')),
                direction: @js(old('direction', 'increase')),
                target: @js(old('target', 'regular')),
                rounding: @js(old('rounding', 'precision')),
                amount: @js(old('amount', '')),
                labels: @js([
                    'all' => __('pricing.all'),
                    'brand' => __('pricing.brand'),
                    'category' => __('pricing.category'),
                    'selected' => __('pricing.selected'),
                    'percentage' => __('pricing.percentage'),
                    'fixed' => __('pricing.fixed'),
                    'increase' => __('pricing.increase'),
                    'decrease' => __('pricing.decrease'),
                    'regular' => __('pricing.regular'),
                    'sale' => __('pricing.sale'),
                    'both' => __('pricing.both'),
                    'precision' => __('pricing.precision'),
                    '5' => __('pricing.5'),
                    '10' => __('pricing.10'),
                ])
            }"
            class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start">
            @csrf

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                <div class="border-b border-slate-100 px-5 py-5 sm:px-8 sm:py-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-sm font-black text-brand-700 ring-1 ring-brand-100">01</span>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">{{ __('pricing.step_scope') }}</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('pricing.scope_help') }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-7 p-5 sm:p-8">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-bold text-slate-700 sm:col-span-2">
                            <span>{{ __('pricing.scope') }}</span>
                            <span class="relative">
                                <select name="scope" x-model="scope" class="w-full appearance-none rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pe-10 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                                    @foreach (['all', 'brand', 'category', 'selected'] as $value)
                                        <option value="{{ $value }}" @selected(old('scope', 'all') === $value)>{{ __('pricing.'.$value) }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </span>
                        </label>

                        <label x-cloak x-show="scope === 'brand'" class="grid gap-2 text-sm font-bold text-slate-700">
                            <span>{{ __('pricing.brand') }}</span>
                            <span class="relative">
                                <select name="brand" class="w-full appearance-none rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pe-10 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                                    <option value="">—</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand }}" @selected(old('brand') === $brand)>{{ $brand }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </span>
                        </label>

                        <label x-cloak x-show="scope === 'category'" class="grid gap-2 text-sm font-bold text-slate-700">
                            <span>{{ __('pricing.category') }}</span>
                            <span class="relative">
                                <select name="category_id" class="w-full appearance-none rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pe-10 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                                    <option value="">—</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->trans('name') }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </span>
                        </label>
                    </div>

                    <fieldset x-cloak x-show="scope === 'selected'" class="grid gap-3">
                        <legend class="mb-3 text-sm font-bold text-slate-700">{{ __('pricing.selected') }}</legend>
                        <div class="grid max-h-72 gap-1 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50/70 p-2 sm:grid-cols-2">
                            @foreach ($products as $product)
                                <label class="flex min-w-0 cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-white hover:shadow-sm">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, old('product_ids', []))) class="size-4 shrink-0 rounded border-slate-300 text-brand-700 focus:ring-brand-500/20">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-700">{{ $product->trans('name') }}</span>
                                        <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-400" dir="ltr">{{ $product->sku }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs leading-5 text-slate-500">{{ __('pricing.selection_help') }}</p>
                    </fieldset>

                    <div class="border-t border-slate-100"></div>

                    <div class="grid gap-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-sm font-black text-amber-700 ring-1 ring-amber-100">02</span>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900">{{ __('pricing.step_adjustment') }}</h2>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('pricing.adjustment_help') }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach (['operation' => ['percentage', 'fixed'], 'direction' => ['increase', 'decrease'], 'target' => ['regular', 'sale', 'both'], 'rounding' => ['precision', '5', '10']] as $field => $values)
                                <label class="grid gap-2 text-sm font-bold text-slate-700">
                                    <span>{{ __('pricing.'.$field) }}</span>
                                    <span class="relative">
                                        <select name="{{ $field }}" x-model="{{ $field }}" class="w-full appearance-none rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pe-10 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                                            @foreach ($values as $value)
                                                <option value="{{ $value }}" @selected(old($field, $field === 'operation' ? 'percentage' : ($field === 'direction' ? 'increase' : ($field === 'target' ? 'regular' : 'precision'))) === $value)>{{ __('pricing.'.$value) }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </span>
                                </label>
                            @endforeach

                            <label class="grid gap-2 text-sm font-bold text-slate-700 sm:col-span-2">
                                <span>{{ __('pricing.amount') }}</span>
                                <span class="relative">
                                    <input name="amount" type="number" step="0.0001" min="0.0001" max="999999999" required value="{{ old('amount') }}" x-model="amount" placeholder="10" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pe-16 text-sm font-semibold tabular-nums text-slate-800 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10" dir="ltr">
                                    <span class="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400" x-text="operation === 'percentage' ? '%' : 'EGP'"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/80 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div class="flex items-start gap-3 text-xs leading-5 text-slate-500">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.99 11.99 0 013 6c.478 1.18.75 2.44.75 3.749 0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31.272-2.571.75-3.75A11.96 11.96 0 0112 2.714z" /></svg>
                        <span>{{ __('pricing.safe_preview_note') }}</span>
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-brand-700/15 transition hover:-translate-y-0.5 hover:bg-brand-800 hover:shadow-brand-700/25 focus:outline-none focus:ring-4 focus:ring-brand-500/25 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5h.528m15.444 2.25A7.5 7.5 0 004.278 8.25m0 0H8.25m12 12v-4.5h-.528m0 0a7.5 7.5 0 01-15.444-2.25m15.444 2.25H15.75" /></svg>
                        {{ __('pricing.preview') }}
                    </button>
                </div>
            </div>

            <aside class="grid gap-4 xl:sticky xl:top-6">
                <div class="overflow-hidden rounded-3xl border border-brand-100 bg-white shadow-sm shadow-slate-900/[0.03]">
                    <div class="bg-gradient-to-br from-brand-50 to-white p-5 sm:p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-brand-700 shadow-sm ring-1 ring-brand-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.99 11.99 0 013 6c.478 1.18.75 2.44.75 3.749 0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31.272-2.571.75-3.75A11.96 11.96 0 0112 2.714z" /></svg>
                        </span>
                        <h2 class="mt-4 text-base font-extrabold text-slate-900">{{ __('pricing.step_review') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('pricing.preview_summary') }}</p>
                    </div>

                    <div class="grid gap-4 border-t border-slate-100 p-5 sm:p-6">
                        @foreach (['scope', 'operation', 'direction', 'target', 'rounding'] as $field)
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 ring-1 ring-slate-100">
                                    @if ($field === 'scope')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                                    @elseif ($field === 'operation')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                                    @elseif ($field === 'direction')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5V4.5m0 0-6.75 6.75M12 4.5l6.75 6.75" /></svg>
                                    @elseif ($field === 'target')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M12 3.75v16.5" /></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m-4.5-15h9a3 3 0 010 6h-9a3 3 0 000 6h9" /></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-slate-400">{{ __('pricing.'.$field) }}</p>
                                    <p class="truncate text-sm font-bold text-slate-700" x-text="labels[{{ $field }}]"></p>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 ring-1 ring-slate-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-slate-400">{{ __('pricing.amount') }}</p>
                                <p class="truncate text-sm font-bold tabular-nums text-slate-700" dir="ltr"><span x-text="amount || '—'"></span> <span class="text-xs font-medium text-slate-400" x-text="operation === 'percentage' ? '%' : 'EGP'"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        </span>
                        <h2 class="text-sm font-extrabold text-slate-800">{{ __('pricing.safe_preview_title') }}</h2>
                    </div>
                    <p class="mt-3 text-xs leading-6 text-slate-500">{{ __('pricing.safe_preview_note') }}</p>
                </div>
            </aside>
        </form>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" /></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">{{ __('pricing.history') }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('pricing.history_help') }}</p>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tabular-nums text-slate-600">{{ number_format($batches->total()) }}</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    @php
                        $statusTone = match ($batch->status) {
                            'applied' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
                            'undone' => 'bg-slate-100 text-slate-600 ring-slate-500/10',
                            default => 'bg-amber-50 text-amber-700 ring-amber-600/10',
                        };
                        $batchOptions = json_decode($batch->options, true, flags: JSON_THROW_ON_ERROR);
                    @endphp
                    <a href="{{ route('admin.pricing.show', $batch->id) }}" class="group flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 font-mono text-xs font-bold text-slate-500 ring-1 ring-slate-200 transition group-hover:bg-white group-hover:text-brand-700">#{{ $batch->id }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-slate-800 group-hover:text-brand-700">{{ __('pricing.'.$batchOptions['scope']) }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ $batch->created_at }}</span>
                            </span>
                        </span>
                        <span class="flex items-center justify-between gap-4 sm:justify-end">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusTone }}">{{ __('pricing.'.$batch->status) }}</span>
                            <svg class="h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                        </span>
                    </a>
                @empty
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" /></svg>
                        </span>
                        <p class="mt-4 text-sm font-bold text-slate-700">{{ __('admin.no_records') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('pricing.history_help') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($batches->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-8">{{ $batches->links() }}</div>
            @endif
        </section>
    </div>
</x-admin.layout>
