<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvisoEmergencia;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Representante;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvisoEmergenciaController extends Controller
{
    // ── Historial ─────────────────────────────────────────────────────────

    public function index()
    {
        $avisos = AvisoEmergencia::with(['enviadoPor', 'grupo.grado', 'grupo.seccion'])
            ->latest()
            ->paginate(20);

        return view('admin.avisos_emergencia.index', compact('avisos'));
    }

    // ── Formulario ────────────────────────────────────────────────────────

    public function create()
    {
        $grupos = Grupo::with(['grado', 'seccion'])
            ->activos()
            ->orderBy('id')
            ->get();

        $tipos         = AvisoEmergencia::TIPOS;
        $destinatarios = AvisoEmergencia::DESTINATARIOS_LABELS;

        return view('admin.avisos_emergencia.create', compact('grupos', 'tipos', 'destinatarios'));
    }

    // ── Detalle ───────────────────────────────────────────────────────────

    public function show(AvisoEmergencia $aviso)
    {
        $aviso->load(['enviadoPor', 'grupo.grado', 'grupo.seccion']);
        return view('admin.avisos_emergencia.show', compact('aviso'));
    }

    // ── Eliminar del historial ────────────────────────────────────────────

    public function destroy(AvisoEmergencia $aviso)
    {
        $aviso->delete();
        return redirect()->route('admin.avisos-emergencia.index')
            ->with('success', 'Aviso eliminado del historial.');
    }

    // ── Guardar y enviar ─────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:200',
            'mensaje'       => 'required|string|max:2000',
            'tipo'          => 'required|in:emergencia,suspension,actividad,informativo',
            'destinatarios' => 'required|in:todos,padres,docentes,grupo',
            'grupo_id'      => 'nullable|required_if:destinatarios,grupo|exists:grupos,id',
        ]);

        $data['enviado_por_id'] = auth()->id();

        DB::beginTransaction();

        try {
            // 1. Obtener IDs de usuarios destinatarios
            $userIds = $this->resolverDestinatarios(
                $data['destinatarios'],
                $data['grupo_id'] ?? null
            );

            $data['total_enviados'] = count($userIds);

            // 2. Crear registro
            $aviso = AvisoEmergencia::create($data);

            // 3. Enviar notificaciones en app
            if (count($userIds) > 0) {
                Notificacion::enviarA(
                    $userIds,
                    'alerta',
                    '🚨 ' . $aviso->titulo,
                    $aviso->mensaje,
                    ['aviso_id' => $aviso->id, 'tipo' => $aviso->tipo]
                );
            }

            DB::commit();

            // 4. WhatsApp a representantes si es emergencia o suspensión (fuera de la transacción)
            if (in_array($data['tipo'], ['emergencia', 'suspension'])) {
                $this->enviarWhatsApp($data['destinatarios'], $data['grupo_id'] ?? null, $aviso);
            }

            return redirect()
                ->route('admin.avisos-emergencia.index')
                ->with('success', "Aviso enviado a {$aviso->total_enviados} " . ($aviso->total_enviados === 1 ? 'usuario' : 'usuarios') . ' correctamente.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AvisoEmergencia::store error', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Ocurrió un error al enviar el aviso. Por favor intente nuevamente.');
        }
    }

    // ── Helpers privados ─────────────────────────────────────────────────

    /**
     * Resuelve los IDs de usuarios según el tipo de destinatario.
     */
    private function resolverDestinatarios(string $tipo, ?int $grupoId): array
    {
        return match ($tipo) {

            'todos' => User::activos()
                ->pluck('id')
                ->toArray(),

            'padres' => User::activos()
                ->role('Representante')
                ->pluck('id')
                ->toArray(),

            'docentes' => User::activos()
                ->role('Docente')
                ->pluck('id')
                ->toArray(),

            'grupo' => $this->userIdsDeGrupo($grupoId),

            default => [],
        };
    }

    /**
     * Devuelve los user_ids de los representantes de un grupo dado.
     */
    private function userIdsDeGrupo(?int $grupoId): array
    {
        if (! $grupoId) return [];

        return Matricula::where('grupo_id', $grupoId)
            ->activas()
            ->with(['estudiante.representantes.user'])
            ->get()
            ->flatMap(fn($m) => $m->estudiante->representantes ?? collect())
            ->filter(fn($r) => $r->user && $r->user->activo)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Obtiene los Representante con teléfono según tipo de destinatario y envía WhatsApp.
     * Usa try/catch individual para no detener el flujo si un envío falla.
     */
    private function enviarWhatsApp(string $tipo, ?int $grupoId, AvisoEmergencia $aviso): void
    {
        $school  = \App\Helpers\Setting::get('system_name', 'El centro educativo');
        $emoji   = $aviso->tipo === 'emergencia' ? '🚨' : '📢';
        $mensaje = "{$emoji} *{$school}* — *{$aviso->titulo}*\n\n{$aviso->mensaje}";

        $representantes = $this->representantesParaWhatsApp($tipo, $grupoId);

        foreach ($representantes as $rep) {
            $telefono = $rep->telefono ?? null;
            if (! $telefono) continue;

            try {
                WhatsAppService::send($telefono, $mensaje);
            } catch (\Throwable $e) {
                Log::warning('AvisoEmergencia WhatsApp falló', [
                    'representante_id' => $rep->id,
                    'telefono'         => $telefono,
                    'error'            => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Devuelve colección de Representante para el envío de WhatsApp.
     */
    private function representantesParaWhatsApp(string $tipo, ?int $grupoId)
    {
        if ($tipo === 'grupo') {
            if (! $grupoId) return collect();

            return Representante::whereHas('estudiantes.matriculas', fn($q) =>
                $q->where('grupo_id', $grupoId)->where('estado', 'activa')
            )->whereNotNull('telefono')->get();
        }

        // Para 'todos' y 'padres' enviamos a todos los representantes activos con teléfono
        if (in_array($tipo, ['todos', 'padres'])) {
            return Representante::activos()->whereNotNull('telefono')->get();
        }

        return collect();
    }
}
