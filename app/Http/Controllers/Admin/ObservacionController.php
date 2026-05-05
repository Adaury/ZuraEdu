<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Notificacion;
use App\Models\Observacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $user       = auth()->user();
        $isDocente  = $user->hasRole('Docente');

        $query = Observacion::with([
            'docente', 'estudiante', 'asignacion.asignatura', 'asignacion.grupo', 'periodo',
        ]);

        // Si es docente, solo ve las suyas
        if ($isDocente) {
            $docente = Docente::where('user_id', $user->id)->first();
            if ($docente) {
                $query->where('docente_id', $docente->id);
            }
        }

        // Filtrar por año escolar (vía asignación)
        if ($schoolYear) {
            $query->whereHas('asignacion', fn($q) => $q->where('school_year_id', $schoolYear->id));
        }

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('docente_id')) {
            $query->where('docente_id', $request->docente_id);
        }
        if ($request->filled('grupo_id')) {
            $query->whereHas('asignacion', fn($q) => $q->where('grupo_id', $request->grupo_id));
        }
        if ($request->filled('privada')) {
            $query->where('privada', $request->privada === '1');
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('texto', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) => $s->where('nombres', 'like', "%{$q}%")
                       ->orWhere('apellidos', 'like', "%{$q}%"));
            });
        }

        $observaciones = $query->latest()->paginate(25)->withQueryString();

        // Para los filtros
        $docentes = $isDocente
            ? collect()
            : Docente::activos()->orderBy('apellidos')->get();

        $grupos = Grupo::when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->with(['grado', 'seccion'])
            ->get();

        $tipos = Observacion::TIPOS;

        // Totales por tipo
        $baseQuery = clone $query;
        $totalesTipo = Observacion::selectRaw('tipo, count(*) as total')
            ->when($schoolYear, fn($q) => $q->whereHas('asignacion',
                fn($s) => $s->where('school_year_id', $schoolYear->id)
            ))
            ->when($isDocente && isset($docente), fn($q) => $q->where('docente_id', $docente->id))
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return view('admin.observaciones.index', compact(
            'observaciones', 'docentes', 'grupos', 'tipos',
            'totalesTipo', 'schoolYear', 'isDocente'
        ));
    }

    public function destroy(Observacion $observacion)
    {
        $user      = auth()->user();
        $isDocente = $user->hasRole('Docente');

        // Solo el docente que la creó o un admin/director puede eliminar
        if ($isDocente) {
            $docente = Docente::where('user_id', $user->id)->first();
            if (!$docente || $observacion->docente_id !== $docente->id) {
                abort(403);
            }
        }

        $observacion->delete();
        return back()->with('success', 'Observación eliminada.');
    }

    public function togglePrivada(Observacion $observacion)
    {
        $user      = auth()->user();
        $isDocente = $user->hasRole('Docente');

        if ($isDocente) {
            $docente = Docente::where('user_id', $user->id)->first();
            if (!$docente || $observacion->docente_id !== $docente->id) abort(403);
        }

        $eraPrivada = $observacion->privada;
        $observacion->update(['privada' => !$observacion->privada]);

        // Si pasó de privada a pública, notificar al estudiante/representante
        if ($eraPrivada && !$observacion->privada) {
            try {
                $observacion->load(['estudiante.representantes', 'asignacion.asignatura']);
                $ti     = $observacion->tipo_info;
                $titulo = "Observación de docente: {$ti['label']}";
                $msg    = $observacion->texto;

                if ($observacion->estudiante?->user_id) {
                    Notificacion::enviar($observacion->estudiante->user_id, 'observacion', $titulo, $msg);
                }
                foreach ($observacion->estudiante?->representantes ?? [] as $rep) {
                    if ($rep->user_id) {
                        Notificacion::enviar($rep->user_id, 'observacion', $titulo, $msg);
                    }
                }
            } catch (\Throwable) {}
        }

        return back()->with('success', $observacion->privada ? 'Observación marcada como privada.' : 'Observación ahora es visible para representantes.');
    }

    // ── Exportar observaciones PDF ──────────────────────────────────────
    public function pdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $obs        = $this->buildQuery($request, $schoolYear)->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.observaciones.observaciones_pdf',
            compact('obs', 'schoolYear', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('observaciones_' . now()->format('Ymd') . '.pdf');
    }

    // ── Exportar observaciones Excel ─────────────────────────────────────
    public function excel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $obs = $this->buildQuery($request, $schoolYear)->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Observaciones');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['#', 'Fecha', 'Estudiante', 'Docente', 'Asignatura', 'Tipo', 'Privada', 'Texto'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '1';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($hdrStyle);

        foreach ($obs as $i => $o) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $o->created_at?->format('d/m/Y') ?? '');
            $sheet->setCellValue("C{$row}", $o->estudiante?->nombre_completo ?? '');
            $sheet->setCellValue("D{$row}", $o->docente?->nombre_completo ?? '');
            $sheet->setCellValue("E{$row}", $o->asignacion?->asignatura?->nombre ?? '');
            $sheet->setCellValue("F{$row}", $o->tipo_info['label'] ?? $o->tipo);
            $sheet->setCellValue("G{$row}", $o->privada ? 'Sí' : 'No');
            $sheet->setCellValue("H{$row}", $o->texto ?? '');
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }
        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'obs_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'observaciones_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function buildQuery(Request $request, $schoolYear)
    {
        $user      = auth()->user();
        $isDocente = $user->hasRole('Docente');

        $query = Observacion::with(['docente', 'estudiante', 'asignacion.asignatura', 'periodo']);

        if ($isDocente) {
            $docente = Docente::where('user_id', $user->id)->first();
            if ($docente) $query->where('docente_id', $docente->id);
        }

        if ($schoolYear) {
            $query->whereHas('asignacion', fn($q) => $q->where('school_year_id', $schoolYear->id));
        }
        if ($request->filled('tipo'))      $query->where('tipo', $request->tipo);
        if ($request->filled('docente_id'))$query->where('docente_id', $request->docente_id);
        if ($request->filled('grupo_id'))  $query->whereHas('asignacion', fn($q) => $q->where('grupo_id', $request->grupo_id));
        if ($request->filled('privada'))   $query->where('privada', $request->privada === '1');

        return $query->latest();
    }
}
