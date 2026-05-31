<?php

namespace App\Jobs;

use App\Helpers\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviarWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // segundos entre reintentos

    public function __construct(
        public readonly string $to,
        public readonly string $message,
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        if (! Setting::moduleEnabled('whatsapp')) return;

        $to = preg_replace('/\s+/', '', $this->to);
        if (empty($to)) return;

        $ok = match (Setting::get('whatsapp_provider', 'twilio')) {
            'twilio' => $this->sendTwilio($to),
            'meta'   => $this->sendMeta($to),
            default  => false,
        };

        if (! $ok) {
            throw new \RuntimeException("WhatsApp: envío fallido a {$to}");
        }
    }

    private function sendTwilio(string $to): bool
    {
        $sid   = Setting::get('whatsapp_account_sid');
        $token = Setting::get('whatsapp_auth_token');
        $from  = Setting::get('whatsapp_from_number');

        if (! $sid || ! $token || ! $from) {
            Log::warning('WhatsApp Twilio: credenciales no configuradas — job cancelado.');
            $this->delete(); // no reintentar si faltan credenciales
            return true;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To'   => "whatsapp:{$to}",
                'Body' => $this->message,
            ]);

        if ($response->successful()) {
            Log::info('WhatsApp Twilio enviado', ['to' => $to]);
            return true;
        }

        Log::error('WhatsApp Twilio error', [
            'to'     => $to,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return false;
    }

    private function sendMeta(string $to): bool
    {
        $token = Setting::get('whatsapp_auth_token');
        $from  = Setting::get('whatsapp_from_number');

        if (! $token || ! $from) {
            Log::warning('WhatsApp Meta: credenciales no configuradas — job cancelado.');
            $this->delete();
            return true;
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$from}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => preg_replace('/[^0-9]/', '', $to),
                'type' => 'text',
                'text' => ['body' => $this->message],
            ]);

        if ($response->successful()) {
            Log::info('WhatsApp Meta enviado', ['to' => $to]);
            return true;
        }

        Log::error('WhatsApp Meta error', [
            'to'     => $to,
            'status' => $response->status(),
        ]);
        return false;
    }
}
