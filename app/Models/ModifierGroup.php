<?php

namespace App\Models;

use Database\Factories\ModifierGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ModifierGroup extends Model
{
    /** @use HasFactory<ModifierGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'selection_type',
        'is_required',
        'min_select',
        'max_select',
        'allow_quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'allow_quantity' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ModifierGroup $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    /** @return HasMany<ModifierOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
