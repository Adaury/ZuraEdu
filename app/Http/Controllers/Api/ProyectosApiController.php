<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProyectoEscolar;
use App\Models\SchoolYear;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class ProyectosApiController extends Controller
{
    public function misProyectos(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->firstOrFail();
        $schoolYear = SchoolYear::actual();

        $proyectos = ProyectoEscolar::with(['tutor:id,name', 'fases'])
            ->whereHas('integrantes', fn($q) => $q->where('estudiante_id', $estudiante->id))
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProyectoEscolar $p) use ($estudiante) {
                $integrante = $p->integrantes->firstWhere('estudiante_id', $estudiante->id);
                return [
                    'id'           => $p->id,
                    'titulo'       => $p->titulo,
                    'area'         => $p->area ?? null,
                    'estado'       => $p->estado,
                    'estado_label' => $p->estado_label,
                    'estado_color' => $p->estado_color,
                    'fecha_inicio' => $p->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin'    => $p->fecha_fin?->format('Y-m-d'),
                    'tutor'        => $p->tutor?->name,
                    'rol'          => $integrante?->rol ?? 'integrante',
                    'fases_total'  => $p->fases->count(),
                    'fases_ok'     => $p->fases->where('completada', true)->count(),
                ];
            });

        return response()->json([
            'data'        => $proyectos,
            'school_year' => $schoolYear?->nombre,
        ]);
    }

    public function hijoProyectos(Request $request, Estudiante $estudiante)
    {
        $user           = $request->user();
        $representante  = \App\Models\Representante::where('user_id', $user->id)->firstOrFail();

        if (! $representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            abort(403);
        }

        $proyectos = ProyectoEscolar::with(['tutor:id,name', 'fases'])
            ->whereHas('integrantes', fn($q) => $q->where('estudiante_id', $estudiante->id))
            ->latest()
            ->get()
            ->map(fn(ProyectoEscolar $p) => [
                'id'           => $p->id,
                'titulo'       => $p->titulo,
                'area'         => $p->area ?? null,
                'estado'       => $p->estado,
                'estado_label' => $p->estado_label,
                'estado_color' => $p->estado_color,
                'fecha_inicio' => $p->fecha_inicio?->format('Y-m-d'),
                'fecha_fin'    => $p->fecha_fin?->format('Y-m-d'),
                'tutor'        => $p->tutor?->name,
                'fases_total'  => $p->fases->count(),
                'fases_ok'     => $p->fases->where('completada', true)->count(),
            ]);

        return response()->json([
            'estudiante' => ['id' => $estudiante->id, 'nombre' => $estudiante->nombre_completo],
            'data'       => $proyectos,
        ]);
    }
}
