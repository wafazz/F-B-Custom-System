<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shift_id
 * @property 'cash_in'|'cash_out' $type
 * @property string $amount
 * @property string $reason
 * @property int $recorded_by_user_id
 */
class PosCashMovement extends Model
{
    protected $fillable = [
        'shift_id',
        'type',
        'amount',
        'reason',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'shift_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function signedAmount(): float
    {
        return $this->type === 'cash_in' ? (float) $this->amount : -(float) $this->amount;
    }
}
