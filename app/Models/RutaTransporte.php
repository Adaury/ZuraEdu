<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RutaTransporte extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'rutas_transporte';

    protected $fillable = [
        'nombre',
        'descripcion',
        'conductor',
        'vehiculo',
        'capacidad',
        'activo',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'capacidad' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function paradas()
    {
        return $this->hasMany(ParadaRuta::class, 'ruta_id')->orderBy('orden');
    }

    public function estudiantesRuta()
    {
        return $this->hasMany(EstudianteRuta::class, 'ruta_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_ruta', 'ruta_id', 'estudiante_id')
                    ->withPivot('tipo', 'parada_id')
                    ->withTimestamps();
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getOcupacionAttribute(): int
    {
        return $this->estudiantesRuta()->count();
    }

    public function getPorcentajeOcupacionAttribute(): int
    {
        if ($this->capacidad <= 0) return 0;
        return (int) round(($this->ocupacion / $this->capacidad) * 100);
    }

    public function getDisponiblesAttribute(): int
    {
        return max(0, $this->capacidad - $this->ocupacion);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
