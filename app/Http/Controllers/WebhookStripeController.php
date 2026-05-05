<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BillingController;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookStripeController extends Controller
{
    public function __construct(
        private StripeService   $stripe,
        private BillingController $billing,
    ) {}

    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripe->verificarWebhook($payload, $sigHeader);
        } catch (\RuntimeException $e) {
            Log::warning('Stripe webhook firma inválida: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }

        Log::info('Stripe webhook recibido: ' . $event['type']);

        try {
            match ($event['type']) {
                'checkout.session.completed' => $this->onCheckoutCompleted($event['data']['object']),
                default                      => null,
            };
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error al procesar: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function onCheckoutCompleted(array $session): void
    {
        if ($session['payment_status'] !== 'paid') {
            return;
        }

        $meta = $session['metadata'] ?? [];

        if (empty($meta['tenant_id']) || empty($meta['plan_slug'])) {
            Log::warning('Stripe webhook: metadata incompleta', $meta);
            return;
        }

        $this->billing->activarSuscripcion(
            tenantId:        (int) $meta['tenant_id'],
            planSlug:        $meta['plan_slug'],
            ciclo:           $meta['ciclo'] ?? 'mensual',
            meses:           (int) ($meta['meses'] ?? 1),
            monto:           $session['amount_total'] / 100,
            stripeSessionId: $session['id'],
            metodoPago:      'stripe',
        );
    }
}
