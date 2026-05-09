<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\FichaSalud;
use App\Models\IncidenteMedico;
use Illuminate\Http\Request;

class SaludController extends Controller
{
    // ── Ficha de Salud ────────────────────────────────────────────────────

    /**
     * Mostrar / editar ficha de salud de un estudiante.
     */
    public function ficha(Estudiante $estudiante)
    {
        $ficha      = FichaSalud::firstOrNew(['estudiante_id' => $estudiante->id]);
        $tiposSangre = FichaSalud::TIPOS_SANGRE;
        $incidentes  = $estudiante->incidentesMedicos()
                                  ->latest('fecha')
                                  ->take(10)
                                  ->get();

        return view('admin.salud.ficha', compact('estudiante', 'ficha', 'tiposSangre', 'incidentes'));
    }

    /**
     * Guardar (crear o actualizar) ficha de salud.
     */
    public function guardarFicha(Request $request, Estudiante $estudiante)
    {
        $data = $request->validate([
            'tipo_sangre'         => 'nullable|string|max:5',
            'alergias'            => 'nullable|string|max:2000',
            'condiciones_medicas' => 'nullable|string|max:2000',
            'medicamentos'        => 'nullable|string|max:2000',
            'contacto_emergencia' => 'nullable|string|max:150',
            'telefono_emergencia' => 'nullable|string|max:30',
            'seguro_medico'       => 'nullable|string|max:100',
            'num_seguro'          => 'nullable|string|max:60',
        ]);

        FichaSalud::updateOrCreate(
            ['estudiante_id' => $estudiante->id],
            $data
        );

        return redirect()
            ->route('admin.salud.ficha', $estudiante)
            ->with('success', 'Ficha de salud actualizada correctamente.');
    }

    // ── Incidentes Médicos ────────────────────────────────────────────────

    /**
     * Listado de incidentes con filtros.
     */
    public function incidentes(Request $request)
    {
        $query = IncidenteMedico::with('estudiante')->latest('fecha');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('descripcion', 'like', "%{$q}%")
                   ->orWhere('accion_tomada', 'like', "%{$q}%")
                   ->orWhere('remitido_a', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) =>
                       $s->where('nombres', 'like', "%{$q}%")
                         ->orWhere('apellidos', 'like', "%{$q}%")
                   );
            });
        }

        $incidentes  = $query->paginate(25)->withQueryString();
        $tipos       = IncidenteMedico::TIPOS;
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        // Conteos por tipo para tarjetas resumen
        $conteosTipo = IncidenteMedico::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return view('admin.salud.incidentes', compact(
            'incidentes', 'tipos', 'estudiantes', 'conteosTipo'
        ));
    }

    /**
     * Formulario para registrar un incidente.
     */
    public function crearIncidente(Request $request)
    {
        $estudianteId = $request->estudiante_id;
        $estudiante   = $estudianteId ? Estudiante::find($estudianteId) : null;
        $estudiantes  = Estudiante::activos()->orderBy('apellidos')->get();
        $tipos        = IncidenteMedico::TIPOS;

        return view('admin.salud.incidente_create', compact('estudiantes', 'tipos', 'estudiante'));
    }

    /**
     * Almacenar incidente médico.
     */
    public function guardarIncidente(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'fecha'         => 'required|date',
            'tipo'          => 'required|in:accidente,enfermedad,alergia,otro',
            'descripcion'   => 'required|string|max:3000',
            'accion_tomada' => 'required|string|max:3000',
            'remitido_a'    => 'nullable|string|max:150',
        ]);

        IncidenteMedico::create($data);

        return redirect()
            ->route('admin.salud.incidentes')
            ->with('success', 'Incidente médico registrado correctamente.');
    }

    /**
     * Eliminar un incidente médico.
     */
    public function eliminarIncidente(IncidenteMedico $incidente)
    {
        $incidente->delete();

        return back()->with('success', 'Incidente eliminado.');
    }

    // ── Excel Incidentes ─────────────────────────────────────────────────

    public function incidentesExcel(Request $request)
    {
        $query = IncidenteMedico::with('estudiante')->latest('fecha');

        if ($request->filled('tipo'))          $query->where('tipo', $request->tipo);
        if ($request->filled('estudiante_id')) $query->where('estudiante_id', $request->estudiante_id);
        if ($request->filled('fecha_desde'))   $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))   $query->whereDate('fecha', '<=', $request->fecha_hasta);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) =>
                $sq->where('descripcion', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) =>
                       $s->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
                   )
            );
        }

        $incidentes = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Incidentes Médicos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => 'b45309']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Incidentes Médicos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha', 'Estudiante', 'Tipo', 'Descripción', 'Acción Tomada', 'Remitido A'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($incidentes->values() as $i => $inc) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $inc->fecha?->format('d/m/Y') ?? '—');
            $ws->setCellValue("C{$row}", $inc->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", ucfirst($inc->tipo));
            $ws->setCellValue("E{$row}", $inc->descripcion);
            $ws->setCellValue("F{$row}", $inc->accion_tomada);
            $ws->setCellValue("G{$row}", $inc->remitido_a ?? '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fef3c7');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'incidentes_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'incidentes_medicos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF Lista de Incidentes ───────────────────────────────────────────

    public function incidentesPdf(Request $request)
    {
        $query = IncidenteMedico::with('estudiante')->latest('fecha');

        if ($request->filled('tipo'))          $query->where('tipo', $request->tipo);
        if ($request->filled('estudiante_id')) $query->where('estudiante_id', $request->estudiante_id);
        if ($request->filled('fecha_desde'))   $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))   $query->whereDate('fecha', '<=', $request->fecha_hasta);

        $incidentes = $query->get();
        $tipos      = IncidenteMedico::TIPOS;
        $inst       = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.salud.incidentes_pdf',
            compact('incidentes', 'tipos', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('incidentes_medicos_' . now()->format('Ymd') . '.pdf');
    }

    // ── PDF Ficha Médica ──────────────────────────────────────────────────

    /**
     * Generar PDF de la ficha médica completa del estudiante.
     */
    public function fichaPdf(Estudiante $estudiante)
    {
        $ficha      = FichaSalud::where('estudiante_id', $estudiante->id)->first();
        $incidentes = $estudiante->incidentesMedicos()->latest('fecha')->get();
        $inst       = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config     = ConfigInstitucional::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.salud.ficha_pdf',
            compact('estudiante', 'ficha', 'incidentes', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $nombre = 'ficha_salud_' . str_replace([' ', ','], '_', strtolower($estudiante->nombre_completo))
                  . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
