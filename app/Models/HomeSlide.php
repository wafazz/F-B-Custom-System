<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $image
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property bool $is_global
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 */
class HomeSlide extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'placement',
        'cta_label',
        'cta_url',
        'is_global',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'home_slide_branch');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /** @param  Builder<self>  $query */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('is_global', true)
            ->orWhereHas('branches', fn (Builder $sub) => $sub->where('branches.id', $branchId))
        );
    }

    /** @param  Builder<self>  $query */
    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }
}
