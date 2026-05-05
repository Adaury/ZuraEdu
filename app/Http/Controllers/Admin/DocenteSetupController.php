<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DocenteSetupController extends Controller
{
    public function show(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $isPortal = request()->routeIs('portal.docente.setup*') || auth()->user()->hasRole('Docente');

        // Admin puede pasar ?docente_id=X para configurar un docente específico
        if (! $isPortal && $request->filled('docente_id')) {
            $docente = Docente::findOrFail($request->integer('docente_id'));
        } else {
            $docente = Docente::where('user_id', auth()->id())->first();
        }

        // Asignaciones ya configuradas para este docente en el año activo
        $asignacionesExistentes = $docente
            ? Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
                ->where('docente_id', $docente->id)
                ->where('school_year_id', $schoolYear->id)
                ->get()
            : collect();

        // IDs ya marcados → para pre-chequear checkboxes
        $marcados = $asignacionesExistentes
            ->mapWithKeys(fn ($a) => [
                $a->grupo_id . ':' . $a->asignatura_id . ':' . ($a->tipo_evaluacion ?? 'componentes') => true,
            ]);

        // Siempre mostrar todos los grupos y asignaturas activos del año;
        // los ya asignados se pre-marcan mediante $marcados.
        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($g) => $g->grado->nivel);

        $asignaturas = Asignatura::where('activo', true)
            ->orderBy('area')
            ->orderBy('nombre')
            ->get();

        // Todos los grupos del año (para el selector de maestro guía)
        $todosGrupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get()
            ->sortBy(fn ($g) => $g->grado->nivel);

        // Detectar si ya es maestro guía de algún grupo
        $tutor_user_id = $docente?->user_id ?? auth()->id();
        $grupoGuia   = $todosGrupos->first(fn ($g) => $g->tutor_id === $tutor_user_id);
        $esGuia      = $grupoGuia !== null;
        $grupoGuiaId = $grupoGuia?->id;

        $layout     = $isPortal ? 'layouts.portal' : 'layouts.admin';
        $storeRoute = $isPortal ? route('portal.docente.setup.store') : route('admin.docente.setup.store');

        return view('admin.docente.setup', compact(
            'schoolYear', 'grupos', 'todosGrupos', 'asignaturas', 'docente', 'layout', 'storeRoute',
            'marcados', 'asignacionesExistentes', 'esGuia', 'grupoGuiaId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cedula'         => 'required|string|max:20',
            'telefono'       => 'nullable|string|max:20',
            'especialidad'   => 'nullable|string|max:100',
            'area_trabajo'   => 'required|in:academica,tecnica,ambas',
            'materias'       => 'nullable|array',
            'materias.*'     => 'string',
            'es_maestro_guia' => 'nullable|boolean',
            'grupo_guia_id'   => 'nullable|exists:grupos,id',
            'docente_id'      => 'nullable|exists:docentes,id',
        ]);

        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $isPortal = auth()->user()->hasRole('Docente');

        // Admin puede pasar docente_id para editar un docente específico
        if (! $isPortal && $request->filled('docente_id')) {
            $docente = Docente::findOrFail($request->integer('docente_id'));
            $docente->update([
                'cedula'       => $request->cedula,
                'telefono'     => $request->telefono,
                'especialidad' => $request->especialidad,
                'area'         => $request->area_trabajo,
                'estado'       => 'activo',
            ]);
        } else {
            $user = auth()->user();
            // Crear o actualizar el registro de Docente para el usuario logueado
            $docente = Docente::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nombres'      => $user->name,
                    'apellidos'    => $user->apellidos ?? '',
                    'cedula'       => $request->cedula,
                    'telefono'     => $request->telefono,
                    'especialidad' => $request->especialidad,
                    'area'         => $request->area_trabajo,
                    'email'        => $user->email,
                    'estado'       => 'activo',
                ]
            );
        }

        // Sincronizar materias: construir el set deseado y comparar con lo existente
        $deseados = collect();
        foreach ($request->input('materias', []) as $materia) {
            $parts = explode(':', $materia);
            if (count($parts) < 3) {
                continue;
            }
            [$grupoId, $asigId, $tipo] = $parts;
            $asignatura = Asignatura::find((int) $asigId);
            if (! $asignatura) {
                continue;
            }

            // Primero buscar una asignación existente para este grupo+asignatura
            // (puede tener docente_id=null si fue auto-creada como materia básica,
            //  o ya tener este docente asignado). Actualizamos en vez de duplicar.
            $asignacion = Asignacion::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', (int) $grupoId)
                ->where('asignatura_id', (int) $asigId)
                ->where(fn($q) => $q->whereNull('docente_id')
                                    ->orWhere('docente_id', $docente->id))
                ->first();

            if ($asignacion) {
                // Actualizar el docente y reactivar si estaba inactiva
                $asignacion->update([
                    'docente_id'     => $docente->id,
                    'activo'         => true,
                    'tipo_evaluacion'=> $tipo,
                    'area'           => $asignatura->area,
                ]);
            } else {
                // Crear nueva asignación solo si no existe ninguna para este grupo+materia
                $asignacion = Asignacion::create([
                    'school_year_id'  => $schoolYear->id,
                    'grupo_id'        => (int) $grupoId,
                    'asignatura_id'   => (int) $asigId,
                    'docente_id'      => $docente->id,
                    'activo'          => true,
                    'area'            => $asignatura->area,
                    'tipo_evaluacion' => $tipo,
                ]);
            }

            $deseados->push($asignacion->id);
        }

        // Desactivar asignaciones desmarcadas de este docente (no eliminar para preservar historial)
        Asignacion::where('docente_id', $docente->id)
            ->where('school_year_id', $schoolYear->id)
            ->whereNotIn('id', $deseados->toArray())
            ->update(['activo' => false]);

        // Asignar como maestro guía si aplica
        if ($request->boolean('es_maestro_guia') && $request->grupo_guia_id) {
            $tutorUserId = $docente->user_id ?? auth()->id();
            Grupo::find($request->grupo_guia_id)?->update(['tutor_id' => $tutorUserId]);
        }

        // Limpiar caché del portal para que los cambios se reflejen de inmediato
        Cache::forget("portal_docente_{$docente->id}_asignaciones_{$schoolYear->id}");

        if (auth()->user()->hasRole('Docente')) {
            return redirect()->route('portal.docente.dashboard')
                ->with('success', 'Configuración guardada. ¡Bienvenido!');
        }

        // Admin: redirigir al perfil del docente si viene de ahí
        return redirect()->route('admin.docentes.show', $docente->id)
            ->with('success', 'Asignaciones de ' . $docente->nombre_completo . ' actualizadas correctamente.');
    }
}
