<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes an audit trail for important admin / system actions.
 */
class AuditLogger
{
    public function __construct(private ?Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $entityType = null,
        ?int $entityId = null,
    ): AuditLog {
        $entityId = $entityId ?? $entity?->getKey();
        $entityType = $entityType ?? ($entity !== null ? $entity::class : null);

        $values = fn (?array $data) => $data === null ? null : collect($data)->reject(
            fn ($value) => $value instanceof Model,
        )->all();

        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $values($oldValues),
            'new_values' => $values($newValues),
            'ip_address' => $this->request->ip(),
        ]);
    }

    public function productCreated(Model $product, array $data): void
    {
        $this->log('product_created', $product, newValues: $data);
    }

    public function productUpdated(Model $product, array $old, array $new): void
    {
        $this->log('product_updated', $product, $old, $new);
    }

    public function productDeleted(Model $product, array $data): void
    {
        $this->log('product_deleted', null, oldValues: $data, entityType: $product::class, entityId: $product->getKey());
    }

    public function productPriceChanged(Model $product, string $oldPrice, string $newPrice): void
    {
        $this->log('product_price_changed', $product, ['price' => $oldPrice], ['price' => $newPrice]);
    }

    public function stockChanged(Model $product, int $oldStock, int $newStock): void
    {
        $this->log('stock_changed', $product, ['stock_quantity' => $oldStock], ['stock_quantity' => $newStock]);
    }

    public function orderStatusChanged(Model $order, string $oldStatus, string $newStatus): void
    {
        $this->log('order_status_changed', $order, ['status' => $oldStatus], ['status' => $newStatus]);
    }

    public function packageUpdated(Model $package, array $old, array $new): void
    {
        $this->log('package_updated', $package, $old, $new);
    }

    public function categoryUpdated(Model $category, array $old, array $new): void
    {
        $this->log('category_updated', $category, $old, $new);
    }

    public function settingsUpdated(array $old, array $new): void
    {
        $this->log('settings_updated', null, $old, $new);
    }
}
