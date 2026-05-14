<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Estudiante;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Asistencia;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\Planificacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;

class PortalRepresentanteController extends Controller
{
    /**
     * Muestra el portal del representante.
     * Acceso via URL firmada — sin login requerido.
     */
    public function show(Request $request, Estudiante $estudiante)
    {
        $schoolYear = SchoolYear::actual();

        // Matrícula activa del año escolar actual
        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
            ->latest()
            ->first();

        // Periodos del año escolar
        $periodos = $this->getPeriodos($schoolYear);

        // Calificaciones técnicas publicadas (área técnica — por período)
        $calificaciones = collect();
        if ($matricula) {
            $calificaciones = Calificacion::with(['asignacion.asignatura', 'periodo'])
                ->where('matricula_id', $matricula->id)
                ->where('publicado', true)
                ->get()
                ->groupBy('periodo_id');
        }

        // Calificaciones académicas publicadas (área académica — full-year)
        $calificacionesAcademicas = collect();
        if ($matricula && $schoolYear) {
            $calificacionesAcademicas = CalificacionAcademica::with(['asignacion.asignatura'])
                ->where('matricula_id', $matricula->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('publicado', true)
                ->get();
        }

        // Asistencias por período
        $asistencias = collect();
        if ($matricula) {
            $rawAsistencias = Asistencia::with('asignacion.asignatura')
                ->where('matricula_id', $matricula->id)
                ->orderBy('fecha', 'desc')
                ->get();

            // Resumen por asignación
            $asistencias = $rawAsistencias
                ->groupBy('asignacion_id')
                ->map(function ($rows) {
                    $total    = $rows->count();
                    $presentes= $rows->whereIn('estado', ['presente', 'tardanza'])->count();
                    $ausentes = $rows->where('estado', 'ausente')->count();
                    $pct      = $total > 0 ? round($presentes / $total * 100, 1) : null;
                    return [
                        'asignatura' => $rows->first()->asignacion?->asignatura?->nombre ?? '—',
                        'total'      => $total,
                        'presentes'  => $presentes,
                        'ausentes'   => $ausentes,
                        'porcentaje' => $pct,
                    ];
                })
                ->values();
        }

        // Resumen de promedio general por período
        $promediosPorPeriodo = [];
        foreach ($periodos as $p) {
            $cals = $calificaciones->get($p->id, collect());
            if ($cals->count()) {
                $promediosPorPeriodo[$p->id] = round($cals->avg('nota_final'), 1);
            }
        }

        // Horario del grupo (si hay uno publicado)
        $gridHorario   = [];
        $franjasHorario = collect();
        $horarioActivo = null;
        if ($matricula && $schoolYear) {
            $horarioActivo = Horario::where('school_year_id', $schoolYear->id)
                ->where('estado', 'publicado')
                ->latest()
                ->first();
            if ($horarioActivo) {
                $detallesHorario = HorarioDetalle::with(['asignacion.asignatura', 'asignacion.docente', 'franja', 'aula'])
                    ->where('horario_id', $horarioActivo->id)
                    ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $matricula->grupo_id))
                    ->get();
                $franjasHorario = FranjaHoraria::where('activa', true)->orderBy('numero')->get();
                foreach ($detallesHorario as $d) {
                    $gridHorario[$d->franja_id][$d->dia] = $d;
                }
            }
        }

        // Planificaciones técnicas publicadas del grupo
        $planificaciones = collect();
        if ($matricula && $schoolYear) {
            $asignacionIds = Asignacion::where('grupo_id', $matricula->grupo_id)
                ->where('school_year_id', $schoolYear->id)
                ->where('area', 'tecnica')
                ->pluck('id');

            if ($asignacionIds->isNotEmpty()) {
                $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente', 'raItems', 'actividades'])
                    ->whereIn('asignacion_id', $asignacionIds)
                    ->where('publicado', true)
                    ->latest()
                    ->get()
                    ->groupBy('asignacion_id');
            }
        }

        // Observaciones públicas del estudiante
        $observaciones = collect();
        if ($matricula) {
            $observaciones = Observacion::with(['docente', 'asignacion.asignatura'])
                ->where('estudiante_id', $estudiante->id)
                ->where('privada', false)
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('portal.representante', compact(
            'estudiante',
            'matricula',
            'schoolYear',
            'periodos',
            'calificaciones',
            'calificacionesAcademicas',
            'asistencias',
            'promediosPorPeriodo',
            'gridHorario',
            'franjasHorario',
            'horarioActivo',
            'planificaciones',
            'observaciones'
        ));
    }

    /**
     * Genera un enlace firmado para el portal del representante.
     * Solo accesible desde el panel admin (al ver ficha del estudiante).
     */
    public static function generarEnlace(Estudiante $estudiante): string
    {
        return URL::signedRoute(
            'portal.representante',
            ['estudiante' => $estudiante->id],
            now()->addDays(30)
        );
    }
}
