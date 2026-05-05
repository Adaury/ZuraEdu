<?php

namespace App\Services;

use App\Helpers\Setting;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public static function isAvailable(): bool
    {
        return Setting::isPrivado() && Setting::moduleEnabled('payments');
    }

    public static function createCheckout(string $description, float $amount, array $metadata = []): ?array
    {
        if (! static::isAvailable()) return null;

        try {
            return match (Setting::get('payments_gateway', 'stripe')) {
                'stripe'  => static::stripeCheckout($description, $amount, $metadata),
                'cardnet' => static::cardnetCheckout($description, $amount, $metadata),
                default   => null,
            };
        } catch (\Throwable $e) {
            Log::error('PaymentService error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private static function stripeCheckout(string $description, float $amount, array $metadata): array
    {
        $sk       = Setting::get('payments_stripe_sk');
        $currency = strtolower(Setting::get('payments_currency', 'DOP'));
        $appUrl   = config('app.url');

        if (! $sk) throw new \RuntimeException('Stripe secret key no configurada.');
        if (! class_exists('\Stripe\Stripe')) throw new \RuntimeException('Ejecuta: composer require stripe/stripe-php');

        \Stripe\Stripe::setApiKey($sk);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => (int) ($amount * 100),
                    'product_data' => ['name' => $description],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => "{$appUrl}/pagos/confirmacion?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url'  => "{$appUrl}/pagos/cancelado",
            'metadata'    => $metadata,
        ]);

        return ['url' => $session->url, 'id' => $session->id];
    }

    private static function cardnetCheckout(string $description, float $amount, array $metadata): array
    {
        // TODO: Integración CardNet RD — https://developers.cardnet.com.do
        throw new \RuntimeException('Integración CardNet pendiente. Usa Stripe por ahora.');
    }
}
