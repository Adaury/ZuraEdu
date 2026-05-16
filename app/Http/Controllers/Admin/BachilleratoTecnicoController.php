<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaTecnica;
use App\Models\CursoTecnico;
use App\Models\ModuloFormativo;
use Illuminate\Http\Request;

class BachilleratoTecnicoController extends Controller
{
    // ── Vista principal ──────────────────────────────────────────────────────

    public function index()
    {
        $areas = AreaTecnica::withCount('cursos')
            ->with(['cursos' => fn($q) => $q->withCount('modulos')->with('modulos')])
            ->orderBy('orden')->orderBy('nombre')
            ->get();

        $cursos = CursoTecnico::with('area')
            ->withCount('modulos')
            ->orderBy('area_tecnica_id')->orderBy('orden')->orderBy('nombre')
            ->get();

        $modulos = ModuloFormativo::with('curso.area')
            ->orderBy('curso_tecnico_id')->orderBy('orden')->orderBy('nombre')
            ->get();

        return view('admin.bachillerato_tecnico.index', compact('areas', 'cursos', 'modulos'));
    }

    // ── Áreas Técnicas ───────────────────────────────────────────────────────

    public function storeArea(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'codigo'      => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'orden'       => 'nullable|integer|min:0',
        ]);

        AreaTecnica::create([
            'nombre'      => $data['nombre'],
            'codigo'      => $data['codigo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'color'       => $data['color'] ?? '#1e3a6e',
            'orden'       => $data['orden'] ?? 0,
            'activo'      => true,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'areas'])
            ->with('success', "Área técnica \"{$data['nombre']}\" creada correctamente.");
    }

    public function updateArea(Request $request, AreaTecnica $area)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'codigo'      => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'orden'       => 'nullable|integer|min:0',
        ]);

        $area->update([
            'nombre'      => $data['nombre'],
            'codigo'      => $data['codigo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'color'       => $data['color'] ?? $area->color,
            'orden'       => $data['orden'] ?? 0,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'areas'])
            ->with('success', "Área técnica actualizada correctamente.");
    }

    public function destroyArea(AreaTecnica $area)
    {
        $nombre = $area->nombre;
        $area->delete();

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'areas'])
            ->with('success', "Área técnica \"{$nombre}\" eliminada. Sus cursos y módulos también fueron eliminados.");
    }

    public function toggleArea(AreaTecnica $area)
    {
        $area->update(['activo' => !$area->activo]);
        $estado = $area->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Área técnica {$estado}.");
    }

    // ── Cursos Técnicos ──────────────────────────────────────────────────────

    public function storeCurso(Request $request)
    {
        $data = $request->validate([
            'area_tecnica_id' => 'required|exists:areas_tecnicas,id',
            'nombre'          => 'required|string|max:150',
            'codigo'          => 'nullable|string|max:30',
            'descripcion'     => 'nullable|string',
            'duracion_horas'  => 'nullable|integer|min:1',
            'orden'           => 'nullable|integer|min:0',
        ]);

        CursoTecnico::create([
            'area_tecnica_id' => $data['area_tecnica_id'],
            'nombre'          => $data['nombre'],
            'codigo'          => $data['codigo'] ?? null,
            'descripcion'     => $data['descripcion'] ?? null,
            'duracion_horas'  => $data['duracion_horas'] ?? null,
            'orden'           => $data['orden'] ?? 0,
            'activo'          => true,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'cursos'])
            ->with('success', "Curso técnico \"{$data['nombre']}\" creado correctamente.");
    }

    public function updateCurso(Request $request, CursoTecnico $curso)
    {
        $data = $request->validate([
            'area_tecnica_id' => 'required|exists:areas_tecnicas,id',
            'nombre'          => 'required|string|max:150',
            'codigo'          => 'nullable|string|max:30',
            'descripcion'     => 'nullable|string',
            'duracion_horas'  => 'nullable|integer|min:1',
            'orden'           => 'nullable|integer|min:0',
        ]);

        $curso->update([
            'area_tecnica_id' => $data['area_tecnica_id'],
            'nombre'          => $data['nombre'],
            'codigo'          => $data['codigo'] ?? null,
            'descripcion'     => $data['descripcion'] ?? null,
            'duracion_horas'  => $data['duracion_horas'] ?? null,
            'orden'           => $data['orden'] ?? 0,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'cursos'])
            ->with('success', "Curso técnico actualizado correctamente.");
    }

    public function destroyCurso(CursoTecnico $curso)
    {
        $nombre = $curso->nombre;
        $curso->delete();

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'cursos'])
            ->with('success', "Curso técnico \"{$nombre}\" eliminado. Sus módulos también fueron eliminados.");
    }

    public function toggleCurso(CursoTecnico $curso)
    {
        $curso->update(['activo' => !$curso->activo]);
        $estado = $curso->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Curso técnico {$estado}.");
    }

    // ── Módulos Formativos ───────────────────────────────────────────────────

    public function storeModulo(Request $request)
    {
        $data = $request->validate([
            'curso_tecnico_id' => 'required|exists:cursos_tecnicos,id',
            'nombre'           => 'required|string|max:150',
            'codigo'           => 'nullable|string|max:30',
            'descripcion'      => 'nullable|string',
            'duracion_horas'   => 'nullable|integer|min:1',
            'creditos'         => 'nullable|numeric|min:0',
            'orden'            => 'nullable|integer|min:0',
        ]);

        ModuloFormativo::create([
            'curso_tecnico_id' => $data['curso_tecnico_id'],
            'nombre'           => $data['nombre'],
            'codigo'           => $data['codigo'] ?? null,
            'descripcion'      => $data['descripcion'] ?? null,
            'duracion_horas'   => $data['duracion_horas'] ?? null,
            'creditos'         => $data['creditos'] ?? null,
            'orden'            => $data['orden'] ?? 0,
            'activo'           => true,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'modulos'])
            ->with('success', "Módulo formativo \"{$data['nombre']}\" creado correctamente.");
    }

    public function updateModulo(Request $request, ModuloFormativo $modulo)
    {
        $data = $request->validate([
            'curso_tecnico_id' => 'required|exists:cursos_tecnicos,id',
            'nombre'           => 'required|string|max:150',
            'codigo'           => 'nullable|string|max:30',
            'descripcion'      => 'nullable|string',
            'duracion_horas'   => 'nullable|integer|min:1',
            'creditos'         => 'nullable|numeric|min:0',
            'orden'            => 'nullable|integer|min:0',
        ]);

        $modulo->update([
            'curso_tecnico_id' => $data['curso_tecnico_id'],
            'nombre'           => $data['nombre'],
            'codigo'           => $data['codigo'] ?? null,
            'descripcion'      => $data['descripcion'] ?? null,
            'duracion_horas'   => $data['duracion_horas'] ?? null,
            'creditos'         => $data['creditos'] ?? null,
            'orden'            => $data['orden'] ?? 0,
        ]);

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'modulos'])
            ->with('success', "Módulo formativo actualizado correctamente.");
    }

    public function destroyModulo(ModuloFormativo $modulo)
    {
        $nombre = $modulo->nombre;
        $modulo->delete();

        return redirect()->route('admin.bachillerato-tecnico.index', ['tab' => 'modulos'])
            ->with('success', "Módulo formativo \"{$nombre}\" eliminado.");
    }

    public function toggleModulo(ModuloFormativo $modulo)
    {
        $modulo->update(['activo' => !$modulo->activo]);
        $estado = $modulo->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Módulo formativo {$estado}.");
    }
}
