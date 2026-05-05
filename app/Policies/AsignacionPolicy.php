<?php

namespace App\Policies;

use App\Models\Asignacion;
use App\Models\User;

/**
 * Policy sobre Asignacion: cubre permisos de calificaciones Y asistencia.
 *
 * El middleware can:ver-calificaciones etc. filtra por permiso global.
 * Esta policy añade la restricción por grupo específico para Docentes.
 */
class AsignacionPolicy
{
    // ─── Calificaciones ──────────────────────────────────────────────────────

    /**
     * ¿Puede ver las calificaciones de esta asignación?
     * Método usado como: $this->authorize('verCalificaciones', $asignacion)
     */
    public function verCalificaciones(User $user, Asignacion $asignacion): bool
    {
        if (! $user->can('ver-calificaciones')) {
            return false;
        }

        if ($user->hasRole('Docente')) {
            return $this->docentePuedeAcceder($user, $asignacion);
        }

        return true;
    }

    /**
     * ¿Puede ingresar / modificar calificaciones de esta asignación?
     */
    public function ingresarCalificaciones(User $user, Asignacion $asignacion): bool
    {
        if (! $user->can('ingresar-calificaciones')) {
            return false;
        }

        if ($user->hasRole('Docente')) {
            $docente = $user->docente;
            if (! $docente) return false;
            return $asignacion->docente_id === $docente->id;
        }

        return true;
    }

    // ─── Asistencia ───────────────────────────────────────────────────────────

    /**
     * ¿Puede ver la asistencia de esta asignación?
     */
    public function verAsistencia(User $user, Asignacion $asignacion): bool
    {
        if (! $user->can('ver-asistencia')) {
            return false;
        }

        if ($user->hasRole('Docente')) {
            return $this->docentePuedeAcceder($user, $asignacion);
        }

        return true;
    }

    /**
     * ¿Puede registrar / modificar asistencia de esta asignación?
     */
    public function ingresarAsistencia(User $user, Asignacion $asignacion): bool
    {
        if (! $user->can('ingresar-asistencia')) {
            return false;
        }

        if ($user->hasRole('Docente')) {
            $docente = $user->docente;
            if (! $docente) return false;
            return $asignacion->docente_id === $docente->id;
        }

        return true;
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Devuelve true si el docente tiene acceso a la asignación:
     *  a) es el docente asignado directamente, o
     *  b) es el docente guía (tutor) del grupo.
     */
    private function docentePuedeAcceder(User $user, Asignacion $asignacion): bool
    {
        $docente = $user->docente;
        if (! $docente) return false;

        if ($asignacion->docente_id === $docente->id) {
            return true;
        }

        // Tutor del grupo
        return $asignacion->grupo?->tutor_id === $user->id;
    }
}
