<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SchoolYear extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function periodos()
    {
        return $this->hasMany(Periodo::class);
    }

    public function configCalificaciones()
    {
        return $this->hasMany(ConfigCalificacion::class);
    }

    public function boletinConfig()
    {
        return $this->hasMany(BoletinConfig::class);
    }

    public function scopeActivo($q)
    {
        return $q->where('activo', true);
    }

    public static function actual(): ?self
    {
        $tid = tenant_id();
        return Cache::remember("t{$tid}_school_year_actual", 300, fn () =>
            static::where('activo', true)->first()
        );
    }

    public static function flushActualCache(): void
    {
        Cache::forget('t' . tenant_id() . '_school_year_actual');
    }

    public function getNombrePeriodoAttribute(): string
    {
        return 'Año Escolar ' . $this->nombre;
    }
}
