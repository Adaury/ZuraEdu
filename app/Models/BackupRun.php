<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historial de corridas de backup (manual o programado). No usa
 * BelongsToTenant a propósito: el backup cubre la BD compartida completa,
 * no un tenant individual.
 */
class BackupRun extends Model
{
    protected $fillable = [
        'iniciado_en',
        'finalizado_en',
        'duracion_segundos',
        'estado',
        'etapa_fallo',
        'error_mensaje',
        'bd_archivo',
        'bd_tamano_bytes',
        'archivos_archivo',
        'archivos_tamano_bytes',
        'eliminados_retencion',
    ];

    protected $casts = [
        'iniciado_en'   => 'datetime',
        'finalizado_en' => 'datetime',
    ];

    public static function ultimoExitoso(): ?self
    {
        return static::where('estado', 'exitoso')
            ->latest('iniciado_en')
            ->first();
    }
}
