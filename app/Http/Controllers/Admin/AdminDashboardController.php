<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $counts = Order::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status != ? THEN total ELSE 0 END) as revenue', [OrderStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [OrderStatus::Pending->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid', [OrderStatus::Paid->value])
            ->first();

        $lowStockProducts = Product::query()
            ->active()
            ->whereNotNull('stock_quantity')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->with('category')
            ->limit(8)
            ->get();

        $recentOrders = Order::query()
            ->withCount('items')
            ->latest()
            ->limit(8)
            ->get();

        $topSelling = OrderItem::query()
            ->select('name_snapshot', DB::raw('SUM(quantity) as sold_qty'))
            ->whereHas('order', fn ($q) => $q->where('status', '!=', OrderStatus::Cancelled->value))
            ->groupBy('name_snapshot')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalOrders' => (int) $counts->total,
            'revenue' => (float) ($counts->revenue ?? 0),
            'pendingOrders' => (int) $counts->pending,
            'paidOrders' => (int) $counts->paid,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders' => $recentOrders,
            'topSelling' => $topSelling,
        ]);
    }
}
