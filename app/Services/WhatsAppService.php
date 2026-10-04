<?php

namespace App\Services;

use App\Helpers\Setting;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Deja el teléfono en formato internacional SOLO con dígitos (sin «+»): «809-555-1234» → «18095551234».
     * Un número de 10 dígitos se toma como local y se le antepone el código de país (por defecto 1, República Dominicana);
     * «+1 (809) 555-1234», «0018095551234» y «1-809-555-1234» también quedan bien. Menos de 10 dígitos → null (inválido).
     */
    public static function normalizarTelefono(string $telefono, string $codigoPais = '1'): ?string
    {
        $d = preg_replace('/\D+/', '', $telefono);
        $d = preg_replace('/^00/', '', $d);

        if (strlen($d) === 10) {
            $d = preg_replace('/\D+/', '', $codigoPais) . $d;
        }

        return strlen($d) >= 11 && strlen($d) <= 15 ? $d : null;
    }

    public static function send(string $to, string $message): bool
    {
        if (! Setting::moduleEnabled('whatsapp')) return false;

        $to = trim($to);
        if (empty($to)) return false;

        try {
            \App\Jobs\EnviarWhatsApp::dispatch($to, $message);
            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp dispatch error', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public static function sendAbsence(string $phone, string $student, string $subject, string $date): bool
    {
        if (! Setting::get('whatsapp_notify_absence', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');

        $mensaje = PlantillaComunicacionService::render('ausencia_registrada', 'whatsapp', [
            'centro' => $school, 'estudiante' => $student, 'asignatura' => $subject,
            'fecha' => $date, 'url_portal' => config('app.url'),
        ]) ?? "⚠️ *{$school}*\n\nEstimado representante, *{$student}* registró una *ausencia* en *{$subject}* el {$date}.\n\nRevise el portal del representante.";

        return static::send($phone, $mensaje);
    }

    public static function sendGradePublished(string $phone, string $student, string $subject, float $grade): bool
    {
        if (! Setting::get('whatsapp_notify_grades', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');
        $emoji  = $grade >= 80 ? '🟢' : ($grade >= 60 ? '🟡' : '🔴');

        $mensaje = PlantillaComunicacionService::render('calificacion_publicada', 'whatsapp', [
            'centro' => $school, 'estudiante' => $student, 'asignatura' => $subject,
            'nota' => (string) $grade, 'emoji' => $emoji, 'url_portal' => config('app.url'),
        ]) ?? "{$emoji} *{$school}*\n\nCalificaciones de *{$student}* en *{$subject}* publicadas.\n📊 Nota: *{$grade}*\n\nRevise el portal.";

        return static::send($phone, $mensaje);
    }

    public static function sendAlert(string $phone, string $student, string $message): bool
    {
        if (! Setting::get('whatsapp_notify_alerts', '1')) return false;
        $school = Setting::get('system_name', 'El centro educativo');

        $mensaje = PlantillaComunicacionService::render('alerta_generica', 'whatsapp', [
            'centro' => $school, 'estudiante' => $student, 'mensaje' => $message,
            'url_portal' => config('app.url'),
        ]) ?? "🔔 *{$school}*\n\nEstimado representante de *{$student}*:\n\n{$message}\n\n" . config('app.url');

        return static::send($phone, $mensaje);
    }

}
