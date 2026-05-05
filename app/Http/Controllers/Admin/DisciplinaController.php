<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertaSistema;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DisciplinaController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = FaltaDisciplinaria::with(['estudiante', 'docente']);

        // Filtros
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('resuelto')) {
            $query->where('resuelto', $request->resuelto === '1');
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('estudiante', fn($s) =>
                $s->where('nombres', 'like', "%{$q}%")
                  ->orWhere('apellidos', 'like', "%{$q}%")
            );
        }

        $faltas = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(25)->withQueryString();

        // Totales por tipo (para tarjetas resumen)
        $totalesTipo = FaltaDisciplinaria::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $totalPendientes = FaltaDisciplinaria::where('resuelto', false)->count();

        // Para filtros
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $docentes    = Docente::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $tipos       = FaltaDisciplinaria::TIPOS;

        return view('admin.disciplina.index', compact(
            'faltas', 'estudiantes', 'docentes', 'tipos',
            'totalesTipo', 'totalPendientes'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create()
    {
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $docentes    = Docente::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $tipos       = FaltaDisciplinaria::TIPOS;

        return view('admin.disciplina.create', compact('estudiantes', 'docentes', 'tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estudiante_id'    => 'required|exists:estudiantes,id',
            'docente_id'       => 'nullable|exists:docentes,id',
            'tipo'             => 'required|in:tardanza,falta_leve,falta_grave,suspension',
            'descripcion'      => 'required|string|max:1000',
            'fecha'            => 'required|date|before_or_equal:today',
            'notas_resolucion' => 'nullable|string|max:1000',
        ]);

        $data['resuelto'] = false;

        $falta = FaltaDisciplinaria::create($data);

        // Notificación al representante
        $this->notificarRepresentante($falta);

        // Alerta de sistema si es suspensión
        if ($falta->tipo === 'suspension') {
            $this->crearAlertaSuspension($falta);
        }

        return redirect()->route('admin.disciplina.index')
            ->with('success', 'Falta disciplinaria registrada correctamente.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(FaltaDisciplinaria $disciplina)
    {
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $docentes    = Docente::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $tipos       = FaltaDisciplinaria::TIPOS;

        return view('admin.disciplina.create', compact('disciplina', 'estudiantes', 'docentes', 'tipos'));
    }

    public function update(Request $request, FaltaDisciplinaria $disciplina)
    {
        $data = $request->validate([
            'estudiante_id'    => 'required|exists:estudiantes,id',
            'docente_id'       => 'nullable|exists:docentes,id',
            'tipo'             => 'required|in:tardanza,falta_leve,falta_grave,suspension',
            'descripcion'      => 'required|string|max:1000',
            'fecha'            => 'required|date|before_or_equal:today',
            'resuelto'         => 'boolean',
            'notas_resolucion' => 'nullable|string|max:1000',
        ]);

        $data['resuelto'] = $request->boolean('resuelto');

        $disciplina->update($data);

        return redirect()->route('admin.disciplina.index')
            ->with('success', 'Falta disciplinaria actualizada.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(FaltaDisciplinaria $disciplina)
    {
        $disciplina->delete();

        return back()->with('success', 'Falta disciplinaria eliminada.');
    }

    // ── Toggle Resuelto (AJAX / form) ─────────────────────────────────────

    public function toggleResuelto(FaltaDisciplinaria $disciplina)
    {
        $disciplina->update(['resuelto' => ! $disciplina->resuelto]);

        if (request()->expectsJson()) {
            return response()->json([
                'resuelto' => $disciplina->resuelto,
                'label'    => $disciplina->resuelto ? 'Resuelto' : 'Pendiente',
            ]);
        }

        return back()->with('success', $disciplina->resuelto
            ? 'Falta marcada como resuelta.'
            : 'Falta marcada como pendiente.');
    }

    // ── Expediente PDF ────────────────────────────────────────────────────

    public function expedientePdf(Estudiante $estudiante)
    {
        $faltas = FaltaDisciplinaria::with('docente')
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('fecha')
            ->get();

        // Resumen de conteos por tipo
        $conteosPorTipo = $faltas->groupBy('tipo')->map->count();

        $inst      = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $schoolYear = SchoolYear::actual();
        $tipos     = FaltaDisciplinaria::TIPOS;

        $pdf = Pdf::loadView('admin.disciplina.expediente_pdf', compact(
            'estudiante', 'faltas', 'conteosPorTipo', 'inst', 'schoolYear', 'tipos'
        ))->setPaper('letter', 'portrait');

        $nombre = str_replace([',', ' '], ['', '_'], $estudiante->nombre_completo);

        return $pdf->download("expediente_disciplinario_{$nombre}_" . now()->format('Ymd') . '.pdf');
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    private function notificarRepresentante(FaltaDisciplinaria $falta): void
    {
        try {
            $falta->load(['estudiante.representantes', 'estudiante.user']);

            $tipoInfo = FaltaDisciplinaria::TIPOS[$falta->tipo] ?? ['label' => $falta->tipo];
            $nombre   = $falta->estudiante->nombre_completo ?? 'el estudiante';
            $fecha    = $falta->fecha->format('d/m/Y');
            $titulo   = "Falta disciplinaria: {$tipoInfo['label']}";
            $mensaje  = "Se registró una falta {$tipoInfo['label']} para {$nombre} el {$fecha}.";

            // Notificar al usuario del estudiante si existe
            if ($falta->estudiante?->user_id) {
                Notificacion::enviar(
                    $falta->estudiante->user_id,
                    'alerta',
                    $titulo,
                    $mensaje
                );
            }

            // Notificar a cada representante vinculado
            foreach ($falta->estudiante?->representantes ?? [] as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar($rep->user_id, 'alerta', $titulo, $mensaje);
                }
            }
        } catch (\Throwable) {
            // No interrumpir el flujo por fallo de notificación
        }
    }

    private function crearAlertaSuspension(FaltaDisciplinaria $falta): void
    {
        try {
            $nombre = $falta->estudiante->nombre_completo ?? 'Estudiante';
            $fecha  = $falta->fecha->format('d/m/Y');

            AlertaSistema::create([
                'tipo'            => 'otro',
                'titulo'          => "Suspensión registrada: {$nombre}",
                'mensaje'         => "Se registró una suspensión para el estudiante {$nombre} con fecha {$fecha}. Descripción: {$falta->descripcion}",
                'nivel'           => 'danger',
                'destinatario_rol'=> 'Administrador',
                'referencia_tipo' => 'FaltaDisciplinaria',
                'referencia_id'   => $falta->id,
                'school_year_id'  => optional(SchoolYear::actual())->id,
                'creado_por'      => auth()->id(),
            ]);
        } catch (\Throwable) {}
    }
}
