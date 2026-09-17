<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\Notificacion;
use App\Models\SolicitudRepresentante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SolicitudesAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudRepresentante::with(['representante', 'estudiante', 'respondidoPor'])
            ->orderByRaw("FIELD(estado, 'pendiente', 'en_proceso', 'aprobada', 'rechazada')")
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($s) use ($q) {
                $s->where('asunto', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%")
                  ->orWhereHas('representante', fn($r) =>
                      $r->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%")
                  );
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        $tid   = tenant_id();
        $stats = Cache::remember("t{$tid}_solicitudes_rep_stats", 60, fn () => [
            'pendientes'  => SolicitudRepresentante::where('estado', 'pendiente')->count(),
            'en_proceso'  => SolicitudRepresentante::where('estado', 'en_proceso')->count(),
            'total_hoy'   => SolicitudRepresentante::whereDate('created_at', today())->count(),
        ]);

        $tipos   = SolicitudRepresentante::TIPOS;
        $estados = SolicitudRepresentante::estados();

        return view('admin.solicitudes.index', compact('solicitudes', 'stats', 'tipos', 'estados'));
    }

    public function show(SolicitudRepresentante $solicitud)
    {
        $solicitud->load(['representante', 'estudiante', 'respondidoPor']);
        $tipos   = SolicitudRepresentante::TIPOS;
        $estados = SolicitudRepresentante::estados();
        return view('admin.solicitudes.show', compact('solicitud', 'tipos', 'estados'));
    }

    public function responder(Request $request, SolicitudRepresentante $solicitud)
    {
        $data = $request->validate([
            'estado'    => 'required|in:en_proceso,aprobada,rechazada',
            'respuesta' => 'required|string|max:2000',
        ]);

        $solicitud->update([
            'estado'        => $data['estado'],
            'respuesta'     => $data['respuesta'],
            'respondido_por'=> Auth::id(),
            'respondido_en' => now(),
        ]);

        // Si es una justificación de ausencia aprobada, reflejar el cambio en
        // el registro real de Asistencia. Antes la solicitud quedaba
        // "aprobada" sin que el % de asistencia (el que alimenta
        // calcularPromocion()) cambiara en absoluto -- la aprobación no
        // surtía ningún efecto real.
        $asistenciasActualizadas = 0;
        if ($data['estado'] === 'aprobada' && $solicitud->tipo === 'justificacion_ausencia' && $solicitud->fecha_evento) {
            $tipoKey = null;
            if (preg_match('/^Tipo: (.+?)\n/', (string) $solicitud->descripcion, $m)) {
                $tipoKey = array_search($m[1], Asistencia::TIPOS_JUSTIFICACION, true) ?: null;
            }

            $asistencias = Asistencia::whereHas('matricula', fn ($q) => $q->where('estudiante_id', $solicitud->estudiante_id))
                ->whereDate('fecha', $solicitud->fecha_evento)
                ->whereIn('estado', ['ausente', 'tarde'])
                ->get();

            foreach ($asistencias as $asistencia) {
                $estadoAnterior = $asistencia->estado;
                $asistencia->update([
                    'estado'             => 'excusa',
                    'justificacion'      => $solicitud->descripcion,
                    'justificacion_tipo' => $tipoKey,
                    'registrado_por'     => Auth::id(),
                ]);

                ActivityLog::registrar(
                    'asistencia.estado_cambiado',
                    Asistencia::class,
                    $asistencia->id,
                    "Matrícula #{$asistencia->matricula_id} | Fecha: {$asistencia->fecha} | Estado: {$estadoAnterior} → excusa (justificación de representante aprobada, solicitud #{$solicitud->id})"
                );
                $asistenciasActualizadas++;
            }
        }

        // Notificar al representante si tiene cuenta de usuario
        $userId = $solicitud->representante?->user_id;
        if ($userId) {
            $estadoLabel = SolicitudRepresentante::estados()[$data['estado']]['label'] ?? $data['estado'];
            try {
                Notificacion::enviar(
                    $userId,
                    'solicitud',
                    "Solicitud {$estadoLabel}: {$solicitud->asunto}",
                    $data['respuesta'],
                );
            } catch (\Throwable) {}
        }

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_rep_stats");
        Cache::forget("t{$tid}_sol_rep_pend");

        $mensaje = 'Respuesta enviada correctamente.';
        if ($data['estado'] === 'aprobada' && $solicitud->tipo === 'justificacion_ausencia') {
            $mensaje .= $asistenciasActualizadas > 0
                ? " Se actualizó la asistencia de {$asistenciasActualizadas} clase(s) a justificada."
                : ' No se encontró ningún registro de asistencia ausente/tarde para esa fecha — revísalo si el docente aún no la ha tomado.';
        }

        return back()->with('success', $mensaje);
    }
}
