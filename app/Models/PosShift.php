<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $opened_by_user_id
 * @property Carbon $opened_at
 * @property string $opening_float
 * @property int|null $closed_by_user_id
 * @property Carbon|null $closed_at
 * @property string|null $expected_cash
 * @property string|null $counted_cash
 * @property string|null $variance
 * @property string|null $notes
 */
class PosShift extends Model
{
    protected $fillable = [
        'branch_id',
        'opened_by_user_id',
        'opened_at',
        'opening_float',
        'closed_by_user_id',
        'closed_at',
        'expected_cash',
        'counted_cash',
        'variance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'shift_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shift_id');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }
}
