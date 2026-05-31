<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BillingController;
use App\Mail\PagoReembolsado;
use App\Mail\SuscripcionActivada;
use App\Models\Pago;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookStripeController extends Controller
{
    public function __construct(
        private StripeService     $stripe,
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

        $eventId   = $event['id']   ?? null;
        $eventType = $event['type'] ?? 'unknown';

        // ── Idempotencia ──────────────────────────────────────────────────
        if ($eventId && DB::table('stripe_webhook_events')->where('stripe_event_id', $eventId)->exists()) {
            Log::info("Stripe webhook: evento ya procesado [{$eventId}]");
            return response()->json(['received' => true, 'skipped' => true]);
        }

        Log::info("Stripe webhook recibido: {$eventType} [{$eventId}]");

        $status = 'processed';
        $error  = null;

        try {
            match ($eventType) {
                'checkout.session.completed'  => $this->onCheckoutCompleted($event['data']['object']),
                'charge.refunded'             => $this->onChargeRefunded($event['data']['object']),
                'charge.dispute.created'      => $this->onDisputeCreated($event['data']['object']),
                'payment_intent.payment_failed' => $this->onPaymentFailed($event['data']['object']),
                'checkout.session.expired'    => $this->onSessionExpired($event['data']['object']),
                default                       => null,
            };
        } catch (\Throwable $e) {
            Log::error("Stripe webhook error [{$eventType}]: " . $e->getMessage(), [
                'event_id' => $eventId,
                'trace'    => $e->getTraceAsString(),
            ]);
            $status = 'failed';
            $error  = $e->getMessage();

            // Guardar registro de fallo y retornar 500 para que Stripe reintente
            if ($eventId) {
                DB::table('stripe_webhook_events')->insert([
                    'stripe_event_id' => $eventId,
                    'type'            => $eventType,
                    'status'          => 'failed',
                    'error'           => substr($error, 0, 500),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json(['error' => 'Error interno'], 500);
        }

        // Registrar evento procesado (idempotencia futura)
        if ($eventId) {
            DB::table('stripe_webhook_events')->insertOrIgnore([
                'stripe_event_id' => $eventId,
                'type'            => $eventType,
                'status'          => $status,
                'error'           => $error,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json(['received' => true]);
    }

    // ── checkout.session.completed ────────────────────────────────────────

    private function onCheckoutCompleted(array $session): void
    {
        if ($session['payment_status'] !== 'paid') {
            Log::info('Stripe webhook: checkout sin pago confirmado', ['status' => $session['payment_status']]);
            return;
        }

        $meta = $session['metadata'] ?? [];

        // ── Pago de cuota de estudiante ───────────────────────────────────
        if (! empty($meta['pago_id'])) {
            $this->procesarPagoEstudiante((int) $meta['pago_id'], $session['id']);
            return;
        }

        // ── Suscripción de tenant (flujo original) ────────────────────────
        if (empty($meta['tenant_id']) || empty($meta['plan_slug'])) {
            Log::warning('Stripe webhook: metadata sin tenant_id/plan_slug ni pago_id', $meta);
            return;
        }

        // Evitar doble activación con la URL de éxito
        if (Subscription::where('stripe_session_id', $session['id'])->exists()) {
            Log::info('Stripe webhook: checkout ya procesado vía success URL', ['session' => $session['id']]);
            return;
        }

        $this->billing->activarSuscripcion(
            tenantId:           (int) $meta['tenant_id'],
            planSlug:           $meta['plan_slug'],
            ciclo:              $meta['ciclo'] ?? 'mensual',
            meses:              (int) ($meta['meses'] ?? 1),
            monto:              $session['amount_total'] / 100,
            stripeSessionId:    $session['id'],
            stripePaymentIntent: $session['payment_intent'] ?? null,
            metodoPago:         'stripe',
        );

        // Guardar customer_id si vino en la sesión
        if (! empty($session['customer'])) {
            $tenant = Tenant::find((int) $meta['tenant_id']);
            if ($tenant && ! $tenant->stripe_customer_id) {
                $tenant->update(['stripe_customer_id' => $session['customer']]);
            }
        }

        // Email de confirmación
        $this->enviarEmailActivacion((int) $meta['tenant_id'], $meta['plan_slug'], $meta['ciclo'] ?? 'mensual');
    }

    private function procesarPagoEstudiante(int $pagoId, string $sessionId): void
    {
        $pago = Pago::find($pagoId);

        if (! $pago) {
            Log::warning("Stripe webhook: pago #{$pagoId} no encontrado");
            return;
        }

        if ($pago->estado === 'pagado') {
            Log::info("Stripe webhook: pago #{$pagoId} ya estaba pagado — idempotente");
            return;
        }

        $pago->update([
            'estado'      => 'pagado',
            'fecha_pago'  => now()->toDateString(),
            'metodo_pago' => 'stripe',
            'referencia'  => $sessionId,
        ]);

        Log::info("Stripe webhook: pago #{$pagoId} marcado como pagado", ['session' => $sessionId]);

        \App\Events\PagoConfirmado::dispatch($pago);
    }

    // ── charge.refunded ───────────────────────────────────────────────────

    private function onChargeRefunded(array $charge): void
    {
        // Solo actuar en reembolso total
        if ($charge['amount_refunded'] < $charge['amount']) {
            Log::info('Stripe webhook: reembolso parcial, sin cambio de plan', [
                'charge'            => $charge['id'],
                'amount'            => $charge['amount'],
                'amount_refunded'   => $charge['amount_refunded'],
            ]);
            return;
        }

        $paymentIntentId = $charge['payment_intent'] ?? null;

        if (! $paymentIntentId) {
            Log::warning('Stripe webhook: charge.refunded sin payment_intent', ['charge' => $charge['id']]);
            return;
        }

        $subscription = Subscription::where('stripe_payment_intent', $paymentIntentId)->first();

        if (! $subscription) {
            Log::warning('Stripe webhook: no se encontró suscripción para reembolso', [
                'payment_intent' => $paymentIntentId,
            ]);
            return;
        }

        $tenant = Tenant::find($subscription->tenant_id);
        if (! $tenant) return;

        Log::info("Stripe webhook: reembolso total — downgrade tenant [{$tenant->id}] a free");

        $this->billing->downgradarAFree($subscription->tenant_id, 'reembolso_stripe');

        $this->enviarEmailReembolso($tenant, $subscription);
    }

    // ── charge.dispute.created ────────────────────────────────────────────

    private function onDisputeCreated(array $dispute): void
    {
        $paymentIntentId = $dispute['payment_intent'] ?? null;

        Log::warning('Stripe webhook: disputa creada', [
            'dispute_id'     => $dispute['id'],
            'amount'         => $dispute['amount'] ?? 0,
            'reason'         => $dispute['reason'] ?? 'unknown',
            'payment_intent' => $paymentIntentId,
        ]);

        if (! $paymentIntentId) return;

        $subscription = Subscription::where('stripe_payment_intent', $paymentIntentId)->first();
        if (! $subscription) return;

        // Suspender preventivamente hasta resolución
        $subscription->update(['estado' => 'suspendida']);

        $tenant = Tenant::find($subscription->tenant_id);
        if ($tenant) {
            Log::warning("Stripe webhook: tenant [{$tenant->id}] suspendido por disputa [{$dispute['id']}]");
        }
    }

    // ── payment_intent.payment_failed ─────────────────────────────────────

    private function onPaymentFailed(array $paymentIntent): void
    {
        $lastError = $paymentIntent['last_payment_error'] ?? [];

        Log::warning('Stripe webhook: pago fallido', [
            'payment_intent' => $paymentIntent['id'],
            'error_code'     => $lastError['code']    ?? 'unknown',
            'error_message'  => $lastError['message'] ?? '—',
            'customer'       => $paymentIntent['customer'] ?? null,
        ]);
        // En modo one-time payment el usuario simplemente no completa el checkout.
        // Solo registramos para auditoría.
    }

    // ── checkout.session.expired ──────────────────────────────────────────

    private function onSessionExpired(array $session): void
    {
        Log::info('Stripe webhook: sesión de checkout expirada', [
            'session'    => $session['id'],
            'tenant_id'  => $session['metadata']['tenant_id'] ?? null,
            'plan'       => $session['metadata']['plan_slug'] ?? null,
        ]);
        // No hay suscripción pendiente que limpiar (la sesión de Stripe nunca se pagó).
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function enviarEmailActivacion(int $tenantId, string $planSlug, string $ciclo): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant || ! $tenant->email_contacto) return;

        try {
            Mail::to($tenant->email_contacto)->send(new SuscripcionActivada($tenant, $planSlug, $ciclo));
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook: no se pudo enviar email de activación — ' . $e->getMessage());
        }
    }

    private function enviarEmailReembolso(Tenant $tenant, Subscription $subscription): void
    {
        if (! $tenant->email_contacto) return;

        try {
            Mail::to($tenant->email_contacto)->send(new PagoReembolsado($tenant, $subscription));
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook: no se pudo enviar email de reembolso — ' . $e->getMessage());
        }
    }
}
