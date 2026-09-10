<?php

namespace App\Services;

use App\Helpers\Setting;
use App\Models\Notificacion;
use App\Models\User;

/**
 * Gating de Notificaciones Configurables (Fase 5, pieza 3). Todo estático,
 * sin caché propia: Setting::all() ya memoiza por tenant_id
 * (app/Helpers/Setting.php), así que las lecturas de institución no cuestan
 * queries adicionales tras la primera del request/job.
 */
class NotificacionPreferenciaService
{
    public static function categoria(string $tipo): string
    {
        return Notificacion::TIPO_CATEGORIA[$tipo] ?? 'sistema';
    }

    /** Toggle de institución para el canal in-app. 'sistema' nunca es apagable (ver Notificacion::CATEGORIAS). */
    public static function inAppActivo(string $tipo): bool
    {
        $categoria = static::categoria($tipo);

        if (! empty(Notificacion::CATEGORIAS[$categoria]['inapp_bloqueado'])) {
            return true;
        }

        return Setting::get("notif_inapp_{$categoria}", '1') !== '0';
    }

    /** Toggle de institución para push, por tipo. */
    public static function pushInstitucionActivo(string $tipo): bool
    {
        return static::pushInstitucionActivoCategoria(static::categoria($tipo));
    }

    /** Toggle de institución para push, directo por categoría (usado por la UI y por enviarA()). */
    public static function pushInstitucionActivoCategoria(string $categoria): bool
    {
        return Setting::get("notif_push_{$categoria}", '1') !== '0';
    }

    /**
     * Preferencia individual del usuario. Lee SOLO la columna (no hidrata
     * el modelo completo) -- Builder::value() salta los casts de Eloquent,
     * de ahí el json_decode manual. Olvidar esto hace que $prefs sea
     * siempre '[]' y el switch de usuario nunca funcione, sin fallar
     * ningún test que no lo verifique explícitamente.
     */
    public static function pushUsuarioActivo(int $userId, string $tipo): bool
    {
        $categoria = static::categoria($tipo);
        $raw       = User::withoutTenant()->where('id', $userId)->value('notif_push_prefs');
        $prefs     = is_array($raw) ? $raw : (json_decode((string) ($raw ?? ''), true) ?: []);

        return ($prefs[$categoria] ?? true) !== false;
    }

    /**
     * AND de institución y usuario. Cortocircuita antes de tocar `users` si
     * la institución ya apagó el canal para todos.
     */
    public static function pushActivo(int $userId, string $tipo): bool
    {
        return static::pushInstitucionActivo($tipo) && static::pushUsuarioActivo($userId, $tipo);
    }

    /**
     * Versión bulk para Notificacion::enviarA()/EnviarPushLoteJob: 1 sola
     * query para N destinatarios en vez de N queries individuales.
     *
     * @param  array<int>  $userIds
     * @return array<int>
     */
    public static function filtrarUsuariosConPush(array $userIds, string $tipo): array
    {
        if (empty($userIds) || ! static::pushInstitucionActivo($tipo)) {
            return [];
        }

        $categoria = static::categoria($tipo);

        // pluck() SÍ aplica el cast 'array' (a diferencia de value() arriba)
        // -- por eso el mismo is_array() defensivo en ambos métodos, para
        // que el código sea correcto sin depender de recordar cuál castea.
        return User::withoutTenant()
            ->whereIn('id', $userIds)
            ->pluck('notif_push_prefs', 'id')
            ->filter(function ($raw) use ($categoria) {
                $prefs = is_array($raw) ? $raw : (json_decode((string) ($raw ?? ''), true) ?: []);

                return ($prefs[$categoria] ?? true) !== false;
            })
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
