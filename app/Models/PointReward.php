<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $banner_image
 * @property int $points_cost
 * @property int|null $product_id
 * @property string $kind
 * @property int $max_claims_per_user
 * @property int|null $stock
 * @property int $claimed_count
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string $status
 */
class PointReward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'banner_image',
        'points_cost',
        'product_id',
        'kind',
        'max_claims_per_user',
        'stock',
        'claimed_count',
        'valid_from',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /** @return HasMany<PointRewardClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(PointRewardClaim::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now));
    }
}
