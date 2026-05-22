<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\EvaluacionDocente;
use Illuminate\Http\Request;

class EvaluacionesDocenteApiController extends Controller
{
    /** GET /api/v1/docente/mis-evaluaciones */
    public function misEvaluaciones(Request $request)
    {
        if (! $request->user()->hasRole('Docente')) {
            return response()->json(['message' => 'Solo para docentes.'], 403);
        }
        $docente = Docente::where('user_id', $request->user()->id)->first();
        if (! $docente) {
            return response()->json(['message' => 'Perfil de docente no encontrado.'], 404);
        }

        $evaluaciones = EvaluacionDocente::with('evaluador')
            ->where('docente_id', $docente->id)
            ->latest()
            ->get()
            ->map(function ($ev) {
                $nivel = $ev->nivelDesempeno();
                return [
                    'id'                  => $ev->id,
                    'periodo_evaluado'    => $ev->periodo_evaluado,
                    'promedio'            => round($ev->promedio_calculado, 2),
                    'nivel_label'         => $nivel['label'],
                    'nivel_color'         => $nivel['text'],
                    'nivel_bg'            => $nivel['color'],
                    'evaluador'           => $ev->evaluador?->name,
                    'fecha'               => $ev->created_at->format('d/m/Y'),
                    'observaciones'       => $ev->observaciones,
                    'criterios'           => collect($ev->criterios())->map(fn($c) => [
                        'key'   => $c['key'],
                        'label' => $c['label'],
                        'valor' => $ev->{$c['key']},
                    ])->values(),
                ];
            });

        $promedioGeneral = $evaluaciones->count()
            ? round($evaluaciones->avg('promedio'), 2)
            : null;

        return response()->json([
            'evaluaciones'     => $evaluaciones,
            'promedio_general' => $promedioGeneral,
            'total'            => $evaluaciones->count(),
        ]);
    }
}
