<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $voucher_id
 * @property int $user_id
 * @property Carbon $claimed_at
 * @property Carbon|null $used_at
 * @property int|null $order_id
 */
class VoucherClaim extends Model
{
    protected $fillable = [
        'voucher_id',
        'user_id',
        'claimed_at',
        'used_at',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->whereNull('used_at');
    }
}
