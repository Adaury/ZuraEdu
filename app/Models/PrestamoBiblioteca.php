<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoBiblioteca extends Model
{
    use BelongsToTenant;

    protected $table = 'prestamos_biblioteca';

    protected $fillable = [
        'libro_id',
        'estudiante_id',
        'fecha_prestamo',
        'fecha_vencimiento',
        'fecha_devolucion',
        'estado',
        'notas',
        'renovaciones',
    ];

    protected $casts = [
        'fecha_prestamo'    => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_devolucion'  => 'date',
        'renovaciones'      => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeDevueltos($query)
    {
        return $query->where('estado', 'devuelto');
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['activo', 'vencido']);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getEstaVencidoAttribute(): bool
    {
        return $this->estado === 'vencido'
            || ($this->estado === 'activo' && $this->fecha_vencimiento < now()->startOfDay());
    }

    public function getBadgeEstadoAttribute(): string
    {
        return match($this->estado) {
            'activo'   => 'primary',
            'devuelto' => 'success',
            'vencido'  => 'danger',
            default    => 'secondary',
        };
    }
}
