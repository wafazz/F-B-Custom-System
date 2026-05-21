<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $day_number
 * @property string|null $label
 * @property string $reward_type
 * @property int|null $points
 * @property int|null $voucher_id
 * @property bool $is_active
 */
class DailyCheckInReward extends Model
{
    protected $fillable = [
        'day_number',
        'label',
        'reward_type',
        'points',
        'voucher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
