<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'base_price', 'status', 'is_featured', 'category_id'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'base_price',
        'sst_applicable',
        'image',
        'gallery',
        'calories',
        'prep_time_minutes',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'base_price' => 'decimal:2',
            'sst_applicable' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<ModifierGroup, $this> */
    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group')
            ->withPivot('sort_order')
            ->orderBy('product_modifier_group.sort_order')
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_product')
            ->withPivot(['is_available', 'price_override'])
            ->withTimestamps();
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Products that are visible AND in stock at the given branch. */
    public function scopeAvailableAtBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('branches', fn ($q) => $q
                ->where('branches.id', $branchId)
                ->where('branch_product.is_available', true))
            ->where(function (Builder $q) use ($branchId) {
                $q->whereDoesntHave('stocks', fn ($s) => $s
                    ->where('branch_id', $branchId)
                    ->where('track_quantity', true))
                    ->orWhereHas('stocks', fn ($s) => $s
                        ->where('branch_id', $branchId)
                        ->where('is_available', true)
                        ->where(function (Builder $sq) {
                            $sq->where('track_quantity', false)
                                ->orWhere('quantity', '>', 0);
                        }));
            });
    }

    public function priceForBranch(int $branchId): float
    {
        $override = $this->branches
            ->firstWhere('id', $branchId)
            ?->getRelationValue('pivot')
            ?->getAttribute('price_override');

        return (float) ($override ?? $this->base_price);
    }
}
