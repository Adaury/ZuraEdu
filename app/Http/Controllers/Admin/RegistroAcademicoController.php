<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\PreMatricula;
use App\Models\SchoolYear;
use App\Models\Grupo;
use App\Models\Grado;
use Illuminate\Support\Facades\DB;

class RegistroAcademicoController extends Controller
{
    public function dashboard()
    {
        $schoolYear = SchoolYear::activo()->first();

        // ── KPIs ────────────────────────────────────────────────────────────
        $totalEstudiantes = Estudiante::where('estado', 'activo')->count();

        $matriculasActivas = Matricula::where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        $prePendientes = PreMatricula::pendientes()->count();

        $matriculasEsteMes = Matricula::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $nuevosEstudiantesEsteMes = Estudiante::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // ── Estudiantes recientes ────────────────────────────────────────────
        $estudiantesRecientes = Estudiante::with([
            'matriculas' => fn($q) => $q->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->with(['grupo.grado', 'grupo.seccion']),
        ])
        ->orderByDesc('created_at')
        ->limit(8)
        ->get();

        // ── Pre-matrículas pendientes ────────────────────────────────────────
        $preMatriculas = PreMatricula::pendientes()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // ── Distribución por grado ───────────────────────────────────────────
        $porGrado = Matricula::select('grados.nombre', DB::raw('count(*) as total'))
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->where('matriculas.estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('matriculas.school_year_id', $schoolYear->id))
            ->groupBy('grados.id', 'grados.nombre', 'grados.nivel')
            ->orderBy('grados.nivel')
            ->get();

        // ── Stats sin grupo (sin asignar) ────────────────────────────────────
        $sinGrupo = Estudiante::where('estado', 'activo')
            ->whereDoesntHave('matriculas', fn($q) => $q->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            )
            ->count();

        return view('admin.registro_academico.dashboard', compact(
            'schoolYear',
            'totalEstudiantes',
            'matriculasActivas',
            'prePendientes',
            'matriculasEsteMes',
            'nuevosEstudiantesEsteMes',
            'estudiantesRecientes',
            'preMatriculas',
            'porGrado',
            'sinGrupo'
        ));
    }
}
