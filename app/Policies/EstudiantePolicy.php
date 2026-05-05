<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\User;

/**
 * Controla el acceso a los datos de un estudiante concreto.
 */
class EstudiantePolicy
{
    /**
     * ¿Puede el usuario ver los datos del estudiante?
     *
     * - Roles administrativos con gestionar-estudiantes o ver-calificaciones: sí.
     * - Docente: solo si enseña en el grupo actual del estudiante o es su tutor.
     * - Representante/Padre: solo sus propios hijos (a través del portal, no del admin).
     */
    public function view(User $user, Estudiante $estudiante): bool
    {
        // Roles con gestión amplia siempre pueden
        if ($user->can('gestionar-estudiantes')) {
            return true;
        }

        if ($user->hasRole('Docente')) {
            $docente = $user->docente;
            if (! $docente) return false;

            // ¿El docente tiene alguna asignación en el grupo activo del estudiante?
            $grupoIds = $estudiante->matriculas()
                ->activas()
                ->pluck('grupo_id');

            $tieneAsignacion = \App\Models\Asignacion::whereIn('grupo_id', $grupoIds)
                ->where('docente_id', $docente->id)
                ->exists();

            if ($tieneAsignacion) return true;

            // ¿Es tutor de alguno de esos grupos?
            return \App\Models\Grupo::whereIn('id', $grupoIds)
                ->where('tutor_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * ¿Puede el usuario crear / editar un estudiante?
     */
    public function manage(User $user): bool
    {
        return $user->can('gestionar-estudiantes');
    }
}
