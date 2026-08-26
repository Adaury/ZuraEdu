<?php

namespace App\Policies;

use App\Models\Matricula;
use App\Models\User;

/**
 * Policy sobre Boletín (accedido vía Matricula).
 * Docentes solo ven boletines de los grupos donde tienen asignaciones activas o son tutores.
 * Admin/Director tienen acceso total.
 */
class BoletinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver-boletines');
    }

    /**
     * ¿Puede ver el boletín de esta matrícula?
     */
    public function ver(User $user, Matricula $matricula): bool
    {
        if (! $user->can('ver-boletines')) {
            return false;
        }

        if ($user->tieneRolDocente()) {
            $docente = $user->docente;
            if (! $docente) return false;

            $grupo = $matricula->grupo;
            if (! $grupo) return false;

            // Es tutor del grupo
            if ($grupo->tutor_id === $user->id) return true;

            // Tiene asignación activa en el grupo
            return $docente->asignaciones()
                ->where('grupo_id', $grupo->id)
                ->where('activo', true)
                ->exists();
        }

        // Cualquier otro rol con ver-boletines (Administrador, Director, Coordinadores,
        // Secretaría, Registrador Académico, Encargado de Área, Personal Administrativo...)
        // ve sin restricción adicional por estudiante — el permiso de módulo ya filtra
        // quién llega aquí. Antes esto solo cubría 'Administrador'/'Director' hardcodeados
        // y bloqueaba con 403 a todos los demás roles con el permiso (regresión al activar
        // esta Policy, ver docs/AUDITORIA_DON_BOSCO_ZURAEDU.md).
        return true;
    }

    /**
     * ¿Puede descargar el PDF del boletín?
     */
    public function pdf(User $user, Matricula $matricula): bool
    {
        return $this->ver($user, $matricula);
    }
}
