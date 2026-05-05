<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaSistema extends Model
{
    protected $table = 'alertas_sistema';

    protected $fillable = [
        'tipo', 'titulo', 'mensaje', 'nivel',
        'destinatario_id', 'destinatario_rol',
        'referencia_tipo', 'referencia_id',
        'leida', 'fecha_leida', 'expira_en',
        'school_year_id', 'creado_por',
    ];

    protected $casts = [
        'leida'       => 'boolean',
        'expira_en'   => 'datetime',
        'fecha_leida' => 'datetime',
    ];

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expira_en')
              ->orWhere('expira_en', '>', now());
        });
    }

    public function scopeParaUsuario($query, int $userId, ?string $rol = null)
    {
        return $query->where(function ($q) use ($userId, $rol) {
            $q->where('destinatario_id', $userId);
            if ($rol) {
                $q->orWhere('destinatario_rol', $rol);
            }
        });
    }

    public function marcarLeida(): void
    {
        $this->update([
            'leida'       => true,
            'fecha_leida' => now(),
        ]);
    }

    public function getNivelIconoAttribute(): string
    {
        return match ($this->nivel) {
            'danger'  => 'bi-exclamation-triangle-fill text-danger',
            'warning' => 'bi-exclamation-circle-fill text-warning',
            'success' => 'bi-check-circle-fill text-success',
            default   => 'bi-info-circle-fill text-info',
        };
    }

    public static function tiposLabels(): array
    {
        return [
            'riesgo_academico' => 'Riesgo Académico',
            'entrega_notas'    => 'Entrega de Notas',
            'baja_asistencia'  => 'Baja Asistencia',
            'periodo_cierre'   => 'Cierre de Período',
            'evento_calendario'=> 'Evento de Calendario',
            'otro'             => 'Otro',
        ];
    }
}
