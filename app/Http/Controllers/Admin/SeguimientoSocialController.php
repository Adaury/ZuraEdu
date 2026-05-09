<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CasoSeguimiento;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\IntervencionCaso;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;

class SeguimientoSocialController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = CasoSeguimiento::with(['estudiante', 'responsable'])
            ->withCount('intervenciones');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('nivel_riesgo')) {
            $query->where('nivel_riesgo', $request->nivel_riesgo);
        }
        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('estudiante', fn($s) =>
                $s->where('nombres', 'like', "%{$q}%")
                  ->orWhere('apellidos', 'like', "%{$q}%")
                  ->orWhere('numero_matricula', 'like', "%{$q}%")
            );
        }

        $casos = $query->latest()->paginate(20)->withQueryString();

        // Contadores de resumen
        $totales = [
            'abiertos'        => CasoSeguimiento::where('estado', 'abierto')->count(),
            'en_seguimiento'  => CasoSeguimiento::where('estado', 'en_seguimiento')->count(),
            'cerrados'        => CasoSeguimiento::where('estado', 'cerrado')->count(),
            'criticos'        => CasoSeguimiento::where('nivel_riesgo', 'critico')
                                    ->whereIn('estado', ['abierto', 'en_seguimiento'])->count(),
        ];

        $responsables = User::has('casosAsignados')
            ->orderBy('name')->get();

        return view('admin.seguimiento_social.index', compact(
            'casos', 'totales', 'responsables'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create()
    {
        $estudiantes  = Estudiante::activos()->orderBy('apellidos')->orderBy('nombres')->get();
        $responsables = User::role(['Administrador', 'Director', 'Coordinador Académico', 'Docente'])
            ->orderBy('name')->get();

        return view('admin.seguimiento_social.create', compact('estudiantes', 'responsables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estudiante_id'  => 'required|exists:estudiantes,id',
            'tipo'           => 'required|in:academico,social,familiar,conductual,otro',
            'descripcion'    => 'required|string|max:5000',
            'nivel_riesgo'   => 'required|in:bajo,medio,alto,critico',
            'estado'         => 'required|in:abierto,en_seguimiento,cerrado',
            'responsable_id' => 'nullable|exists:users,id',
            'fecha_apertura' => 'required|date',
        ]);

        $caso = CasoSeguimiento::create($data);

        try {
            $caso->load(['estudiante', 'responsable']);
            $nombre = $caso->estudiante?->nombre_completo ?? 'un estudiante';
            $riesgo = ucfirst($caso->nivel_riesgo);
            if ($caso->responsable_id) {
                Notificacion::enviar(
                    $caso->responsable_id,
                    'alerta',
                    '🔍 Nuevo caso de seguimiento asignado',
                    "Se te ha asignado un caso de seguimiento social para {$nombre} (Riesgo: {$riesgo})."
                );
            }
        } catch (\Throwable) {}

        return redirect()
            ->route('admin.seguimiento-social.show', $caso)
            ->with('success', 'Caso de seguimiento creado correctamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(CasoSeguimiento $caso)
    {
        $caso->load([
            'estudiante.matriculaActiva.grupo.grado',
            'estudiante.matriculaActiva.grupo.seccion',
            'responsable',
            'intervencionesDesc',
        ]);

        $responsables = User::role(['Administrador', 'Director', 'Coordinador Académico', 'Docente'])
            ->orderBy('name')->get();

        return view('admin.seguimiento_social.show', compact('caso', 'responsables'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, CasoSeguimiento $caso)
    {
        $data = $request->validate([
            'tipo'           => 'required|in:academico,social,familiar,conductual,otro',
            'descripcion'    => 'required|string|max:5000',
            'nivel_riesgo'   => 'required|in:bajo,medio,alto,critico',
            'estado'         => 'required|in:abierto,en_seguimiento,cerrado',
            'responsable_id' => 'nullable|exists:users,id',
            'fecha_apertura' => 'required|date',
            'fecha_cierre'   => 'nullable|date|after_or_equal:fecha_apertura',
        ]);

        $caso->update($data);

        return back()->with('success', 'Caso actualizado correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(CasoSeguimiento $caso)
    {
        $caso->delete();
        return redirect()
            ->route('admin.seguimiento-social.index')
            ->with('success', 'Caso eliminado.');
    }

    // ── Agregar intervención ──────────────────────────────────────────────────

    public function addIntervencion(Request $request, CasoSeguimiento $caso)
    {
        $data = $request->validate([
            'descripcion'       => 'required|string|max:5000',
            'tipo_intervencion' => 'required|in:reunion,llamada,visita,derivacion,otro',
            'fecha'             => 'required|date',
            'resultado'         => 'nullable|string|max:3000',
            'siguiente_accion'  => 'nullable|string|max:2000',
        ]);

        $data['caso_id'] = $caso->id;
        IntervencionCaso::create($data);

        // Actualizar estado a en_seguimiento si estaba abierto
        if ($caso->estado === 'abierto') {
            $caso->update(['estado' => 'en_seguimiento']);
        }

        return back()->with('success', 'Intervención registrada.');
    }

    // ── Cerrar caso ───────────────────────────────────────────────────────────

    public function cerrarCaso(Request $request, CasoSeguimiento $caso)
    {
        $data = $request->validate([
            'fecha_cierre' => 'required|date|after_or_equal:' . $caso->fecha_apertura->format('Y-m-d'),
        ]);

        $caso->load(['estudiante', 'responsable']);
        $caso->update([
            'estado'       => 'cerrado',
            'fecha_cierre' => $data['fecha_cierre'],
        ]);

        try {
            $nombre = $caso->estudiante?->nombre_completo ?? 'un estudiante';
            if ($caso->responsable_id) {
                Notificacion::enviar(
                    $caso->responsable_id,
                    'general',
                    '✅ Caso de seguimiento cerrado',
                    "El caso de seguimiento social de {$nombre} ha sido cerrado."
                );
            }
        } catch (\Throwable) {}

        return back()->with('success', 'Caso cerrado correctamente.');
    }

    // ── Lista Excel ───────────────────────────────────────────────────────────

    public function listaExcel(Request $request)
    {
        $query = CasoSeguimiento::with(['estudiante', 'responsable'])->latest();

        if ($request->filled('estado'))         $query->where('estado', $request->estado);
        if ($request->filled('tipo'))           $query->where('tipo', $request->tipo);
        if ($request->filled('nivel_riesgo'))   $query->where('nivel_riesgo', $request->nivel_riesgo);
        if ($request->filled('responsable_id')) $query->where('responsable_id', $request->responsable_id);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('estudiante', fn($s) =>
                $s->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
            );
        }

        $casos = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Seguimiento Social');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '7c3aed']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Casos de Seguimiento Social — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha Apertura', 'Estudiante', 'Tipo', 'Nivel Riesgo', 'Estado', 'Responsable'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($casos->values() as $i => $c) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $c->fecha_apertura?->format('d/m/Y') ?? '—');
            $ws->setCellValue("C{$row}", $c->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", ucfirst($c->tipo));
            $ws->setCellValue("E{$row}", ucfirst(str_replace('_', ' ', $c->nivel_riesgo)));
            $ws->setCellValue("F{$row}", ucfirst(str_replace('_', ' ', $c->estado)));
            $ws->setCellValue("G{$row}", $c->responsable?->name ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f5f3ff');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'seguimiento_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'seguimiento_social_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────────

    public function listaPdf(Request $request)
    {
        $query = CasoSeguimiento::with(['estudiante', 'responsable'])->latest();

        if ($request->filled('estado'))         $query->where('estado', $request->estado);
        if ($request->filled('tipo'))           $query->where('tipo', $request->tipo);
        if ($request->filled('nivel_riesgo'))   $query->where('nivel_riesgo', $request->nivel_riesgo);
        if ($request->filled('responsable_id')) $query->where('responsable_id', $request->responsable_id);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('estudiante', fn($s) =>
                $s->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
            );
        }

        $casos = $query->get();
        $inst  = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.seguimiento_social.lista_pdf',
            compact('casos', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('seguimiento_social_' . now()->format('Ymd') . '.pdf');
    }

    // ── Informe PDF ───────────────────────────────────────────────────────────

    public function informePdf(CasoSeguimiento $caso)
    {
        $caso->load([
            'estudiante.matriculaActiva.grupo.grado',
            'estudiante.matriculaActiva.grupo.seccion',
            'responsable',
            'intervenciones',
        ]);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.seguimiento_social.informe_pdf',
            compact('caso', 'inst')
        )->setPaper('letter', 'portrait');

        $nombre = 'caso_seguimiento_' . $caso->id . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
