<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\EspecialidadTecnica;
use Illuminate\Http\Request;

class EspecialidadTecnicaController extends Controller
{
    public function index()
    {
        $especialidades = EspecialidadTecnica::orderBy('orden')
            ->with(['coordinador', 'docentes'])
            ->get();

        return view('admin.especialidades.index', compact('especialidades'));
    }

    public function create()
    {
        $docentes = Docente::whereIn('area', ['tecnica', 'ambas'])
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->get();

        return view('admin.especialidades.create', compact('docentes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:100',
            'codigo'         => 'required|string|max:20|unique:especialidades_tecnicas,codigo',
            'descripcion'    => 'nullable|string',
            'color'          => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'icono'          => 'nullable|string|max:50',
            'coordinador_id' => 'nullable|exists:docentes,id',
            'orden'          => 'nullable|integer|min:0',
        ]);

        $data['activo'] = true;
        EspecialidadTecnica::create($data);

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad creada exitosamente.');
    }

    public function edit(EspecialidadTecnica $especialidad)
    {
        $docentes = Docente::whereIn('area', ['tecnica', 'ambas'])
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->get();

        $docentesAsignados = $especialidad->docentes()->pluck('docentes.id')->toArray();

        return view('admin.especialidades.edit', compact('especialidad', 'docentes', 'docentesAsignados'));
    }

    public function update(Request $request, EspecialidadTecnica $especialidad)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:100',
            'codigo'         => 'required|string|max:20|unique:especialidades_tecnicas,codigo,' . $especialidad->id,
            'descripcion'    => 'nullable|string',
            'color'          => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'icono'          => 'nullable|string|max:50',
            'coordinador_id' => 'nullable|exists:docentes,id',
            'activo'         => 'boolean',
            'orden'          => 'nullable|integer|min:0',
        ]);

        $especialidad->update($data);

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad actualizada.');
    }

    public function destroy(EspecialidadTecnica $especialidad)
    {
        $especialidad->delete();
        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad eliminada.');
    }

    public function asignarDocente(Request $request, EspecialidadTecnica $especialidad)
    {
        $data = $request->validate([
            'docente_id'      => 'required|exists:docentes,id',
            'es_coordinador'  => 'boolean',
            'fecha_asignacion'=> 'nullable|date',
        ]);

        $especialidad->docentes()->syncWithoutDetaching([
            $data['docente_id'] => [
                'es_coordinador'   => $request->boolean('es_coordinador'),
                'fecha_asignacion' => $data['fecha_asignacion'] ?? now()->toDateString(),
            ],
        ]);

        if ($request->boolean('es_coordinador')) {
            $especialidad->update(['coordinador_id' => $data['docente_id']]);
        }

        return back()->with('success', 'Docente asignado a la especialidad.');
    }

    public function removerDocente(EspecialidadTecnica $especialidad, Docente $docente)
    {
        $especialidad->docentes()->detach($docente->id);

        if ($especialidad->coordinador_id === $docente->id) {
            $especialidad->update(['coordinador_id' => null]);
        }

        return back()->with('success', 'Docente removido de la especialidad.');
    }
}
