<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::query()
            ->where('status', 'active')
            ->with(['items.product.images'])
            ->latest()
            ->paginate(9);

        return view('store.packages', compact('packages'));
    }

    public function show(Package $package)
    {
        abort_unless($package->status === ProductStatus::Active, 404);

        $package->load(['items.product.images']);

        return view('store.package', compact('package'));
    }
}
