<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicRiskScore;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class RiesgoApiController extends Controller
{
    /** GET /api/v1/riesgo/mi-score — para el estudiante autenticado */
    public function miScore(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) {
            return response()->json(['message' => 'Solo para estudiantes.'], 403);
        }

        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) {
            return response()->json(['message' => 'Perfil no encontrado.'], 404);
        }

        return $this->datos($estudiante);
    }

    /** GET /api/v1/riesgo/hijo/{estudiante} — para el representante */
    public function hijoScore(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiantes.id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return $this->datos($estudiante);
    }

    private function datos(Estudiante $estudiante)
    {
        $sy    = SchoolYear::actual();
        $score = $sy
            ? AcademicRiskScore::where('estudiante_id', $estudiante->id)
                ->where('school_year_id', $sy->id)
                ->first()
            : null;

        if (! $score) {
            return response()->json([
                'calculado' => false,
                'estudiante' => $estudiante->nombre_completo,
            ]);
        }

        $cfg = $score->nivel_config;

        return response()->json([
            'calculado'        => true,
            'estudiante'       => $estudiante->nombre_completo,
            'score'            => $score->score,
            'nivel'            => $score->nivel,
            'nivel_label'      => $cfg['label'],
            'nivel_color'      => $cfg['color'],
            'dim_academico'    => round($score->dim_academico, 1),
            'dim_asistencia'   => round($score->dim_asistencia, 1),
            'dim_disciplina'   => round($score->dim_disciplina, 1),
            'dim_tendencia'    => round($score->dim_tendencia, 1),
            'materias_en_riesgo' => $score->materias_en_riesgo,
            'total_materias'   => $score->total_materias,
            'promedio_general' => $score->promedio_general,
            'pct_asistencia'   => $score->pct_asistencia,
            'tardanzas'        => $score->tardanzas,
            'faltas_leves'     => $score->faltas_leves,
            'faltas_graves'    => $score->faltas_graves,
            'suspensiones'     => $score->suspensiones,
            'calculado_en'     => $score->calculado_en,
        ]);
    }
}
