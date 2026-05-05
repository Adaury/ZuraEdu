<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoEquipo extends Model
{
    use BelongsToTenant;

    protected $table = 'prestamos_equipo';

    protected $fillable = [
        'equipo_id',
        'usuario_id',
        'fecha_prestamo',
        'fecha_vencimiento',
        'fecha_devolucion',
        'motivo',
        'estado',
    ];

    protected $casts = [
        'fecha_prestamo'    => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_devolucion'  => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
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
        return match ($this->estado) {
            'activo'   => 'primary',
            'devuelto' => 'success',
            'vencido'  => 'danger',
            default    => 'secondary',
        };
    }
}
