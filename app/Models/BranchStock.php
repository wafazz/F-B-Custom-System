<?php

namespace App\Models;

use App\Events\BranchStockChanged;
use Database\Factories\BranchStockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BranchStock extends Model
{
    /** @use HasFactory<BranchStockFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'is_available', 'track_quantity', 'low_threshold'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $table = 'branch_stock';

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
        'low_threshold',
        'is_available',
        'track_quantity',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'track_quantity' => 'boolean',
            'last_restocked_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'low_threshold')
            ->where('track_quantity', true);
    }

    public function isLow(): bool
    {
        return $this->track_quantity && $this->quantity <= $this->low_threshold;
    }

    /** Atomically apply a stock change and broadcast availability flip if needed. */
    public function applyMovement(string $type, int $delta, ?string $reason = null, ?Model $reference = null, ?int $userId = null): StockMovement
    {
        /** @var StockMovement $movement */
        $movement = DB::transaction(function () use ($type, $delta, $reason, $reference, $userId) {
            $previouslyAvailable = $this->is_available && (! $this->track_quantity || $this->quantity > 0);

            $this->quantity = max(0, $this->quantity + $delta);
            if ($type === 'restock') {
                $this->last_restocked_at = now()->toDateTimeString();
            }
            $this->save();

            $row = new StockMovement([
                'user_id' => $userId,
                'type' => $type,
                'quantity_change' => $delta,
                'quantity_after' => $this->quantity,
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass() ?? self::class,
                'reference_id' => $reference?->getKey() ?? $this->getKey(),
            ]);
            $this->movements()->save($row);

            $nowAvailable = $this->is_available && (! $this->track_quantity || $this->quantity > 0);
            if ($previouslyAvailable !== $nowAvailable) {
                event(new BranchStockChanged($this->branch_id, $this->product_id, $nowAvailable, $this->quantity));
            }

            return $row;
        });

        return $movement;
    }
}
