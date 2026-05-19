<?php

namespace App\Http\Controllers\Admin;

use App\Events\DashboardActualizado;
use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $estado     = $request->input('estado', 'pendiente');
        $search     = $request->input('buscar');

        $query = Inscripcion::with(['estudiante', 'grado', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear?->id)
            ->when($estado !== 'todos', fn($q) => $q->where('estado', $estado))
            ->when($search, fn($q) => $q->whereHas('estudiante', fn($s) =>
                $s->where('nombres',  'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('numero_matricula', 'like', "%{$search}%")
            ))
            ->orderByDesc('created_at');

        $inscripciones = $query->paginate(25)->withQueryString();

        $conteos = [
            'pendiente' => Inscripcion::where('school_year_id', $schoolYear?->id)->where('estado', 'pendiente')->count(),
            'asignada'  => Inscripcion::where('school_year_id', $schoolYear?->id)->where('estado', 'asignada')->count(),
            'cancelada' => Inscripcion::where('school_year_id', $schoolYear?->id)->where('estado', 'cancelada')->count(),
        ];
        $conteos['total'] = array_sum($conteos);

        $grados  = Grado::orderBy('nivel')->get();
        $grupos  = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        // Estudiantes disponibles para inscribir (activos, no inscritos aún este año)
        $yaInscritos = Inscripcion::where('school_year_id', $schoolYear?->id)->pluck('estudiante_id');
        $estudiantesDisponibles = Estudiante::activos()
            ->whereNotIn('id', $yaInscritos)
            ->orderBy('apellidos')
            ->get(['id', 'nombres', 'apellidos', 'numero_matricula']);

        // Estadística de continuidad del año anterior
        $syAnterior     = SchoolYear::where('id', '<', $schoolYear?->id ?? 0)->orderByDesc('id')->first();
        $continuarCount = 0;
        if ($syAnterior) {
            $yaInscritos2 = Inscripcion::where('school_year_id', $schoolYear?->id)->pluck('estudiante_id');
            $continuarCount = Matricula::where('school_year_id', $syAnterior->id)
                ->where('estado', 'activa')
                ->whereNotIn('estudiante_id', $yaInscritos2)
                ->count();
        }

        return view('admin.inscripciones.index', compact(
            'inscripciones', 'schoolYear', 'conteos', 'estado',
            'grados', 'grupos', 'estudiantesDisponibles',
            'syAnterior', 'continuarCount'
        ));
    }

    public function store(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $data = $request->validate([
            'estudiante_id'   => 'required|exists:estudiantes,id',
            'origen'          => 'required|in:continuidad,nueva,traslado',
            'grado_id'        => 'nullable|exists:grados,id',
            'observaciones'   => 'nullable|string|max:500',
        ]);

        if (Inscripcion::where('school_year_id', $schoolYear?->id)
            ->where('estudiante_id', $data['estudiante_id'])
            ->exists()
        ) {
            return back()->with('error', 'Este estudiante ya tiene una inscripción para este año escolar.');
        }

        Inscripcion::create([
            'school_year_id'   => $schoolYear?->id,
            'estudiante_id'    => $data['estudiante_id'],
            'estado'           => 'pendiente',
            'origen'           => $data['origen'],
            'grado_id'         => $data['grado_id'] ?? null,
            'observaciones'    => $data['observaciones'] ?? null,
            'fecha_inscripcion' => today(),
        ]);

        return back()->with('success', 'Estudiante inscrito correctamente. Pendiente de asignación de curso.');
    }

    public function storeMasivo(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $syAnteriorId = $request->integer('school_year_anterior_id');

        if (! $syAnteriorId || ! $schoolYear) {
            return back()->with('error', 'Datos inválidos para inscripción masiva.');
        }

        $syAnterior = SchoolYear::find($syAnteriorId);
        if (! $syAnterior) {
            return back()->with('error', 'Año escolar anterior no encontrado.');
        }

        $yaInscritos = Inscripcion::where('school_year_id', $schoolYear->id)->pluck('estudiante_id');

        $matriculasAnteriores = Matricula::where('school_year_id', $syAnteriorId)
            ->where('estado', 'activa')
            ->whereNotIn('estudiante_id', $yaInscritos)
            ->pluck('estudiante_id');

        if ($matriculasAnteriores->isEmpty()) {
            return back()->with('error', 'No hay estudiantes nuevos para inscribir desde ese año escolar.');
        }

        $tid  = tenant_id() ?? 0;
        $hoy  = today()->toDateString();
        $rows = $matriculasAnteriores->map(fn($eid) => [
            'tenant_id'         => $tid,
            'school_year_id'    => $schoolYear->id,
            'estudiante_id'     => $eid,
            'estado'            => 'pendiente',
            'origen'            => 'continuidad',
            'fecha_inscripcion' => $hoy,
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->all();

        // Chunk para evitar límites de SQL
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('inscripciones')->insertOrIgnore($chunk);
        }

        $total = count($rows);
        return back()->with('success', "{$total} estudiantes inscritos por continuidad desde {$syAnterior->nombre}.");
    }

    public function asignar(Request $request, Inscripcion $inscripcion)
    {
        $data = $request->validate([
            'grupo_id'      => 'required|exists:grupos,id',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $schoolYear = SchoolYear::actual();

        // Verificar que el estudiante no esté ya matriculado
        $yaMatriculado = Matricula::where('school_year_id', $schoolYear?->id)
            ->where('estudiante_id', $inscripcion->estudiante_id)
            ->exists();

        if ($yaMatriculado) {
            return back()->with('error', 'Este estudiante ya está matriculado en este año escolar.');
        }

        DB::transaction(function () use ($data, $inscripcion, $schoolYear) {
            $numeroOrden = Matricula::where('grupo_id', $data['grupo_id'])->count() + 1;

            $matricula = Matricula::create([
                'school_year_id'  => $schoolYear->id,
                'estudiante_id'   => $inscripcion->estudiante_id,
                'grupo_id'        => $data['grupo_id'],
                'fecha_matricula' => today(),
                'numero_orden'    => $numeroOrden,
                'estado'          => 'activa',
                'observaciones'   => $data['observaciones'] ?? $inscripcion->observaciones,
            ]);

            $inscripcion->update([
                'estado'       => 'asignada',
                'grupo_id'     => $data['grupo_id'],
                'matricula_id' => $matricula->id,
            ]);

            // Notificaciones
            try {
                DashboardActualizado::dispatch(tenant_id() ?? 0, 'nueva_matricula', [
                    'grupo_id' => $matricula->grupo_id,
                ]);
            } catch (\Throwable) {}

            try {
                $matricula->load(['estudiante.representantes', 'grupo.grado', 'grupo.seccion']);
                $est    = $matricula->estudiante;
                $grupo  = $matricula->grupo;
                $nombre = $grupo ? "{$grupo->grado?->nombre} {$grupo->seccion?->nombre}" : '—';
                $titulo  = '✅ Matrícula confirmada';
                $mensaje = "{$est?->nombre_completo} ha sido matriculado/a en {$nombre}.";
                if ($est?->user_id) {
                    Notificacion::enviar($est->user_id, 'general', $titulo, $mensaje);
                }
                foreach ($est?->representantes ?? [] as $rep) {
                    if ($rep->user_id) {
                        Notificacion::enviar($rep->user_id, 'general', $titulo, $mensaje);
                    }
                }
            } catch (\Throwable) {}
        });

        return back()->with('success', 'Estudiante asignado al curso y matrícula creada correctamente.');
    }

    public function asignarMasivo(Request $request)
    {
        $data = $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer|exists:inscripciones,id',
            'grupo_id' => 'required|exists:grupos,id',
        ]);

        $schoolYear = SchoolYear::actual();
        $grupoId    = $data['grupo_id'];
        $creados    = 0;
        $errores    = 0;

        DB::transaction(function () use ($data, $schoolYear, $grupoId, &$creados, &$errores) {
            foreach ($data['ids'] as $id) {
                $inscripcion = Inscripcion::find($id);
                if (! $inscripcion || $inscripcion->estado !== 'pendiente') continue;

                $yaExiste = Matricula::where('school_year_id', $schoolYear?->id)
                    ->where('estudiante_id', $inscripcion->estudiante_id)
                    ->exists();

                if ($yaExiste) { $errores++; continue; }

                $numeroOrden = Matricula::where('grupo_id', $grupoId)->count() + $creados + 1;

                $matricula = Matricula::create([
                    'school_year_id'  => $schoolYear->id,
                    'estudiante_id'   => $inscripcion->estudiante_id,
                    'grupo_id'        => $grupoId,
                    'fecha_matricula' => today(),
                    'numero_orden'    => $numeroOrden,
                    'estado'          => 'activa',
                ]);

                $inscripcion->update([
                    'estado'       => 'asignada',
                    'grupo_id'     => $grupoId,
                    'matricula_id' => $matricula->id,
                ]);

                $creados++;
            }
        });

        $msg = "{$creados} estudiante(s) asignado(s) correctamente.";
        if ($errores > 0) $msg .= " {$errores} omitido(s) por ya estar matriculado(s).";

        return back()->with('success', $msg);
    }

    public function destroy(Inscripcion $inscripcion)
    {
        if ($inscripcion->estado === 'asignada') {
            return back()->with('error', 'No se puede cancelar una inscripción ya asignada. Gestione la matrícula directamente.');
        }

        $inscripcion->update(['estado' => 'cancelada']);

        return back()->with('success', 'Inscripción cancelada.');
    }
}
