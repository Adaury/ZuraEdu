<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConductaRegistro;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ConductaApiController extends Controller
{
    /** GET /api/v1/conducta — Estudiante ve su propia conducta */
    public function miConducta(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Estudiante')) {
            return response()->json(['message' => 'Solo para estudiantes.'], 403);
        }
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);
        return $this->datos($estudiante);
    }

    /** GET /api/v1/conducta/hijo/{estudiante} — Representante ve conducta de su hijo */
    public function hijoConducta(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }
        return $this->datos($estudiante);
    }

    private function datos(Estudiante $estudiante)
    {
        $sy        = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) {
            return response()->json([
                'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
                'periodos'   => [],
                'escala'     => [],
                'registros'  => [],
            ]);
        }

        $periodos = Periodo::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->orderBy('numero')->get()
            ->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre]);

        $registros = ConductaRegistro::with(['asignacion.asignatura', 'asignacion.docente'])
            ->where('matricula_id', $matricula->id)
            ->get();

        $escalaRaw = ConductaRegistro::ESCALA;
        $escala    = collect($escalaRaw)->map(fn($v, $k) => [
            'valor'  => $k,
            'label'  => $v['label'],
            'nombre' => $v['nombre'],
            'color'  => $v['color'],
        ])->values();

        $porPeriodo = $registros->groupBy('periodo_id')->map(function ($regs) use ($escalaRaw) {
            return $regs->map(function ($r) use ($escalaRaw) {
                $concepto = $r->concepto;
                return [
                    'asignatura'       => $r->asignacion?->asignatura?->nombre ?? '—',
                    'asignatura_color' => $r->asignacion?->asignatura?->color  ?? '#64748b',
                    'docente'          => $r->asignacion?->docente
                        ? "{$r->asignacion->docente->apellidos}, {$r->asignacion->docente->nombres}"
                        : '—',
                    'puntualidad'     => $r->puntualidad,
                    'participacion'   => $r->participacion,
                    'respeto'         => $r->respeto,
                    'trabajo_equipo'  => $r->trabajo_equipo,
                    'responsabilidad' => $r->responsabilidad,
                    'orden'           => $r->orden,
                    'concepto'        => $concepto,
                    'concepto_label'  => $concepto ? $escalaRaw[$concepto]['label'] : null,
                    'concepto_color'  => $concepto ? $escalaRaw[$concepto]['color'] : null,
                    'observaciones'   => $r->observaciones,
                ];
            })->sortBy('asignatura')->values();
        });

        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'periodos'   => $periodos,
            'escala'     => $escala,
            'registros'  => $porPeriodo,
        ]);
    }
}
