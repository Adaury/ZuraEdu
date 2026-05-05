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
