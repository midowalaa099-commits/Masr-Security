@php
    $children = $categoryGroups->get($category->id, collect());
    $isSelected = (string) request('category') === (string) $category->id;
@endphp
<div class="grid gap-1">
    @if ($children->isNotEmpty())
        <details id="category-group-{{ $category->id }}" @if (in_array($category->id, $expandedCategoryIds, true)) open @endif class="group/category">
            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-open/category:rotate-90 rtl:group-open/category:-rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                <span class="min-w-0 flex-1 truncate">{{ $category->trans('name') }}</span>
            </summary>
            <div class="ms-3 mt-1 grid gap-1 border-s border-slate-200 ps-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs text-slate-500 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:font-bold has-[:checked]:text-brand-800">
                    <input type="radio" name="category" value="{{ $category->id }}" @checked($isSelected) class="h-3.5 w-3.5 shrink-0 border-slate-300 text-brand-700 focus:ring-brand-500">
                    <span class="truncate">{{ $category->trans('name') }} {{ __('store.all') }}</span>
                </label>
                @foreach ($children as $child)
                    @include('store.shop-category', ['category' => $child])
                @endforeach
            </div>
        </details>
    @else
        <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:font-bold has-[:checked]:text-brand-800">
            <input type="radio" name="category" value="{{ $category->id }}" @checked($isSelected) class="h-4 w-4 shrink-0 border-slate-300 text-brand-700 focus:ring-brand-500">
            <span class="min-w-0 truncate">{{ $category->trans('name') }}</span>
        </label>
    @endif
</div>
