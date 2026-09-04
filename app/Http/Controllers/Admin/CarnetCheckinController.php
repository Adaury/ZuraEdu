<?php

namespace App\Http\Controllers\Admin;

use App\Events\CarnetEscaneado;
use App\Http\Controllers\Controller;
use App\Jobs\NotificarPadreAccesoJob;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\CarnetZona;
use App\Services\CarnetQrService;
use App\Services\CarnetRiskScoreService;
use Illuminate\Http\Request;

class CarnetCheckinController extends Controller
{
    // ── Vista kiosco tablet ───────────────────────────────────────────────────

    public function kiosko()
    {
        $zonas = CarnetZona::activas()->orderBy('nombre')->get();
        return view('admin.carnet.checkin', compact('zonas'));
    }

    // ── Escaneo via API POST (kiosco / app) ───────────────────────────────────

    public function scan(Request $request)
    {
        $request->validate([
            'qr_token'    => 'required|string',
            'zona_id'     => 'nullable|integer',
            'tipo_evento' => 'nullable|in:entrada,salida,biblioteca,comedor,laboratorio,evento,prestamo',
        ]);

        $tenant   = app()->bound('tenant') ? app('tenant') : null;
        $tenantId = $tenant?->id ?? 0;

        $carnet = CarnetQrService::resolverQrPermanente($request->qr_token, $tenantId);

        if (! $carnet) {
            return response()->json([
                'success' => false,
                'estado'  => 'denegado',
                'mensaje' => 'QR no válido o carnet suspendido.',
                'color'   => 'danger',
            ], 404);
        }

        // Detectar tardanza (entrada después de la hora de entrada configurada — 7:30 AM por defecto)
        $tipoEvento = $request->tipo_evento ?? 'entrada';
        $estado     = 'presente';

        if ($tipoEvento === 'entrada') {
            $horaEntrada = now()->setTimeFromTimeString('07:30:00');
            if (now()->greaterThan($horaEntrada)) {
                $estado = 'tardanza';
            }
        }

        $acceso = CarnetAcceso::create([
            'carnet_identidad_id' => $carnet->id,
            'tipo_evento'         => $tipoEvento,
            'estado'              => $tipoEvento === 'salida' ? 'salida_anticipada' : $estado,
            'zona_id'             => $request->zona_id,
            'dispositivo'         => $request->userAgent(),
            'ip_address'          => $request->ip(),
            'registrado_por'      => auth()->id(),
        ]);

        $hora  = $acceso->hora;
        $grupo = $carnet->matricula?->grupo?->nombre_completo;
        $foto  = $carnet->user?->foto ? asset('storage/' . $carnet->user->foto) : null;

        // Broadcast al kiosco en tiempo real
        try {
            CarnetEscaneado::dispatch(
                $tenantId,
                $carnet->nombre_completo,
                $carnet->numero_carnet,
                $tipoEvento,
                $acceso->estado,
                $hora,
                $foto,
                $grupo,
            );
        } catch (\Throwable) {}

        // Notificar al padre en background
        if ($carnet->matricula_id) {
            dispatch(new NotificarPadreAccesoJob(
                carnetId:   $carnet->id,
                tipoEvento: $tipoEvento,
                estado:     $acceso->estado,
                hora:       $hora,
                tenantId:   $tenantId,
            ));
        }

        // Invalidar caché de riesgo
        CarnetRiskScoreService::invalidar($carnet);

        return response()->json([
            'success'       => true,
            'estado'        => $acceso->estado,
            'tipo_evento'   => $tipoEvento,
            'nombre'        => $carnet->nombre_completo,
            'numero_carnet' => $carnet->numero_carnet,
            'grupo'         => $grupo,
            'hora'          => $hora,
            'foto'          => $foto,
            'color'         => match($acceso->estado) {
                'tardanza'          => 'warning',
                'denegado'          => 'danger',
                'salida_anticipada' => 'info',
                default             => 'success',
            },
            'mensaje' => match($acceso->estado) {
                'tardanza'          => "Tardanza registrada — {$carnet->nombre_completo}",
                'salida_anticipada' => "Salida registrada — {$carnet->nombre_completo}",
                default             => "Acceso permitido — {$carnet->nombre_completo}",
            },
        ]);
    }

    // ── Scan via URL pública (cualquier cámara, sin login) ────────────────────
    // El QR impreso en el carnet físico es literalmente esta URL — cualquiera
    // que lo escanee (o tenga una foto del carnet) llega aquí sin sesión. El
    // token es permanente (dura hasta que se reimprime el carnet), así que
    // NO debe devolver datos que identifiquen a la persona (nombre, grupo) —
    // hallazgo Alto de auditoría 2026-09-04 (H2): antes devolvía nombre
    // completo y grupo, exponiendo esa información indefinidamente a quien
    // sea que tuviera el token. La app móvil (docente) NO usa este endpoint
    // — usa el autenticado scan() vía carnetApi.scan() — así que reducir la
    // respuesta aquí no rompe ningún cliente real del sistema.
    public function scanPublico(string $qrToken)
    {
        $tenant   = app()->bound('tenant') ? app('tenant') : null;
        $tenantId = $tenant?->id ?? 0;

        $carnet = CarnetQrService::resolverQrPermanente($qrToken, $tenantId);

        if (! $carnet) {
            return response()->json(['valido' => false], 404);
        }

        return response()->json([
            'valido'        => $carnet->estado === 'activo',
            'numero_carnet' => $carnet->numero_carnet,
            'tipo'          => $carnet->tipo,
        ]);
    }
}
