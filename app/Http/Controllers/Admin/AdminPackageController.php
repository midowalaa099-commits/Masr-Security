<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CalculatePackageRequest;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\MediaStorage;
use App\Services\PackageItemsValidator;
use App\Services\ProductOptions;
use App\Services\UniqueSlugGenerator;
use App\Support\SearchPattern;
use Brick\Math\BigDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminPackageController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaStorage $media,
        private readonly UniqueSlugGenerator $slugs,
        private readonly ProductOptions $options,
    ) {}

    public function index(Request $request)
    {
        $packages = Package::query()
            ->with(['items.product'])
            ->withCount('items')
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->whereLike('name_en', SearchPattern::contains($search), caseSensitive: false)
                ->orWhereLike('name_ar', SearchPattern::contains($search), caseSensitive: false)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $products = $this->options->initial($this->oldProductIds());
        $productOptions = $products->map($this->options->option(...));

        return view('admin.packages.create', compact('products', 'productOptions'));
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'items']);

        $data['slug'] = $data['slug'] ?: $this->slugs->generate($data['name_en'], 'package', new Package);
        $data['use_component_pricing'] = $request->boolean('use_component_pricing');
        $data['featured'] = $request->boolean('featured');

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->media->store($request->file('cover_image'), 'packages');
        }

        try {
            $package = DB::transaction(function () use ($data, $request): Package {
                $package = Package::create($data);
                $this->syncItems($package, $request->validated('items'));
                $this->audit->log('package_created', $package, newValues: $package->only([
                    'name_ar', 'name_en', 'slug', 'use_component_pricing', 'base_price', 'discount_amount', 'status',
                ]));

                return $package;
            });
        } catch (Throwable $exception) {
            $this->deleteCover($data['cover_image'] ?? null);
            throw $exception;
        }

        return redirect()->route('admin.packages.edit', $package)->with('success', __('admin.package_created'));
    }

    public function show(Package $package)
    {
        return redirect()->route('admin.packages.edit', $package);
    }

    public function edit(Package $package)
    {
        $package->load('items.product');

        $products = $this->options->initial(array_values(array_unique([
            ...$package->items->pluck('product_id')->all(), ...$this->oldProductIds(),
        ])));
        $productOptions = $products->map($this->options->option(...));

        return view('admin.packages.edit', compact('package', 'products', 'productOptions'));
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'remove_cover', 'items']);

        $data['slug'] = $data['slug'] ?: $this->slugs->generate($data['name_en'], 'package', new Package, $package);
        $data['use_component_pricing'] = $request->boolean('use_component_pricing');
        $data['featured'] = $request->boolean('featured');
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->media->store($request->file('cover_image'), 'packages');
        }

        if (! $request->hasFile('cover_image') && $request->boolean('remove_cover')) {
            $data['cover_image'] = null;
        }

        try {
            $replacedCover = DB::transaction(function () use ($package, $data, $request): ?string {
                $locked = Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
                $replacedCover = array_key_exists('cover_image', $data) ? $locked->cover_image : null;
                $old = $locked->only(['name_ar', 'name_en', 'use_component_pricing', 'base_price', 'discount_amount', 'status', 'featured']);
                $locked->update($data);
                if ($request->has('items')) {
                    $this->syncItems($locked, $request->validated('items') ?? []);
                }
                $this->audit->packageUpdated($locked, $old, $locked->only(array_keys($old)));

                return $replacedCover;
            });
        } catch (Throwable $exception) {
            if ($request->hasFile('cover_image')) {
                $this->deleteCover($data['cover_image']);
            }
            throw $exception;
        }
        if ($replacedCover !== null && $replacedCover !== ($data['cover_image'] ?? null)) {
            $this->deleteCover($replacedCover);
        }

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
            $this->media->delete($package->cover_image);
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', __('admin.package_deleted'));
    }

    public function calculate(CalculatePackageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $products = Product::query()
            ->whereKey(collect($validated['items'])->pluck('product_id')->unique())
            ->get(['id', 'price', 'sale_price'])
            ->keyBy('id');

        $errors = [];
        $total = BigDecimal::of('0.00');
        $lineTotals = [];

        foreach ($validated['items'] as $index => $row) {
            $product = $products->get((int) $row['product_id']);

            if ($product === null) {
                $errors["items.{$index}.product_id"] = [
                    __('validation.exists', ['attribute' => "items.{$index}.product id"]),
                ];

                continue;
            }

            $lineTotal = BigDecimal::of($product->displayPrice())->multipliedBy($row['quantity'])->toScale(2);
            $lineTotals[$index] = (string) $lineTotal;
            $total = $total->plus($lineTotal);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return response()->json(['total' => $total->toFloat(), 'formatted' => money((string) $total), 'line_totals' => $lineTotals]);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(Package $package, array $items): void
    {
        $package->items()->delete();

        if ($items !== []) {
            $timestamp = now();
            $package->items()->insert(array_map(fn (array $row): array => [
                'package_id' => $package->id, 'product_id' => (int) $row['product_id'],
                'quantity' => (int) $row['quantity'], 'created_at' => $timestamp, 'updated_at' => $timestamp,
            ], array_values($items)));
        }
    }

    /** @return list<int> */
    private function oldProductIds(): array
    {
        return collect((array) old('items', []))->take(PackageItemsValidator::MAX_ITEMS)
            ->pluck('product_id')->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id): int => (int) $id)->values()->all();
    }

    private function deleteCover(?string $path): void
    {
        try {
            $this->media->delete($path);
        } catch (Throwable $exception) {
            Log::warning('package.cover_cleanup_failed', ['exception_type' => $exception::class]);
        }
    }
}
