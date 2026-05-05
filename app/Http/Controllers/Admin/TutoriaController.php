<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Models\SesionTutoria;
use App\Models\Tutoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TutoriaController extends Controller
{
    // ── Asignaciones de Tutores ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $yearId = $request->integer('year_id') ?: $schoolYear?->id;
        $years  = SchoolYear::orderByDesc('fecha_inicio')->get();

        $tutorias = Tutoria::with(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones'])
            ->when($yearId, fn($q) => $q->where('school_year_id', $yearId))
            ->orderBy('grupo_id')
            ->get();

        return view('admin.tutorias.index', compact('tutorias', 'schoolYear', 'years', 'yearId'));
    }

    public function create()
    {
        $schoolYear = SchoolYear::actual();

        $docentes = Docente::activos()->orderBy('apellidos')->get();

        // Grupos sin tutor asignado en este año escolar
        $gruposConTutoria = Tutoria::where('school_year_id', $schoolYear?->id)->pluck('grupo_id');

        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        return view('admin.tutorias.create', compact('docentes', 'grupos', 'schoolYear', 'gruposConTutoria'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'docente_id'    => 'required|exists:docentes,id',
            'grupo_id'      => 'required|exists:grupos,id',
            'school_year_id'=> 'required|exists:school_years,id',
            'descripcion'   => 'nullable|string|max:500',
        ]);

        // Verificar que el grupo no tenga ya un tutor en este año
        $existe = Tutoria::where('grupo_id', $request->grupo_id)
            ->where('school_year_id', $request->school_year_id)
            ->exists();

        if ($existe) {
            return back()->withInput()
                ->with('error', 'Este grupo ya tiene un tutor asignado para el año escolar seleccionado.');
        }

        Tutoria::create($request->only(['docente_id', 'grupo_id', 'school_year_id', 'descripcion']) + ['activo' => true]);

        return redirect()->route('admin.tutorias.index')
            ->with('success', 'Tutor asignado correctamente al grupo.');
    }

    public function destroy(Tutoria $tutoria)
    {
        $grupo = $tutoria->grupo->nombre_completo ?? '';
        $tutoria->delete();

        return redirect()->route('admin.tutorias.index')
            ->with('success', "Tutoría del grupo \"{$grupo}\" eliminada.");
    }

    // ── Sesiones de Tutoría ───────────────────────────────────────────────────

    public function sesiones(Tutoria $tutoria)
    {
        $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones']);

        return view('admin.tutorias.sesiones', compact('tutoria'));
    }

    public function crearSesion(Request $request, Tutoria $tutoria)
    {
        if ($request->isMethod('GET')) {
            return view('admin.tutorias.sesiones', compact('tutoria'));
        }

        $data = $request->validate([
            'fecha'                 => 'required|date',
            'tema'                  => 'required|string|max:255',
            'descripcion'           => 'nullable|string',
            'estudiantes_atendidos' => 'nullable|string',
            'acuerdos'              => 'nullable|string',
            'proxima_sesion'        => 'nullable|date|after:fecha',
        ]);

        $tutoria->sesiones()->create($data);

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión registrada exitosamente.');
    }

    public function editarSesion(Request $request, Tutoria $tutoria, SesionTutoria $sesion)
    {
        // Verificar que la sesión pertenece a esta tutoría
        abort_unless($sesion->tutoria_id === $tutoria->id, 404);

        if ($request->isMethod('GET')) {
            $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones']);
            return view('admin.tutorias.sesiones', compact('tutoria', 'sesion'));
        }

        $data = $request->validate([
            'fecha'                 => 'required|date',
            'tema'                  => 'required|string|max:255',
            'descripcion'           => 'nullable|string',
            'estudiantes_atendidos' => 'nullable|string',
            'acuerdos'              => 'nullable|string',
            'proxima_sesion'        => 'nullable|date',
        ]);

        $sesion->update($data);

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión actualizada correctamente.');
    }

    public function eliminarSesion(Tutoria $tutoria, SesionTutoria $sesion)
    {
        abort_unless($sesion->tutoria_id === $tutoria->id, 404);

        $sesion->delete();

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión eliminada.');
    }

    // ── Informe PDF ───────────────────────────────────────────────────────────

    public function informePdf(Tutoria $tutoria)
    {
        $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'grupo.estudiantes', 'schoolYear', 'sesionesAsc']);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView('admin.tutorias.informe_pdf', compact('tutoria', 'inst'))
            ->setPaper('letter', 'portrait');

        $nombreGrupo = $tutoria->grupo->nombre_completo ?? 'grupo';
        $filename    = 'informe_tutoria_' . str_replace(' ', '_', strtolower($nombreGrupo)) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
