<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Services\ZuraPlanificacionAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanificacionAIController extends Controller
{
    public function __construct(private ZuraPlanificacionAI $ai) {}

    public function generarRA(Request $request, Asignacion $asignacion): JsonResponse
    {
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

    public function mejorarTexto(Request $request, Asignacion $asignacion): JsonResponse
    {
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
