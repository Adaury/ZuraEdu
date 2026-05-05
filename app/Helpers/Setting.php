<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting
{
    private const CACHE_KEY = 'system_settings_all';
    private const CACHE_TTL = 300;

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        Cache::forget(static::CACHE_KEY);
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        Cache::forget(static::CACHE_KEY);
    }

    public static function all(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return DB::table('system_settings')->pluck('value', 'key')->toArray();
        });
    }

    public static function isPrivado(): bool
    {
        return static::get('institution_type', 'publico') === 'privado';
    }

    public static function moduleEnabled(string $module): bool
    {
        return (bool) static::get("module_{$module}", false);
    }

    public static function flush(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
