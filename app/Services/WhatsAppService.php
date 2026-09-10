<?php

namespace App\Services;

use App\Helpers\Setting;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
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
