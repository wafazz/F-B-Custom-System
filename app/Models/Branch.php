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
        if ($this->status !== 'active' || ! $this->accepts_orders) {
            return false;
        }

        $time = $time ?? now();
        $day = strtolower($time->englishDayOfWeek);

        /** @var array<string, array{enabled?: bool, open?: string, close?: string}>|null $allHours */
        $allHours = $this->operating_hours;
        $hours = is_array($allHours) ? ($allHours[$day] ?? null) : null;

        if (! is_array($hours) || empty($hours['enabled'])) {
            return false;
        }

        $current = $time->format('H:i');

        return $current >= ($hours['open'] ?? '00:00')
            && $current <= ($hours['close'] ?? '23:59');
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
