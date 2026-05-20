<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $label
 * @property string $color
 * @property string|null $image_path
 * @property int $weight
 * @property string $prize_type
 * @property int|null $prize_points
 * @property int|null $voucher_id
 * @property int $sort_order
 * @property bool $is_active
 */
class SpinWheelSegment extends Model
{
    protected $fillable = [
        'label',
        'color',
        'image_path',
        'weight',
        'prize_type',
        'prize_points',
        'voucher_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
