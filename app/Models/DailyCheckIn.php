<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $check_in_date
 * @property int $day_number_awarded
 * @property string $reward_type
 * @property int $awarded_points
 * @property int|null $voucher_claim_id
 */
class DailyCheckIn extends Model
{
    protected $fillable = [
        'user_id',
        'check_in_date',
        'day_number_awarded',
        'reward_type',
        'awarded_points',
        'voucher_claim_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'day_number_awarded' => 'integer',
            'awarded_points' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<VoucherClaim, $this> */
    public function voucherClaim(): BelongsTo
    {
        return $this->belongsTo(VoucherClaim::class);
    }
}
