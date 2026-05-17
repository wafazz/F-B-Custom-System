<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'operating_hours',
        'pickup_radius_meters',
        'sst_rate',
        'sst_enabled',
        'service_charge_rate',
        'service_charge_enabled',
        'receipt_header',
        'receipt_footer',
        'cover_image',
        'logo',
        'status',
        'accepts_orders',
        'sort_order',
        'auto_print_labels',
        'label_copies',
        'label_size',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'sst_rate' => 'decimal:2',
            'sst_enabled' => 'boolean',
            'service_charge_rate' => 'decimal:2',
            'service_charge_enabled' => 'boolean',
            'accepts_orders' => 'boolean',
            'auto_print_labels' => 'boolean',
            'label_copies' => 'integer',
        ];
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_staff')
            ->using(BranchStaff::class)
            ->withPivot(['pin', 'employment_type', 'hired_at', 'ended_at', 'is_active'])
            ->withTimestamps();
    }

    /** Alias used by Filament's AttachAction inverse-relation auto-discovery. */
    public function users(): BelongsToMany
    {
        return $this->staff();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product')
            ->withPivot(['is_available', 'price_override'])
            ->withTimestamps();
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('accepts_orders', true);
    }

    public function isOpenNow(?Carbon $time = null): bool
    {
        return $this->closedReason($time) === null;
    }

    /**
     * Returns null when the branch is open right now, otherwise a short
     * human-readable reason ("Paused by admin", "Closed Sunday", "Opens 08:00", etc.).
     */
    public function closedReason(?Carbon $time = null): ?string
    {
        if ($this->status !== 'active') {
            return 'Paused by admin';
        }
        if (! $this->accepts_orders) {
            return 'Not accepting orders';
        }

        $time = $time ?? now();
        $day = strtolower($time->englishDayOfWeek);
        $dayLabel = ucfirst($day);

        /** @var array<string, array{enabled?: bool, open?: string, close?: string}>|null $allHours */
        $allHours = $this->operating_hours;
        $hours = is_array($allHours) ? ($allHours[$day] ?? null) : null;

        if (! is_array($hours)) {
            return "No hours set for {$dayLabel}";
        }
        if (empty($hours['enabled'])) {
            return "Closed on {$dayLabel}";
        }

        $currentMinutes = $time->hour * 60 + $time->minute;
        $openMinutes = $this->parseTimeToMinutes($hours['open'] ?? '00:00');
        $closeMinutes = $this->parseTimeToMinutes($hours['close'] ?? '23:59');
        $openText = $hours['open'] ?? '00:00';
        $closeText = $hours['close'] ?? '23:59';

        // Close <= open means the branch closes after midnight
        // (e.g. open 08:00, close 00:00 or 02:00). Wrap around to next day.
        if ($closeMinutes <= $openMinutes) {
            return $currentMinutes >= $openMinutes || $currentMinutes < $closeMinutes
                ? null
                : "Opens {$openText}";
        }

        if ($currentMinutes < $openMinutes) {
            return "Opens {$openText}";
        }
        if ($currentMinutes > $closeMinutes) {
            return "Closed since {$closeText}";
        }

        return null;
    }

    protected function parseTimeToMinutes(string $time): int
    {
        // Robust against "HH:MM", "HH:MM:SS", or ISO datetime — we only want
        // the wall-clock H + M.
        if (preg_match('/(\d{1,2}):(\d{2})/', $time, $m)) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }

        return 0;
    }

    public static function defaultOperatingHours(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn ($day) => [
                $day => ['enabled' => true, 'open' => '08:00', 'close' => '22:00'],
            ])
            ->toArray();
    }
}
