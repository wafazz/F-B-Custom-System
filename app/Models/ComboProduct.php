<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $combo_id
 * @property int $product_id
 * @property int $quantity
 * @property int $sort_order
 */
class ComboProduct extends Model
{
    protected $table = 'combo_products';

    protected $fillable = [
        'combo_id',
        'product_id',
        'quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Combo, $this> */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
