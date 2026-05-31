<?php

namespace App\Services;

use App\Helpers\Setting;
use App\Services\CardNetService;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public static function isAvailable(): bool
    {
        return Setting::isPrivado() && Setting::moduleEnabled('payments');
    }

    public static function createCheckout(
        string $description,
        float $amount,
        array $metadata = [],
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): ?array {
        if (! static::isAvailable()) return null;

        try {
            return match (Setting::get('payments_gateway', 'stripe')) {
                'stripe'  => static::stripeCheckout($description, $amount, $metadata, $successUrl, $cancelUrl),
                'cardnet' => static::cardnetCheckout($description, $amount, $metadata),
                default   => null,
            };
        } catch (\Throwable $e) {
            Log::error('PaymentService error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public static function isStripe(): bool
    {
        return Setting::get('payments_gateway', 'stripe') === 'stripe';
    }

    private static function stripeCheckout(
        string $description,
        float $amount,
        array $metadata,
        ?string $successUrl,
        ?string $cancelUrl,
    ): array {
        $sk       = Setting::get('payments_stripe_sk');
        $currency = strtolower(Setting::get('payments_currency', 'DOP'));
        $appUrl   = config('app.url');

        if (! $sk) throw new \RuntimeException('Stripe secret key no configurada.');

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($sk, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', array_filter([
                'payment_method_types[]'                         => 'card',
                'mode'                                           => 'payment',
                'line_items[0][price_data][currency]'            => $currency,
                'line_items[0][price_data][unit_amount]'         => (int) ($amount * 100),
                'line_items[0][price_data][product_data][name]'  => $description,
                'line_items[0][quantity]'                        => 1,
                'success_url'                                    => $successUrl ?? "{$appUrl}/stripe/pago-ok?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url'                                     => $cancelUrl  ?? "{$appUrl}/stripe/pago-cancelado",
            ] + static::flattenMetadata($metadata)));

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? 'Error de Stripe';
            throw new \RuntimeException("Stripe: {$msg}");
        }

        $session = $response->json();
        return ['url' => $session['url'], 'id' => $session['id']];
    }

    private static function flattenMetadata(array $metadata): array
    {
        $flat = [];
        foreach ($metadata as $k => $v) {
            $flat["metadata[{$k}]"] = $v;
        }
        return $flat;
    }

    private static function cardnetCheckout(string $description, float $amount, array $metadata): array
    {
        if (! CardNetService::isConfigured()) {
            throw new \RuntimeException('CardNet no configurado. Ve a Pagos → Configuración y completa Merchant ID y Secret Key.');
        }

        $pagoId  = $metadata['pago_id'] ?? mt_rand(100000, 999999);
        $orderId = CardNetService::generateOrderId((int) $pagoId);

        $checkout = CardNetService::createCheckoutParams($orderId, $amount, array_merge($metadata, [
            'description' => $description,
        ]));

        // Guardamos los parámetros del form en caché para que el endpoint
        // /cardnet/checkout los recupere y renderice el auto-submit.
        $token = \Illuminate\Support\Str::uuid()->toString();
        cache()->put("cardnet_form_{$token}", $checkout, now()->addMinutes(30));

        return [
            'url' => config('app.url') . "/cardnet/checkout/{$token}",
            'id'  => $orderId,
        ];
    }
}
