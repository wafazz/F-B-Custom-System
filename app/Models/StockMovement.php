<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'branch_stock_id',
        'user_id',
        'type',
        'quantity_change',
        'quantity_after',
        'reason',
        'reference_type',
        'reference_id',
    ];

    public function branchStock(): BelongsTo
    {
        return $this->belongsTo(BranchStock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
