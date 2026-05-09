<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RespuestaTicket;
use App\Models\TicketSoporte;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // ── Determinar si el usuario actual es admin/director ─────────────────
    private function esAdmin(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Director', 'Coordinador Académico']);
    }

    // ── Listado ───────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user    = auth()->user();
        $esAdmin = $this->esAdmin();

        $query = TicketSoporte::with(['solicitante', 'asignadoA'])
            ->latest();

        // Docentes y demás roles solo ven sus propios tickets
        if (! $esAdmin) {
            $query->delSolicitante($user->id);
        }

        // Filtros
        if ($request->filled('estado')) {
            $query->conEstado($request->estado);
        }
        if ($request->filled('categoria')) {
            $query->conCategoria($request->categoria);
        }
        if ($request->filled('prioridad')) {
            $query->conPrioridad($request->prioridad);
        }

        $tickets    = $query->paginate(20)->withQueryString();
        $categorias = TicketSoporte::CATEGORIAS;
        $prioridades = TicketSoporte::PRIORIDADES;
        $estados    = TicketSoporte::ESTADOS;

        // Contadores rápidos (scope del usuario)
        $baseCount = $esAdmin
            ? TicketSoporte::query()
            : TicketSoporte::delSolicitante($user->id);

        $contadores = [
            'total'      => (clone $baseCount)->count(),
            'abierto'    => (clone $baseCount)->conEstado('abierto')->count(),
            'en_proceso' => (clone $baseCount)->conEstado('en_proceso')->count(),
            'resuelto'   => (clone $baseCount)->conEstado('resuelto')->count(),
        ];

        return view('admin.soporte.index', compact(
            'tickets', 'categorias', 'prioridades', 'estados', 'contadores', 'esAdmin'
        ));
    }

    // ── Formulario crear ──────────────────────────────────────────────────
    public function create()
    {
        $categorias  = TicketSoporte::CATEGORIAS;
        $prioridades = TicketSoporte::PRIORIDADES;

        return view('admin.soporte.create', compact('categorias', 'prioridades'));
    }

    // ── Guardar ticket ────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:200',
            'descripcion' => 'required|string|max:5000',
            'categoria'   => 'required|in:tecnico,academico,administrativo,otro',
            'prioridad'   => 'required|in:baja,media,alta,urgente',
        ]);

        $data['solicitante_id'] = auth()->id();

        $ticket = TicketSoporte::create($data);

        // Notificar a todos los Administradores y Directores
        $admins = User::role(['Administrador', 'Director'])->pluck('id')->toArray();

        if (! empty($admins)) {
            Notificacion::enviarA(
                $admins,
                'alerta',
                'Nuevo ticket de soporte',
                auth()->user()->nombre_completo . ' abrió: ' . \Illuminate\Support\Str::limit($ticket->titulo, 70),
                ['ticket_id' => $ticket->id]
            );
        }

        return redirect()->route('admin.soporte.show', $ticket)
            ->with('success', 'Ticket creado correctamente.');
    }

    // ── Ver ticket + hilo ─────────────────────────────────────────────────
    public function show(TicketSoporte $soporte)
    {
        $user = auth()->user();

        // Solo el solicitante o admin pueden ver
        if ($soporte->solicitante_id !== $user->id && ! $this->esAdmin()) {
            abort(403);
        }

        $soporte->load(['solicitante', 'asignadoA', 'respuestas.user']);

        $admins      = User::role(['Administrador', 'Director'])->activos()->get(['id', 'name', 'apellidos']);
        $categorias  = TicketSoporte::CATEGORIAS;
        $prioridades = TicketSoporte::PRIORIDADES;
        $estados     = TicketSoporte::ESTADOS;
        $esAdmin     = $this->esAdmin();

        return view('admin.soporte.show', compact(
            'soporte', 'admins', 'categorias', 'prioridades', 'estados', 'esAdmin'
        ));
    }

    // ── Responder ticket ──────────────────────────────────────────────────
    public function responder(Request $request, TicketSoporte $soporte)
    {
        $user = auth()->user();

        if ($soporte->solicitante_id !== $user->id && ! $this->esAdmin()) {
            abort(403);
        }

        if (in_array($soporte->estado, ['resuelto', 'cerrado']) && ! $this->esAdmin()) {
            return back()->with('error', 'No puedes responder un ticket cerrado.');
        }

        $request->validate([
            'mensaje' => 'required|string|max:5000',
        ]);

        RespuestaTicket::create([
            'ticket_id' => $soporte->id,
            'user_id'   => $user->id,
            'mensaje'   => $request->mensaje,
        ]);

        // Si el ticket estaba abierto y responde un admin, pasar a en_proceso
        if ($soporte->estado === 'abierto' && $this->esAdmin()) {
            $soporte->update(['estado' => 'en_proceso']);
        }

        // Notificar al solicitante si quien responde no es él mismo
        if ($soporte->solicitante_id !== $user->id) {
            Notificacion::enviar(
                $soporte->solicitante_id,
                'general',
                'Respuesta a tu ticket #' . $soporte->id,
                $user->nombre_completo . ' respondió: ' . \Illuminate\Support\Str::limit($request->mensaje, 80),
                ['ticket_id' => $soporte->id]
            );
        }

        return redirect()->route('admin.soporte.show', $soporte)
            ->with('success', 'Respuesta enviada.');
    }

    // ── Cambiar estado ────────────────────────────────────────────────────
    public function cambiarEstado(Request $request, TicketSoporte $soporte)
    {
        $user = auth()->user();

        // Solicitante solo puede cerrar tickets resueltos
        if (! $this->esAdmin()) {
            if ($soporte->solicitante_id !== $user->id) {
                abort(403);
            }
            if ($request->estado !== 'cerrado' || $soporte->estado !== 'resuelto') {
                abort(403);
            }
        }

        $request->validate([
            'estado' => 'required|in:abierto,en_proceso,resuelto,cerrado',
        ]);

        $soporte->update(['estado' => $request->estado]);

        // Notificar al solicitante
        if ($soporte->solicitante_id !== $user->id) {
            $etiqueta = TicketSoporte::ESTADOS[$request->estado] ?? $request->estado;
            Notificacion::enviar(
                $soporte->solicitante_id,
                'general',
                'Estado de tu ticket actualizado',
                'Tu ticket #' . $soporte->id . ' cambió a: ' . $etiqueta,
                ['ticket_id' => $soporte->id]
            );
        }

        return back()->with('success', 'Estado actualizado.');
    }

    // ── Excel de tickets (solo admin) ─────────────────────────────────────
    public function listaExcel(Request $request)
    {
        if (! $this->esAdmin()) abort(403);

        $query = TicketSoporte::with(['solicitante', 'asignadoA'])->latest();

        if ($request->filled('estado'))    $query->conEstado($request->estado);
        if ($request->filled('categoria')) $query->conCategoria($request->categoria);
        if ($request->filled('prioridad')) $query->conPrioridad($request->prioridad);

        $tickets = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Tickets');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Tickets de Soporte — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Título', 'Solicitante', 'Categoría', 'Prioridad', 'Estado', 'Asignado A', 'Fecha'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        $bgEstado = ['abierto' => 'dbeafe', 'en_proceso' => 'fef3c7', 'resuelto' => 'd1fae5', 'cerrado' => 'f3f4f6'];

        foreach ($tickets as $i => $t) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $t->titulo);
            $ws->setCellValue("C{$row}", $t->solicitante?->name ?? '—');
            $ws->setCellValue("D{$row}", TicketSoporte::CATEGORIAS[$t->categoria] ?? $t->categoria);
            $ws->setCellValue("E{$row}", TicketSoporte::PRIORIDADES[$t->prioridad] ?? $t->prioridad);
            $ws->setCellValue("F{$row}", TicketSoporte::ESTADOS[$t->estado] ?? $t->estado);
            $ws->setCellValue("G{$row}", $t->asignadoA?->name ?? '—');
            $ws->setCellValue("H{$row}", $t->created_at->format('d/m/Y H:i'));
            $bg = $bgEstado[$t->estado] ?? 'ffffff';
            $ws->getStyle("A{$row}:H{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'tkt_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'tickets_soporte_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        if (! $this->esAdmin()) abort(403);

        $query = TicketSoporte::with(['solicitante', 'asignadoA'])->latest();

        if ($request->filled('estado'))    $query->conEstado($request->estado);
        if ($request->filled('categoria')) $query->conCategoria($request->categoria);
        if ($request->filled('prioridad')) $query->conPrioridad($request->prioridad);

        $tickets = $query->get();
        $inst    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.soporte.lista_pdf',
            compact('tickets', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('tickets_soporte_' . now()->format('Ymd') . '.pdf');
    }

    // ── Asignar ticket (solo admin) ───────────────────────────────────────
    public function asignar(Request $request, TicketSoporte $soporte)
    {
        if (! $this->esAdmin()) {
            abort(403);
        }

        $request->validate([
            'asignado_a_id' => 'nullable|exists:users,id',
        ]);

        $soporte->update(['asignado_a_id' => $request->asignado_a_id ?: null]);

        // Notificar al usuario asignado
        if ($request->filled('asignado_a_id') && $request->asignado_a_id != auth()->id()) {
            Notificacion::enviar(
                (int) $request->asignado_a_id,
                'alerta',
                'Ticket asignado',
                'Se te asignó el ticket #' . $soporte->id . ': ' . \Illuminate\Support\Str::limit($soporte->titulo, 70),
                ['ticket_id' => $soporte->id]
            );
        }

        return back()->with('success', 'Asignación actualizada.');
    }
}
