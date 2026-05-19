<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $segment_id
 * @property int $awarded_points
 * @property int|null $voucher_claim_id
 * @property Carbon $spun_at
 */
class SpinAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'segment_id',
        'awarded_points',
        'voucher_claim_id',
        'spun_at',
    ];

    protected function casts(): array
    {
        return [
            'spun_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SpinWheelSegment, $this> */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(SpinWheelSegment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
