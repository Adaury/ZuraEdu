<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfigInstitucional extends Model
{
    use BelongsToTenant;

    protected $table = 'config_institucional';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'grupo',
        'descripcion',
    ];

    public static function get(string $clave, $default = null)
    {
        $tid = tenant_id();
        return Cache::remember("config_t{$tid}_{$clave}", 300, function () use ($clave, $default) {
            $c = static::where('clave', $clave)->first();
            if (!$c) return $default;
            return match ($c->tipo) {
                'boolean' => (bool) $c->valor,
                'integer' => (int) $c->valor,
                'json'    => json_decode($c->valor, true),
                default   => $c->valor,
            };
        });
    }

    public static function set(string $clave, $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => is_array($valor) ? json_encode($valor) : $valor]
        );
        Cache::forget("config_t" . tenant_id() . "_{$clave}");
    }

    public static function esPrivado(): bool
    {
        return static::get('tipo_institucion') === 'privado';
    }

    public static function esPublico(): bool
    {
        return static::get('tipo_institucion') === 'publico';
    }

    public static function moduloActivo(string $modulo): bool
    {
        return (bool) static::get("modulo_{$modulo}_activo", false);
    }
}
