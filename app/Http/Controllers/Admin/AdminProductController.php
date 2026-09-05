<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'images' => fn ($q) => $q->orderBy('sort_order')->limit(1)])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('category'), fn ($q, $cat) => $q->where('category_id', (int) $cat))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = ProductStatus::cases();
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.products.index', compact('products', 'statuses', 'categories'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['images', 'specs']);

        $data['slug'] = $data['slug'] ?: $this->uniqueSlug('product', $data['sku']);
        $data['featured'] = $request->boolean('featured');

        $product = Product::create($data);

        $this->storeImages($product, $request->file('images', []));
        $this->syncSpecs($product, $request->input('specs', []));

        $this->audit->productCreated($product, $product->only([
            'sku', 'name_ar', 'name_en', 'slug', 'price', 'sale_price', 'stock_quantity', 'status', 'type', 'featured',
        ]));

        return redirect()->route('admin.products.edit', $product)->with('success', __('admin.product_created'));
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'specs']);

        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->only([
            'price', 'sale_price', 'stock_quantity', 'sku', 'name_ar', 'name_en', 'status', 'featured',
        ]);

        $data = $request->safe()->except(['images', 'specs']);

        $data['slug'] = $data['slug'] ?: $this->uniqueSlug('product', $data['sku'], $product);
        $data['featured'] = $request->boolean('featured');

        $product->update($data);

        $this->storeImages($product, $request->file('images', []));
        $this->syncSpecs($product, $request->input('specs', []));

        if ((string) $old['price'] !== (string) $product->price || (string) $old['sale_price'] !== (string) $product->sale_price) {
            $this->audit->productPriceChanged($product, (string) $old['price'], (string) $product->price);
        }

        if ((string) $old['stock_quantity'] !== (string) $product->stock_quantity) {
            $this->audit->stockChanged($product, (int) $old['stock_quantity'], (int) $product->stock_quantity);
        }

        $this->audit->productUpdated($product, $old, $product->fresh()->only(array_keys($old)));

        return redirect()->route('admin.products.edit', $product)->with('success', __('admin.product_updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $usedInPackage = PackageItem::query()->where('product_id', $product->id)->exists();
        $inOrderHistory = OrderItem::query()
            ->where('orderable_type', Product::class)
            ->where('orderable_id', $product->id)
            ->exists();

        if ($usedInPackage || $inOrderHistory) {
            return back()->with('error', __('admin.product_in_use_delete_blocked'));
        }

        $this->audit->productDeleted($product, $product->only(['sku', 'name_ar', 'name_en', 'price']));

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('admin.product_deleted'));
    }

    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:3072'],
        ]);

        $path = $request->file('image')->store('products/'.$product->id, 'public');

        $product->images()->create([
            'path' => $path,
            'sort_order' => $product->images()->count(),
        ]);

        return back()->with('success', __('admin.image_uploaded'));
    }

    public function destroyImage(ProductImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', __('admin.image_deleted'));
    }

    public function reorderImages(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:product_images,id'],
        ]);

        foreach (array_values($request->input('ids')) as $index => $id) {
            ProductImage::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    private function storeImages(Product $product, array $files): void
    {
        $start = $product->images()->count();

        foreach (array_values($files) as $index => $file) {
            $path = $file->store('products/'.$product->id, 'public');

            $product->images()->create([
                'path' => $path,
                'sort_order' => $start + $index,
            ]);
        }
    }

    /**
     * @param  array<int, array{id?: int, key?: string, value_ar?: string, value_en?: string}>  $specs
     */
    private function syncSpecs(Product $product, array $specs): void
    {
        $incoming = collect($specs)
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => array_merge([
                'key' => '', 'value_ar' => '', 'value_en' => '', 'id' => null,
            ], $row))
            ->reject(fn ($row) => trim((string) ($row['key'] ?? '')) === '')
            ->values()
            ->all();

        $incomingIds = collect($incoming)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $product->specs()->whereNotIn('id', $incomingIds)->delete();

        foreach ($incoming as $index => $row) {
            if (! empty($row['id'])) {
                $product->specs()->whereKey((int) $row['id'])->update([
                    'spec_key' => trim((string) $row['key']),
                    'spec_value_ar' => $row['value_ar'] ?: null,
                    'spec_value_en' => $row['value_en'] ?: null,
                    'sort_order' => $index,
                ]);
            } else {
                $product->specs()->create([
                    'spec_key' => trim((string) $row['key']),
                    'spec_value_ar' => $row['value_ar'] ?: null,
                    'spec_value_en' => $row['value_en'] ?: null,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function uniqueSlug(string $kind, string $reference, ?Product $ignore = null): string
    {
        $base = Str::slug($reference) ?: Str::slug($reference);
        $base = $base ?: $kind;
        $slug = $base;
        $i = 2;

        $query = Product::query()->where('slug', $slug);

        if ($ignore) {
            $query->where('id', '!=', $ignore->id);
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = Product::query()->where('slug', $slug);
            if ($ignore) {
                $query->where('id', '!=', $ignore->id);
            }
        }

        return $slug;
    }
}
