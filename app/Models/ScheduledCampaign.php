<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ScheduledCampaign extends Model
{
    public function deliveries(): HasMany
    {
        return $this->hasMany(CampaignDelivery::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Fill the push placeholders: {name} → customer's first name,
     * {branch} → outlet name (where a branch context applies),
     * {usual} → the customer's most-bought item (usual-order reminders).
     * $tokens carries audience-specific extras keyed by placeholder name
     * (without braces), e.g. ['points' => 320, 'needed' => 80, 'tier' => 'Gold'].
     *
     * @param  array<string, int|string>  $tokens
     */
    public function renderMessage(string $text, ?User $user = null, ?string $branchName = null, ?string $usual = null, array $tokens = []): string
    {
        $first = $user !== null ? trim((string) explode(' ', (string) $user->name)[0]) : '';

        $search = ['{name}', '{branch}', '{usual}'];
        $replace = [$first, (string) $branchName, (string) $usual];
        foreach ($tokens as $key => $value) {
            $search[] = '{'.$key.'}';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $text);
    }

    /** Active proximity campaigns that have a target branch + radius set. */
    public static function activeLocationCampaigns()
    {
        return static::query()
            ->where('trigger_type', 'location')
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->whereNotNull('radius_meters')
            ->get();
    }

    protected $fillable = [
        'name',
        'trigger_type',
        'title',
        'body',
        'url',
        'branch_id',
        'radius_meters',
        'voucher_id',
        'audience',
        'inactivity_signal',
        'inactivity_days',
        'inactivity_repeat',
        'inactivity_cooldown_days',
        'frequency',
        'scheduled_at',
        'run_time',
        'run_days',
        'delay_minutes',
        'is_active',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'inactivity_days' => 'integer',
            'inactivity_repeat' => 'boolean',
            'inactivity_cooldown_days' => 'integer',
            'radius_meters' => 'integer',
            'delay_minutes' => 'integer',
            'run_days' => 'array',
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
            // Optional weekday filter (peak days). Empty = every day.
            $days = array_map('intval', $this->run_days ?? []);
            if ($days !== [] && ! in_array((int) $now->dayOfWeek, $days, true)) {
                return false;
            }

            [$h, $m] = array_pad(explode(':', (string) $this->run_time), 2, '0');
            $runToday = $now->copy()->setTime((int) $h, (int) $m, 0);

            return $now->greaterThanOrEqualTo($runToday)
                && ($this->last_sent_at === null || $this->last_sent_at->lessThan($runToday));
        }

        if ($this->frequency === 'monthly' && $this->run_time) {
            // run_days holds the day(s) of month to fire on (e.g. payday: 25, 28).
            // A chosen day past the month's length (e.g. 31 in Feb) clamps to the
            // last day, so picking 31 reliably means "end of month".
            $days = array_map('intval', $this->run_days ?? []);
            if ($days === []) {
                return false;
            }
            $matches = false;
            foreach ($days as $d) {
                if (min($d, $now->daysInMonth) === (int) $now->day) {
                    $matches = true;
                    break;
                }
            }
            if (! $matches) {
                return false;
            }

            [$h, $m] = array_pad(explode(':', (string) $this->run_time), 2, '0');
            $runToday = $now->copy()->setTime((int) $h, (int) $m, 0);

            return $now->greaterThanOrEqualTo($runToday)
                && ($this->last_sent_at === null || $this->last_sent_at->lessThan($runToday));
        }

        return false;
    }
}
