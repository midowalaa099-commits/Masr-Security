<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminPackageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $packages = Package::query()
            ->with(['items.product'])
            ->withCount('items')
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $products = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->orderBy('name_en')
            ->get();

        return view('admin.packages.create', compact('products'));
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'items']);

        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name_en']);
        $data['use_component_pricing'] = $request->boolean('use_component_pricing');
        $data['featured'] = $request->boolean('featured');

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('packages', 'public');
        }

        $package = Package::create($data);

        $this->syncItems($package, $request->input('items', []));

        $this->audit->log('package_created', $package, newValues: $package->only([
            'name_ar', 'name_en', 'slug', 'use_component_pricing', 'base_price', 'discount_amount', 'status',
        ]));

        return redirect()->route('admin.packages.edit', $package)->with('success', __('admin.package_created'));
    }

    public function show(Package $package)
    {
        return redirect()->route('admin.packages.edit', $package);
    }

    public function edit(Package $package)
    {
        $package->load('items.product');

        $products = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->orderBy('name_en')
            ->get();

        return view('admin.packages.edit', compact('package', 'products'));
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $old = $package->only(['name_ar', 'name_en', 'use_component_pricing', 'base_price', 'discount_amount', 'status', 'featured']);

        $data = $request->safe()->except(['cover_image', 'remove_cover', 'items']);

        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name_en'], $package);
        $data['use_component_pricing'] = $request->boolean('use_component_pricing');
        $data['featured'] = $request->boolean('featured');

        if ($request->hasFile('cover_image')) {
            if ($package->cover_image) {
                Storage::disk('public')->delete($package->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('packages', 'public');
        }

        if ($request->boolean('remove_cover') && $package->cover_image) {
            Storage::disk('public')->delete($package->cover_image);
            $data['cover_image'] = null;
        }

        $package->update($data);

        if ($request->has('items')) {
            $this->syncItems($package, $request->input('items', []));
        }

        $this->audit->packageUpdated($package, $old, $package->fresh()->only(array_keys($old)));

        return redirect()->route('admin.packages.edit', $package)->with('success', __('admin.package_updated'));
    }

    public function destroy(Package $package): RedirectResponse
    {
        $inOrderHistory = OrderItem::query()
            ->where('orderable_type', Package::class)
            ->where('orderable_id', $package->id)
            ->exists();

        if ($inOrderHistory) {
            return back()->with('error', __('admin.package_in_use_delete_blocked'));
        }

        $this->audit->log('package_deleted', $package, oldValues: $package->only(['name_ar', 'name_en', 'slug']));

        if ($package->cover_image) {
            Storage::disk('public')->delete($package->cover_image);
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', __('admin.package_deleted'));
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $total = 0.0;

        foreach ($request->input('items') as $row) {
            $product = Product::query()->whereKey($row['product_id'])->firstOrFail();
            $total += $product->displayPrice() * (int) $row['quantity'];
        }

        return response()->json(['total' => round($total, 2), 'formatted' => money($total)]);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(Package $package, array $items): void
    {
        $package->items()->delete();

        foreach (array_values($items) as $row) {
            if (empty($row['product_id'])) {
                continue;
            }

            $package->items()->create([
                'product_id' => (int) $row['product_id'],
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
            ]);
        }
    }

    private function uniqueSlug(string $name, ?Package $ignore = null): string
    {
        $base = Str::slug($name) ?: 'package';
        $slug = $base;
        $i = 2;

        $query = Package::query()->where('slug', $slug);

        if ($ignore) {
            $query->where('id', '!=', $ignore->id);
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = Package::query()->where('slug', $slug);
            if ($ignore) {
                $query->where('id', '!=', $ignore->id);
            }
        }

        return $slug;
    }
}
