<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comunicado;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Planificacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $cacheKey = 't' . (tenant_id() ?? 0) . '_search_' . md5(strtolower($q));
        $results  = Cache::remember($cacheKey, 60, function () use ($q) {
        $results  = [];
        $like     = "%{$q}%";

        // ── Estudiantes ───────────────────────────────────────────────
        $estudiantes = Estudiante::where(function ($query) use ($like) {
            $query->where('nombres', 'like', $like)
                  ->orWhere('apellidos', 'like', $like)
                  ->orWhere('numero_matricula', 'like', $like)
                  ->orWhere('cedula', 'like', $like);
        })->limit(5)->get(['id', 'nombres', 'apellidos', 'numero_matricula']);

        foreach ($estudiantes as $e) {
            $results[] = [
                'grupo' => 'Estudiantes',
                'icon'  => 'bi-person-fill',
                'color' => '#1d4ed8',
                'label' => $e->apellidos . ', ' . $e->nombres,
                'sub'   => 'Matrícula: ' . $e->numero_matricula,
                'url'   => route('admin.estudiantes.show', $e->id),
            ];
        }

        // ── Docentes ──────────────────────────────────────────────────
        $docentes = Docente::where(function ($query) use ($like) {
            $query->where('nombres', 'like', $like)
                  ->orWhere('apellidos', 'like', $like)
                  ->orWhere('cedula', 'like', $like);
        })->limit(4)->get(['id', 'nombres', 'apellidos', 'especialidad']);

        foreach ($docentes as $d) {
            $results[] = [
                'grupo' => 'Docentes',
                'icon'  => 'bi-person-badge-fill',
                'color' => '#047857',
                'label' => $d->apellidos . ', ' . $d->nombres,
                'sub'   => $d->especialidad ?? 'Docente',
                'url'   => route('admin.docentes.show', $d->id),
            ];
        }

        // ── Grupos ────────────────────────────────────────────────────
        $schoolYearId = SchoolYear::actual()?->id;
        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYearId, fn($q) => $q->where('school_year_id', $schoolYearId))
            ->whereHas('grado', fn($q) => $q->where('nombre', 'like', $like))
            ->orWhereHas('seccion', fn($q) => $q->where('nombre', 'like', $like))
            ->limit(3)->get();

        // Also search by "1ro A" style
        if ($grupos->isEmpty()) {
            $grupos = Grupo::with(['grado', 'seccion'])
                ->when($schoolYearId, fn($q) => $q->where('school_year_id', $schoolYearId))
                ->get()
                ->filter(fn($g) => str_contains(
                    strtolower($g->nombre_completo . ' ' . $g->nombre_corto), strtolower($q)
                ))
                ->take(3);
        }

        foreach ($grupos as $g) {
            $results[] = [
                'grupo' => 'Grupos',
                'icon'  => 'bi-grid-3x3-gap-fill',
                'color' => '#7c3aed',
                'label' => $g->nombre_completo,
                'sub'   => 'Grupo · ' . ($g->aula ?? 'Sin aula'),
                'url'   => route('admin.grupos.show', $g->id),
            ];
        }

        // ── Planificaciones ───────────────────────────────────────────
        $planificaciones = Planificacion::with(['asignacion.asignatura', 'asignacion.docente'])
            ->where(function ($query) use ($like) {
                $query->where('modulo_nombre', 'like', $like)
                      ->orWhere('mf_codigo', 'like', $like)
                      ->orWhere('familia_profesional', 'like', $like);
            })
            ->when($schoolYearId, fn($q) => $q->whereHas('asignacion',
                fn($s) => $s->where('school_year_id', $schoolYearId)
            ))
            ->limit(3)->get();

        foreach ($planificaciones as $p) {
            $results[] = [
                'grupo' => 'Planificaciones',
                'icon'  => 'bi-journal-text',
                'color' => '#7c3aed',
                'label' => $p->modulo_nombre ?? $p->asignacion?->asignatura?->nombre ?? 'Planificación',
                'sub'   => ($p->mf_codigo ? $p->mf_codigo . ' · ' : '') . ($p->asignacion?->docente?->nombre_completo ?? ''),
                'url'   => route('admin.planificacion.show', $p->id),
            ];
        }

        // ── Comunicados ───────────────────────────────────────────────
        $comunicados = Comunicado::where('titulo', 'like', $like)
            ->orWhere('cuerpo', 'like', $like)
            ->limit(3)->get(['id', 'titulo', 'published_at']);

        foreach ($comunicados as $c) {
            $results[] = [
                'grupo' => 'Comunicados',
                'icon'  => 'bi-megaphone-fill',
                'color' => '#2563eb',
                'label' => $c->titulo,
                'sub'   => 'Comunicado · ' . ($c->published_at?->format('d/m/Y') ?? ''),
                'url'   => route('admin.comunicados.index'),
            ];
        }

        // ── Pagos (por concepto o estudiante) ─────────────────────────
        if (\App\Models\ConfigInstitucional::moduloActivo('pagos')) {
            $pagos = Pago::with(['matricula.estudiante', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
                ->where(function ($query) use ($like) {
                    $query->where('concepto', 'like', $like)
                          ->orWhere('referencia', 'like', $like)
                          ->orWhereHas('matricula.estudiante', fn($e) =>
                              $e->where('nombres', 'like', $like)
                                ->orWhere('apellidos', 'like', $like)
                          );
                })
                ->whereHas('matricula', fn($m) => $m->where('school_year_id', $schoolYearId))
                ->limit(4)->get();

            foreach ($pagos as $p) {
                $est = $p->matricula?->estudiante;
                $results[] = [
                    'grupo' => 'Pagos',
                    'icon'  => 'bi-cash-coin',
                    'color' => '#0f766e',
                    'label' => $p->concepto . ' — ' . ($est ? ($est->apellidos . ', ' . $est->nombres) : ''),
                    'sub'   => 'RD$ ' . number_format($p->monto, 2) . ' · ' . ucfirst($p->estado),
                    'url'   => $p->matricula ? route('admin.pagos.por-estudiante', $p->matricula) : route('admin.pagos.index'),
                ];
            }
        }

        return $results;
        }); // end Cache::remember

        return response()->json(['results' => $results]);
    }
}
