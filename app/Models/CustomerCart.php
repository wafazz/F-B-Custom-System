<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCart extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'items',
        'item_count',
        'subtotal',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'item_count' => 'integer',
            'subtotal' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }
}
