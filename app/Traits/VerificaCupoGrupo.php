<?php

namespace App\Traits;

use App\Models\Grupo;
use App\Models\Matricula;
use Illuminate\Validation\ValidationException;

/**
 * Auditoría Don Bosco (Sección 2, "Concurrencia y rendimiento"):
 * grupos.capacidad existe (tinyint, default 35) y ya se usa para bloquear
 * en Transporte (EstudianteRuta), pero ningún punto de entrada de
 * matrícula lo comprobaba -- un grupo podía sobre-matricularse sin límite.
 */
trait VerificaCupoGrupo
{
    /**
     * Debe llamarse DENTRO de una transacción con el grupo ya bloqueado
     * (lockForUpdate) -- de lo contrario dos requests concurrentes podrían
     * pasar el chequeo antes de que cualquiera cree su matrícula, dejando
     * pasar una sobre-matrícula exactamente en el escenario de carrera que
     * este fix busca evitar.
     */
    protected function verificarCupoDisponible(Grupo $grupo, int $nuevasMatriculas = 1): void
    {
        $ocupados = Matricula::where('grupo_id', $grupo->id)->where('estado', 'activa')->count();

        if ($ocupados + $nuevasMatriculas > $grupo->capacidad) {
            $disponibles = max(0, $grupo->capacidad - $ocupados);
            throw ValidationException::withMessages([
                'grupo_id' => "El grupo {$grupo->nombre_completo} no tiene cupo suficiente: {$ocupados}/{$grupo->capacidad} ocupados, quedan {$disponibles} disponible(s).",
            ]);
        }
    }
}
