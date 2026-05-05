<?php

namespace App\Services;

use App\Helpers\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Integración con CardNet RD — Pago en Línea (hosted payment page).
 *
 * Flujo:
 *   1. createCheckoutParams()  → devuelve array de parámetros firmados
 *   2. El caller redirige al usuario a un endpoint propio que auto-submite
 *      el form hacia CARDNET_URL_PAGO (página hosteada de CardNet).
 *   3. CardNet notifica el resultado vía IPN a /cardnet/notify (POST).
 *   4. verifyNotification() valida la firma y devuelve el resultado.
 *
 * Documentación: https://developers.cardnet.com.do
 */
class CardNetService
{
    // Producción
    const URL_PAGO_PROD    = 'https://pagonline.cardnet.com.do/paymentpage/payment';
    // Sandbox
    const URL_PAGO_SANDBOX = 'https://pagonlineqa.cardnet.com.do/paymentpage/payment';

    const CURRENCY_DOP = '214';
    const CURRENCY_USD = '840';

    // Códigos de respuesta aprobados
    const RESPUESTAS_APROBADAS = ['00', '08', '10'];

    // ── Config ────────────────────────────────────────────────────────────

    public static function merchantId(): string
    {
        return Setting::get('payments_cardnet_merchant_id', '');
    }

    public static function terminalId(): string
    {
        return Setting::get('payments_cardnet_terminal_id', '00000001');
    }

    public static function secretKey(): string
    {
        return Setting::get('payments_cardnet_secret_key', '');
    }

    public static function sandbox(): bool
    {
        return (bool) Setting::get('payments_cardnet_sandbox', '1');
    }

    public static function urlPago(): string
    {
        return static::sandbox() ? static::URL_PAGO_SANDBOX : static::URL_PAGO_PROD;
    }

    public static function isConfigured(): bool
    {
        return static::merchantId() !== '' && static::secretKey() !== '';
    }

    // ── Construcción de parámetros de pago ───────────────────────────────

    /**
     * Genera los parámetros firmados para el form POST a CardNet.
     *
     * @param  string $orderId      ID único de la orden (max 12 chars, alfanumérico)
     * @param  float  $amount       Monto total en la moneda configurada
     * @param  array  $metadata     Datos adicionales guardados en caché (pago_id, etc.)
     * @return array{params: array, url: string}
     */
    public static function createCheckoutParams(string $orderId, float $amount, array $metadata = []): array
    {
        $merchantId     = static::merchantId();
        $terminalId     = static::terminalId();
        $transactionType = '1'; // Sale
        $currency       = static::currencyCode();
        $amountCents    = (int) round($amount * 100);
        $taxCents       = 0; // Sin ITBIS separado; incluido en el monto
        $appUrl         = config('app.url');

        $params = [
            'MerchantId'      => $merchantId,
            'TerminalId'      => $terminalId,
            'TransactionType' => $transactionType,
            'OrderId'         => $orderId,
            'Amount'          => $amountCents,
            'Tax'             => $taxCents,
            'Currency'        => $currency,
            'ReturnUrl'       => "{$appUrl}/cardnet/retorno",
            'CancelUrl'       => "{$appUrl}/pagos/cancelado",
            'NotifyUrl'       => "{$appUrl}/cardnet/notify",
        ];

        $params['Signature'] = static::buildSignature(
            $merchantId,
            $terminalId,
            $transactionType,
            $orderId,
            $amountCents,
            $currency
        );

        // Guardar metadata en caché para recuperarla al recibir el IPN
        cache()->put("cardnet_order_{$orderId}", $metadata, now()->addHours(2));

        return [
            'params' => $params,
            'url'    => static::urlPago(),
        ];
    }

    // ── Firma ─────────────────────────────────────────────────────────────

    /**
     * HMAC-SHA256 de los campos clave según especificación CardNet RD.
     * Concatena: MerchantId + TerminalId + TransactionType + OrderId + Amount + Currency
     */
    public static function buildSignature(
        string $merchantId,
        string $terminalId,
        string $transactionType,
        string $orderId,
        int    $amount,
        string $currency
    ): string {
        $data = $merchantId . $terminalId . $transactionType . $orderId . $amount . $currency;
        return hash_hmac('sha256', $data, static::secretKey());
    }

    // ── Verificación de notificación IPN ─────────────────────────────────

    /**
     * Verifica la firma del IPN de CardNet y devuelve un resultado estructurado.
     *
     * @param  array $payload  Datos POST recibidos de CardNet
     * @return array{approved: bool, order_id: string, auth_code: string, response_code: string, metadata: array}|null
     *         null si la firma no es válida
     */
    public static function verifyNotification(array $payload): ?array
    {
        $orderId      = $payload['OrderId']          ?? '';
        $responseCode = $payload['ResponseCode']     ?? '';
        $authCode     = $payload['AuthorizationCode'] ?? '';
        $transId      = $payload['TransactionId']    ?? '';
        $signature    = $payload['Signature']        ?? '';

        // Verificar firma del IPN: HMAC-SHA256(OrderId + ResponseCode + AuthorizationCode, secret)
        $expectedSig = hash_hmac('sha256', $orderId . $responseCode . $authCode, static::secretKey());

        if (! hash_equals($expectedSig, strtolower($signature))) {
            Log::warning('CardNet IPN: firma inválida', [
                'order_id'  => $orderId,
                'expected'  => $expectedSig,
                'received'  => $signature,
            ]);
            return null;
        }

        $metadata = cache()->pull("cardnet_order_{$orderId}", []);

        Log::info('CardNet IPN recibido', [
            'order_id'      => $orderId,
            'response_code' => $responseCode,
            'auth_code'     => $authCode,
            'transaction_id'=> $transId,
            'approved'      => in_array($responseCode, static::RESPUESTAS_APROBADAS),
        ]);

        return [
            'approved'       => in_array($responseCode, static::RESPUESTAS_APROBADAS),
            'order_id'       => $orderId,
            'auth_code'      => $authCode,
            'response_code'  => $responseCode,
            'transaction_id' => $transId,
            'metadata'       => $metadata,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private static function currencyCode(): string
    {
        return match (strtoupper(Setting::get('payments_currency', 'DOP'))) {
            'USD'   => static::CURRENCY_USD,
            default => static::CURRENCY_DOP,
        };
    }

    /**
     * Genera un OrderId único de máximo 12 caracteres alfanuméricos.
     */
    public static function generateOrderId(int $pagoId): string
    {
        return 'SGE' . str_pad($pagoId, 9, '0', STR_PAD_LEFT);
    }
}
