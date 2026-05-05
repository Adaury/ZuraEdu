<?php

namespace App\Services;

use App\Models\AlertaSistema;
use App\Models\Asignacion;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\EspecialidadTecnica;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AcademicAlertService
{
    // Valores por defecto cuando no están configurados en la BD
    const DEFAULT_NOTA_MINIMA       = 60;
    const DEFAULT_ASISTENCIA_MINIMA = 75;

    private function notaMinima(): float
    {
        return (float) ConfigInstitucional::get('alerta_nota_minima', self::DEFAULT_NOTA_MINIMA);
    }

    private function asistenciaMinima(): float
    {
        return (float) ConfigInstitucional::get('alerta_asistencia_minima', self::DEFAULT_ASISTENCIA_MINIMA);
    }

    /**
     * Evalúa todos los estudiantes activos y genera alertas de baja académica.
     * Llama desde un Artisan command o desde el dashboard del admin.
     */
    public function evaluarTodos(?int $schoolYearId = null): array
    {
        $schoolYear = $schoolYearId
            ? SchoolYear::find($schoolYearId)
            : SchoolYear::actual();

        if (! $schoolYear) {
            return ['error' => 'No hay año escolar activo.'];
        }

        $generadas  = 0;
        $omitidas   = 0;

        // All active matriculas for this school year
        $matriculas = Matricula::with([
            'estudiante',
            'grupo.grado',
            'grupo.seccion',
            'grupo.tutor',
            'calificaciones.asignacion.docente',
            'calificaciones.asignacion.asignatura',
        ])
        ->where('school_year_id', $schoolYear->id)
        ->where('estado', 'activa')
        ->get();

        foreach ($matriculas as $matricula) {
            $result = $this->evaluarMatricula($matricula, $schoolYear->id);
            $generadas += $result['generadas'];
            $omitidas  += $result['omitidas'];
        }

        return compact('generadas', 'omitidas');
    }

    /**
     * Evalúa una matrícula específica y genera alertas si corresponde.
     */
    public function evaluarMatricula(Matricula $matricula, int $schoolYearId): array
    {
        $generadas = 0;
        $omitidas  = 0;

        $estudiante = $matricula->estudiante;
        $grupo      = $matricula->grupo;

        if (! $estudiante || ! $grupo) {
            return compact('generadas', 'omitidas');
        }

        // Get calificaciones grouped by asignacion
        $calificaciones = $matricula->calificaciones()
            ->with(['asignacion.docente', 'asignacion.asignatura', 'periodo'])
            ->get();

        foreach ($calificaciones as $cal) {
            $asignacion = $cal->asignacion;
            if (! $asignacion) continue;

            // Check nota
            $nota = $cal->nota_final ?? $cal->nota_completiva ?? null;
            if ($nota !== null && (float) $nota < $this->notaMinima()) {
                $creada = $this->crearAlertaBajaAcademica(
                    $matricula, $asignacion, (float) $nota, $schoolYearId
                );
                $creada ? $generadas++ : $omitidas++;
            }

            // Check asistencia
            $pctAsistencia = $this->calcularPorcentajeAsistencia($matricula->id, $asignacion->id);
            if ($pctAsistencia !== null && $pctAsistencia < $this->asistenciaMinima()) {
                $creada = $this->crearAlertaBajaAsistencia(
                    $matricula, $asignacion, $pctAsistencia, $schoolYearId
                );
                $creada ? $generadas++ : $omitidas++;
            }
        }

        return compact('generadas', 'omitidas');
    }

    private function crearAlertaBajaAcademica(
        Matricula $matricula,
        Asignacion $asignacion,
        float $nota,
        int $schoolYearId
    ): bool {
        $estudiante = $matricula->estudiante;
        $asignatura = $asignacion->asignatura;
        $grupo      = $matricula->grupo;

        $titulo  = "Baja académica: {$estudiante->apellidos}, {$estudiante->nombres}";
        $mensaje = "El estudiante {$estudiante->apellidos}, {$estudiante->nombres} "
            . "tiene una nota de {$nota} en {$asignatura->nombre} "
            . "({$grupo->nombre_completo}). Requiere atención.";

        $ref = "matricula_{$matricula->id}_asignacion_{$asignacion->id}_baja_academica";

        return $this->notificarDestinatarios(
            $titulo, $mensaje, 'riesgo_academico', 'danger',
            $matricula, $asignacion, $ref, $schoolYearId
        );
    }

    private function crearAlertaBajaAsistencia(
        Matricula $matricula,
        Asignacion $asignacion,
        float $pct,
        int $schoolYearId
    ): bool {
        $estudiante = $matricula->estudiante;
        $asignatura = $asignacion->asignatura;
        $grupo      = $matricula->grupo;

        $titulo  = "Baja asistencia: {$estudiante->apellidos}, {$estudiante->nombres}";
        $mensaje = "El estudiante {$estudiante->apellidos}, {$estudiante->nombres} "
            . "tiene " . round($pct) . "% de asistencia en {$asignatura->nombre} "
            . "({$grupo->nombre_completo}). Mínimo requerido: " . round($this->asistenciaMinima()) . "%.";

        $ref = "matricula_{$matricula->id}_asignacion_{$asignacion->id}_baja_asistencia";

        return $this->notificarDestinatarios(
            $titulo, $mensaje, 'baja_asistencia', 'warning',
            $matricula, $asignacion, $ref, $schoolYearId
        );
    }

    /**
     * Crea AlertaSistema para todos los destinatarios relevantes.
     */
    private function notificarDestinatarios(
        string $titulo, string $mensaje, string $tipo, string $nivel,
        Matricula $matricula, Asignacion $asignacion,
        string $referencia, int $schoolYearId
    ): bool {
        $grupo         = $matricula->grupo;
        $destinatarios = collect();

        // 1. Maestro guía del grupo (tutor_id is already a user_id on grupos)
        if ($grupo->tutor_id) {
            $destinatarios->push(['user_id' => $grupo->tutor_id]);
        }

        // 2. Docente que imparte la materia (Docente→user_id)
        if ($asignacion->docente?->user_id) {
            $destinatarios->push(['user_id' => $asignacion->docente->user_id]);
        }

        // 3. Coordinador del área técnica (si aplica)
        // EspecialidadTecnica.coordinador() → Docente, Docente.user() → User
        if ($asignacion->area === 'tecnica' && $asignacion->docente_id) {
            $coordinadorUser = EspecialidadTecnica::query()
                ->whereHas('docentes', fn ($q) => $q->where('docentes.id', $asignacion->docente_id))
                ->with('coordinador.user')
                ->first()
                ?->coordinador
                ?->user;

            if ($coordinadorUser) {
                $destinatarios->push(['user_id' => $coordinadorUser->id]);
            }
        }

        // 4. Director / Admin (Spatie roles)
        $admins = User::role(['Admin', 'Director'])->get();
        foreach ($admins as $admin) {
            $destinatarios->push(['user_id' => $admin->id]);
        }

        // Remove duplicates and empty entries
        $destinatarios = $destinatarios
            ->unique('user_id')
            ->filter(fn ($d) => !empty($d['user_id']));

        if ($destinatarios->isEmpty()) {
            return false;
        }

        $creada = false;
        foreach ($destinatarios as $dest) {
            // Avoid duplicate alerts (same matricula + tipo + destinatario + day)
            $existe = AlertaSistema::where('referencia_tipo', 'alerta_academica')
                ->where('referencia_id', $matricula->id)
                ->where('destinatario_id', $dest['user_id'])
                ->where('tipo', $tipo)
                ->whereDate('created_at', today())
                ->exists();

            if (! $existe) {
                AlertaSistema::create([
                    'tipo'             => $tipo,
                    'titulo'           => $titulo,
                    'mensaje'          => $mensaje,
                    'nivel'            => $nivel,
                    'destinatario_id'  => $dest['user_id'],
                    'destinatario_rol' => null,
                    'referencia_tipo'  => 'alerta_academica',
                    'referencia_id'    => $matricula->id,
                    'leida'            => false,
                    'school_year_id'   => $schoolYearId,
                    'creado_por'       => null,
                ]);
                $creada = true;
            }
        }

        return $creada;
    }

    private function calcularPorcentajeAsistencia(int $matriculaId, int $asignacionId): ?float
    {
        $total = \App\Models\Asistencia::where('matricula_id', $matriculaId)
            ->where('asignacion_id', $asignacionId)
            ->count();

        if ($total === 0) return null;

        $presentes = \App\Models\Asistencia::where('matricula_id', $matriculaId)
            ->where('asignacion_id', $asignacionId)
            ->whereIn('estado', ['presente', 'tardanza'])
            ->count();

        return ($presentes / $total) * 100;
    }
}
