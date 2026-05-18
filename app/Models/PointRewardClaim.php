<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $point_reward_id
 * @property int $user_id
 * @property int $points
 * @property Carbon $claimed_at
 */
class PointRewardClaim extends Model
{
    protected $fillable = [
        'point_reward_id',
        'user_id',
        'points',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PointReward, $this> */
    public function pointReward(): BelongsTo
    {
        return $this->belongsTo(PointReward::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
