<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property bool $is_encrypted
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    /** Auto-decrypt on read, auto-encrypt on write when is_encrypted is true. */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($raw, array $attrs) {
                if ($raw === null) {
                    return null;
                }
                if (empty($attrs['is_encrypted'])) {
                    return (string) $raw;
                }
                try {
                    return Crypt::decryptString((string) $raw);
                } catch (Throwable) {
                    return null;
                }
            },
            set: function ($plain, array $attrs) {
                if ($plain === null) {
                    return ['value' => null];
                }
                if (! empty($attrs['is_encrypted'])) {
                    return ['value' => Crypt::encryptString((string) $plain)];
                }

                return ['value' => (string) $plain];
            },
        );
    }
}
