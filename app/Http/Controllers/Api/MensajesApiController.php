<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MensajesApiController extends Controller
{
    /** GET /api/v1/mensajes */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $recibidos = MensajeDestinatario::with(['mensaje.remitente'])
            ->where('destinatario_id', $userId)
            ->where('eliminado', false)
            ->latest()
            ->get()
            ->map(fn($d) => [
                'id'        => $d->id,
                'asunto'    => $d->mensaje?->asunto,
                'remitente' => $d->mensaje?->remitente?->name,
                'leido'     => $d->leido_at !== null,
                'fecha'     => $d->mensaje?->created_at?->toIso8601String(),
                'preview'   => Str::limit(strip_tags($d->mensaje?->cuerpo ?? ''), 80),
            ]);

        $enviados = Mensaje::with('destinatarios.destinatario')
            ->where('remitente_id', $userId)
            ->latest()
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'asunto'        => $m->asunto,
                'destinatarios' => $m->destinatarios->map(fn($d) => $d->destinatario?->name)->filter()->join(', '),
                'fecha'         => $m->created_at?->toIso8601String(),
                'preview'       => Str::limit(strip_tags($m->cuerpo ?? ''), 80),
            ]);

        $noLeidos = MensajeDestinatario::where('destinatario_id', $userId)
            ->whereNull('leido_at')
            ->where('eliminado', false)
            ->count();

        return response()->json([
            'recibidos' => $recibidos,
            'enviados'  => $enviados,
            'no_leidos' => $noLeidos,
        ]);
    }

    /** GET /api/v1/mensajes/destinatarios */
    public function destinatarios(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;

        $rolesPermitidos = match ($role) {
            'Docente'        => ['Administrador', 'Director', 'Coordinador Academico'],
            'Representante',
            'Estudiante'     => ['Administrador', 'Director', 'Docente'],
            default          => ['Administrador', 'Director', 'Docente', 'Coordinador Academico'],
        };

        $destinatarios = User::where('tenant_id', $user->tenant_id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', $rolesPermitidos))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($u) => ['id' => $u->id, 'nombre' => $u->name]);

        return response()->json(['destinatarios' => $destinatarios]);
    }

    /** GET /api/v1/mensajes/{id} */
    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $dest = MensajeDestinatario::with(['mensaje.remitente'])
            ->where('id', $id)
            ->where('destinatario_id', $userId)
            ->first();

        if ($dest) {
            if (! $dest->leido_at) {
                $dest->update(['leido_at' => now()]);
            }
            $m = $dest->mensaje;
            return response()->json([
                'id'        => $dest->id,
                'asunto'    => $m?->asunto,
                'cuerpo'    => $m?->cuerpo,
                'remitente' => $m?->remitente?->name,
                'fecha'     => $m?->created_at?->toIso8601String(),
                'tipo'      => 'recibido',
            ]);
        }

        $m = Mensaje::with('destinatarios.destinatario')
            ->where('id', $id)
            ->where('remitente_id', $userId)
            ->first();

        if ($m) {
            return response()->json([
                'id'            => $m->id,
                'asunto'        => $m->asunto,
                'cuerpo'        => $m->cuerpo,
                'destinatarios' => $m->destinatarios->map(fn($d) => $d->destinatario?->name)->filter()->join(', '),
                'fecha'         => $m->created_at?->toIso8601String(),
                'tipo'          => 'enviado',
            ]);
        }

        return response()->json(['message' => 'Mensaje no encontrado.'], 404);
    }

    /** POST /api/v1/mensajes */
    public function store(Request $request)
    {
        $data = $request->validate([
            'asunto'             => 'required|string|max:255',
            'cuerpo'             => 'required|string',
            'destinatario_ids'   => 'required|array|min:1',
            'destinatario_ids.*' => 'integer|exists:users,id',
        ]);

        $tenantId = tenant_id() ?? 0;

        $mensaje = Mensaje::create([
            'tenant_id'    => $tenantId,
            'remitente_id' => $request->user()->id,
            'asunto'       => $data['asunto'],
            'cuerpo'       => $data['cuerpo'],
        ]);

        foreach ($data['destinatario_ids'] as $destId) {
            MensajeDestinatario::create([
                'mensaje_id'      => $mensaje->id,
                'destinatario_id' => $destId,
            ]);
        }

        return response()->json(['ok' => true, 'id' => $mensaje->id], 201);
    }
}
