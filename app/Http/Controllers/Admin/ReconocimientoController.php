<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\Notificacion;
use App\Models\Reconocimiento;
use App\Models\TipoReconocimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReconocimientoController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $total      = Reconocimiento::count();
        $entregados = Reconocimiento::entregados()->count();
        $pendientes = Reconocimiento::pendientes()->count();
        $esteMes    = Reconocimiento::whereMonth('fecha', now()->month)
                          ->whereYear('fecha', now()->year)->count();

        // Por tipo
        $porTipo = TipoReconocimiento::withCount('reconocimientos')
            ->orderByDesc('reconocimientos_count')
            ->get();

        // Estudiantes con más reconocimientos
        $topEstudiantes = Reconocimiento::with('estudiante')
            ->selectRaw('estudiante_id, count(*) as total_rec')
            ->groupBy('estudiante_id')
            ->orderByDesc('total_rec')
            ->limit(5)
            ->get();

        // Últimos reconocimientos
        $recientes = Reconocimiento::with(['estudiante', 'tipo', 'emitidoPor'])
            ->latest('fecha')
            ->limit(8)
            ->get();

        $tipos = TipoReconocimiento::all();

        return view('admin.reconocimientos.dashboard', compact(
            'total', 'entregados', 'pendientes', 'esteMes',
            'porTipo', 'topEstudiantes', 'recientes', 'tipos'
        ));
    }

    // ── Index / listado ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Reconocimiento::with(['estudiante', 'tipo', 'emitidoPor']);

        if ($request->filled('tipo_id')) {
            $query->where('tipo_id', $request->tipo_id);
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->filled('entregado')) {
            $query->where('entregado', $request->entregado === '1');
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) =>
                       $s->where('nombres', 'like', "%{$q}%")
                         ->orWhere('apellidos', 'like', "%{$q}%")
                   );
            });
        }

        $reconocimientos = $query->latest('fecha')->paginate(25)->withQueryString();

        $tipos      = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        // Totales rápidos
        $totalGeneral  = Reconocimiento::count();
        $totalEntregados = Reconocimiento::entregados()->count();
        $totalPendientes = Reconocimiento::pendientes()->count();

        return view('admin.reconocimientos.index', compact(
            'reconocimientos', 'tipos', 'estudiantes',
            'totalGeneral', 'totalEntregados', 'totalPendientes'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create()
    {
        $tipos       = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        return view('admin.reconocimientos.create', compact('tipos', 'estudiantes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_id'       => 'required|exists:tipos_reconocimiento,id',
            'titulo'        => 'required|string|max:160',
            'descripcion'   => 'nullable|string|max:1000',
            'fecha'         => 'required|date',
        ]);

        $data['emitido_por_id'] = auth()->id();
        $data['entregado']      = false;

        $rec = Reconocimiento::create($data);

        try {
            $rec->load(['estudiante.representantes', 'tipo']);
            $nombre  = $rec->estudiante?->nombre_completo ?? 'el estudiante';
            $titulo  = '🏆 Reconocimiento otorgado';
            $mensaje = "Se ha otorgado un reconocimiento a {$nombre}: {$rec->titulo}";
            if ($rec->estudiante?->user_id) {
                Notificacion::enviar($rec->estudiante->user_id, 'academica', $titulo, $mensaje);
            }
            foreach ($rec->estudiante?->representantes ?? [] as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar($rep->user_id, 'academica', $titulo, $mensaje);
                }
            }
        } catch (\Throwable) {}

        return redirect()->route('admin.reconocimientos.index')
                         ->with('success', 'Reconocimiento registrado correctamente.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(Reconocimiento $reconocimiento)
    {
        $tipos       = TipoReconocimiento::orderBy('nombre')->get();
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        return view('admin.reconocimientos.create', compact('reconocimiento', 'tipos', 'estudiantes'));
    }

    public function update(Request $request, Reconocimiento $reconocimiento)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_id'       => 'required|exists:tipos_reconocimiento,id',
            'titulo'        => 'required|string|max:160',
            'descripcion'   => 'nullable|string|max:1000',
            'fecha'         => 'required|date',
        ]);

        $reconocimiento->update($data);

        return redirect()->route('admin.reconocimientos.index')
                         ->with('success', 'Reconocimiento actualizado.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Reconocimiento $reconocimiento)
    {
        $reconocimiento->delete();

        return back()->with('success', 'Reconocimiento eliminado.');
    }

    // ── Marcar como entregado (PATCH) ─────────────────────────────────────

    public function marcarEntregado(Reconocimiento $reconocimiento)
    {
        $reconocimiento->update([
            'entregado'     => true,
            'fecha_entrega' => now()->toDateString(),
        ]);

        try {
            $reconocimiento->load(['estudiante.representantes']);
            $nombre  = $reconocimiento->estudiante?->nombre_completo ?? 'el estudiante';
            $titulo  = '🎖️ Reconocimiento entregado';
            $mensaje = "El reconocimiento «{$reconocimiento->titulo}» fue entregado a {$nombre}.";
            if ($reconocimiento->estudiante?->user_id) {
                Notificacion::enviar($reconocimiento->estudiante->user_id, 'academica', $titulo, $mensaje);
            }
            foreach ($reconocimiento->estudiante?->representantes ?? [] as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar($rep->user_id, 'academica', $titulo, $mensaje);
                }
            }
        } catch (\Throwable) {}

        return back()->with('success', 'Reconocimiento marcado como entregado.');
    }

    // ── Diploma PDF ───────────────────────────────────────────────────────

    public function diplomaPdf(Reconocimiento $reconocimiento)
    {
        $reconocimiento->load(['estudiante', 'tipo', 'emitidoPor']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('director_nombre', '');
        $cod  = ConfigInstitucional::get('codigo_centro', '');
        $logo = ConfigInstitucional::get('logo', null);

        $logoUrl = $logo ? public_path('storage/' . $logo) : null;
        if ($logoUrl && !file_exists($logoUrl)) {
            $logoUrl = null;
        }

        $pdf = Pdf::loadView('admin.reconocimientos.diploma_pdf', compact(
            'reconocimiento', 'inst', 'dir', 'cod', 'logoUrl'
        ))->setPaper('letter', 'landscape');

        $nombre = 'diploma_' . str($reconocimiento->estudiante->apellidos)->slug() . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }

    public function listaExcel(Request $request)
    {
        $query = Reconocimiento::with(['estudiante', 'tipo', 'emitidoPor']);

        if ($request->filled('tipo_id'))       $query->where('tipo_id', $request->tipo_id);
        if ($request->filled('estudiante_id')) $query->where('estudiante_id', $request->estudiante_id);
        if ($request->filled('entregado'))     $query->where('entregado', $request->entregado === '1');

        $recs = $query->latest('fecha')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Reconocimientos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'b45309']],
        ];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Lista de Reconocimientos — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Fecha', 'Estudiante', 'Tipo', 'Título', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($recs as $i => $r) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $r->fecha?->format('d/m/Y') ?? '—');
            $ws->setCellValue("C{$row}", $r->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", $r->tipo?->nombre ?? '—');
            $ws->setCellValue("E{$row}", $r->titulo ?? '—');
            $ws->setCellValue("F{$row}", $r->entregado ? 'Entregado' : 'Pendiente');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fffbeb');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rec_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'reconocimientos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = Reconocimiento::with(['estudiante', 'tipo', 'emitidoPor']);

        if ($request->filled('tipo_id'))       $query->where('tipo_id', $request->tipo_id);
        if ($request->filled('estudiante_id')) $query->where('estudiante_id', $request->estudiante_id);
        if ($request->filled('entregado'))     $query->where('entregado', $request->entregado === '1');

        $recs = $query->latest('fecha')->get();
        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView('admin.reconocimientos.lista_pdf', compact('recs', 'inst'))
            ->setPaper('letter', 'landscape');

        return $pdf->download('reconocimientos_' . now()->format('Ymd') . '.pdf');
    }

    // ── Historial por Estudiante ──────────────────────────────────────────

    public function historialEstudiante(Estudiante $estudiante)
    {
        $reconocimientos = Reconocimiento::with(['tipo', 'emitidoPor'])
            ->where('estudiante_id', $estudiante->id)
            ->latest('fecha')
            ->get();

        $tipos = TipoReconocimiento::withCount(['reconocimientos as total' => function ($q) use ($estudiante) {
            $q->where('estudiante_id', $estudiante->id);
        }])->having('total', '>', 0)->get();

        return view('admin.reconocimientos.historial_estudiante', compact(
            'estudiante', 'reconocimientos', 'tipos'
        ));
    }
}
