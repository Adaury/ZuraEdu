<?php

namespace App\Policies;

use App\Models\Grupo;
use App\Models\User;

/**
 * Policy sobre Grupo.
 * Docentes solo pueden ver los grupos donde tienen asignaciones activas o son tutores.
 * Admin/Director tienen acceso total.
 */
class GrupoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gestionar-grupos') || $user->hasRole(['Admin', 'Director', 'Docente']);
    }

    public function view(User $user, Grupo $grupo): bool
    {
        if ($user->hasRole(['Admin', 'Director'])) {
            return true;
        }

        if ($user->hasRole('Docente')) {
            $docente = $user->docente;
            if (! $docente) return false;

            // Es tutor del grupo
            if ($grupo->tutor_id === $user->id) return true;

            // Tiene asignación activa en el grupo
            return $docente->asignaciones()
                ->where('grupo_id', $grupo->id)
                ->where('activo', true)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar-grupos');
    }

    public function update(User $user, Grupo $grupo): bool
    {
        return $user->can('gestionar-grupos');
    }

    public function delete(User $user, Grupo $grupo): bool
    {
        return $user->can('gestionar-grupos');
    }

    public function asignarTutor(User $user, Grupo $grupo): bool
    {
        return $user->can('gestionar-grupos');
    }
}
