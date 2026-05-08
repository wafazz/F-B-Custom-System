<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $referrer_user_id
 * @property int $referee_user_id
 * @property int $order_id
 * @property int $referrer_points
 * @property int $referee_points
 */
class ReferralReward extends Model
{
    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'order_id',
        'referrer_points',
        'referee_points',
    ];

    /** @return BelongsTo<User, $this> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
