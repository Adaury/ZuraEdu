<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeService
{
    private string $secret;
    private string $webhookSecret;
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->secret        = config('services.stripe.secret', '');
        $this->webhookSecret = config('services.stripe.webhook_secret', '');
    }

    public function estaConfigurado(): bool
    {
        return ! empty($this->secret) && str_starts_with($this->secret, 'sk_');
    }

    // ── Crear sesión de pago ──────────────────────────────────────────────

    public function crearCheckoutSession(Tenant $tenant, Plan $plan, string $ciclo): array
    {
        $precio = $ciclo === 'anual' ? $plan->precio_anual : $plan->precio_mensual;
        $meses  = $ciclo === 'anual' ? 12 : 1;

        $params = [
            'payment_method_types[]'                            => 'card',
            'mode'                                              => 'payment',
            'line_items[0][price_data][currency]'               => strtolower($plan->moneda ?? 'usd'),
            'line_items[0][price_data][product_data][name]'    => "ZuraEdu {$plan->nombre} — " . ucfirst($ciclo),
            'line_items[0][price_data][product_data][description]' => $plan->descripcion ?? '',
            'line_items[0][price_data][unit_amount]'            => (int) round($precio * 100),
            'line_items[0][quantity]'                           => 1,
            'success_url'                                       => route('admin.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                                        => route('admin.billing.cancel'),
            'customer_email'                                    => $tenant->email_contacto ?? '',
            'metadata[tenant_id]'                               => $tenant->id,
            'metadata[plan_slug]'                               => $plan->slug,
            'metadata[ciclo]'                                   => $ciclo,
            'metadata[meses]'                                   => $meses,
        ];

        // Usar customer_id si ya existe
        if ($tenant->stripe_customer_id) {
            unset($params['customer_email']);
            $params['customer'] = $tenant->stripe_customer_id;
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->asForm()
            ->post("{$this->baseUrl}/checkout/sessions", $params);

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? 'Error desconocido de Stripe';
            throw new RuntimeException("Stripe error: {$msg}");
        }

        return $response->json();
    }

    // ── Recuperar sesión ──────────────────────────────────────────────────

    public function obtenerSession(string $sessionId): array
    {
        $response = Http::withBasicAuth($this->secret, '')
            ->get("{$this->baseUrl}/checkout/sessions/{$sessionId}");

        if ($response->failed()) {
            throw new RuntimeException('No se pudo verificar la sesión de pago.');
        }

        return $response->json();
    }

    // ── Verificar firma del webhook ───────────────────────────────────────

    public function verificarWebhook(string $payload, string $sigHeader): array
    {
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v]    = explode('=', $part, 2);
            $parts[$k][] = $v;
        }

        $timestamp  = $parts['t'][0]  ?? null;
        $signatures = $parts['v1']    ?? [];

        if (! $timestamp || empty($signatures)) {
            throw new RuntimeException('Firma de webhook inválida.');
        }

        if (abs(time() - (int) $timestamp) > 300) {
            throw new RuntimeException('Webhook expirado (>5 min).');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $this->webhookSecret);

        if (! in_array($expected, $signatures, true)) {
            throw new RuntimeException('Firma de webhook no coincide.');
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    // ── Crear / buscar customer ───────────────────────────────────────────

    public function obtenerOCrearCustomer(Tenant $tenant): string
    {
        if ($tenant->stripe_customer_id) {
            return $tenant->stripe_customer_id;
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->asForm()
            ->post("{$this->baseUrl}/customers", [
                'email'              => $tenant->email_contacto ?? '',
                'name'               => $tenant->nombre_institucion,
                'metadata[tenant_id]'=> $tenant->id,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('No se pudo crear el customer en Stripe.');
        }

        $customerId = $response->json('id');
        $tenant->update(['stripe_customer_id' => $customerId]);

        return $customerId;
    }
}
