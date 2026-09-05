<x-admin.layout title="{{ __('admin.categories') }}">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data"
        class="max-w-3xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name_en" :value="__('admin.name_en')" />
                <x-text-input id="name_en" name="name_en" class="mt-1 block w-full" :value="old('name_en', $category->name_en)" required />
                <x-input-error :messages="$errors->get('name_en')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="name_ar" :value="__('admin.name_ar')" />
                <x-text-input id="name_ar" name="name_ar" class="mt-1 block w-full" :value="old('name_ar', $category->name_ar)" required />
                <x-input-error :messages="$errors->get('name_ar')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="slug" :value="__('admin.slug')" />
            <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $category->slug)" dir="ltr" />
            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="parent_id" :value="__('admin.parent_category')" />
                <select id="parent_id" name="parent_id" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">— {{ __('admin.none') }} —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name_en }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="sort_order" :value="__('admin.sort_order')" />
                <x-text-input id="sort_order" type="number" name="sort_order" class="mt-1 block w-full" :value="old('sort_order', $category->sort_order)" min="0" />
                <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="image" :value="__('admin.image')" />
            <div class="mt-1 flex items-center gap-4">
                @if ($category->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) }}" alt="{{ $category->trans('name') }}" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
                @endif
                <input id="image" type="file" name="image" accept="image/*"
                    class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700" />
            </div>
            <x-input-error :messages="$errors->get('image')" class="mt-2" />

            @if ($category->image)
                <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    {{ __('admin.remove_image') }}
                </label>
            @endif
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $category->is_active))>
            {{ __('admin.status_active') }}
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.save') }}</button>
            <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">{{ __('admin.cancel') }}</a>
        </div>
    </form>
</x-admin.layout>