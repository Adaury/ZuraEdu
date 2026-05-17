<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MensajesPortalController extends Controller
{
    /**
     * Detecta el portal activo por el prefijo de URL.
     * Retorna 'docente' | 'padre'
     */
    private function portalActual(): string
    {
        $segment = request()->segment(2); // portal/docente -> segment(2) = docente
        return in_array($segment, ['padre', 'docente', 'estudiante']) ? $segment : 'docente';
    }

    // ── Bandeja ───────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $userId  = auth()->id();
        $tab     = $request->get('tab', 'recibidos');
        $portal  = $this->portalActual();

        if ($tab === 'recibidos') {
            $mensajes = MensajeDestinatario::with(['mensaje.remitente'])
                ->where('destinatario_id', $userId)
                ->where('eliminado', false)
                ->latest()
                ->paginate(15);
        } else {
            $mensajes = Mensaje::with(['destinatarios.destinatario'])
                ->where('remitente_id', $userId)
                ->latest()
                ->paginate(15);
        }

        $noLeidos = MensajeDestinatario::where('destinatario_id', $userId)
            ->whereNull('leido_at')
            ->where('eliminado', false)
            ->count();

        return view("portal.{$portal}.mensajes.index", compact('tab', 'mensajes', 'noLeidos', 'portal'));
    }

    // ── Redactar ──────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $portal   = $this->portalActual();
        $tenantId = auth()->user()->tenant_id;
        $userId   = auth()->id();
        $tid      = tenant_id() ?? 0;

        if ($portal === 'docente') {
            $roles = ['Administrador', 'Director', 'Coordinador Academico'];
        } else {
            $roles = ['Administrador', 'Director', 'Docente'];
        }

        $cacheKey = "t{$tid}_mensajes_destinatarios_{$portal}";
        $destinatarios = Cache::remember($cacheKey, 300, function () use ($tenantId, $roles) {
            return User::where('tenant_id', $tenantId)
                ->whereHas('roles', fn($q) => $q->whereIn('name', $roles))
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        })->reject(fn($u) => $u->id === $userId)->values();

        $replyTo = null;
        if ($request->filled('reply_to')) {
            $replyTo = Mensaje::find($request->reply_to);
        }

        return view("portal.{$portal}.mensajes.create", compact('destinatarios', 'replyTo', 'portal'));
    }

    // ── Guardar ───────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $portal = $this->portalActual();
        $userId = auth()->id();

        $request->validate([
            'asunto'          => 'required|string|max:255',
            'cuerpo'          => 'required|string',
            'destinatarios'   => 'required|array|min:1',
            'destinatarios.*' => 'exists:users,id',
        ]);

        $ids = array_unique(array_filter($request->destinatarios, fn($id) => $id != $userId));

        if (empty($ids)) {
            return back()->withInput()->withErrors(['destinatarios' => 'Selecciona al menos un destinatario.']);
        }

        $mensaje = Mensaje::create([
            'tenant_id'    => auth()->user()->tenant_id,
            'remitente_id' => $userId,
            'asunto'       => $request->asunto,
            'cuerpo'       => $request->cuerpo,
            'tipo'         => 'individual',
        ]);

        // Bulk insert — 1 query en lugar de N firstOrCreate
        $now = now();
        $rows = array_map(fn($destId) => [
            'mensaje_id'      => $mensaje->id,
            'destinatario_id' => $destId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $ids);
        MensajeDestinatario::insertOrIgnore($rows);

        // Invalidar badge + notificar en un solo loop
        $tid = tenant_id() ?? 0;
        $remitente = auth()->user()->name;
        foreach ($ids as $destId) {
            Cache::forget("t{$tid}_user_{$destId}_msg_unread");
            try {
                \App\Models\Notificacion::enviar(
                    $destId, 'general',
                    "Nuevo mensaje de {$remitente}",
                    \Illuminate\Support\Str::limit($request->asunto, 80),
                    ['mensaje_id' => $mensaje->id]
                );
            } catch (\Throwable $e) {}
        }

        $indexRoute = "portal.{$portal}.mensajes.index";
        return redirect()->route($indexRoute, ['tab' => 'enviados'])
            ->with('success', 'Mensaje enviado.');
    }

    // ── Ver mensaje ───────────────────────────────────────────────────────
    public function show(Request $request, Mensaje $mensaje)
    {
        $userId = auth()->id();
        $portal = $this->portalActual();

        $esDestinatario = MensajeDestinatario::where('mensaje_id', $mensaje->id)
            ->where('destinatario_id', $userId)
            ->exists();

        if ($mensaje->remitente_id !== $userId && !$esDestinatario) {
            abort(403);
        }

        if ($esDestinatario) {
            $updated = MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('destinatario_id', $userId)
                ->whereNull('leido_at')
                ->update(['leido_at' => now()]);
            if ($updated) {
                Cache::forget('t' . (tenant_id() ?? 0) . "_user_{$userId}_msg_unread");
            }
        }

        $mensaje->load(['remitente', 'destinatarios.destinatario']);

        return view("portal.{$portal}.mensajes.show", compact('mensaje', 'portal', 'esDestinatario'));
    }
}
