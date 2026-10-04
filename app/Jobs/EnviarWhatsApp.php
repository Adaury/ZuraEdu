<?php

namespace App\Jobs;

use App\Helpers\Setting;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviarWhatsApp extends TenantJob
{
    public int $tries   = 3;
    public int $backoff = 60; // segundos entre reintentos

    /** Motivo del último fallo (para la prueba de envío y los logs). */
    public ?string $error = null;

    public function __construct(
        public readonly string $to,
        public readonly string $message,
    ) {
        parent::__construct();
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        if (! Setting::moduleEnabled('whatsapp')) return;

        $r = $this->enviar();

        if (! $r['ok'] && ! $r['definitivo']) {
            throw new \RuntimeException("WhatsApp: envío fallido a {$this->to} — {$r['error']}");
        }
    }

    /**
     * Envía ahora (sin cola). Devuelve ['ok' => bool, 'error' => ?string, 'definitivo' => bool];
     * «definitivo» = no tiene sentido reintentar (número inválido o faltan credenciales).
     */
    public function enviar(): array
    {
        $proveedor = Setting::get('whatsapp_provider', 'twilio');
        $destino   = WhatsAppService::normalizarTelefono($this->to, (string) Setting::get('whatsapp_codigo_pais', '1'));

        if ($destino === null) {
            Log::warning('WhatsApp: número de destino inválido — no se envía.', ['to' => $this->to]);
            return ['ok' => false, 'error' => 'El número de destino no es válido (' . $this->to . ').', 'definitivo' => true];
        }

        return match ($proveedor) {
            'twilio' => $this->sendTwilio($destino),
            'meta'   => $this->sendMeta($destino),
            default  => ['ok' => false, 'error' => 'Proveedor desconocido.', 'definitivo' => true],
        };
    }

    private function sendTwilio(string $destino): array
    {
        $sid   = Setting::get('whatsapp_account_sid');
        $token = Setting::get('whatsapp_auth_token');
        $from  = WhatsAppService::normalizarTelefono((string) Setting::get('whatsapp_from_number'), (string) Setting::get('whatsapp_codigo_pais', '1'));

        if (! $sid || ! $token || ! $from) {
            Log::warning('WhatsApp Twilio: credenciales o número de origen no configurados (o inválidos) — no se envía.');
            return ['ok' => false, 'error' => 'Faltan el Account SID, el Auth Token o el número de Twilio (o el número no es válido).', 'definitivo' => true];
        }

        if (! preg_match('/^(AC|SK)[0-9a-f]{32}$/i', trim($sid))) {
            return ['ok' => false, 'error' => 'El Account SID guardado no tiene el formato de Twilio (debe empezar con «AC» y medir 34 caracteres; el actual mide ' . strlen(trim($sid)) . '). Corrígelo en esta pantalla.', 'definitivo' => true];
        }

        // Con plantilla aprobada (Content SID) se puede escribir a quien no nos ha escrito; sin ella solo vale dentro de las 24 h
        $plantilla = trim((string) Setting::get('whatsapp_twilio_content_sid'));
        $cuerpo = $plantilla !== ''
            ? ['ContentSid' => $plantilla, 'ContentVariables' => json_encode(['1' => $this->message], JSON_UNESCAPED_UNICODE)]
            : ['Body' => $this->message];

        $response = Http::withBasicAuth(trim($sid), trim($token))
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/" . trim($sid) . "/Messages.json", [
                'From' => "whatsapp:+{$from}",
                'To'   => "whatsapp:+{$destino}",
            ] + $cuerpo);

        if ($response->successful()) {
            Log::info('WhatsApp Twilio enviado', ['to' => $destino]);
            return ['ok' => true, 'error' => null, 'definitivo' => false];
        }

        Log::error('WhatsApp Twilio error', ['to' => $destino, 'status' => $response->status(), 'body' => $response->body()]);
        $detalle = $response->json('message') ?? substr($response->body(), 0, 160);
        $ayuda = str_contains($detalle, 'ContentSid') && $plantilla === ''
            ? ' — WhatsApp no permite iniciar una conversación con texto libre: crea una plantilla en Twilio (Content Template Builder), pega su Content SID (HX…) en esta pantalla y vuelve a probar. Si usas el Sandbox, el destinatario debe haberse unido enviando el código de unión a ' . '+' . $from . '.'
            : '';

        return ['ok' => false, 'error' => 'Twilio respondió ' . $response->status() . ': ' . $detalle . $ayuda, 'definitivo' => false];
    }

    private function sendMeta(string $destino): array
    {
        $token = Setting::get('whatsapp_auth_token');
        $from  = trim((string) Setting::get('whatsapp_from_number'));

        if (! $token || ! $from) {
            Log::warning('WhatsApp Meta: token o Phone Number ID no configurados — no se envía.');
            return ['ok' => false, 'error' => 'Faltan el token o el Phone Number ID de Meta.', 'definitivo' => true];
        }
        if (! ctype_digit($from)) {
            Log::warning('WhatsApp Meta: el campo «número de origen» debe ser el Phone Number ID (solo dígitos), no un teléfono.');
            return ['ok' => false, 'error' => 'En Meta, el «número de origen» debe ser el Phone Number ID (solo dígitos, lo da el panel de Meta), no el teléfono con formato.', 'definitivo' => true];
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$from}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => $destino,
                'type' => 'text',
                'text' => ['body' => $this->message],
            ]);

        if ($response->successful()) {
            Log::info('WhatsApp Meta enviado', ['to' => $destino]);
            return ['ok' => true, 'error' => null, 'definitivo' => false];
        }

        $detalle = $response->json('error.message') ?? substr($response->body(), 0, 160);
        Log::error('WhatsApp Meta error', ['to' => $destino, 'status' => $response->status(), 'detalle' => $detalle]);
        return ['ok' => false, 'error' => 'Meta respondió ' . $response->status() . ': ' . $detalle, 'definitivo' => false];
    }
}
