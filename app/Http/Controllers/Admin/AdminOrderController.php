<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $orders = Order::query()
            ->withCount('items')
            ->with('payments')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = OrderStatus::cases();

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payments', 'user']);

        $statuses = OrderStatus::cases();

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        if ($order->status->value === $request->validated('status')) {
            return back()->with('info', __('admin.order_no_change'));
        }

        if ($order->isFulfilled()) {
            return back()->with('error', __('admin.order_fulfilled_no_edits'));
        }

        if (! $order->payments()->exists()) {
            return back()->with('error', __('admin.order_no_payment'));
        }

        $oldStatus = $order->status;
        $newStatus = OrderStatus::from($request->validated('status'));

        $this->audit->orderStatusChanged($order, $oldStatus->value, $newStatus->value);

        DB::transaction(function () use ($order, $request, $oldStatus, $newStatus) {
            // Restore stock when an open order is cancelled before fulfillment.
            if ($newStatus === OrderStatus::Cancelled && $oldStatus->isBeforeFulfillment()) {
                $this->inventory->restoreForOrder($order);
            }

            // Re-reserve stock when a cancelled order is reopened before fulfillment.
            if ($oldStatus === OrderStatus::Cancelled && $newStatus->isBeforeFulfillment()) {
                $this->inventory->decrementForOrder($order);
            }

            $order->update([
                'status' => $newStatus,
                'tracking_number' => $request->validated('tracking_number') ?: $order->tracking_number,
                'admin_note' => $request->filled('admin_note') ? $request->validated('admin_note') : $order->admin_note,
            ]);
        });

        return back()->with('success', __('admin.order_status_updated'));
    }
}
