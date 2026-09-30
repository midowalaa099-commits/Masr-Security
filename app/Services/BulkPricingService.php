<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use stdClass;

class BulkPricingService
{
    public function __construct(private BulkPriceCalculator $calculator, private AuditLogger $audit) {}

    /** @param array<string, mixed> $options */
    public function preview(array $options, int $userId): int
    {
        return DB::transaction(function () use ($options, $userId): int {
            $batchId = DB::table('bulk_price_changes')->insertGetId([
                'token' => (string) Str::uuid(), 'user_id' => $userId,
                'options' => json_encode($options, JSON_THROW_ON_ERROR), 'status' => 'preview',
                'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $query = Product::query()->select(['id', 'sku', 'price', 'sale_price', 'pricing_revision', 'brand', 'category_id']);
            match ($options['scope']) {
                'brand' => $query->whereRaw('LOWER(TRIM(brand)) = ?', [mb_strtolower(trim($options['brand']))]),
                'category' => $query->where('category_id', $options['category_id']),
                'selected' => $query->whereIn('id', $options['product_ids']),
                default => $query,
            };
            $rows = [];
            foreach ($query->lazyById(200) as $product) {
                $before = $this->snapshot($product);
                $after = ['price' => $product->price, 'sale_price' => $product->sale_price];
                if (in_array($options['target'], ['regular', 'both'], true)) {
                    $after['price'] = $this->calculator->calculate($product->price, $options);
                }
                if (in_array($options['target'], ['sale', 'both'], true) && $product->sale_price !== null) {
                    $after['sale_price'] = $this->calculator->calculate($product->sale_price, $options);
                }
                $reason = $options['target'] === 'sale' && $product->sale_price === null
                    ? 'no_sale_price' : $this->calculator->invalidReason($after);
                if ($reason === null && $after['price'] === $before['price'] && $after['sale_price'] === $before['sale_price']) {
                    $reason = 'unchanged';
                }
                $rows[] = [
                    'bulk_price_change_id' => $batchId, 'product_id' => $product->id, 'sku' => $product->sku,
                    'before' => json_encode($before, JSON_THROW_ON_ERROR), 'after' => json_encode($after, JSON_THROW_ON_ERROR),
                    'status' => $reason === null ? 'ready' : 'skipped', 'reason' => $reason,
                ];
                if (count($rows) === 200) {
                    DB::table('bulk_price_change_items')->insert($rows);
                    $rows = [];
                }
            }
            if ($rows !== []) {
                DB::table('bulk_price_change_items')->insert($rows);
            }
            $this->audit->log('bulk_pricing_previewed', newValues: $options, entityType: 'bulk_price_change', entityId: $batchId);

            return $batchId;
        });
    }

    public function apply(int $batchId, int $userId, string $token): bool
    {
        return DB::transaction(function () use ($batchId, $userId, $token): bool {
            $batch = $this->lockedBatch($batchId, $userId, $token);
            if ($batch->status !== 'preview') {
                return false;
            }
            abort_if(now()->greaterThan($batch->expires_at), 409, __('pricing.expired'));
            foreach ($this->items($batchId)->where('status', 'ready')->lazyById(200) as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                $before = json_decode($item->before, true, flags: JSON_THROW_ON_ERROR);
                $after = json_decode($item->after, true, flags: JSON_THROW_ON_ERROR);
                if ($product === null || ! $this->snapshotMatches($product, $before)) {
                    throw ValidationException::withMessages(['pricing' => __('pricing.changed_since_preview')]);
                }
                $product->fill($after)->save();
                $this->items($batchId)->where('id', $item->id)->update([
                    'status' => 'applied', 'applied_revision' => $product->pricing_revision,
                ]);
                $this->audit->log('bulk_pricing_applied', $product, $before, $after + ['batch_id' => $batchId]);
            }
            DB::table('bulk_price_changes')->where('id', $batchId)->update([
                'status' => 'applied', 'applied_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit->log('bulk_pricing_completed', newValues: ['batch_id' => $batchId], entityType: 'bulk_price_change', entityId: $batchId);

            return true;
        }, 3);
    }

    public function undo(int $batchId, int $userId, string $token): void
    {
        DB::transaction(function () use ($batchId, $userId, $token): void {
            $batch = $this->lockedBatch($batchId, $userId, $token);
            if ($batch->status === 'undone') {
                return;
            }
            abort_unless($batch->status === 'applied', 409);
            foreach ($this->items($batchId)->where('status', 'applied')->lazyById(200) as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                $before = json_decode($item->before, true, flags: JSON_THROW_ON_ERROR);
                $after = json_decode($item->after, true, flags: JSON_THROW_ON_ERROR);
                if ($product === null || $product->pricing_revision !== $item->applied_revision
                    || $product->price !== $after['price'] || $product->sale_price !== $after['sale_price']) {
                    $this->setResult($item->id, 'undo_conflict', $product === null ? 'deleted' : 'changed_after_apply');

                    continue;
                }
                $restored = ['price' => $before['price'], 'sale_price' => $before['sale_price']];
                $product->fill($restored)->save();
                $this->setResult($item->id, 'undone');
                $this->audit->log('bulk_pricing_undone', $product, $after, $restored + ['batch_id' => $batchId]);
            }
            DB::table('bulk_price_changes')->where('id', $batchId)->update([
                'status' => 'undone', 'undone_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit->log('bulk_pricing_undo_completed', newValues: ['batch_id' => $batchId], entityType: 'bulk_price_change', entityId: $batchId);
        }, 3);
    }

    private function lockedBatch(int $batchId, int $userId, string $token): stdClass
    {
        $batch = DB::table('bulk_price_changes')->where('id', $batchId)->lockForUpdate()->first();
        abort_if($batch === null, 404);
        abort_unless((int) $batch->user_id === $userId && hash_equals($batch->token, $token), 403);

        return $batch;
    }

    private function items(int $batchId): Builder
    {
        return DB::table('bulk_price_change_items')->where('bulk_price_change_id', $batchId);
    }

    private function setResult(int $itemId, string $status, ?string $reason = null): void
    {
        DB::table('bulk_price_change_items')->where('id', $itemId)->update(compact('status', 'reason'));
    }

    /** @param array<string, mixed> $expected */
    private function snapshotMatches(Product $product, array $expected): bool
    {
        $current = $this->snapshot($product);
        ksort($current);
        ksort($expected);

        return $current === $expected;
    }

    /** @return array{price: string, sale_price: ?string, pricing_revision: ?string, brand: ?string, category_id: ?int} */
    private function snapshot(Product $product): array
    {
        return [
            'price' => $product->price, 'sale_price' => $product->sale_price,
            'pricing_revision' => $product->pricing_revision, 'brand' => $product->brand,
            'category_id' => $product->category_id === null ? null : (int) $product->category_id,
        ];
    }
}
