<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting
{
    private const CACHE_TTL = 300;

    /** Per-request in-memory store — eliminates repeated cache-driver reads within one request. */
    private static ?array $loaded = null;

    private static function cacheKey(): string
    {
        return 'system_settings_all_t' . (tenant_id() ?? 0);
    }

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
        Cache::forget(static::cacheKey());
        static::$loaded = null;
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        Cache::forget(static::cacheKey());
        static::$loaded = null;
    }

    public static function all(): array
    {
        if (static::$loaded !== null) return static::$loaded;

        return static::$loaded = Cache::remember(static::cacheKey(), static::CACHE_TTL, function () {
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
        Cache::forget(static::cacheKey());
        static::$loaded = null;
    }
}
