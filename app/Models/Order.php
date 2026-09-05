<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'phone',
        'email',
        'governorate',
        'city',
        'address_line',
        'notes',
        'subtotal',
        'shipping_fee',
        'total',
        'status',
        'payment_method',
        'tracking_number',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isForUser(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function isFulfilled(): bool
    {
        return in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true);
    }

    public function markAwaitingPayment(): void
    {
        if ($this->status === OrderStatus::Pending) {
            $this->update(['status' => OrderStatus::AwaitingPayment]);
        }
    }
}
