<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row (id = 1) for the daily check-in feature.
 *
 * @property int $id
 * @property int $max_days
 * @property bool $reset_on_skip
 */
class DailyCheckInSetting extends Model
{
    protected $fillable = [
        'max_days',
        'reset_on_skip',
    ];

    protected function casts(): array
    {
        return [
            'max_days' => 'integer',
            'reset_on_skip' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['max_days' => 7, 'reset_on_skip' => true],
        );
    }
}
