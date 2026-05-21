<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $branch_id
 * @property int|null $order_id
 * @property int $rating
 * @property string|null $comment
 * @property bool $is_hidden
 */
class BranchReview extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'order_id',
        'rating',
        'comment',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
