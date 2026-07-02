<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string|null $heading
 * @property string $token
 * @property bool $is_active
 * @property string $layout
 * @property int $seconds_per_slide
 * @property bool $show_price
 * @property Carbon|null $last_seen_at
 * @property array<string, mixed>|null $settings
 */
class MenuDisplay extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'heading',
        'token',
        'is_active',
        'layout',
        'seconds_per_slide',
        'show_price',
        'posters',
        'videos',
        'last_seen_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_price' => 'boolean',
            'seconds_per_slide' => 'integer',
            'posters' => 'array',
            'videos' => 'array',
            'last_seen_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MenuDisplay $row) {
            if (empty($row->token)) {
                $row->token = Str::random(48);
            }
        });
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'menu_display_category')
            ->withPivot('sort_order')
            ->orderBy('menu_display_category.sort_order')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.id');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
