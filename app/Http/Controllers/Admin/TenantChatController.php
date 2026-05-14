<?php

namespace App\Http\Controllers\Admin;

use App\Events\NuevoMensajeTenantChat;
use App\Http\Controllers\Controller;
use App\Models\TenantChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantChatController extends Controller
{
    public function index(Request $request)
    {
        $mensajes = TenantChatMessage::with('user')
            ->where('tenant_id', tenant_id() ?? 0)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => $m->toChat());

        return response()->json($mensajes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mensaje' => 'required|string|max:2000',
        ]);

        $msg = TenantChatMessage::create([
            'tenant_id' => tenant_id() ?? 0,
            'user_id'   => Auth::id(),
            'mensaje'   => $data['mensaje'],
            'tipo'      => 'texto',
        ]);

        $msg->load('user');

        try {
            NuevoMensajeTenantChat::dispatch($msg);
        } catch (\Throwable) {}

        return response()->json($msg->toChat(), 201);
    }
}
