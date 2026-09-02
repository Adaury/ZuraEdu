<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting
{
    private const CACHE_TTL = 300;

    /**
     * In-memory store, indexado por tenant_id — NO un solo array plano.
     * En un proceso de vida corta (request HTTP normal) da igual, pero en un
     * proceso de larga duración que atiende varios tenants (queue worker,
     * o un comando de consola que itera tenants vía LoopsPerTenant) un
     * array plano sin la clave de tenant arrastraba los settings del primer
     * tenant leído a todos los siguientes hasta el próximo set()/flush().
     */
    private static array $loaded = [];

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
            ['tenant_id' => tenant_id(), 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        static::flush();
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['tenant_id' => tenant_id(), 'key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        static::flush();
    }

    public static function all(): array
    {
        $tid = tenant_id() ?? 0;

        if (array_key_exists($tid, static::$loaded)) {
            return static::$loaded[$tid];
        }

        return static::$loaded[$tid] = Cache::remember(static::cacheKey(), static::CACHE_TTL, function () use ($tid) {
            return DB::table('system_settings')
                ->where('tenant_id', $tid)
                ->pluck('value', 'key')->toArray();
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
        unset(static::$loaded[tenant_id() ?? 0]);
    }
}
