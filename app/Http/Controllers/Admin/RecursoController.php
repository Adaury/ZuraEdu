<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecursoFisico;
use App\Models\ReservaRecurso;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecursoController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  CRUD DE RECURSOS FÍSICOS
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = RecursoFisico::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre',    'like', "%{$q}%")
                   ->orWhere('ubicacion', 'like', "%{$q}%");
            });
        }

        $recursos = $query->withCount([
            'reservas as reservas_pendientes' => fn($q) => $q->where('estado', 'pendiente'),
            'reservas as reservas_hoy'        => fn($q) => $q->where('fecha', today())->where('estado', 'aprobada'),
        ])->orderBy('tipo')->orderBy('nombre')->paginate(20)->withQueryString();

        $tipos   = RecursoFisico::TIPOS;
        $totales = RecursoFisico::selectRaw('tipo, count(*) as total')->groupBy('tipo')->pluck('total', 'tipo');

        return view('admin.recursos.index', compact('recursos', 'tipos', 'totales'));
    }

    public function create()
    {
        $tipos = RecursoFisico::TIPOS;
        return view('admin.recursos.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'tipo'        => 'required|in:' . implode(',', array_keys(RecursoFisico::TIPOS)),
            'capacidad'   => 'nullable|integer|min:1|max:9999',
            'ubicacion'   => 'nullable|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);
        RecursoFisico::create($data);

        return redirect()->route('admin.recursos.index')->with('success', 'Recurso creado correctamente.');
    }

    public function edit(RecursoFisico $recurso)
    {
        $tipos = RecursoFisico::TIPOS;
        return view('admin.recursos.create', compact('recurso', 'tipos'));
    }

    public function update(Request $request, RecursoFisico $recurso)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'tipo'        => 'required|in:' . implode(',', array_keys(RecursoFisico::TIPOS)),
            'capacidad'   => 'nullable|integer|min:1|max:9999',
            'ubicacion'   => 'nullable|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $recurso->update($data);

        return redirect()->route('admin.recursos.index')->with('success', 'Recurso actualizado.');
    }

    public function destroy(RecursoFisico $recurso)
    {
        // Verificar que no tenga reservas aprobadas futuras
        $tieneReservas = $recurso->reservas()
            ->where('estado', 'aprobada')
            ->where('fecha', '>=', today())
            ->exists();

        if ($tieneReservas) {
            return back()->with('error', 'No se puede eliminar el recurso porque tiene reservas aprobadas pendientes.');
        }

        $recurso->delete();
        return redirect()->route('admin.recursos.index')->with('success', 'Recurso eliminado.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RESERVAS POR RECURSO
    // ══════════════════════════════════════════════════════════════════════

    public function reservas(Request $request, RecursoFisico $recurso)
    {
        // Semana seleccionada
        $fechaBase  = $request->filled('semana')
            ? Carbon::parse($request->semana)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $semanaFin  = $fechaBase->copy()->endOfWeek(Carbon::SUNDAY);
        $diasSemana = [];
        for ($d = $fechaBase->copy(); $d->lte($semanaFin); $d->addDay()) {
            $diasSemana[] = $d->copy();
        }

        // Reservas de la semana
        $reservasSemana = $recurso->reservas()
            ->with('solicitante')
            ->whereBetween('fecha', [$fechaBase->toDateString(), $semanaFin->toDateString()])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy(fn($r) => $r->fecha->toDateString());

        // Pendientes globales del recurso
        $pendientes = $recurso->reservas()
            ->with('solicitante')
            ->pendientes()
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        // Horas del calendario (07:00 – 21:00 en bloques de 1 h)
        $horas = [];
        for ($h = 7; $h <= 20; $h++) {
            $horas[] = sprintf('%02d:00', $h);
        }

        return view('admin.recursos.reservas', compact(
            'recurso', 'diasSemana', 'reservasSemana', 'pendientes',
            'horas', 'fechaBase', 'semanaFin'
        ));
    }

    public function crearReserva(Request $request, RecursoFisico $recurso)
    {
        if ($request->isMethod('GET')) {
            return view('admin.recursos.reservas', [
                'recurso'        => $recurso,
                'modoCrear'      => true,
                'diasSemana'     => [],
                'reservasSemana' => collect(),
                'pendientes'     => collect(),
                'horas'          => [],
                'fechaBase'      => Carbon::today(),
                'semanaFin'      => Carbon::today(),
            ]);
        }

        $data = $request->validate([
            'fecha'        => 'required|date|after_or_equal:today',
            'hora_inicio'  => 'required|date_format:H:i',
            'hora_fin'     => 'required|date_format:H:i|after:hora_inicio',
            'motivo'       => 'required|string|max:250',
            'notas'        => 'nullable|string|max:500',
        ]);

        // Verificar conflicto con reservas APROBADAS
        if ($recurso->tieneConflicto($data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            return back()->withInput()->with(
                'error',
                'Ya existe una reserva aprobada para ese recurso en ese horario. Por favor elige otro horario.'
            );
        }

        ReservaRecurso::create([
            'recurso_id'     => $recurso->id,
            'solicitante_id' => auth()->id(),
            'fecha'          => $data['fecha'],
            'hora_inicio'    => $data['hora_inicio'],
            'hora_fin'       => $data['hora_fin'],
            'motivo'         => $data['motivo'],
            'notas'          => $data['notas'] ?? null,
            'estado'         => 'pendiente',
        ]);

        return redirect()
            ->route('admin.recursos.reservas', $recurso)
            ->with('success', 'Reserva enviada. Queda pendiente de aprobación.');
    }

    public function aprobar(ReservaRecurso $reserva)
    {
        // Verificar conflicto al momento de aprobar
        if ($reserva->recurso->tieneConflicto(
            $reserva->fecha->toDateString(),
            $reserva->hora_inicio,
            $reserva->hora_fin,
            $reserva->id
        )) {
            return back()->with('error', 'No se puede aprobar: existe conflicto con otra reserva ya aprobada.');
        }

        $reserva->update(['estado' => 'aprobada']);
        return back()->with('success', 'Reserva aprobada.');
    }

    public function rechazar(Request $request, ReservaRecurso $reserva)
    {
        $request->validate(['notas' => 'nullable|string|max:500']);

        $reserva->update([
            'estado' => 'rechazada',
            'notas'  => $request->notas ?? $reserva->notas,
        ]);

        return back()->with('success', 'Reserva rechazada.');
    }

    public function cancelar(ReservaRecurso $reserva)
    {
        // Solo el solicitante o admin puede cancelar
        if (auth()->id() !== $reserva->solicitante_id && !auth()->user()->hasAnyRole(['Administrador', 'Director'])) {
            abort(403);
        }

        $reserva->delete();
        return back()->with('success', 'Reserva cancelada.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXCEL LISTA DE RECURSOS
    // ══════════════════════════════════════════════════════════════════════

    public function listaExcel(Request $request)
    {
        $query = RecursoFisico::withCount([
            'reservas as reservas_pendientes' => fn($q) => $q->where('estado', 'pendiente'),
        ]);

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('activo')) $query->where('activo', $request->activo === '1');
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")->orWhere('ubicacion', 'like', "%{$q}%"));
        }

        $recursos = $query->orderBy('tipo')->orderBy('nombre')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Recursos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Recursos y Aulas — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Tipo', 'Capacidad', 'Ubicación', 'Descripción', 'Pendientes', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        foreach ($recursos as $i => $rec) {
            $row = $i + 4;
            $tipoLabel = RecursoFisico::TIPOS[$rec->tipo]['label'] ?? ucfirst($rec->tipo);
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $rec->nombre);
            $ws->setCellValue("C{$row}", $tipoLabel);
            $ws->setCellValue("D{$row}", $rec->capacidad ?? '—');
            $ws->setCellValue("E{$row}", $rec->ubicacion ?? '—');
            $ws->setCellValue("F{$row}", $rec->descripcion ?? '—');
            $ws->setCellValue("G{$row}", $rec->reservas_pendientes);
            $ws->setCellValue("H{$row}", $rec->activo ? 'Activo' : 'Inactivo');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rec_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'recursos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PDF LISTA DE RECURSOS
    // ══════════════════════════════════════════════════════════════════════

    public function listaPdf(Request $request)
    {
        $query = RecursoFisico::withCount([
            'reservas as reservas_pendientes' => fn($q) => $q->where('estado', 'pendiente'),
        ]);

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('activo')) $query->where('activo', $request->activo === '1');
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq->where('nombre', 'like', "%{$q}%")->orWhere('ubicacion', 'like', "%{$q}%"));
        }

        $recursos = $query->orderBy('tipo')->orderBy('nombre')->get();
        $inst     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.recursos.lista_pdf',
            compact('recursos', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('recursos_' . now()->format('Ymd') . '.pdf');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VISTA GENERAL DE DISPONIBILIDAD
    // ══════════════════════════════════════════════════════════════════════

    public function disponibilidad(Request $request)
    {
        $fecha = $request->filled('fecha')
            ? Carbon::parse($request->fecha)
            : Carbon::today();

        $recursos = RecursoFisico::activos()
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        // Para cada recurso, cargar reservas aprobadas del día
        $reservasPorRecurso = ReservaRecurso::with('solicitante', 'recurso')
            ->whereIn('recurso_id', $recursos->pluck('id'))
            ->where('fecha', $fecha->toDateString())
            ->where('estado', 'aprobada')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('recurso_id');

        // Horas de referencia (07:00 – 20:00)
        $horas = [];
        for ($h = 7; $h <= 20; $h++) {
            $horas[] = sprintf('%02d:00', $h);
        }

        return view('admin.recursos.disponibilidad', compact(
            'recursos', 'reservasPorRecurso', 'fecha', 'horas'
        ));
    }
}
