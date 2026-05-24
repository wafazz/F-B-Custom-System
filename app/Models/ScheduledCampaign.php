<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScheduledCampaign extends Model
{
    protected $fillable = [
        'name',
        'trigger_type',
        'title',
        'body',
        'url',
        'audience',
        'inactivity_signal',
        'inactivity_days',
        'frequency',
        'scheduled_at',
        'run_time',
        'delay_minutes',
        'is_active',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'inactivity_days' => 'integer',
            'delay_minutes' => 'integer',
            'scheduled_at' => 'datetime',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    /** The active abandoned-cart reminder, if the admin has one enabled. */
    public static function activeAbandonedCart(): ?self
    {
        return static::query()
            ->where('trigger_type', 'abandoned_cart')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Should this campaign fire at $now?
     *  - once  → its scheduled_at has passed and it has never sent
     *  - daily → today's run_time has passed and it hasn't sent yet today
     */
    public function isDue(Carbon $now): bool
    {
        if (! $this->is_active || $this->trigger_type !== 'schedule') {
            return false; // abandoned_cart is event-driven, never cron-fired
        }

        if ($this->frequency === 'once') {
            return $this->scheduled_at !== null
                && $this->scheduled_at->lessThanOrEqualTo($now)
                && $this->last_sent_at === null;
        }

        if ($this->frequency === 'daily' && $this->run_time) {
            [$h, $m] = array_pad(explode(':', (string) $this->run_time), 2, '0');
            $runToday = $now->copy()->setTime((int) $h, (int) $m, 0);

            return $now->greaterThanOrEqualTo($runToday)
                && ($this->last_sent_at === null || $this->last_sent_at->lessThan($runToday));
        }

        return false;
    }
}
