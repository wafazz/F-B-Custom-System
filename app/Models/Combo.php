<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image
 * @property string $price
 * @property string $status
 * @property int $sort_order
 * @property array<int, int>|null $branch_ids
 */
class Combo extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'price',
        'status',
        'sort_order',
        'branch_ids',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'branch_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Combo $combo) {
            if (empty($combo->slug)) {
                $combo->slug = Str::slug($combo->name);
            }
        });
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'combo_products')
            ->withPivot('quantity', 'sort_order')
            ->orderBy('combo_products.sort_order')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(function ($q) use ($branchId) {
            $q->whereNull('branch_ids')
                ->orWhereJsonContains('branch_ids', $branchId);
        });
    }
}
