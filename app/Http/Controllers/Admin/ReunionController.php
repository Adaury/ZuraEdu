<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcuerdoReunion;
use App\Models\ConfigInstitucional;
use App\Models\Notificacion;
use App\Models\Reunion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReunionController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────
    public function dashboard()
    {
        $total       = Reunion::count();
        $programadas = Reunion::where('estado', 'programada')->count();
        $realizadas  = Reunion::where('estado', 'realizada')->count();
        $canceladas  = Reunion::where('estado', 'cancelada')->count();

        $esteMes  = Reunion::whereMonth('fecha', now()->month)
                           ->whereYear('fecha', now()->year)->count();

        // Por tipo
        $porTipo = collect(Reunion::tiposLabel())->mapWithKeys(function ($label, $tipo) {
            return [$tipo => Reunion::where('tipo', $tipo)->count()];
        })->filter(fn($cnt) => $cnt > 0)->sortDesc();

        // Acuerdos pendientes (no cumplidos)
        $acuerdosPendientes = AcuerdoReunion::where('cumplido', false)->count();
        $acuerdosVencidos   = AcuerdoReunion::where('cumplido', false)
            ->where('fecha_limite', '<', now()->toDateString())
            ->whereNotNull('fecha_limite')
            ->count();

        // Próximas reuniones programadas
        $proximas = Reunion::with('convocante')
            ->where('estado', 'programada')
            ->where('fecha', '>=', now())
            ->orderBy('fecha')
            ->limit(5)
            ->get();

        // Últimas reuniones
        $recientes = Reunion::with('convocante')
            ->withCount('acuerdos')
            ->latest('fecha')
            ->limit(8)
            ->get();

        return view('admin.reuniones.dashboard', compact(
            'total', 'programadas', 'realizadas', 'canceladas', 'esteMes',
            'porTipo', 'acuerdosPendientes', 'acuerdosVencidos',
            'proximas', 'recientes'
        ));
    }

    // ── Index ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Reunion::with('convocante')->latest('fecha');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhere('lugar', 'like', "%{$q}%");
            });
        }

        $reuniones = $query->paginate(20)->withQueryString();
        $tipos     = Reunion::tiposLabel();
        $estados   = Reunion::estadosLabel();

        return view('admin.reuniones.index', compact('reuniones', 'tipos', 'estados'));
    }

    // ── Create ────────────────────────────────────────────────────────────
    public function create()
    {
        $tipos        = Reunion::tiposLabel();
        $estados      = Reunion::estadosLabel();
        $convocantes  = User::where('activo', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.reuniones.create', compact('tipos', 'estados', 'convocantes'));
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:consejo_directivo,reunion_padres,reunion_docentes,comite,otra',
            'fecha'         => 'required|date',
            'lugar'         => 'nullable|string|max:255',
            'convocante_id' => 'nullable|exists:users,id',
            'agenda'        => 'nullable|string',
            'participantes' => 'nullable|string',
            'estado'        => 'required|in:programada,realizada,cancelada',
        ]);

        $reunion = Reunion::create($data);

        try {
            $fecha   = $reunion->fecha->format('d/m/Y');
            $titulo  = "📅 Nueva reunión programada: {$reunion->titulo}";
            $mensaje = "Se ha programado una reunión el {$fecha}" . ($reunion->lugar ? " en {$reunion->lugar}" : '') . '.';

            $roles = match ($reunion->tipo) {
                'reunion_padres'    => ['Padre', 'Representante'],
                'reunion_docentes'  => ['Docente'],
                'consejo_directivo' => ['Admin', 'Director'],
                default             => ['Admin', 'Director', 'Docente'],
            };

            $userIds = User::role($roles)->where('activo', true)->pluck('id')->toArray();
            if (!empty($userIds)) {
                Notificacion::enviarA($userIds, 'general', $titulo, $mensaje, ['reunion_id' => $reunion->id]);
            }
        } catch (\Throwable) {}

        return redirect()
            ->route('admin.reuniones.show', $reunion)
            ->with('success', 'Reunión creada correctamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────
    public function show(Reunion $reunion)
    {
        $reunion->load(['convocante', 'acuerdos']);

        return view('admin.reuniones.show', compact('reunion'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    public function edit(Reunion $reunion)
    {
        $tipos       = Reunion::tiposLabel();
        $estados     = Reunion::estadosLabel();
        $convocantes = User::where('activo', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.reuniones.create', compact('reunion', 'tipos', 'estados', 'convocantes'));
    }

    // ── Update ────────────────────────────────────────────────────────────
    public function update(Request $request, Reunion $reunion)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:consejo_directivo,reunion_padres,reunion_docentes,comite,otra',
            'fecha'         => 'required|date',
            'lugar'         => 'nullable|string|max:255',
            'convocante_id' => 'nullable|exists:users,id',
            'agenda'        => 'nullable|string',
            'participantes' => 'nullable|string',
            'estado'        => 'required|in:programada,realizada,cancelada',
        ]);

        $reunion->update($data);

        return redirect()
            ->route('admin.reuniones.show', $reunion)
            ->with('success', 'Reunión actualizada correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Reunion $reunion)
    {
        $reunion->delete();

        return redirect()
            ->route('admin.reuniones.index')
            ->with('success', 'Reunión eliminada.');
    }

    // ── Agregar acuerdo (POST) ────────────────────────────────────────────
    public function addAcuerdo(Request $request, Reunion $reunion)
    {
        $data = $request->validate([
            'descripcion'  => 'required|string',
            'responsable'  => 'nullable|string|max:255',
            'fecha_limite' => 'nullable|date',
        ]);

        $data['reunion_id'] = $reunion->id;
        $data['cumplido']   = false;

        AcuerdoReunion::create($data);

        return back()->with('success', 'Acuerdo registrado.');
    }

    // ── Toggle cumplido (PATCH) ───────────────────────────────────────────
    public function toggleCumplido(AcuerdoReunion $acuerdo)
    {
        $acuerdo->update(['cumplido' => !$acuerdo->cumplido]);

        return back()->with('success', $acuerdo->cumplido ? 'Marcado como cumplido.' : 'Marcado como pendiente.');
    }

    // ── Lista Excel ───────────────────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $query = Reunion::with('convocante')->latest('fecha');

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(fn($sq) =>
                $sq->where('titulo', 'like', "%{$q}%")->orWhere('lugar', 'like', "%{$q}%")
            );
        }

        $reuniones = $query->get();
        $tipos     = Reunion::tiposLabel();
        $estados   = Reunion::estadosLabel();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Reuniones');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1e40af']],
        ];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Lista de Reuniones — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['Fecha', 'Título', 'Tipo', 'Lugar', 'Convocante', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($reuniones->values() as $i => $r) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $r->fecha?->format('d/m/Y') ?? '—');
            $ws->setCellValue("B{$row}", $r->titulo);
            $ws->setCellValue("C{$row}", $tipos[$r->tipo] ?? $r->tipo);
            $ws->setCellValue("D{$row}", $r->lugar ?? '—');
            $ws->setCellValue("E{$row}", $r->convocante?->name ?? '—');
            $ws->setCellValue("F{$row}", $estados[$r->estado] ?? $r->estado);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'reuniones_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'reuniones_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = Reunion::with('convocante')->latest('fecha');

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(fn($sq) =>
                $sq->where('titulo', 'like', "%{$q}%")->orWhere('lugar', 'like', "%{$q}%")
            );
        }

        $reuniones = $query->get();
        $tipos     = Reunion::tiposLabel();
        $estados   = Reunion::estadosLabel();
        $inst      = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.reuniones.lista_pdf',
            compact('reuniones', 'tipos', 'estados', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('reuniones_' . now()->format('Ymd') . '.pdf');
    }

    // ── PDF del Acta ──────────────────────────────────────────────────────
    public function actaPdf(Reunion $reunion)
    {
        $reunion->load(['convocante', 'acuerdos']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('nombre_director', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.reuniones.acta_pdf',
            compact('reunion', 'inst', 'dir')
        )->setPaper('letter', 'portrait');

        $slug = Str::slug($reunion->titulo ?? 'acta');
        return $pdf->download("acta_reunion_{$slug}.pdf");
    }
}
