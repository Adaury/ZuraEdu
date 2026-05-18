<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\MaterialClase;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ClassroomApiController extends Controller
{
    /** GET /api/v1/classroom
     * Devuelve las clases virtuales del usuario según su rol.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sy   = SchoolYear::actual();
        $role = $user->roles->first()?->name;

        if ($role === 'Docente') {
            $docente = Docente::where('user_id', $user->id)->first();
            if (! $docente) return response()->json(['clases' => []]);

            $clases = ClaseVirtual::with('asignacion.asignatura')
                ->whereHas('asignacion', fn($q) => $q
                    ->where('docente_id', $docente->id)
                    ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                )
                ->where('activo', true)->get()
                ->map(fn($c) => $this->mapClase($c));

            return response()->json(['role' => 'docente', 'clases' => $clases]);
        }

        if ($role === 'Estudiante') {
            $est = Estudiante::where('user_id', $user->id)->first();
            $mat = $est?->matriculas()
                ->where('estado', 'activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->latest()->first();

            if (! $mat) return response()->json(['clases' => []]);

            $clases = ClaseVirtual::with('asignacion.asignatura')
                ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $mat->grupo_id))
                ->where('activo', true)->get()
                ->map(fn($c) => $this->mapClase($c));

            return response()->json(['role' => 'estudiante', 'clases' => $clases]);
        }

        if ($role === 'Representante') {
            $rep   = Representante::where('user_id', $user->id)->first();
            $hijos = $rep ? $rep->estudiantes()->get() : collect();

            $data = $hijos->map(function ($est) use ($sy) {
                $mat = $est->matriculas()
                    ->where('estado', 'activa')
                    ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                    ->latest()->first();

                if (! $mat) return ['estudiante' => "{$est->nombres} {$est->apellidos}", 'clases' => []];

                $clases = ClaseVirtual::with('asignacion.asignatura')
                    ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $mat->grupo_id))
                    ->where('activo', true)->get()
                    ->map(fn($c) => $this->mapClase($c));

                return ['estudiante' => "{$est->nombres} {$est->apellidos}", 'clases' => $clases];
            });

            return response()->json(['role' => 'padre', 'hijos' => $data]);
        }

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /** GET /api/v1/classroom/{claseVirtual}/materiales */
    public function materiales(Request $request, ClaseVirtual $claseVirtual)
    {
        $user = $request->user();

        if (! $this->puedeVerClase($user, $claseVirtual)) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $materiales = MaterialClase::with('archivos')
            ->where('clase_virtual_id', $claseVirtual->id)
            ->where('publicado', true)
            ->orderByDesc('created_at')
            ->get();

        // Obtener entrega del estudiante si aplica
        $entregasMap = [];
        if ($user->hasRole('Estudiante')) {
            $est = Estudiante::where('user_id', $user->id)->first();
            $mat = $est?->matriculas()->where('estado', 'activa')->latest()->first();
            if ($mat) {
                EntregaClassroom::where('matricula_id', $mat->id)
                    ->whereIn('material_id', $materiales->pluck('id'))
                    ->get()
                    ->each(fn($e) => $entregasMap[$e->material_id] = $e);
            }
        }

        $data = $materiales->map(fn($m) => [
            'id'           => $m->id,
            'titulo'       => $m->titulo,
            'tipo'         => $m->tipo,
            'contenido'    => $m->contenido,
            'url_externo'  => $m->url_externo,
            'fecha_limite' => $m->fecha_limite?->toIso8601String(),
            'puntos'       => $m->puntos,
            'es_tarea'     => $m->esTarea(),
            'vencido'      => $m->estaVencido(),
            'archivos'     => $m->archivos->map(fn($a) => [
                'nombre'=> $a->nombre_original,
                'url'   => $a->url ?? asset('storage/'.$a->ruta),
                'tipo'  => $a->tipo_mime ?? null,
            ]),
            'entrega'      => isset($entregasMap[$m->id]) ? [
                'estado'       => $entregasMap[$m->id]->estado,
                'calificacion' => $entregasMap[$m->id]->calificacion,
                'fecha'        => $entregasMap[$m->id]->fecha_entrega?->toIso8601String(),
                'comentario'   => $entregasMap[$m->id]->comentario_docente,
            ] : null,
        ]);

        return response()->json([
            'clase'      => $this->mapClase($claseVirtual),
            'materiales' => $data,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function mapClase(ClaseVirtual $c): array
    {
        return [
            'id'            => $c->id,
            'nombre'        => $c->nombre,
            'descripcion'   => $c->descripcion,
            'portada_color' => $c->portada_color,
            'asignatura'    => $c->asignacion?->asignatura?->nombre,
            'docente'       => $c->asignacion?->docente
                ? "{$c->asignacion->docente->apellidos}, {$c->asignacion->docente->nombres}"
                : null,
        ];
    }

    private function puedeVerClase($user, ClaseVirtual $clase): bool
    {
        $role = $user->roles->first()?->name;
        $sy   = SchoolYear::actual();

        if ($role === 'Docente') {
            $docente = Docente::where('user_id', $user->id)->first();
            return $docente && $clase->asignacion?->docente_id === $docente->id;
        }

        if ($role === 'Estudiante') {
            $est     = Estudiante::where('user_id', $user->id)->first();
            $mat     = $est?->matriculas()->where('estado','activa')->when($sy, fn($q) => $q->where('school_year_id', $sy->id))->latest()->first();
            return $mat && $clase->asignacion?->grupo_id === $mat->grupo_id;
        }

        if ($role === 'Representante') {
            $rep = Representante::where('user_id', $user->id)->first();
            if (! $rep) return false;
            $grupoIds = $rep->estudiantes()->get()->flatMap(function ($est) use ($sy) {
                return $est->matriculas()->where('estado','activa')
                    ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                    ->pluck('grupo_id');
            })->unique()->values()->all();
            return in_array($clase->asignacion?->grupo_id, $grupoIds);
        }

        if (in_array($role, ['Administrador', 'Director'])) {
            return true;
        }

        return false;
    }
}
