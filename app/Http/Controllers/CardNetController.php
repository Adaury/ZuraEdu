<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\CardNetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardNetController extends Controller
{
    /**
     * Muestra el formulario de auto-submit hacia la página de pago de CardNet.
     * El token identifica los parámetros guardados en caché.
     */
    public function checkout(string $token)
    {
        $data = cache()->get("cardnet_form_{$token}");

        if (! $data) {
            return redirect('/pagos/cancelado')->with('error', 'El enlace de pago expiró. Intenta de nuevo.');
        }

        return view('cardnet.checkout', [
            'url'    => $data['url'],
            'params' => $data['params'],
        ]);
    }

    /**
     * Notificación IPN server-to-server enviada por CardNet tras el pago.
     * Debe ser accesible sin autenticación y sin CSRF (exento en el middleware).
     */
    public function notify(Request $request)
    {
        Log::info('CardNet IPN recibido', $request->all());

        $result = CardNetService::verifyNotification($request->all());

        if (! $result) {
            return response('FIRMA_INVALIDA', 400);
        }

        if ($result['approved']) {
            $this->procesarPagoAprobado($result);
        } else {
            Log::info('CardNet IPN: pago rechazado', [
                'order_id'      => $result['order_id'],
                'response_code' => $result['response_code'],
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Retorno del usuario desde la página de CardNet (GET).
     * CardNet redirige aquí al finalizar (aprobado o no). El estado real
     * lo conocemos por el IPN, así que mostramos una pantalla de espera.
     */
    public function retorno(Request $request)
    {
        $orderId = $request->query('OrderId', '');
        $code    = $request->query('ResponseCode', '');

        $approved = in_array($code, CardNetService::RESPUESTAS_APROBADAS);

        return view('cardnet.retorno', compact('orderId', 'approved', 'code'));
    }

    // ── Lógica de negocio ─────────────────────────────────────────────────

    private function procesarPagoAprobado(array $result): void
    {
        $metadata = $result['metadata'];
        $pagoId   = $metadata['pago_id'] ?? null;

        if (! $pagoId) {
            Log::warning('CardNet IPN: metadata sin pago_id', $result);
            return;
        }

        $pago = Pago::find($pagoId);

        if (! $pago) {
            Log::warning('CardNet IPN: Pago no encontrado', ['pago_id' => $pagoId]);
            return;
        }

        if ($pago->estado === 'pagado') {
            Log::info('CardNet IPN: pago ya estaba marcado como pagado', ['pago_id' => $pagoId]);
            return;
        }

        $pago->update([
            'estado'           => 'pagado',
            'fecha_pago'       => now(),
            'metodo_pago'      => 'cardnet',
            'referencia'       => $result['auth_code'],
            'notas'            => "CardNet | OrderId: {$result['order_id']} | Auth: {$result['auth_code']} | Tx: {$result['transaction_id']}",
        ]);

        Log::info('CardNet IPN: pago actualizado a pagado', [
            'pago_id'  => $pagoId,
            'order_id' => $result['order_id'],
            'auth_code'=> $result['auth_code'],
        ]);

        \App\Events\PagoConfirmado::dispatch($pago);
    }
}
