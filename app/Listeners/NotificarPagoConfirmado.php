<?php

namespace App\Listeners;

use App\Events\PagoConfirmado;
use App\Models\Notificacion;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotificarPagoConfirmado implements ShouldQueue
{
    public string $queue = 'notifications';
    public int    $tries = 2;

    public function handle(PagoConfirmado $event): void
    {
        $pago = $event->pago->load([
            'matricula.estudiante.representantes',
            'matricula.estudiante',
        ]);

        $matricula  = $pago->matricula;
        $estudiante = $matricula?->estudiante;

        if (! $estudiante) {
            Log::warning("NotificarPagoConfirmado: pago #{$pago->id} sin estudiante asociado.");
            return;
        }

        $mon     = \App\Helpers\Setting::get('payments_currency', 'RD$');
        $school  = \App\Helpers\Setting::get('system_name', 'El centro educativo');
        $monto   = $mon . ' ' . number_format($pago->monto, 2);
        $fecha   = $pago->fecha_pago
            ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y')
            : now()->format('d/m/Y');
        $metodo  = ucfirst($pago->metodo_pago ?? 'en línea');

        $tituloNotif  = '✅ Pago confirmado';
        $cuerpoNotif  = "Tu pago de *{$pago->concepto}* por {$monto} fue procesado exitosamente el {$fecha}.";
        $msgWhatsApp  = "✅ *{$school}*\n\nEstimado representante, el pago de *{$pago->concepto}* por *{$monto}* ha sido confirmado.\n📅 Fecha: {$fecha}\n💳 Método: {$metodo}\n\nDescargue su recibo desde el portal.";

        // 1. Notificación in-app al estudiante
        if ($estudiante->user_id) {
            Notificacion::enviar(
                $estudiante->user_id,
                'pago',
                $tituloNotif,
                $cuerpoNotif,
            );
        }

        // 2. Notificación in-app + WhatsApp a cada representante
        foreach ($estudiante->representantes as $rep) {
            if ($rep->user_id) {
                Notificacion::enviar(
                    $rep->user_id,
                    'pago',
                    $tituloNotif,
                    "El pago de {$pago->concepto} por {$monto} de *{$estudiante->nombre_completo}* fue confirmado.",
                );
            }

            if (! empty($rep->telefono)) {
                WhatsAppService::send($rep->telefono, $msgWhatsApp);
            }
        }

        Log::info("PagoConfirmado notificado — pago #{$pago->id}", [
            'estudiante' => $estudiante->nombre_completo,
            'monto'      => $monto,
            'metodo'     => $metodo,
        ]);
    }

    public function failed(PagoConfirmado $event, \Throwable $exception): void
    {
        Log::error("NotificarPagoConfirmado falló — pago #{$event->pago->id}: " . $exception->getMessage());
    }
}
