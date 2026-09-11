<?php

namespace App\Traits;

use App\Models\Matricula;

/**
 * `estudiantes.estado` debe reflejar si el estudiante tiene alguna
 * matrícula activa vigente. Antes de este fix convivían dos caminos de
 * "dar de baja" con comportamiento distinto: RegistroAcademicoController
 * sí sincronizaba (pero siempre a 'inactivo' sin comprobar si el
 * estudiante tenía otra matrícula activa en paralelo), y
 * MatriculaController::cambiarEstado()/destroy() no sincronizaba en
 * absoluto -- un estudiante podía quedar con su única matrícula
 * 'retirada' pero `estudiantes.estado` seguía en 'activo' (hallazgo de
 * auditoría contra el informe Don Bosco, sección "Consistencia de
 * registros": "estudiantes retirados que continúan activos").
 */
trait SincronizaEstadoEstudiante
{
    protected function sincronizarEstadoEstudiante(Matricula $matricula): void
    {
        $estudiante = $matricula->estudiante;
        if (! $estudiante) return;

        $tieneOtraMatriculaActiva = Matricula::where('estudiante_id', $estudiante->id)
            ->where('id', '!=', $matricula->id)
            ->where('estado', 'activa')
            ->exists();

        $activo = $matricula->estado === 'activa' || $tieneOtraMatriculaActiva;

        $estudiante->update(['estado' => $activo ? 'activo' : 'inactivo']);
    }
}
