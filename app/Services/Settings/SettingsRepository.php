<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    public const CACHE_KEY = 'app.settings';

    /** @var array<string, string|null>|null */
    protected ?array $loaded = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->all()[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    public function set(string $key, ?string $value, bool $encrypted = false): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_encrypted' => $encrypted],
        );
        $this->forget();
    }

    /** @param  array<string, array{value: ?string, encrypted?: bool}>  $rows */
    public function setMany(array $rows): void
    {
        foreach ($rows as $key => $row) {
            $this->set($key, $row['value'] ?? null, $row['encrypted'] ?? false);
        }
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
            $out = [];
            foreach (Setting::all() as $row) {
                $out[$row->key] = $row->value;
            }

            return $out;
        });
    }

    public function forget(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }
}
