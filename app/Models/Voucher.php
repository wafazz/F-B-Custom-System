<?php

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $discount_type
 * @property string $discount_value
 * @property string $min_subtotal
 * @property string|null $max_discount
 * @property int|null $max_uses
 * @property int $max_uses_per_user
 * @property int $used_count
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property array<int, int>|null $branch_ids
 * @property string $status
 */
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_subtotal',
        'max_discount',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'valid_from',
        'valid_until',
        'branch_ids',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'branch_ids' => 'array',
        ];
    }

    /** @return HasMany<VoucherRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /** @return HasMany<VoucherClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(VoucherClaim::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now));
    }
}
