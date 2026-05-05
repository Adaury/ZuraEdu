<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarioAcademico extends Model
{
    use BelongsToTenant;

    protected $table = 'calendario_academico';

    protected $fillable = [
        'school_year_id', 'titulo', 'descripcion', 'tipo',
        'fecha_inicio', 'fecha_fin', 'hora_inicio', 'color',
        'aplica_a', 'periodo_id', 'creado_por', 'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeVigentes($query)
    {
        return $query->where('fecha_inicio', '>=', now()->toDateString())
                     ->where('activo', true);
    }

    public function scopeDelAnio($query, int $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeParaRol($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('aplica_a', 'todos')
              ->orWhere('aplica_a', $rol);
        });
    }

    public function getEsPuntualAttribute(): bool
    {
        return is_null($this->fecha_fin);
    }

    public function getDiasRestantesAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->fecha_inicio, false));
    }

    public static function tiposLabels(): array
    {
        return [
            'entrega_notas'  => 'Entrega de Notas',
            'examen'         => 'Examen',
            'suspension'     => 'Suspensión',
            'inicio_periodo' => 'Inicio de Período',
            'fin_periodo'    => 'Fin de Período',
            'actividad'      => 'Actividad',
            'feriado'        => 'Feriado',
            'reunion'        => 'Reunión',
            'otro'           => 'Otro',
        ];
    }
}
