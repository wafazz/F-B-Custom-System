<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property Carbon $last_seen_at
 * @property string|null $user_agent
 */
class UserPresence extends Model
{
    protected $table = 'user_presence';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'last_seen_at',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public static function onlineCount(int $windowMinutes = 5): int
    {
        return static::query()
            ->where('last_seen_at', '>=', now()->subMinutes($windowMinutes))
            ->count();
    }
}
