<?php

namespace App\Http\Controllers\Portal;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\ClassroomMessage;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ClassroomChatController extends Controller
{
    // ── Mensajes generales del aula (paginados) ────────────────────────────

    public function index(ClaseVirtual $claseVirtual, Request $request)
    {
        $this->autorizar($claseVirtual);

        $fijados = ClassroomMessage::where('clase_virtual_id', $claseVirtual->id)
            ->where('fijado', true)
            ->with('user')
            ->latest()
            ->get();

        $mensajes = ClassroomMessage::where('clase_virtual_id', $claseVirtual->id)
            ->where('tipo', 'general')
            ->with('user')
            ->latest()
            ->paginate(40);

        return response()->json([
            'mensajes' => $mensajes,
            'fijados'  => $fijados,
        ]);
    }

    // ── Enviar mensaje ─────────────────────────────────────────────────────

    public function store(ClaseVirtual $claseVirtual, Request $request)
    {
        $this->autorizar($claseVirtual);

        $data = $request->validate([
            'mensaje'     => 'required|string|max:1000',
            'tipo'        => 'in:general,privado',
            'receptor_id' => 'nullable|exists:users,id',
        ]);

        $tenantId = app('tenant')?->id ?? auth()->user()->tenant_id;

        $msg = ClassroomMessage::create([
            'tenant_id'       => $tenantId,
            'clase_virtual_id'=> $claseVirtual->id,
            'user_id'         => auth()->id(),
            'mensaje'         => $data['mensaje'],
            'tipo'            => $data['tipo'] ?? 'general',
            'receptor_id'     => $data['receptor_id'] ?? null,
        ]);

        $msg->load('user');

        MessageSent::dispatch(
            $claseVirtual->id,
            auth()->id(),
            $msg->user->name,
            $msg->mensaje,
            $msg->created_at->format('H:i'),
        );

        return response()->json([
            'id'         => $msg->id,
            'user_id'    => $msg->user_id,
            'user_name'  => $msg->user->name,
            'mensaje'    => $msg->mensaje,
            'tipo'       => $msg->tipo,
            'fijado'     => false,
            'created_at' => $msg->created_at->format('H:i'),
            'es_propio'  => true,
        ], 201);
    }

    // ── Fijar / desfijar mensaje (solo docente) ────────────────────────────

    public function togglePin(ClaseVirtual $claseVirtual, ClassroomMessage $message)
    {
        abort_unless(
            $this->esDocente($claseVirtual),
            403, 'Solo el docente puede fijar mensajes.'
        );

        $message->update(['fijado' => !$message->fijado]);

        return response()->json(['fijado' => $message->fijado]);
    }

    // ── Eliminar mensaje ───────────────────────────────────────────────────

    public function destroy(ClaseVirtual $claseVirtual, ClassroomMessage $message)
    {
        abort_unless(
            $message->user_id === auth()->id() || $this->esDocente($claseVirtual),
            403
        );

        $message->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function autorizar(ClaseVirtual $clase): void
    {
        $user = auth()->user();

        if ($user->hasRole('Docente')) {
            $docente = Docente::where('user_id', $user->id)->firstOrFail();
            abort_unless($clase->asignacion->docente_id === $docente->id, 403);
            return;
        }

        if ($user->hasRole('Estudiante')) {
            $matricula = $this->getMatricula();
            abort_unless($clase->asignacion->grupo_id === $matricula?->grupo_id, 403);
            return;
        }

        abort(403);
    }

    private function esDocente(ClaseVirtual $clase): bool
    {
        $docente = Docente::where('user_id', auth()->id())->first();
        return $docente && $clase->asignacion->docente_id === $docente->id;
    }

    private function getMatricula(): ?Matricula
    {
        $est = Estudiante::where('user_id', auth()->id())->first();
        if (! $est) return null;
        $sy = SchoolYear::actual();
        return Matricula::where('estudiante_id', $est->id)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('estado', 'activa')
            ->first();
    }
}
