<?php

namespace App\Http\Controllers;

use App\Events\SupportAdminReply;
use App\Events\SupportMessageReceived;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    // ── Visitante: iniciar sesión ─────────────────────────────────────────

    public function start(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:120',
            'email'     => 'nullable|email|max:180',
            'telefono'  => 'nullable|string|max:30',
            'mensaje'   => 'required|string|max:2000',
        ]);

        $tenantId = tenant_id() ?? 0;

        $session = SupportSession::iniciar(
            $tenantId,
            $data['nombre'],
            $data['email'] ?? null,
            $data['telefono'] ?? null,
        );

        $msg = SupportMessage::create([
            'session_id' => $session->id,
            'mensaje'    => $data['mensaje'],
            'origen'     => 'visitor',
        ]);

        try {
            SupportMessageReceived::dispatch(
                $tenantId,
                $session->id,
                $session->token,
                $session->visitor_nombre,
                $data['mensaje'],
                now()->format('H:i'),
            );
        } catch (\Throwable) {}

        return response()->json([
            'token'    => $session->token,
            'mensaje'  => $msg->toChat(),
        ], 201);
    }

    // ── Visitante: enviar mensaje ─────────────────────────────────────────

    public function send(Request $request, string $token)
    {
        $session = SupportSession::where('token', $token)
            ->whereIn('status', ['open'])
            ->firstOrFail();

        $data = $request->validate(['mensaje' => 'required|string|max:2000']);

        $msg = SupportMessage::create([
            'session_id' => $session->id,
            'mensaje'    => $data['mensaje'],
            'origen'     => 'visitor',
        ]);

        $session->update(['ultimo_mensaje_at' => now()]);

        try {
            SupportMessageReceived::dispatch(
                $session->tenant_id,
                $session->id,
                $session->token,
                $session->visitor_nombre,
                $data['mensaje'],
                now()->format('H:i'),
            );
        } catch (\Throwable) {}

        return response()->json($msg->toChat(), 201);
    }

    // ── Visitante: obtener mensajes ───────────────────────────────────────

    public function messages(string $token)
    {
        $session = SupportSession::where('token', $token)->firstOrFail();
        $session->mensajes()->where('origen', 'admin')->update(['leido' => true]);

        return response()->json(
            $session->mensajes()->get()->map->toChat()
        );
    }

    // ── Admin: listar sesiones ────────────────────────────────────────────

    public function adminIndex(\Illuminate\Http\Request $request)
    {
        $tenantId = tenant_id() ?? 0;
        $status   = $request->query('status', 'open'); // open | resolved | all

        $query = SupportSession::delTenant($tenantId)
            ->withCount(['mensajes as sin_leer' => fn($q) => $q->where('origen', 'visitor')->where('leido', false)])
            ->orderByDesc('ultimo_mensaje_at')
            ->limit(60);

        if ($status !== 'all') {
            $query->where('status', $status === 'resolved' ? 'resolved' : 'open');
        }

        $sesiones = $query->get()->map->toCard();

        return response()->json($sesiones);
    }

    // ── Admin: obtener mensajes de una sesión ─────────────────────────────

    public function adminMessages(SupportSession $session)
    {
        abort_unless((tenant_id() ?? 0) === (int) $session->tenant_id, 403);

        $session->mensajes()->where('origen', 'visitor')->update(['leido' => true]);

        return response()->json(
            $session->mensajes()->get()->map->toChat()
        );
    }

    // ── Admin: responder ──────────────────────────────────────────────────

    public function adminReply(Request $request, SupportSession $session)
    {
        abort_unless((tenant_id() ?? 0) === (int) $session->tenant_id, 403);
        abort_if(in_array($session->status, ['closed', 'resolved']), 422, 'La conversación ya está cerrada.');

        $data = $request->validate(['mensaje' => 'required|string|max:2000']);

        $msg = SupportMessage::create([
            'session_id' => $session->id,
            'mensaje'    => $data['mensaje'],
            'origen'     => 'admin',
            'user_id'    => auth()->id(),
            'leido'      => true,
        ]);

        $session->update([
            'atendido_por'      => auth()->id(),
            'ultimo_mensaje_at' => now(),
        ]);

        try {
            SupportAdminReply::dispatch(
                $session->token,
                auth()->user()->name,
                $data['mensaje'],
                now()->format('H:i'),
            );
        } catch (\Throwable) {}

        return response()->json($msg->toChat(), 201);
    }

    // ── Admin: cerrar conversación ────────────────────────────────────────

    public function adminClose(SupportSession $session)
    {
        abort_unless((tenant_id() ?? 0) === (int) $session->tenant_id, 403);
        $session->update(['status' => 'resolved']);

        return response()->json(['status' => 'resolved']);
    }

    // ── Admin: vista HTML del panel ───────────────────────────────────────

    public function adminPanel()
    {
        return view('admin.soporte.chat');
    }
}
