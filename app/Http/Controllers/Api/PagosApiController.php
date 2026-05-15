<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\Estudiante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class PagosApiController extends Controller
{
    /** GET /api/v1/pagos
     * Para Representante: pagos de sus hijos.
     * Para Estudiante: sus propios pagos.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sy   = SchoolYear::actual();

        if ($user->hasRole('Representante')) {
            return $this->pagosRepresentante($user, $sy);
        }

        if ($user->hasRole('Estudiante')) {
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);
            return $this->pagosEstudiante($estudiante, $sy);
        }

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /** GET /api/v1/pagos/hijo/{estudiante} */
    public function hijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }
        return $this->pagosEstudiante($estudiante, SchoolYear::actual());
    }

    private function pagosRepresentante($user, $sy)
    {
        $rep   = Representante::where('user_id', $user->id)->first();
        if (! $rep) return response()->json(['hijos' => []]);

        $hijos = $rep->estudiantes()->get();
        $data  = $hijos->map(function ($estudiante) use ($sy) {
            return [
                'estudiante_id'  => $estudiante->id,
                'estudiante'     => "{$estudiante->nombres} {$estudiante->apellidos}",
                'pagos'          => $this->buildPagos($estudiante, $sy),
            ];
        });

        return response()->json(['hijos' => $data]);
    }

    private function pagosEstudiante(Estudiante $estudiante, $sy)
    {
        return response()->json([
            'estudiante' => "{$estudiante->nombres} {$estudiante->apellidos}",
            'pagos'      => $this->buildPagos($estudiante, $sy),
        ]);
    }

    private function buildPagos(Estudiante $estudiante, $sy): array
    {
        $matricula = $estudiante->matriculas()
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        if (! $matricula) return [];

        return Pago::where('matricula_id', $matricula->id)
            ->orderByDesc('fecha_vencimiento')
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'concepto'         => $p->concepto,
                'monto'            => (float) $p->monto,
                'monto_pagado'     => (float) ($p->monto_pagado ?? 0),
                'estado'           => $p->estado,
                'estado_label'     => ucfirst($p->estado),
                'fecha_vencimiento'=> $p->fecha_vencimiento?->toDateString(),
                'fecha_pago'       => $p->fecha_pago?->toDateString(),
                'vencido'          => $p->estaVencido(),
            ])
            ->all();
    }
}
