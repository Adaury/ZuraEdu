<?php

namespace App\Services\Calendario;

use App\Jobs\NotificarEventoCalendarioJob;
use App\Models\CalendarioAcademico;
use App\Models\CalendarioDestinatario;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Decide A QUIÉN se avisa de un evento del calendario y lo encola (mensajería interna + notificación + correo con .ics).
 * Todo sale de `User`, que ya está aislado por tenant (BelongsToTenant): un ID de otro colegio recibido del navegador
 * simplemente no se encuentra, así que nunca se avisa ni se da acceso a alguien de otro tenant.
 */
class CalendarioNotificador
{
    /** Grupos que el administrador puede marcar → roles de Spatie. 'personal' = todo el que no sea ninguno de los otros tres. */
    public const GRUPOS = [
        'padres'      => 'Padres / representantes',
        'docentes'    => 'Docentes',
        'estudiantes' => 'Estudiantes',
        'personal'    => 'Personal administrativo y directivo',
    ];

    private const ROLES_GRUPO = [
        'padres'      => ['Representante'],
        'docentes'    => ['Docente'],
        'estudiantes' => ['Estudiante'],
    ];

    /**
     * @param  array<int,string>  $grupos    claves de self::GRUPOS
     * @param  array<int,int|string>  $userIds  personas elegidas una a una
     * @param  array<int,int|string>  $padresDeGrupos  IDs de grupos (aulas): se avisa a los representantes de sus estudiantes
     * @return Collection<int,User>
     */
    public function resolver(array $grupos, array $userIds, ?int $excluirUserId = null, array $padresDeGrupos = [], array $padresDeGrados = []): Collection
    {
        // "Padres de un grado completo" = padres de todos los grupos activos de ese grado en el año escolar activo.
        $padresDeGrupos = array_merge($padresDeGrupos, $this->gruposDeGrados($padresDeGrados));

        $grupos  = array_values(array_intersect($grupos, array_keys(self::GRUPOS)));
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        $base = fn () => User::query()->where('activo', true)
            ->when($excluirUserId, fn ($q) => $q->where('users.id', '!=', $excluirUserId));

        $porId = collect();

        foreach ($grupos as $g) {
            if ($g === 'personal') {
                $usuarios = $base()
                    ->whereHas('roles')
                    ->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', ['Representante', 'Docente', 'Estudiante', 'SuperAdmin']))
                    ->get();
            } else {
                $usuarios = $base()->role(self::ROLES_GRUPO[$g])->get();
            }
            $porId = $porId->union($usuarios->keyBy('id'));
        }

        if ($userIds) {
            $porId = $porId->union($base()->whereIn('users.id', $userIds)->get()->keyBy('id'));
        }

        $idsPadres = $this->usuariosDePadresDeGrupos($padresDeGrupos);
        if ($idsPadres) {
            $porId = $porId->union($base()->whereIn('users.id', $idsPadres)->get()->keyBy('id'));
        }

        return $porId->values();
    }

    /**
     * IDs de los grupos activos del año escolar activo que pertenecen a esos grados. Grupo y SchoolYear pasan por el scope de
     * tenant: un ID de grado de otro colegio no produce ningún grupo.
     *
     * @param  array<int,int|string>  $gradoIds
     * @return array<int,int>
     */
    public function gruposDeGrados(array $gradoIds): array
    {
        $gradoIds = array_values(array_unique(array_filter(array_map('intval', $gradoIds))));
        $anio     = $gradoIds ? SchoolYear::actual() : null;
        if (! $anio) {
            return [];
        }

        return Grupo::where('school_year_id', $anio->id)->where('activo', true)->whereIn('grado_id', $gradoIds)->pluck('id')->all();
    }

    /**
     * Cuentas de usuario de los representantes de los estudiantes matriculados (activos) en esos grupos.
     * La relación es la real (grupo → matrícula → estudiante → representante → usuario) y todo pasa por el scope de tenant:
     * un ID de grupo de otro colegio no devuelve nada. Un representante con varios hijos en los grupos elegidos sale una sola vez.
     *
     * @param  array<int,int|string>  $grupoIds
     * @return array<int,int>
     */
    public function usuariosDePadresDeGrupos(array $grupoIds): array
    {
        $grupoIds = array_values(array_unique(array_filter(array_map('intval', $grupoIds))));
        if (! $grupoIds) {
            return [];
        }

        $gruposValidos = Grupo::whereIn('id', $grupoIds)->pluck('id');
        if ($gruposValidos->isEmpty()) {
            return [];
        }

        $estudiantes = Matricula::whereIn('grupo_id', $gruposValidos)->where('estado', 'activa')->pluck('estudiante_id');
        if ($estudiantes->isEmpty()) {
            return [];
        }

        return Representante::whereHas('estudiantes', fn ($q) => $q->whereIn('estudiantes.id', $estudiantes))
            ->pluck('user_id')->unique()->values()->all();
    }

    /**
     * Registra los destinatarios y encola el envío. Devuelve cuántas personas se avisarán.
     * $reenviar = true (al editar un evento) vuelve a avisar también a quienes ya habían recibido el aviso.
     */
    public function notificar(CalendarioAcademico $evento, array $grupos, array $userIds, bool $reenviar = false, bool $actualizacion = false, array $padresDeGrupos = [], array $padresDeGrados = []): int
    {
        $usuarios = $this->resolver($grupos, $userIds, $evento->creado_por, $padresDeGrupos, $padresDeGrados);
        if ($usuarios->isEmpty()) {
            return 0;
        }

        $pendientes = [];
        foreach ($usuarios as $u) {
            $fila = CalendarioDestinatario::firstOrCreate(['calendario_id' => $evento->id, 'user_id' => $u->id]);
            if ($reenviar && $fila->notificado_at) {
                $fila->update(['notificado_at' => null, 'correo_enviado_at' => null]);
            }
            if (! $fila->notificado_at) {
                $pendientes[] = $u->id;
            }
        }

        foreach (array_chunk($pendientes, 50) as $lote) {
            NotificarEventoCalendarioJob::dispatch($evento->id, $lote, $actualizacion)->onQueue('notifications');
        }

        return count($pendientes);
    }
}
