<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $branch_id
 * @property string $scope
 * @property string $platform
 * @property string $token
 * @property string|null $device_id
 * @property string|null $device_name
 * @property string|null $app_version
 * @property Carbon|null $last_seen_at
 */
class DeviceToken extends Model
{
    public const SCOPE_CUSTOMER = 'customer';

    public const SCOPE_POS = 'pos';

    protected $fillable = [
        'user_id',
        'branch_id',
        'scope',
        'platform',
        'token',
        'device_id',
        'device_name',
        'app_version',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
