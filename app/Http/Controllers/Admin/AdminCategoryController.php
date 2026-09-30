<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use App\Services\MediaStorage;
use App\Services\UniqueSlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminCategoryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaStorage $media,
        private readonly UniqueSlugGenerator $slugs,
    ) {}

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount('products')
            ->with('parent')
            ->when($request->has('search') && $request->query('search') !== '', function ($q) use ($request) {
                $q->where(fn ($q2) => $q2
                    ->where('name_en', 'like', '%'.$request->query('search').'%')
                    ->orWhere('name_ar', 'like', '%'.$request->query('search').'%'));
            })
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();

        $parents = Category::query()->whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    public function create()
    {
        $parents = Category::query()->whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->media->store($request->file('image'), 'categories');
        }

        $slug = $data['slug'] ?? null;
        $data['slug'] = $slug ?: $this->slugs->generate($data['name_en'], 'category', new Category);

        $category = Category::create($data + ['is_active' => $request->boolean('is_active')]);

        Cache::forget('storefront.nav.category_ids');

        $this->audit->categoryUpdated($category, [], $category->only([
            'name_ar', 'name_en', 'slug', 'parent_id', 'is_active', 'sort_order',
        ]));

        return redirect()->route('admin.categories.index')->with('success', __('admin.category_created'));
    }

    public function edit(Category $category)
    {
        $parents = Category::query()
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['name_ar', 'name_en', 'slug', 'parent_id', 'is_active', 'sort_order']);

        $data = $request->safe()->except(['image', 'remove_image']);
        $replacedImage = null;

        if ($request->hasFile('image')) {
            $replacedImage = $category->image;
            $data['image'] = $this->media->store($request->file('image'), 'categories');
        }

        if (! $request->hasFile('image') && $request->boolean('remove_image') && $category->image) {
            $replacedImage = $category->image;
            $data['image'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        if ($replacedImage !== null && $replacedImage !== $category->image) {
            $this->media->delete($replacedImage);
        }

        Cache::forget('storefront.nav.category_ids');

        $this->audit->categoryUpdated($category, $old, $category->fresh()->only(array_keys($old)));

        return redirect()->route('admin.categories.index')->with('success', __('admin.category_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->audit->log('category_deleted', $category, oldValues: $category->only(['name_ar', 'name_en', 'slug']));

        if ($category->image) {
            $this->media->delete($category->image);
        }

        Category::query()->where('parent_id', $category->id)->update(['parent_id' => null]);

        $category->delete();

        Cache::forget('storefront.nav.category_ids');

        return redirect()->route('admin.categories.index')->with('success', __('admin.category_deleted'));
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        Cache::forget('storefront.nav.category_ids');

        return back()->with('success', __('admin.category_updated'));
    }
}
