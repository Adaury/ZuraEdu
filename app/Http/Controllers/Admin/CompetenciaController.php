<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Asignatura, CompetenciaEspecifica, IndicadorLogro};
use Illuminate\Http\{Request, JsonResponse};

class CompetenciaController extends Controller
{
    // ── Índice admin: listar CE/IL por asignatura ─────────────────────────────

    public function index(Request $request)
    {
        $ciclo      = $request->get('ciclo', 'primer_ciclo');
        $asignatura = $request->get('asignatura_id')
            ? Asignatura::findOrFail($request->asignatura_id)
            : null;

        $asignaturas = Asignatura::where('activo', true)->orderBy('nombre')->get();

        $competencias = collect();
        if ($asignatura) {
            $competencias = CompetenciaEspecifica::with('indicadoresActivos')
                ->where('asignatura_id', $asignatura->id)
                ->where('ciclo', $ciclo)
                ->orderBy('orden')
                ->get();
        }

        return view('admin.competencias.index', compact(
            'asignaturas', 'competencias', 'asignatura', 'ciclo'
        ));
    }

    // ── CRUD Competencias ─────────────────────────────────────────────────────

    public function storeCompetencia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asignatura_id' => 'required|exists:asignaturas,id',
            'ciclo'         => 'required|in:primer_ciclo,segundo_ciclo',
            'codigo'        => 'required|string|max:10',
            'nombre'        => 'required|string|max:250',
            'descripcion'   => 'nullable|string|max:1000',
            'orden'         => 'integer|min:1',
        ]);

        // Auto-generar orden si no viene
        if (empty($data['orden'])) {
            $data['orden'] = CompetenciaEspecifica::where('asignatura_id', $data['asignatura_id'])
                ->where('ciclo', $data['ciclo'])->max('orden') + 1;
        }

        $ce = CompetenciaEspecifica::create($data);

        return response()->json(['ok' => true, 'ce' => $ce->load('indicadoresActivos')]);
    }

    public function updateCompetencia(Request $request, CompetenciaEspecifica $competencia): JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:250',
            'descripcion' => 'nullable|string|max:1000',
            'orden'       => 'integer|min:1',
            'activo'      => 'boolean',
        ]);

        $competencia->update($data);

        return response()->json(['ok' => true, 'ce' => $competencia]);
    }

    public function destroyCompetencia(CompetenciaEspecifica $competencia): JsonResponse
    {
        // Verificar que no tenga evaluaciones registradas
        if ($competencia->evaluaciones()->exists()) {
            return response()->json(['ok' => false, 'error' => 'Esta competencia ya tiene evaluaciones registradas y no puede eliminarse.'], 422);
        }

        $competencia->delete();
        return response()->json(['ok' => true]);
    }

    // ── CRUD Indicadores ──────────────────────────────────────────────────────

    public function storeIndicador(Request $request): JsonResponse
    {
        $data = $request->validate([
            'competencia_id' => 'required|exists:competencias_especificas,id',
            'codigo'         => 'required|string|max:10',
            'descripcion'    => 'required|string|max:1000',
            'orden'          => 'integer|min:1',
        ]);

        if (empty($data['orden'])) {
            $data['orden'] = IndicadorLogro::where('competencia_id', $data['competencia_id'])
                ->max('orden') + 1;
        }

        $il = IndicadorLogro::create($data);

        return response()->json(['ok' => true, 'il' => $il]);
    }

    public function updateIndicador(Request $request, IndicadorLogro $indicador): JsonResponse
    {
        $data = $request->validate([
            'descripcion' => 'required|string|max:1000',
            'orden'       => 'integer|min:1',
            'activo'      => 'boolean',
        ]);

        $indicador->update($data);
        return response()->json(['ok' => true, 'il' => $indicador]);
    }

    public function destroyIndicador(IndicadorLogro $indicador): JsonResponse
    {
        if ($indicador->evaluaciones()->exists()) {
            return response()->json(['ok' => false, 'error' => 'Este indicador ya tiene evaluaciones y no puede eliminarse.'], 422);
        }

        $indicador->delete();
        return response()->json(['ok' => true]);
    }

    // ── Reordenar (drag-and-drop) ─────────────────────────────────────────────

    public function reordenarCompetencias(Request $request): JsonResponse
    {
        $request->validate(['orden' => 'required|array', 'orden.*' => 'integer']);

        foreach ($request->orden as $index => $ceId) {
            CompetenciaEspecifica::where('id', $ceId)->update(['orden' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function reordenarIndicadores(Request $request): JsonResponse
    {
        $request->validate(['orden' => 'required|array', 'orden.*' => 'integer']);

        foreach ($request->orden as $index => $ilId) {
            IndicadorLogro::where('id', $ilId)->update(['orden' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
