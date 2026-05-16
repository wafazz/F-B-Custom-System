<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $branch_id
 * @property int|null $user_id
 * @property OrderType $order_type
 * @property string|null $dine_in_table
 * @property Carbon|null $pickup_at
 * @property OrderStatus $status
 * @property string $subtotal
 * @property string $sst_amount
 * @property string $discount_amount
 * @property string $total
 * @property PaymentStatus $payment_status
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property Carbon|null $paid_at
 * @property string|null $notes
 * @property string|null $cancellation_reason
 * @property array<string, mixed>|null $customer_snapshot
 * @property Carbon|null $preparing_at
 * @property Carbon|null $ready_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'branch_id',
        'shift_id',
        'user_id',
        'order_type',
        'dine_in_table',
        'pickup_at',
        'status',
        'subtotal',
        'sst_amount',
        'service_charge_amount',
        'discount_amount',
        'total',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'notes',
        'packaging',
        'use_own_tumbler',
        'tumbler_discount_amount',
        'cancellation_reason',
        'customer_snapshot',
        'preparing_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'order_type' => OrderType::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'sst_amount' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'pickup_at' => 'datetime',
            'paid_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'customer_snapshot' => 'array',
            'packaging' => 'array',
            'use_own_tumbler' => 'boolean',
            'tumbler_discount_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Pending->value,
            OrderStatus::Preparing->value,
            OrderStatus::Ready->value,
        ]);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [
            OrderStatus::Completed,
            OrderStatus::Cancelled,
            OrderStatus::Refunded,
        ], true);
    }

    public static function generateNumber(string $branchCode): string
    {
        $today = now()->format('ymd');
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branchCode) ?: 'SC', 0, 6));
        $sequence = static::query()
            ->where('number', 'like', "{$prefix}-{$today}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $today, $sequence);
    }
}
