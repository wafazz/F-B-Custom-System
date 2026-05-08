<?php

namespace App\Models;

use Database\Factories\MembershipTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $min_lifetime_spend
 * @property string $earn_multiplier
 * @property string|null $color
 * @property int $sort_order
 */
class MembershipTier extends Model
{
    /** @use HasFactory<MembershipTierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_lifetime_spend',
        'earn_multiplier',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_lifetime_spend' => 'decimal:2',
            'earn_multiplier' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MembershipTier $tier) {
            if (empty($tier->slug)) {
                $tier->slug = Str::slug($tier->name);
            }
        });
    }

    /** @return HasMany<CustomerTier, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(CustomerTier::class);
    }

    public static function tierForSpend(float $lifetimeSpend): ?self
    {
        return self::query()
            ->where('min_lifetime_spend', '<=', $lifetimeSpend)
            ->orderByDesc('min_lifetime_spend')
            ->first();
    }
}
