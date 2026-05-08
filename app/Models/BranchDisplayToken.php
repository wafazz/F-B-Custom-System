<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $token
 * @property bool $is_active
 * @property Carbon|null $last_seen_at
 * @property array<string, mixed>|null $settings
 */
class BranchDisplayToken extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'token',
        'is_active',
        'last_seen_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BranchDisplayToken $row) {
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
