<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductBrandRequest;
use App\Models\ProductBrand;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminProductBrandController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $brands = ProductBrand::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('admin.brands.index', compact('brands'));
    }

    public function store(StoreProductBrandRequest $request): RedirectResponse
    {
        $brand = ProductBrand::create($request->validated());

        $this->audit->log('product_brand_created', $brand, newValues: ['name' => $brand->name]);

        return redirect()->route('admin.brands.index')->with('success', __('admin.brand_created'));
    }

    public function destroy(ProductBrand $brand): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($brand): bool {
            $lockedBrand = ProductBrand::query()->lockForUpdate()->findOrFail($brand->getKey());

            if ($lockedBrand->products()->lockForUpdate()->first() !== null) {
                return false;
            }

            $this->audit->log('product_brand_deleted', $lockedBrand, oldValues: ['name' => $lockedBrand->name]);
            $lockedBrand->delete();

            return true;
        });

        if (! $deleted) {
            return back()->with('error', __('admin.brand_in_use', ['brand' => $brand->name]));
        }

        return redirect()->route('admin.brands.index')->with('success', __('admin.brand_deleted'));
    }
}
