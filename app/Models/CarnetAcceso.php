<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarnetAcceso extends Model
{
    use BelongsToTenant;

    protected $table = 'carnet_accesos';

    protected $fillable = [
        'carnet_identidad_id', 'tipo_evento', 'estado',
        'zona_id', 'dispositivo', 'ip_address', 'notas', 'registrado_por',
    ];

    const TIPOS_EVENTO = [
        'entrada'    => 'Entrada',
        'salida'     => 'Salida',
        'biblioteca' => 'Biblioteca',
        'comedor'    => 'Comedor',
        'laboratorio'=> 'Laboratorio',
        'evento'     => 'Evento',
        'prestamo'   => 'Préstamo',
    ];

    const ESTADOS = [
        'presente'         => ['label' => 'Presente',          'color' => 'success', 'icon' => 'bi-check-circle-fill'],
        'tardanza'         => ['label' => 'Tardanza',           'color' => 'warning', 'icon' => 'bi-clock-fill'],
        'salida_anticipada'=> ['label' => 'Salida anticipada',  'color' => 'info',    'icon' => 'bi-box-arrow-right'],
        'denegado'         => ['label' => 'Denegado',           'color' => 'danger',  'icon' => 'bi-x-circle-fill'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function carnet(): BelongsTo
    {
        return $this->belongsTo(CarnetIdentidad::class, 'carnet_identidad_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(CarnetZona::class, 'zona_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeHoy($q)
    {
        return $q->whereDate('created_at', today());
    }

    public function scopeEntradas($q)
    {
        return $q->where('tipo_evento', 'entrada');
    }

    public function scopeSalidas($q)
    {
        return $q->where('tipo_evento', 'salida');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getEstadoBadgeAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? ['label' => $this->estado, 'color' => 'secondary', 'icon' => 'bi-circle'];
    }

    public function getHoraAttribute(): string
    {
        return $this->created_at?->format('h:i A') ?? '—';
    }
}
