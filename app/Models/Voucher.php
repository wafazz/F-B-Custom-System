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
 * @property string|null $banner_image
 * @property string $discount_type
 * @property string $discount_value
 * @property string $min_subtotal
 * @property string|null $max_discount
 * @property int|null $max_uses
 * @property int $max_uses_per_user
 * @property int $used_count
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string|null $valid_from_time
 * @property string|null $valid_until_time
 * @property array<int, int>|null $branch_ids
 * @property array<int, int>|null $tier_ids
 * @property array<int, int>|null $birthday_months
 * @property array<int, int>|null $product_ids
 * @property array<int, int>|null $combo_ids
 * @property int|null $bxgy_buy_qty
 * @property int|null $bxgy_free_qty
 * @property array<int, int>|null $bxgy_free_product_ids
 * @property array<int, int>|null $bxgy_free_combo_ids
 * @property bool $new_users_only
 * @property int|null $points_cost
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
        'banner_image',
        'discount_type',
        'discount_value',
        'min_subtotal',
        'max_discount',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'valid_from',
        'valid_until',
        'valid_from_time',
        'valid_until_time',
        'branch_ids',
        'tier_ids',
        'birthday_months',
        'product_ids',
        'combo_ids',
        'bxgy_buy_qty',
        'bxgy_free_qty',
        'bxgy_free_product_ids',
        'bxgy_free_combo_ids',
        'new_users_only',
        'points_cost',
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
            'tier_ids' => 'array',
            'birthday_months' => 'array',
            'product_ids' => 'array',
            'combo_ids' => 'array',
            'bxgy_buy_qty' => 'integer',
            'bxgy_free_qty' => 'integer',
            'bxgy_free_product_ids' => 'array',
            'bxgy_free_combo_ids' => 'array',
            'new_users_only' => 'boolean',
        ];
    }

    public function isBxgy(): bool
    {
        return $this->discount_type === 'buy_x_get_y';
    }

    /**
     * Is this voucher targeted to the given customer? Returns true when all
     * configured eligibility rules pass (tier_ids + birthday_months). null
     * arrays mean "no restriction on this dimension."
     */
    public function isEligibleFor(User $user): bool
    {
        if ($this->new_users_only) {
            $hasOrders = Order::query()->where('user_id', $user->getKey())->exists();
            if ($hasOrders) {
                return false;
            }
        }

        if (! empty($this->tier_ids)) {
            $tierRow = CustomerTier::query()->where('user_id', $user->getKey())->first();
            $userTierId = $tierRow?->membership_tier_id;
            if ($userTierId === null || ! in_array((int) $userTierId, $this->intArray($this->tier_ids), true)) {
                return false;
            }
        }

        if (! empty($this->birthday_months)) {
            $dob = $user->date_of_birth;
            if ($dob === null) {
                return false;
            }
            $month = (int) Carbon::parse($dob)->format('n');
            if (! in_array($month, $this->intArray($this->birthday_months), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filament's multi-select serialises picked values as strings inside the
     * JSON column; SQL whereIn coerces fine but PHP strict comparisons don't.
     * Normalise so all in_array() checks line up.
     *
     * @param  array<int|string, mixed>  $values
     * @return list<int>
     */
    protected function intArray(array $values): array
    {
        return array_values(array_map(static fn ($v): int => (int) $v, $values));
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
