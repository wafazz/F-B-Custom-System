<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property int $membership_tier_id
 * @property string $lifetime_spend
 * @property Carbon|null $achieved_at
 */
class CustomerTier extends Model
{
    protected $table = 'customer_tier';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'membership_tier_id',
        'lifetime_spend',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'lifetime_spend' => 'decimal:2',
            'achieved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MembershipTier, $this> */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
