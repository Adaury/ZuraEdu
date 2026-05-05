<?php

namespace App\Services;

use App\Helpers\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static function send(string $to, string $message): bool
    {
        if (! Setting::moduleEnabled('whatsapp')) return false;

        try {
            return match (Setting::get('whatsapp_provider', 'twilio')) {
                'twilio' => static::sendTwilio($to, $message),
                'meta'   => static::sendMeta($to, $message),
                default  => false,
            };
        } catch (\Throwable $e) {
            Log::error('WhatsApp error', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public static function sendAbsence(string $phone, string $student, string $subject, string $date): bool
    {
        if (! Setting::get('whatsapp_notify_absence', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');
        return static::send($phone,
            "⚠️ *{$school}*\n\nEstimado representante, *{$student}* registró una *ausencia* en *{$subject}* el {$date}.\n\nRevise el portal del representante."
        );
    }

    public static function sendGradePublished(string $phone, string $student, string $subject, float $grade): bool
    {
        if (! Setting::get('whatsapp_notify_grades', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');
        $emoji  = $grade >= 80 ? '🟢' : ($grade >= 60 ? '🟡' : '🔴');
        return static::send($phone,
            "{$emoji} *{$school}*\n\nCalificaciones de *{$student}* en *{$subject}* publicadas.\n📊 Nota: *{$grade}*\n\nRevise el portal."
        );
    }

    public static function sendAlert(string $phone, string $student, string $message): bool
    {
        if (! Setting::get('whatsapp_notify_alerts', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');
        return static::send($phone,
            "🔔 *{$school}*\n\nEstimado representante de *{$student}*:\n\n{$message}\n\n" . config('app.url')
        );
    }

    private static function sendTwilio(string $to, string $message): bool
    {
        $sid   = Setting::get('whatsapp_account_sid');
        $token = Setting::get('whatsapp_auth_token');
        $from  = Setting::get('whatsapp_from_number');

        if (! $sid || ! $token || ! $from) {
            Log::warning('WhatsApp Twilio: credenciales no configuradas.');
            return false;
        }

        $response = Http::withBasicAuth($sid, $token)->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To'   => "whatsapp:{$to}",
                'Body' => $message,
            ]);

        if ($response->successful()) {
            Log::info('WhatsApp Twilio OK', ['to' => $to]);
            return true;
        }
        Log::error('WhatsApp Twilio falló', ['status' => $response->status()]);
        return false;
    }

    private static function sendMeta(string $to, string $message): bool
    {
        $token = Setting::get('whatsapp_auth_token');
        $from  = Setting::get('whatsapp_from_number');

        if (! $token || ! $from) {
            Log::warning('WhatsApp Meta: credenciales no configuradas.');
            return false;
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$from}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => preg_replace('/[^0-9]/', '', $to),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->successful()) {
            Log::info('WhatsApp Meta OK', ['to' => $to]);
            return true;
        }
        Log::error('WhatsApp Meta falló', ['status' => $response->status()]);
        return false;
    }
}
