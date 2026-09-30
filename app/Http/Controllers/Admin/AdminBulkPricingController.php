<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmBulkPricingRequest;
use App\Http\Requests\Admin\PreviewBulkPricingRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\BulkPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminBulkPricingController extends Controller
{
    public function __construct(private BulkPricingService $pricing) {}

    public function index(Request $request): View
    {
        return view('admin.pricing.index', [
            'brands' => Product::query()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
            'categories' => Category::query()->orderBy('name_en')->get(['id', 'name_en', 'name_ar']),
            'products' => Product::query()->orderBy('sku')->get(['id', 'sku', 'name_en', 'name_ar']),
            'batches' => DB::table('bulk_price_changes')->where('user_id', $request->user()->id)->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(PreviewBulkPricingRequest $request): RedirectResponse
    {
        $id = $this->pricing->preview($request->validated(), $request->user()->id);

        return redirect()->route('admin.pricing.show', $id);
    }

    public function show(Request $request, int $batch): View
    {
        $change = DB::table('bulk_price_changes')->where('id', $batch)->first();
        abort_if($change === null, 404);
        abort_unless((int) $change->user_id === $request->user()->id, 403);
        $items = DB::table('bulk_price_change_items')->where('bulk_price_change_id', $batch)->orderBy('id')->paginate(100);
        $counts = DB::table('bulk_price_change_items')->where('bulk_price_change_id', $batch)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pricing.show', [
            'batch' => $change, 'options' => json_decode($change->options, true, flags: JSON_THROW_ON_ERROR),
            'items' => $items, 'counts' => $counts,
        ]);
    }

    public function apply(ConfirmBulkPricingRequest $request, int $batch): RedirectResponse
    {
        $this->pricing->apply($batch, $request->user()->id, $request->validated('token'));

        return redirect()->route('admin.pricing.show', $batch)->with('success', __('pricing.apply_complete'));
    }

    public function undo(ConfirmBulkPricingRequest $request, int $batch): RedirectResponse
    {
        $this->pricing->undo($batch, $request->user()->id, $request->validated('token'));

        return redirect()->route('admin.pricing.show', $batch)->with('success', __('pricing.undo_complete'));
    }
}
