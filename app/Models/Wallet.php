<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property string $balance
 * @property string $lifetime_topup
 * @property string $lifetime_spent
 */
class Wallet extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = ['user_id', 'balance', 'lifetime_topup', 'lifetime_spent'];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'lifetime_topup' => 'decimal:2',
            'lifetime_spent' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id');
    }
}
