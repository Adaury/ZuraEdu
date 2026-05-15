<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocenteApiController extends Controller
{
    /** GET /api/v1/docente/grupos
     * Lista las asignaciones del docente con alumnos por grupo.
     */
    public function grupos(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $sy = SchoolYear::actual();

        $asignaciones = Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->get()
            ->map(fn($a) => [
                'asignacion_id' => $a->id,
                'asignatura'    => $a->asignatura?->nombre,
                'color'         => $a->asignatura?->color ?? '#64748b',
                'grupo'         => $a->grupo?->nombre_completo,
                'grupo_id'      => $a->grupo_id,
                'alumnos'       => Matricula::where('grupo_id', $a->grupo_id)
                    ->where('estado', 'activa')
                    ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                    ->with('estudiante')
                    ->get()
                    ->map(fn($m) => [
                        'matricula_id' => $m->id,
                        'nombre'       => $m->estudiante
                            ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                            : '—',
                    ])
                    ->sortBy('nombre')->values(),
            ]);

        return response()->json([
            'docente'      => "{$docente->apellidos}, {$docente->nombres}",
            'school_year'  => $sy?->nombre,
            'asignaciones' => $asignaciones,
        ]);
    }

    /** POST /api/v1/docente/asistencia
     * Registra/actualiza la asistencia de un grupo para una fecha.
     *
     * Body: {
     *   asignacion_id: int,
     *   fecha: "YYYY-MM-DD",
     *   registros: [{ matricula_id: int, estado: "presente|ausente|tardanza|justificado" }]
     * }
     */
    public function registrarAsistencia(Request $request)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $data = $request->validate([
            'asignacion_id'           => ['required', 'integer', 'exists:asignaciones,id'],
            'fecha'                   => ['required', 'date', 'before_or_equal:today'],
            'registros'               => ['required', 'array', 'min:1'],
            'registros.*.matricula_id'=> ['required', 'integer', 'exists:matriculas,id'],
            'registros.*.estado'      => ['required', 'in:presente,ausente,tardanza,justificado'],
        ]);

        // Verificar que la asignacion pertenece al docente
        $asignacion = Asignacion::where('id', $data['asignacion_id'])
            ->where('docente_id', $docente->id)
            ->where('activo', true)
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'Asignación no encontrada o no autorizada.'], 403);
        }

        $guardados = 0;
        DB::transaction(function () use ($data, $asignacion, &$guardados) {
            foreach ($data['registros'] as $reg) {
                Asistencia::updateOrCreate(
                    [
                        'matricula_id'  => $reg['matricula_id'],
                        'asignacion_id' => $asignacion->id,
                        'fecha'         => $data['fecha'],
                    ],
                    ['estado' => $reg['estado']]
                );
                $guardados++;
            }
        });

        return response()->json([
            'ok'       => true,
            'guardados'=> $guardados,
            'fecha'    => $data['fecha'],
        ]);
    }

    /** GET /api/v1/docente/asistencia/{asignacion}?fecha=YYYY-MM-DD
     * Consulta el registro de asistencia de una asignación en una fecha.
     */
    public function consultarAsistencia(Request $request, int $asignacionId)
    {
        $docente = $this->docenteOFail($request);
        if (! $docente instanceof Docente) return $docente;

        $asignacion = Asignacion::where('id', $asignacionId)
            ->where('docente_id', $docente->id)
            ->first();

        if (! $asignacion) {
            return response()->json(['message' => 'Asignación no encontrada.'], 404);
        }

        $fecha = $request->query('fecha', today()->toDateString());
        $sy    = SchoolYear::actual();

        $matriculas = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->with('estudiante')
            ->get();

        $registros = Asistencia::where('asignacion_id', $asignacion->id)
            ->where('fecha', $fecha)
            ->pluck('estado', 'matricula_id');

        $lista = $matriculas->map(fn($m) => [
            'matricula_id' => $m->id,
            'nombre'       => $m->estudiante
                ? "{$m->estudiante->apellidos}, {$m->estudiante->nombres}"
                : '—',
            'estado'       => $registros[$m->id] ?? null,
        ])->sortBy('nombre')->values();

        return response()->json([
            'asignacion_id' => $asignacion->id,
            'asignatura'    => $asignacion->asignatura?->nombre,
            'grupo'         => $asignacion->grupo?->nombre_completo,
            'fecha'         => $fecha,
            'registrado'    => $registros->isNotEmpty(),
            'alumnos'       => $lista,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function docenteOFail(Request $request): Docente|\Illuminate\Http\JsonResponse
    {
        if (! $request->user()->hasRole('Docente')) {
            return response()->json(['message' => 'Solo para docentes.'], 403);
        }

        $docente = Docente::where('user_id', $request->user()->id)->first();
        if (! $docente) {
            return response()->json(['message' => 'Perfil de docente no encontrado.'], 404);
        }

        return $docente;
    }
}
