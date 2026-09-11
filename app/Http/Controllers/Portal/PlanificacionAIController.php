<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Services\ZuraPlanificacionAI;
use App\Traits\HasDocenteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanificacionAIController extends Controller
{
    use HasDocenteContext;

    public function __construct(private ZuraPlanificacionAI $ai) {}

    public function generarRA(Request $request, Asignacion $asignacion): JsonResponse
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'ra_hint'          => 'nullable|string|max:500',
            'nivel_taxonomico' => 'nullable|string|max:100',
            'contexto'         => 'nullable|string|max:500',
            'ra_codigo'        => 'nullable|string|max:20',
        ]);

        $data = $this->ai->generarRA([
            'asignatura'         => $asignacion->asignatura?->nombre ?? 'Informática',
            'grupo'              => $asignacion->grupo?->nombre_completo ?? '',
            'familia_profesional'=> $request->input('familia_profesional', 'Informática y Comunicaciones'),
            'modulo'             => $request->input('modulo', $asignacion->asignatura?->nombre ?? ''),
            'ra_codigo'          => $request->input('ra_codigo', 'RA'),
            'ra_hint'            => $request->input('ra_hint', ''),
            'nivel_taxonomico'   => $request->input('nivel_taxonomico', 'Aplicación'),
            'contexto'           => $request->input('contexto', ''),
        ]);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 422);
        }

        return response()->json($data);
    }

    public function generarActividad(Request $request, Asignacion $asignacion): JsonResponse
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'objetivo_hint' => 'nullable|string|max:500',
            'ra_codigo'     => 'nullable|string|max:20',
            'contexto'      => 'nullable|string|max:500',
        ]);

        $data = $this->ai->generarActividad([
            'asignatura'    => $asignacion->asignatura?->nombre ?? 'Informática',
            'grupo'         => $asignacion->grupo?->nombre_completo ?? '',
            'ra_codigo'     => $request->input('ra_codigo', ''),
            'objetivo_hint' => $request->input('objetivo_hint', ''),
            'contexto'      => $request->input('contexto', ''),
        ]);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 422);
        }

        return response()->json($data);
    }

    /**
     * Genera contenido para una Unidad de PlanifAnual (línea académica).
     * Análogo a generarRA()/generarActividad() para la línea técnica RA/MF/UC.
     */
    public function generarUnidad(Request $request, Asignacion $asignacion, PlanifAnual $plan, PlanifUnidad $unidad): JsonResponse
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id
            || $plan->docente_id !== $docente->id
            || $unidad->planif_anual_id !== $plan->id) {
            abort(403);
        }

        $request->validate([
            'titulo_hint' => 'nullable|string|max:200',
            'contexto'    => 'nullable|string|max:500',
        ]);

        $asignacion->loadMissing(['asignatura', 'grupo.grado']);

        $data = $this->ai->generarUnidad([
            'asignatura'               => $asignacion->asignatura?->nombre ?? '',
            'grado'                    => $asignacion->grupo?->grado?->nombre ?? '',
            'ciclo'                    => $asignacion->grupo?->grado?->ciclo ?? 'segundo_ciclo',
            'numero'                   => $unidad->numero,
            'periodo'                  => $unidad->periodo ?? '',
            'titulo_hint'              => $request->input('titulo_hint', $unidad->titulo ?? ''),
            'contexto'                 => $request->input('contexto', ''),
            'competencias_disponibles' => PlanifUnidad::COMPETENCIAS,
        ]);

        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 422);
        }

        // Defensa contra alucinación: solo aceptar competencias de la lista real.
        if (isset($data['competencias']) && is_array($data['competencias'])) {
            $data['competencias'] = array_values(array_intersect($data['competencias'], PlanifUnidad::COMPETENCIAS));
        }

        return response()->json($data);
    }

    public function mejorarTexto(Request $request, Asignacion $asignacion): JsonResponse
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $request->validate([
            'campo'    => 'required|string|max:80',
            'texto'    => 'required|string|max:2000',
            'contexto' => 'nullable|string|max:300',
        ]);

        $mejorado = $this->ai->mejorarTexto(
            $request->input('campo'),
            $request->input('texto'),
            $request->input('contexto', $asignacion->asignatura?->nombre ?? '')
        );

        return response()->json(['texto' => $mejorado]);
    }
}
