<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\Estudiante;
use App\Services\CarnetQrService;
use App\Services\CarnetRiskScoreService;
use Illuminate\Http\Request;

class CarnetApiController extends Controller
{
    // ── Estudiante: ver su propio carnet ──────────────────────────────────────

    public function miCarnet(Request $request)
    {
        $user   = $request->user();
        $carnet = CarnetIdentidad::with(['matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->where('user_id', $user->id)
            ->where('tipo', 'estudiante')
            ->first();

        if (! $carnet) {
            return response()->json(['message' => 'No tienes carnet generado aún.'], 404);
        }

        return response()->json([
            'carnet' => $this->formatCarnet($carnet),
            'risk'   => CarnetRiskScoreService::calcular($carnet),
        ]);
    }

    // ── Padre: ver carnet del hijo ────────────────────────────────────────────

    public function hijoCarnet(Request $request, Estudiante $estudiante)
    {
        $this->autorizarPadre($request->user(), $estudiante);

        $carnet = CarnetIdentidad::with(['matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->whereHas('matricula', fn($q) => $q->where('estudiante_id', $estudiante->id))
            ->where('tipo', 'estudiante')
            ->first();

        if (! $carnet) {
            return response()->json(['message' => 'El estudiante no tiene carnet generado.'], 404);
        }

        return response()->json([
            'carnet' => $this->formatCarnet($carnet),
            'risk'   => CarnetRiskScoreService::calcular($carnet),
        ]);
    }

    // ── Historial propio ──────────────────────────────────────────────────────

    public function historial(Request $request)
    {
        $user   = $request->user();
        $carnet = CarnetIdentidad::where('user_id', $user->id)->first();

        if (! $carnet) {
            return response()->json(['accesos' => []]);
        }

        $accesos = CarnetAcceso::with('zona')
            ->where('carnet_identidad_id', $carnet->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($a) => $this->formatAcceso($a));

        return response()->json(['accesos' => $accesos]);
    }

    // ── Padre: historial del hijo ─────────────────────────────────────────────

    public function historialHijo(Request $request, Estudiante $estudiante)
    {
        $this->autorizarPadre($request->user(), $estudiante);

        $carnet = CarnetIdentidad::whereHas('matricula', fn($q) => $q->where('estudiante_id', $estudiante->id))->first();

        if (! $carnet) {
            return response()->json(['accesos' => []]);
        }

        $accesos = CarnetAcceso::with('zona')
            ->where('carnet_identidad_id', $carnet->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($a) => $this->formatAcceso($a));

        return response()->json(['accesos' => $accesos]);
    }

    // ── Escaneo desde app (portero/seguridad) ─────────────────────────────────

    public function scan(Request $request)
    {
        $request->validate([
            'qr_token'    => 'required|string',
            'tipo_evento' => 'nullable|in:entrada,salida,biblioteca,comedor,laboratorio,evento,prestamo',
            'zona_id'     => 'nullable|integer',
        ]);

        $tenant   = app()->bound('tenant') ? app('tenant') : null;
        $tenantId = $tenant?->id ?? 0;

        $carnet = CarnetQrService::resolverQrPermanente($request->qr_token, $tenantId);

        if (! $carnet) {
            return response()->json(['success' => false, 'message' => 'QR inválido o carnet suspendido.'], 403);
        }

        $tipoEvento = $request->tipo_evento ?? 'entrada';
        $estado     = 'presente';
        if ($tipoEvento === 'entrada' && now()->greaterThan(now()->setTimeFromTimeString('07:30:00'))) {
            $estado = 'tardanza';
        }
        if ($tipoEvento === 'salida') $estado = 'salida_anticipada';

        $acceso = CarnetAcceso::create([
            'carnet_identidad_id' => $carnet->id,
            'tipo_evento'         => $tipoEvento,
            'estado'              => $estado,
            'zona_id'             => $request->zona_id,
            'dispositivo'         => $request->userAgent(),
            'ip_address'          => $request->ip(),
            'registrado_por'      => $request->user()?->id,
        ]);

        return response()->json([
            'success'  => true,
            'nombre'   => $carnet->nombre_completo,
            'carnet'   => $carnet->numero_carnet,
            'estado'   => $acceso->estado,
            'hora'     => $acceso->hora,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatCarnet(CarnetIdentidad $c): array
    {
        return [
            'id'              => $c->id,
            'numero_carnet'   => $c->numero_carnet,
            'tipo'            => $c->tipo,
            'estado'          => $c->estado,
            'vigencia_hasta'  => $c->vigencia_hasta?->toDateString(),
            'grupo'           => $c->matricula?->grupo?->nombre_completo,
            'qr_url'          => CarnetQrService::qrContent($c),
            'foto'            => $c->user?->foto ? asset('storage/' . $c->user->foto) : null,
        ];
    }

    private function formatAcceso(CarnetAcceso $a): array
    {
        $badge = $a->estado_badge;
        return [
            'id'          => $a->id,
            'tipo_evento' => $a->tipo_evento,
            'estado'      => $a->estado,
            'estado_label'=> $badge['label'],
            'estado_color'=> $badge['color'],
            'zona'        => $a->zona?->nombre,
            'hora'        => $a->hora,
            'fecha'       => $a->created_at?->toDateString(),
        ];
    }

    private function autorizarPadre($user, Estudiante $estudiante): void
    {
        $esRepresentante = $user->hasRole('Representante') &&
            \App\Models\Representante::where('user_id', $user->id)
                ->whereHas('estudiantes', fn($q) => $q->where('estudiantes.id', $estudiante->id))
                ->exists();

        if (! $esRepresentante) {
            abort(403, 'No tienes permiso para ver este estudiante.');
        }
    }
}
