<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    use BelongsToTenant;

    protected $table = 'equipos';

    protected $fillable = [
        'nombre',
        'tipo',
        'codigo',
        'estado',
        'descripcion',
    ];

    // ── Tipos de equipo ───────────────────────────────────────────────────
    const TIPOS = [
        'laptop'    => 'Laptop',
        'tablet'    => 'Tablet',
        'proyector' => 'Proyector',
        'camara'    => 'Cámara',
        'otro'      => 'Otro',
    ];

    const ESTADOS = [
        'disponible'    => 'Disponible',
        'prestado'      => 'Prestado',
        'mantenimiento' => 'En mantenimiento',
        'baja'          => 'De baja',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function prestamos(): HasMany
    {
        return $this->hasMany(PrestamoEquipo::class, 'equipo_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getDisponibleAttribute(): bool
    {
        return $this->estado === 'disponible';
    }

    public function getBadgeEstadoAttribute(): string
    {
        return match ($this->estado) {
            'disponible'    => 'success',
            'prestado'      => 'primary',
            'mantenimiento' => 'warning',
            'baja'          => 'danger',
            default         => 'secondary',
        };
    }

    public function getEtiquetaTipoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }
}
