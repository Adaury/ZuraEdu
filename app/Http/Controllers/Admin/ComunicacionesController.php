<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarMensajeCircularJob;
use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComunicacionesController extends Controller
{
    // ── Bandeja de entrada / enviados / circulares ────────────────────────
    public function index(Request $request)
    {
        $userId = auth()->id();
        $tab    = $request->get('tab', 'recibidos');

        $recibidos = $enviados = $circulares = collect();

        if ($tab === 'recibidos') {
            $mensajes = MensajeDestinatario::with(['mensaje.remitente'])
                ->where('destinatario_id', $userId)
                ->where('eliminado', false)
                ->latest()
                ->paginate(20);
            $recibidos = $mensajes;
        } elseif ($tab === 'enviados') {
            $mensajes = Mensaje::with(['destinatarios.destinatario'])
                ->where('remitente_id', $userId)
                ->latest()
                ->paginate(20);
            $enviados = $mensajes;
        } else {
            $mensajes = Mensaje::with(['remitente'])
                ->where('tipo', 'circular')
                ->latest()
                ->paginate(20);
            $circulares = $mensajes;
        }

        $noLeidos = MensajeDestinatario::where('destinatario_id', $userId)
            ->whereNull('leido_at')
            ->where('eliminado', false)
            ->count();

        return view('admin.comunicaciones.index', compact('tab', 'recibidos', 'enviados', 'circulares', 'noLeidos', 'mensajes'));
    }

    // ── Formulario redactar ───────────────────────────────────────────────
    public function create(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $usuarios = User::where('tenant_id', $tenantId)
            ->where('id', '!=', auth()->id())
            ->where('activo', true)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->groupBy(fn($u) => optional($u->roles->first())->name ?? 'Otros');

        $replyTo = null;
        if ($request->filled('reply_to')) {
            $replyTo = Mensaje::find($request->reply_to);
        }

        return view('admin.comunicaciones.create', compact('usuarios', 'replyTo'));
    }

    // ── Guardar mensaje ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'asunto'             => 'required|string|max:255',
            'cuerpo'             => 'required|string',
            'destinatarios'      => 'nullable|array',
            'destinatarios.*'    => 'exists:users,id',
            'destinatarios_grupo'=> 'nullable|array',
            'adjunto'            => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $tipo   = $request->filled('destinatarios_grupo') ? 'circular' : 'individual';
        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        // Resolver IDs de destinatarios
        $ids = [];

        if ($tipo === 'circular' && $request->filled('destinatarios_grupo')) {
            foreach ($request->destinatarios_grupo as $grupo) {
                $rolMap = [
                    'todos_docentes'    => 'Docente',
                    'todos_padres'      => 'Representante',
                    'todos_estudiantes' => 'Estudiante',
                ];

                if ($grupo === 'todos') {
                    $ids = array_merge($ids,
                        User::where('tenant_id', $tenantId)
                            ->where('id', '!=', $userId)
                            ->pluck('id')
                            ->toArray()
                    );
                } elseif (isset($rolMap[$grupo])) {
                    $ids = array_merge($ids,
                        User::where('tenant_id', $tenantId)
                            ->role($rolMap[$grupo])
                            ->pluck('id')
                            ->toArray()
                    );
                }
            }
        } elseif ($request->filled('destinatarios')) {
            $ids = $request->destinatarios;
        }

        $ids = array_unique(array_filter($ids, fn($id) => $id != $userId));

        if (empty($ids)) {
            return back()->withInput()->withErrors(['destinatarios' => 'Debes seleccionar al menos un destinatario.']);
        }

        // Adjunto
        $adjuntoPath   = null;
        $adjuntoNombre = null;
        if ($request->hasFile('adjunto')) {
            $file          = $request->file('adjunto');
            $adjuntoNombre = $file->getClientOriginalName();
            $adjuntoPath   = $file->store('mensajes', 'public');
        }

        // Crear mensaje
        $mensaje = Mensaje::create([
            'tenant_id'     => $tenantId,
            'remitente_id'  => $userId,
            'asunto'        => $request->asunto,
            'cuerpo'        => $request->cuerpo,
            'tipo'          => $tipo,
            'adjunto_path'  => $adjuntoPath,
            'adjunto_nombre'=> $adjuntoNombre,
        ]);

        // Circulares a muchos destinatarios → job async; mensajes individuales → síncrono
        if (count($ids) > 10 && config('queue.default') !== 'sync') {
            EnviarMensajeCircularJob::dispatch($mensaje->id, array_values($ids))
                ->onQueue('default');
        } else {
            foreach ($ids as $destId) {
                MensajeDestinatario::firstOrCreate([
                    'mensaje_id'      => $mensaje->id,
                    'destinatario_id' => $destId,
                ]);
                \Illuminate\Support\Facades\Cache::forget('t' . (tenant_id() ?? 0) . "_user_{$destId}_msg_unread");
            }

            foreach ($ids as $destId) {
                try {
                    \App\Models\Notificacion::enviar(
                        $destId,
                        'general',
                        'Nuevo mensaje de ' . auth()->user()->name,
                        \Illuminate\Support\Str::limit($request->asunto, 80),
                        ['mensaje_id' => $mensaje->id]
                    );
                } catch (\Throwable) {}
            }
        }

        $successMsg = count($ids) > 10
            ? 'Circular enviada. Los destinatarios la recibirán en breve.'
            : 'Mensaje enviado correctamente.';

        return redirect()->route('admin.comunicaciones.index', ['tab' => 'enviados'])
            ->with('success', $successMsg);
    }

    // ── Ver mensaje ───────────────────────────────────────────────────────
    public function show(Mensaje $mensaje)
    {
        $userId = auth()->id();

        // Solo remitente o destinatario puede ver
        $esDestinatario = MensajeDestinatario::where('mensaje_id', $mensaje->id)
            ->where('destinatario_id', $userId)
            ->exists();

        if ($mensaje->remitente_id !== $userId && !$esDestinatario) {
            abort(403);
        }

        // Marcar como leído y limpiar cache del badge
        if ($esDestinatario) {
            $updated = MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('destinatario_id', $userId)
                ->whereNull('leido_at')
                ->update(['leido_at' => now()]);
            if ($updated) {
                \Illuminate\Support\Facades\Cache::forget('t' . (tenant_id() ?? 0) . "_user_{$userId}_msg_unread");
            }
        }

        $mensaje->load(['remitente', 'destinatarios.destinatario']);

        return view('admin.comunicaciones.show', compact('mensaje', 'esDestinatario'));
    }

    // ── Eliminar (solo remitente o destinatario puede) ────────────────────
    public function destroy(Mensaje $mensaje)
    {
        $userId = auth()->id();

        if ($mensaje->remitente_id === $userId) {
            $mensaje->delete();
        } else {
            MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('destinatario_id', $userId)
                ->update(['eliminado' => true]);
        }

        return redirect()->route('admin.comunicaciones.index')
            ->with('success', 'Mensaje eliminado.');
    }

    // ── API: conteo no leídos ─────────────────────────────────────────────
    public function apiNoLeidos()
    {
        $count = MensajeDestinatario::where('destinatario_id', auth()->id())
            ->whereNull('leido_at')
            ->where('eliminado', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // ── Descargar adjunto ─────────────────────────────────────────────────
    public function descargarAdjunto(Mensaje $mensaje)
    {
        $userId = auth()->id();

        $tieneAcceso = $mensaje->remitente_id === $userId
            || MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('destinatario_id', $userId)
                ->exists();

        if (!$tieneAcceso) abort(403);
        if (!$mensaje->adjunto_path) abort(404);

        return Storage::disk('public')->download(
            $mensaje->adjunto_path,
            $mensaje->adjunto_nombre ?? 'adjunto'
        );
    }
}
