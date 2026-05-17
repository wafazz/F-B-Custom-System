<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $body
 * @property string|null $last_updated_label
 */
class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'body',
        'last_updated_label',
    ];
}
