<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\ConductaRegistro;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ConductaDocenteController extends Controller
{
    use HasDocenteContext;

    public function index(Asignacion $asignacion, Request $request)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')->get();

        $periodoId     = (int) $request->get('periodo_id', $periodos->first()?->id ?? 0);
        $periodoActual = $periodos->find($periodoId);

        $registros = ConductaRegistro::where('asignacion_id', $asignacion->id)
            ->where('periodo_id', $periodoId)
            ->get()
            ->keyBy('matricula_id');

        $indicadores = ConductaRegistro::INDICADORES;
        $escala      = ConductaRegistro::ESCALA;

        return view('portal.docente.conducta.index', compact(
            'docente', 'asignacion', 'matriculas', 'periodos', 'periodoActual',
            'periodoId', 'registros', 'indicadores', 'escala', 'schoolYear'
        ));
    }

    public function guardar(Request $request, Asignacion $asignacion)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403, 'No autorizado');

        $request->validate([
            'matricula_id' => 'required|integer|exists:matriculas,id',
            'periodo_id'   => 'required|integer|exists:periodos,id',
        ]);

        $data = ['asignacion_id' => $asignacion->id];
        foreach (array_keys(ConductaRegistro::INDICADORES) as $campo) {
            $data[$campo] = $request->input($campo) ?: null;
        }
        $data['observaciones'] = $request->input('observaciones', '');

        $registro = ConductaRegistro::updateOrCreate(
            [
                'matricula_id'  => $request->matricula_id,
                'asignacion_id' => $asignacion->id,
                'periodo_id'    => $request->periodo_id,
            ],
            $data
        );

        $escala   = ConductaRegistro::ESCALA;
        $concepto = $registro->concepto;

        return response()->json([
            'ok'       => true,
            'promedio' => $registro->promedio,
            'concepto' => $concepto,
            'label'    => $concepto ? $escala[$concepto]['label'] : '—',
            'color'    => $concepto ? $escala[$concepto]['color'] : '#94a3b8',
        ]);
    }

    public function pdf(Asignacion $asignacion, Request $request)
    {
        $docente = $this->getDocente();
        if ($asignacion->docente_id !== $docente->id) abort(403);

        $asignacion->load(['asignatura', 'grupo.grado', 'grupo.seccion']);
        $schoolYear = SchoolYear::actual();

        $matriculas = Matricula::with('estudiante')
            ->where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('id')->get();

        $periodos = Periodo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('numero')->get();

        $registrosTodos = ConductaRegistro::where('asignacion_id', $asignacion->id)
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->get()
            ->groupBy(fn($r) => $r->matricula_id . '_' . $r->periodo_id);

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $indicadores = ConductaRegistro::INDICADORES;
        $escala      = ConductaRegistro::ESCALA;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'portal.docente.conducta_pdf',
            compact('docente', 'asignacion', 'matriculas', 'periodos',
                    'registrosTodos', 'schoolYear', 'si', 'config', 'indicadores', 'escala')
        )->setPaper('legal', 'landscape');

        $slug = \Illuminate\Support\Str::slug(
            ($asignacion->asignatura?->nombre ?? 'conducta') . '-' .
            ($asignacion->grupo?->nombre_corto ?? 'grupo')
        );
        return $pdf->download("conducta_{$slug}.pdf");
    }
}
