<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $device_fingerprint
 * @property string|null $user_agent
 * @property string|null $platform
 * @property Carbon $installed_at
 * @property Carbon $last_active_at
 */
class PwaInstall extends Model
{
    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'user_agent',
        'platform',
        'installed_at',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
