<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\EspecialidadTecnica;
use App\Models\Grado;
use App\Models\SchoolYear;

class AreaController extends Controller
{
    public function index()
    {
        $areas         = Area::withCount('asignaturas')->orderBy('tipo')->orderBy('nombre')->get();
        $especialidades = EspecialidadTecnica::activas()->orderBy('orden')->get();
        return view('admin.areas.index', compact('areas', 'especialidades'));
    }

    public function academica()
    {
        $schoolYear = SchoolYear::actual();

        // Primer ciclo: grados nivel 1-3
        $docentesPrimerCiclo = Docente::whereIn('area', ['academica', 'ambas'])
            ->where('estado', 'activo')
            ->with(['asignaciones' => function ($q) use ($schoolYear) {
                if ($schoolYear) {
                    $q->where('school_year_id', $schoolYear->id)
                      ->where('activo', true)
                      ->where('area', 'academica')
                      ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [1, 3]))
                      ->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                }
            }])
            ->orderBy('apellidos')
            ->get()
            ->filter(fn($d) => $d->asignaciones->isNotEmpty());

        // Segundo ciclo: grados nivel 4-6
        $docentesSegundoCiclo = Docente::whereIn('area', ['academica', 'ambas'])
            ->where('estado', 'activo')
            ->with(['asignaciones' => function ($q) use ($schoolYear) {
                if ($schoolYear) {
                    $q->where('school_year_id', $schoolYear->id)
                      ->where('activo', true)
                      ->where('area', 'academica')
                      ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [4, 6]))
                      ->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                }
            }])
            ->orderBy('apellidos')
            ->get()
            ->filter(fn($d) => $d->asignaciones->isNotEmpty());

        // Materias académicas agrupadas por área
        $areasConMaterias = Area::where('tipo', '!=', 'tecnica')
            ->with(['asignaturas' => fn($q) => $q->where('activo', true)->orderBy('nombre')])
            ->withCount(['asignaturas' => fn($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->get()
            ->filter(fn($a) => $a->asignaturas->isNotEmpty());

        return view('admin.areas.academica', compact(
            'docentesPrimerCiclo',
            'docentesSegundoCiclo',
            'schoolYear',
            'areasConMaterias'
        ));
    }

    public function tecnica()
    {
        $schoolYear     = SchoolYear::actual();

        // Área técnica: SOLO Segundo Ciclo (grados 4to–6to de secundaria)
        $especialidades = EspecialidadTecnica::activas()
            ->orderBy('orden')
            ->with(['coordinador', 'docentes' => function ($q) use ($schoolYear) {
                $q->where('docentes.estado', 'activo')
                  ->with(['asignaciones' => function ($a) use ($schoolYear) {
                      if ($schoolYear) {
                          $a->where('school_year_id', $schoolYear->id)
                            ->where('activo', true)
                            ->where('area', 'tecnica')
                            ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [4, 6]))
                            ->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                      }
                  }]);
            }])
            ->get();

        // Docentes técnicos sin especialidad (también solo Segundo Ciclo)
        $docentesSinEspecialidad = Docente::whereIn('area', ['tecnica', 'ambas'])
            ->where('estado', 'activo')
            ->whereDoesntHave('especialidades')
            ->with(['asignaciones' => function ($q) use ($schoolYear) {
                if ($schoolYear) {
                    $q->where('school_year_id', $schoolYear->id)
                      ->where('activo', true)
                      ->where('area', 'tecnica')
                      ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [4, 6]))
                      ->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                }
            }])
            ->orderBy('apellidos')
            ->get();

        // Materias técnicas agrupadas por área
        $areasConMaterias = Area::where('tipo', '!=', 'academica')
            ->with(['asignaturas' => fn($q) => $q->where('activo', true)->orderBy('nombre')])
            ->withCount(['asignaturas' => fn($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->get()
            ->filter(fn($a) => $a->asignaturas->isNotEmpty());

        // Todas las materias técnicas (para la tabla general)
        $materiasTecnicas = Asignatura::where('area', 'tecnica')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.areas.tecnica', compact(
            'especialidades',
            'docentesSinEspecialidad',
            'schoolYear',
            'areasConMaterias',
            'materiasTecnicas'
        ));
    }
}
