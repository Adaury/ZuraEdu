<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    // ── Bandeja de entrada ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab  = $request->tab ?? 'recibidos';

        $recibidos = Mensaje::recibidos($user->id)
            ->whereNull('mensaje_padre_id')
            ->with(['remitente', 'respuestas'])
            ->latest()
            ->paginate(20, ['*'], 'pagRecib')
            ->withQueryString();

        $enviados = Mensaje::enviados($user->id)
            ->whereNull('mensaje_padre_id')
            ->with(['destinatario', 'respuestas'])
            ->latest()
            ->paginate(20, ['*'], 'pagEnv')
            ->withQueryString();

        $noLeidos = Mensaje::recibidos($user->id)->noLeidos()->count();

        return view('admin.mensajes.index', compact('recibidos', 'enviados', 'noLeidos', 'tab'));
    }

    // ── Ver mensaje ───────────────────────────────────────────────────────
    public function show(Mensaje $mensaje)
    {
        $user = auth()->user();

        // Solo destinatario o remitente puede ver
        if ($mensaje->remitente_id !== $user->id && $mensaje->destinatario_id !== $user->id) {
            abort(403);
        }

        // Marcar como leído si es el destinatario
        if ($mensaje->destinatario_id === $user->id && ! $mensaje->leido) {
            $mensaje->update(['leido' => true, 'leido_en' => now()]);
        }

        $mensaje->load(['remitente', 'destinatario', 'respuestas.remitente', 'respuestas.destinatario']);

        return view('admin.mensajes.show', compact('mensaje'));
    }

    // ── Nuevo mensaje ─────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $destinatarioId = $request->destinatario_id;
        $destinatario   = $destinatarioId ? User::find($destinatarioId) : null;

        $usuarios = User::where('id', '!=', auth()->id())
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.mensajes.create', compact('usuarios', 'destinatario'));
    }

    // ── Guardar mensaje ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'destinatario_id' => 'required|exists:users,id|different:' . auth()->id(),
            'asunto'          => 'required|string|max:200',
            'cuerpo'          => 'required|string|max:5000',
            'mensaje_padre_id'=> 'nullable|exists:mensajes,id',
        ]);

        $data['remitente_id'] = auth()->id();

        $mensaje = Mensaje::create($data);

        // Notificación interna al destinatario
        try {
            \App\Models\Notificacion::enviar(
                $data['destinatario_id'],
                'general',
                'Nuevo mensaje de ' . auth()->user()->name,
                \Illuminate\Support\Str::limit($data['asunto'], 80),
                ['mensaje_id' => $mensaje->id]
            );
        } catch (\Throwable $e) {}

        if ($request->filled('mensaje_padre_id')) {
            return redirect()->route('admin.mensajes.show', $request->mensaje_padre_id)
                ->with('success', 'Respuesta enviada.');
        }

        return redirect()->route('admin.mensajes.index', ['tab' => 'enviados'])
            ->with('success', 'Mensaje enviado correctamente.');
    }

    // ── Archivar mensaje ──────────────────────────────────────────────────
    public function archivar(Mensaje $mensaje)
    {
        $user = auth()->user();

        if ($mensaje->destinatario_id === $user->id) {
            $mensaje->update(['archivado_destinatario' => true]);
        } elseif ($mensaje->remitente_id === $user->id) {
            $mensaje->update(['archivado_remitente' => true]);
        } else {
            abort(403);
        }

        return back()->with('success', 'Mensaje archivado.');
    }

    // ── Conteo no leídos (API) ────────────────────────────────────────────
    public function conteo()
    {
        return response()->json([
            'count' => Mensaje::recibidos(auth()->id())->noLeidos()->count(),
        ]);
    }
}
