<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\FaseProyecto;
use App\Models\IntegranteProyecto;
use App\Models\ProyectoEscolar;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $query = ProyectoEscolar::with(['tutor', 'schoolYear', 'integrantes'])
            ->withCount('integrantes');

        // Filtro año escolar
        $yearId = $request->filled('school_year_id')
            ? $request->school_year_id
            : ($schoolYear?->id);

        if ($yearId) {
            $query->where('school_year_id', $yearId);
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        $proyectos  = $query->latest()->paginate(20)->withQueryString();
        $schoolYears = SchoolYear::orderByDesc('fecha_inicio')->get();
        $tutores    = User::orderBy('name')->get();
        $areas      = ProyectoEscolar::AREAS;
        $estados    = ProyectoEscolar::ESTADOS;

        // Totales por estado para el año actual
        $totalesEstado = ProyectoEscolar::when($yearId, fn($q) => $q->where('school_year_id', $yearId))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('admin.proyectos.index', compact(
            'proyectos', 'schoolYears', 'tutores', 'areas', 'estados',
            'totalesEstado', 'schoolYear', 'yearId'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create()
    {
        $schoolYear = SchoolYear::actual();
        $schoolYears = SchoolYear::orderByDesc('fecha_inicio')->get();
        $tutores    = User::orderBy('name')->get();
        $areas      = ProyectoEscolar::AREAS;
        $estados    = ProyectoEscolar::ESTADOS;

        return view('admin.proyectos.create', compact(
            'schoolYear', 'schoolYears', 'tutores', 'areas', 'estados'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'         => 'required|string|max:255',
            'descripcion'    => 'nullable|string',
            'area'           => 'required|in:' . implode(',', array_keys(ProyectoEscolar::AREAS)),
            'tutor_id'       => 'required|exists:users,id',
            'school_year_id' => 'required|exists:school_years,id',
            'estado'         => 'required|in:' . implode(',', array_keys(ProyectoEscolar::ESTADOS)),
            'fecha_inicio'   => 'required|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $proyecto = ProyectoEscolar::create($data);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto creado exitosamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(ProyectoEscolar $proyecto)
    {
        $proyecto->load([
            'tutor',
            'schoolYear',
            'fases',
            'integrantes.estudiante',
        ]);

        $estudiantesDisponibles = Estudiante::activos()
            ->whereNotIn('id', $proyecto->integrantes->pluck('estudiante_id'))
            ->orderBy('apellidos')
            ->get();

        return view('admin.proyectos.show', compact('proyecto', 'estudiantesDisponibles'));
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function edit(ProyectoEscolar $proyecto)
    {
        $schoolYears = SchoolYear::orderByDesc('fecha_inicio')->get();
        $tutores     = User::orderBy('name')->get();
        $areas       = ProyectoEscolar::AREAS;
        $estados     = ProyectoEscolar::ESTADOS;

        return view('admin.proyectos.create', compact(
            'proyecto', 'schoolYears', 'tutores', 'areas', 'estados'
        ));
    }

    public function update(Request $request, ProyectoEscolar $proyecto)
    {
        $data = $request->validate([
            'titulo'         => 'required|string|max:255',
            'descripcion'    => 'nullable|string',
            'area'           => 'required|in:' . implode(',', array_keys(ProyectoEscolar::AREAS)),
            'tutor_id'       => 'required|exists:users,id',
            'school_year_id' => 'required|exists:school_years,id',
            'estado'         => 'required|in:' . implode(',', array_keys(ProyectoEscolar::ESTADOS)),
            'fecha_inicio'   => 'required|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $proyecto->update($data);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(ProyectoEscolar $proyecto)
    {
        $proyecto->delete();

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto eliminado.');
    }

    // ── Integrantes ───────────────────────────────────────────────────────────

    public function agregarIntegrante(Request $request, ProyectoEscolar $proyecto)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'rol'           => 'required|in:lider,integrante',
        ]);

        // Si ya existe el lider, degradar al anterior
        if ($data['rol'] === 'lider') {
            IntegranteProyecto::where('proyecto_id', $proyecto->id)
                ->where('rol', 'lider')
                ->update(['rol' => 'integrante']);
        }

        IntegranteProyecto::updateOrCreate(
            ['proyecto_id' => $proyecto->id, 'estudiante_id' => $data['estudiante_id']],
            ['rol' => $data['rol']]
        );

        return back()->with('success', 'Integrante agregado.');
    }

    public function quitarIntegrante(ProyectoEscolar $proyecto, IntegranteProyecto $integrante)
    {
        abort_unless($integrante->proyecto_id === $proyecto->id, 403);
        $integrante->delete();

        return back()->with('success', 'Integrante eliminado del proyecto.');
    }

    // ── Fases ─────────────────────────────────────────────────────────────────

    public function addFase(Request $request, ProyectoEscolar $proyecto)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'fecha_limite' => 'required|date',
        ]);

        $proyecto->fases()->create($data);

        return back()->with('success', 'Fase agregada.');
    }

    public function toggleFase(ProyectoEscolar $proyecto, FaseProyecto $fase)
    {
        abort_unless($fase->proyecto_id === $proyecto->id, 403);
        $fase->update(['completada' => !$fase->completada]);

        return back()->with('success', $fase->completada ? 'Fase marcada como completada.' : 'Fase marcada como pendiente.');
    }

    // ── Certificado PDF ───────────────────────────────────────────────────────

    public function certificadoPdf(ProyectoEscolar $proyecto, \App\Models\Estudiante $estudiante)
    {
        // Verificar que el estudiante pertenece al proyecto
        $integrante = IntegranteProyecto::where('proyecto_id', $proyecto->id)
            ->where('estudiante_id', $estudiante->id)
            ->firstOrFail();

        $proyecto->load(['tutor', 'schoolYear']);

        $inst     = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir      = ConfigInstitucional::get('director_nombre', '');
        $cod      = ConfigInstitucional::get('codigo_centro', '');
        $logoPath = ConfigInstitucional::get('logo_path');
        $logoUrl  = $logoPath ? public_path('storage/' . $logoPath) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.proyectos.certificado_pdf',
            compact('proyecto', 'estudiante', 'integrante', 'inst', 'dir', 'cod', 'logoUrl')
        )->setPaper('letter', 'landscape');

        $nombre = str_replace(' ', '_', $estudiante->nombres . '_' . $estudiante->apellidos);

        return $pdf->download("certificado_proyecto_{$nombre}.pdf");
    }
}
