<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\EstudianteRuta;
use App\Models\ParadaRuta;
use App\Models\RutaTransporte;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    // ── DASHBOARD ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $totalRutas    = RutaTransporte::count();
        $rutasActivas  = RutaTransporte::activos()->count();
        $totalParadas  = ParadaRuta::count();
        $totalInscritos = EstudianteRuta::distinct('estudiante_id')->count('estudiante_id');

        // Capacidad total vs ocupada
        $capacidadTotal = RutaTransporte::activos()->sum('capacidad');
        $ocupacionTotal = EstudianteRuta::whereHas('ruta', fn($q) => $q->where('activo', true))->count();
        $pctOcupacion   = $capacidadTotal > 0 ? round($ocupacionTotal / $capacidadTotal * 100) : 0;

        // Por tipo de servicio
        $porTipo = EstudianteRuta::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        // Rutas con su ocupación
        $rutas = RutaTransporte::withCount('estudiantesRuta')
            ->activos()
            ->orderByDesc('estudiantes_ruta_count')
            ->limit(8)
            ->get();

        // Rutas con disponibilidad baja (>= 80% ocupadas)
        $rutasCriticas = $rutas->filter(function ($r) {
            return $r->capacidad > 0 && ($r->estudiantes_ruta_count / $r->capacidad) >= 0.8;
        });

        return view('admin.transporte.dashboard', compact(
            'totalRutas', 'rutasActivas', 'totalParadas', 'totalInscritos',
            'capacidadTotal', 'ocupacionTotal', 'pctOcupacion',
            'porTipo', 'rutas', 'rutasCriticas'
        ));
    }

    // ── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = RutaTransporte::withCount('estudiantesRuta');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('conductor', 'like', "%{$q}%")
                   ->orWhere('vehiculo', 'like', "%{$q}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $rutas = $query->orderBy('nombre')->paginate(20)->withQueryString();

        return view('admin.transporte.index', compact('rutas'));
    }

    // ── CREATE / STORE ───────────────────────────────────────────────────────

    public function create()
    {
        return view('admin.transporte.create', ['ruta' => new RutaTransporte()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'             => 'required|string|max:120',
            'descripcion'        => 'nullable|string|max:500',
            'conductor'          => 'nullable|string|max:120',
            'telefono_conductor' => 'nullable|string|max:30',
            'vehiculo'           => 'nullable|string|max:120',
            'capacidad'          => 'required|integer|min:1|max:200',
            'activo'             => 'boolean',
            'horario_salida'     => 'nullable|date_format:H:i',
            'horario_regreso'    => 'nullable|date_format:H:i',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $ruta = RutaTransporte::create($data);

        return redirect()->route('admin.transporte.show', $ruta)
                         ->with('success', 'Ruta creada correctamente.');
    }

    // ── SHOW ─────────────────────────────────────────────────────────────────

    public function show(Request $request, RutaTransporte $ruta)
    {
        $ruta->load(['paradas', 'estudiantesRuta.estudiante', 'estudiantesRuta.parada']);

        // Buscador de estudiantes para asignar
        $busqueda  = $request->input('buscar_estudiante');
        $candidatos = collect();

        if ($busqueda) {
            $asignados = $ruta->estudiantesRuta->pluck('estudiante_id');
            $candidatos = Estudiante::activos()
                ->where(function ($q) use ($busqueda) {
                    $q->where('nombres', 'like', "%{$busqueda}%")
                      ->orWhere('apellidos', 'like', "%{$busqueda}%")
                      ->orWhere('numero_matricula', 'like', "%{$busqueda}%");
                })
                ->whereNotIn('id', $asignados)
                ->orderBy('apellidos')
                ->limit(15)
                ->get();
        }

        return view('admin.transporte.show', compact('ruta', 'candidatos', 'busqueda'));
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────────────────

    public function edit(RutaTransporte $ruta)
    {
        return view('admin.transporte.create', compact('ruta'));
    }

    public function update(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'nombre'             => 'required|string|max:120',
            'descripcion'        => 'nullable|string|max:500',
            'conductor'          => 'nullable|string|max:120',
            'telefono_conductor' => 'nullable|string|max:30',
            'vehiculo'           => 'nullable|string|max:120',
            'capacidad'          => 'required|integer|min:1|max:200',
            'activo'             => 'boolean',
            'horario_salida'     => 'nullable|date_format:H:i',
            'horario_regreso'    => 'nullable|date_format:H:i',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $ruta->update($data);

        return redirect()->route('admin.transporte.show', $ruta)
                         ->with('success', 'Ruta actualizada correctamente.');
    }

    // ── DESTROY ──────────────────────────────────────────────────────────────

    public function destroy(RutaTransporte $ruta)
    {
        $ruta->delete();

        return redirect()->route('admin.transporte.index')
                         ->with('success', 'Ruta eliminada.');
    }

    // ── PARADAS (CRUD inline) ────────────────────────────────────────────────

    public function storeParada(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:120',
            'hora_estimada' => 'nullable|date_format:H:i',
        ]);

        $maxOrden = $ruta->paradas()->max('orden') ?? 0;
        $data['ruta_id'] = $ruta->id;
        $data['orden']   = $maxOrden + 1;

        ParadaRuta::create($data);

        return back()->with('success', 'Parada agregada.');
    }

    public function updateParada(Request $request, RutaTransporte $ruta, ParadaRuta $parada)
    {
        abort_if($parada->ruta_id !== $ruta->id, 404);

        $data = $request->validate([
            'nombre'        => 'required|string|max:120',
            'hora_estimada' => 'nullable|date_format:H:i',
            'orden'         => 'required|integer|min:1',
        ]);

        $parada->update($data);

        return back()->with('success', 'Parada actualizada.');
    }

    public function destroyParada(RutaTransporte $ruta, ParadaRuta $parada)
    {
        abort_if($parada->ruta_id !== $ruta->id, 404);
        $parada->delete();

        return back()->with('success', 'Parada eliminada.');
    }

    public function reordenarParadas(Request $request, RutaTransporte $ruta)
    {
        $request->validate(['orden' => 'required|array', 'orden.*' => 'integer']);

        foreach ($request->orden as $pos => $paradaId) {
            ParadaRuta::where('id', $paradaId)
                      ->where('ruta_id', $ruta->id)
                      ->update(['orden' => $pos + 1]);
        }

        return response()->json(['ok' => true]);
    }

    // ── ASIGNAR / DESASIGNAR ESTUDIANTE ──────────────────────────────────────

    public function asignarEstudiante(Request $request, RutaTransporte $ruta)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo'          => 'required|in:ida,vuelta,ambos',
            'parada_id'     => 'nullable|exists:paradas_ruta,id',
        ]);

        // Verificar que el estudiante no esté ya asignado a esta ruta
        $existe = EstudianteRuta::where('ruta_id', $ruta->id)
                                ->where('estudiante_id', $data['estudiante_id'])
                                ->exists();

        if ($existe) {
            return back()->with('error', 'El estudiante ya está asignado a esta ruta.');
        }

        // Verificar capacidad
        if ($ruta->ocupacion >= $ruta->capacidad) {
            return back()->with('error', 'La ruta está a plena capacidad.');
        }

        EstudianteRuta::create([
            'ruta_id'       => $ruta->id,
            'estudiante_id' => $data['estudiante_id'],
            'tipo'          => $data['tipo'],
            'parada_id'     => $data['parada_id'] ?: null,
        ]);

        return back()->with('success', 'Estudiante asignado a la ruta.');
    }

    public function desasignarEstudiante(RutaTransporte $ruta, EstudianteRuta $asignacion)
    {
        abort_if($asignacion->ruta_id !== $ruta->id, 404);
        $asignacion->delete();

        return back()->with('success', 'Estudiante removido de la ruta.');
    }

    // ── EXCEL LISTA DE RUTAS ────────────────────────────────────────────────────

    public function listaExcel(Request $request)
    {
        $query = RutaTransporte::withCount('estudiantesRuta');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")
                ->orWhere('conductor', 'like', "%{$q}%")
                ->orWhere('vehiculo', 'like', "%{$q}%"));
        }

        if ($request->filled('activo')) $query->where('activo', $request->activo === '1');

        $rutas = $query->orderBy('nombre')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Rutas Transporte');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f4c81']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Rutas de Transporte Escolar — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Conductor', 'Vehículo', 'Capacidad', 'Pasajeros', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        foreach ($rutas->values() as $i => $ruta) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $ruta->nombre);
            $ws->setCellValue("C{$row}", $ruta->conductor ?? '—');
            $ws->setCellValue("D{$row}", $ruta->vehiculo ?? '—');
            $ws->setCellValue("E{$row}", $ruta->capacidad);
            $ws->setCellValue("F{$row}", $ruta->estudiantes_ruta_count);
            $ws->setCellValue("G{$row}", $ruta->activo ? 'Activa' : 'Inactiva');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rutas_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'rutas_transporte_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF LISTA DE RUTAS ───────────────────────────────────────────────────

    public function listaPdf(Request $request)
    {
        $query = RutaTransporte::withCount('estudiantesRuta');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")
                ->orWhere('conductor', 'like', "%{$q}%")
                ->orWhere('vehiculo', 'like', "%{$q}%"));
        }

        if ($request->filled('activo')) $query->where('activo', $request->activo === '1');

        $rutas = $query->orderBy('nombre')->get();
        $inst  = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.transporte.lista_pdf',
            compact('rutas', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('rutas_transporte_' . now()->format('Ymd') . '.pdf');
    }

    // ── EXCEL PASAJEROS ──────────────────────────────────────────────────────

    public function pasajerosExcel(RutaTransporte $ruta)
    {
        $ruta->load(['paradas', 'estudiantesRuta.estudiante', 'estudiantesRuta.parada']);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Pasajeros');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '0f4c81']],
        ];

        $ws->mergeCells('A1:D1');
        $ws->setCellValue('A1', 'Pasajeros — ' . $ruta->nombre . ' — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Estudiante', 'Parada', 'Tipo'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:D3')->applyFromArray($hdrStyle);

        foreach ($ruta->estudiantesRuta->values() as $i => $er) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $er->estudiante?->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", $er->parada?->nombre ?? 'Sin parada');
            $ws->setCellValue("D{$row}", ucfirst($er->tipo));
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:D{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('dbeafe');
            }
        }

        foreach (range('A', 'D') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'pasajeros_') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'pasajeros_' . \Illuminate\Support\Str::slug($ruta->nombre) . '_' . now()->format('Ymd') . '.xlsx';

        return response()->download($tmp, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── PDF PASAJEROS ────────────────────────────────────────────────────────

    public function pasajerosPdf(RutaTransporte $ruta)
    {
        $ruta->load(['paradas', 'estudiantesRuta.estudiante', 'estudiantesRuta.parada']);

        // Agrupar estudiantes por parada
        $porParada = $ruta->paradas->map(function ($parada) use ($ruta) {
            $parada->pasajeros = $ruta->estudiantesRuta
                ->filter(fn($er) => $er->parada_id === $parada->id)
                ->values();
            return $parada;
        });

        // Sin parada asignada
        $sinParada = $ruta->estudiantesRuta->filter(fn($er) => is_null($er->parada_id))->values();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $logo = \App\Models\ConfigInstitucional::get('logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.transporte.pasajeros_pdf',
            compact('ruta', 'porParada', 'sinParada', 'inst', 'logo')
        )->setPaper('letter', 'portrait');

        $nombre = 'pasajeros_' . \Illuminate\Support\Str::slug($ruta->nombre) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
