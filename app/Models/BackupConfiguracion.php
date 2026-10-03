<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuración del respaldo automático de la plataforma (una sola fila, sin tenant: el respaldo cubre la base compartida completa).
 * Si todavía no se guardó nada desde la pantalla, actual() devuelve los valores de config/backup.php SIN crear la fila.
 */
class BackupConfiguracion extends Model
{
    protected $table = 'backup_configuracion';

    protected $fillable = [
        'activo', 'frecuencia', 'dia_semana', 'hora', 'zona_horaria', 'retencion_dias', 'incluir_archivos',
        'carpeta_local_activa', 'carpeta_local_ruta',
        'drive_activo', 'drive_client_id', 'drive_client_secret', 'drive_refresh_token', 'drive_cuenta',
        'drive_carpeta_nombre', 'drive_carpeta_id', 'drive_conectado_en',
    ];

    protected $hidden = ['drive_client_secret', 'drive_refresh_token'];

    protected $casts = [
        'activo'               => 'boolean',
        'incluir_archivos'     => 'boolean',
        'carpeta_local_activa' => 'boolean',
        'drive_activo'         => 'boolean',
        'dia_semana'           => 'integer',
        'retencion_dias'       => 'integer',
        'drive_client_secret'  => 'encrypted',
        'drive_refresh_token'  => 'encrypted',
        'drive_conectado_en'   => 'datetime',
    ];

    public const DIAS = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    public static function actual(): self
    {
        return static::query()->first() ?? new static([
            'activo'           => (bool) config('backup.enabled', true),
            'frecuencia'       => 'diaria',
            'dia_semana'       => 0,
            'hora'             => config('backup.hora', '02:30'),
            'zona_horaria'     => config('backup.zona_horaria', 'America/Santo_Domingo'),
            'retencion_dias'   => (int) config('backup.retencion_dias', 7),
            'incluir_archivos' => (bool) config('backup.incluir_archivos', true),
            'drive_carpeta_nombre' => 'ZuraEdu Respaldos',
        ]);
    }

    public function driveConectado(): bool
    {
        return filled($this->drive_refresh_token);
    }

    /** Próxima ejecución programada (en la zona horaria elegida), o null si el respaldo está desactivado. */
    public function proximaEjecucion(?Carbon $desde = null): ?Carbon
    {
        if (! $this->activo || ! config('backup.enabled', true)) {
            return null;
        }

        [$h, $m] = array_map('intval', explode(':', $this->hora ?: '02:30') + [0, 0]);
        $ahora = ($desde ?? now())->copy()->timezone($this->zona_horaria ?: 'UTC');
        $siguiente = $ahora->copy()->setTime($h, $m, 0);

        if ($this->frecuencia === 'semanal') {
            $siguiente = $siguiente->copy()->next($this->dia_semana)->setTime($h, $m, 0);
            if ($ahora->dayOfWeek === (int) $this->dia_semana && $ahora->lt($ahora->copy()->setTime($h, $m, 0))) {
                $siguiente = $ahora->copy()->setTime($h, $m, 0);
            }

            return $siguiente;
        }

        return $siguiente->lte($ahora) ? $siguiente->addDay() : $siguiente;
    }
}
