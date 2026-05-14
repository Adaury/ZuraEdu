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

        // Pre-parse para bulk-load (evita 2 queries por materia en el loop)
        $materiasData = collect($request->input('materias', []))->map(function ($m) {
            $parts = explode(':', $m);
            return count($parts) >= 3 ? $parts : null;
        })->filter()->values();

        $asigIds  = $materiasData->pluck(1)->map('intval')->unique();
        $grupoIds = $materiasData->pluck(0)->map('intval')->unique();

        $asignaturasMap = Asignatura::whereIn('id', $asigIds)->get()->keyBy('id');

        $asignacionesExistMap = Asignacion::where('school_year_id', $schoolYear->id)
            ->whereIn('grupo_id', $grupoIds)
            ->whereIn('asignatura_id', $asigIds)
            ->where(fn($q) => $q->whereNull('docente_id')->orWhere('docente_id', $docente->id))
            ->get()
            ->groupBy(fn($a) => $a->grupo_id . ':' . $a->asignatura_id);

        foreach ($materiasData as $parts) {
            [$grupoId, $asigId, $tipo] = $parts;
            $grupoId = (int) $grupoId;
            $asigId  = (int) $asigId;

            $asignatura = $asignaturasMap->get($asigId);
            if (! $asignatura) continue;

            $asignacion = $asignacionesExistMap->get("{$grupoId}:{$asigId}")?->first();

            if ($asignacion) {
                $asignacion->update([
                    'docente_id'      => $docente->id,
                    'activo'          => true,
                    'tipo_evaluacion' => $tipo,
                    'area'            => $asignatura->area,
                ]);
            } else {
                $asignacion = Asignacion::create([
                    'school_year_id'  => $schoolYear->id,
                    'grupo_id'        => $grupoId,
                    'asignatura_id'   => $asigId,
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
        Cache::forget('t' . (tenant_id() ?? 0) . "_portal_docente_{$docente->id}_asignaciones_{$schoolYear->id}");

        if (auth()->user()->hasRole('Docente')) {
            return redirect()->route('portal.docente.dashboard')
                ->with('success', 'Configuración guardada. ¡Bienvenido!');
        }

        // Admin: redirigir al perfil del docente si viene de ahí
        return redirect()->route('admin.docentes.show', $docente->id)
            ->with('success', 'Asignaciones de ' . $docente->nombre_completo . ' actualizadas correctamente.');
    }
}
