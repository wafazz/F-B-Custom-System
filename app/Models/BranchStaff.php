<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchStaff extends Pivot
{
    protected $table = 'branch_staff';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'branch_id',
        'pin',
        'employment_type',
        'hired_at',
        'ended_at',
        'is_active',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'hired_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
        ];
    }
}
