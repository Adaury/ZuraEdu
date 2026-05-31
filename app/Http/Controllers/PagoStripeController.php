<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoStripeController extends Controller
{
    public function __construct(private StripeService $stripe) {}

    public function ok(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('portal.estudiante.mis-pagos')
                ->with('error', 'Sesión de pago inválida.');
        }

        try {
            $session = $this->stripe->obtenerSession($sessionId);
        } catch (\Throwable $e) {
            Log::warning('PagoStripe: no se pudo verificar sesión — ' . $e->getMessage());
            return $this->redirigirPortal(null, 'No se pudo verificar el estado del pago. Contacta la administración.');
        }

        $meta   = $session['metadata'] ?? [];
        $pagoId = $meta['pago_id'] ?? null;
        $origen = $meta['origen']  ?? 'portal_estudiante';

        if (! $pagoId) {
            Log::warning('PagoStripe: session sin pago_id en metadata', ['session' => $sessionId]);
            return $this->redirigirPortal($origen, 'No se encontró el registro de pago asociado.');
        }

        $pago = Pago::find($pagoId);

        if (! $pago) {
            return $this->redirigirPortal($origen, 'Pago no encontrado.');
        }

        if ($pago->estado === 'pagado') {
            return $this->redirigirPortal($origen, null, '¡Pago confirmado! Tu recibo está disponible.');
        }

        if (($session['payment_status'] ?? '') === 'paid') {
            $pago->update([
                'estado'      => 'pagado',
                'fecha_pago'  => now()->toDateString(),
                'metodo_pago' => 'stripe',
                'referencia'  => $sessionId,
            ]);

            Log::info("PagoStripe: pago #{$pago->id} marcado como pagado vía success URL", [
                'session' => $sessionId,
                'origen'  => $origen,
            ]);

            return $this->redirigirPortal($origen, null, '¡Pago realizado con éxito! Tu recibo está disponible.');
        }

        return $this->redirigirPortal($origen, 'El pago no fue completado. Intenta nuevamente.');
    }

    public function cancelado(Request $request)
    {
        $origen = $request->query('origen', 'portal_estudiante');
        return $this->redirigirPortal($origen, 'Cancelaste el proceso de pago. Puedes intentarlo nuevamente cuando quieras.');
    }

    private function redirigirPortal(?string $origen, ?string $error, ?string $success = null)
    {
        $route = match ($origen) {
            'portal_padre' => 'portal.padre.dashboard',
            default        => 'portal.estudiante.mis-pagos',
        };

        return redirect()->route($route)
            ->with($error ? 'error' : 'success', $error ?? $success);
    }
}
