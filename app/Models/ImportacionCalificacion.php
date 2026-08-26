<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionCalificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'importaciones_calificaciones';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'asignacion_id',
        'periodo_id',
        'archivo_original',
        'archivo_path',
        'estado',
        'total_filas',
        'importados',
        'omitidos',
        'errores',
        'error_fatal',
        'iniciado_at',
        'completado_at',
    ];

    protected $casts = [
        'errores'       => 'array',
        'iniciado_at'   => 'datetime',
        'completado_at' => 'datetime',
    ];

    const ESTADOS = [
        'pendiente'  => 'En cola',
        'procesando' => 'Procesando',
        'completado' => 'Completado',
        'fallido'    => 'Falló',
    ];

    const COLORES_ESTADO = [
        'pendiente'  => 'bg-gray-100 text-gray-700',
        'procesando' => 'bg-blue-100 text-blue-700',
        'completado' => 'bg-green-100 text-green-700',
        'fallido'    => 'bg-red-100 text-red-700',
    ];

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getColorEstadoAttribute(): string
    {
        return self::COLORES_ESTADO[$this->estado] ?? 'bg-gray-100 text-gray-700';
    }

    public function getEnCursoAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'procesando']);
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
