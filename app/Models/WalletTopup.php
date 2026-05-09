<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $amount
 * @property string $status
 * @property string|null $billplz_reference
 * @property \Illuminate\Support\Carbon|null $paid_at
 */
class WalletTopup extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'billplz_reference',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
