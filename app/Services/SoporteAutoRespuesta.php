<?php

namespace App\Services;

use App\Events\SupportAdminReply;
use App\Helpers\Setting;
use App\Models\ConfigInstitucional;
use App\Models\SupportMessage;
use App\Models\SupportSession;

/**
 * Respuestas automáticas del chat de soporte, para que quien escribe nunca se quede sin contestación:
 *  1. Al abrir la conversación: acuse de recibo («un agente te responderá en breve»).
 *  2. Si sigue escribiendo y todavía no lo atendió una persona: se le ofrece el número de soporte (una sola vez).
 *
 * Los mensajes automáticos se guardan como origen «admin» SIN user_id; así se distinguen de los de una persona
 * (user_id presente), y en cuanto un agente responde dejan de enviarse.
 */
class SoporteAutoRespuesta
{
    public function alIniciar(SupportSession $session): ?SupportMessage
    {
        $nombre = trim(strtok($session->visitor_nombre, ' ') ?: '');

        return $this->enviar($session, sprintf(
            'Hola%s, gracias por escribirnos. Recibimos tu mensaje y un agente te responderá aquí en breve. Puedes seguir escribiendo mientras tanto.',
            $nombre !== '' ? ' ' . $nombre : ''
        ));
    }

    /** Tras un mensaje del visitante: si aún no lo atiende una persona y ya recibió el acuse, le damos el número de soporte (una vez). */
    public function alMensajeDelVisitante(SupportSession $session): ?SupportMessage
    {
        $q = $session->mensajes()->where('origen', 'admin');
        $hayPersona = (clone $q)->whereNotNull('user_id')->exists();
        $automaticos = (clone $q)->whereNull('user_id')->count();

        if ($hayPersona || $automaticos !== 1) {
            return null;   // ya lo atiende alguien, o aún no hubo acuse, o ya se envió el número
        }

        $telefono = $this->telefonoDeSoporte();

        return $this->enviar($session, $telefono
            ? "Si tu consulta es urgente o tienes más dudas, también puedes comunicarte con soporte al {$telefono} (llamada o WhatsApp). Mientras tanto, seguimos atentos a tu mensaje aquí."
            : 'Seguimos atentos a tus mensajes: un agente te responderá aquí en cuanto esté disponible.');
    }

    /** Teléfono de soporte del colegio: el configurado como «soporte_telefono» o, si no hay, el teléfono institucional. */
    public function telefonoDeSoporte(): ?string
    {
        $t = trim((string) (Setting::get('soporte_telefono') ?: ConfigInstitucional::get('telefono') ?: ''));

        return $t !== '' ? $t : null;
    }

    private function enviar(SupportSession $session, string $texto): SupportMessage
    {
        $msg = SupportMessage::create([
            'session_id' => $session->id,
            'mensaje'    => $texto,
            'origen'     => 'admin',
            'user_id'    => null,
            'leido'      => false,
        ]);

        try {
            SupportAdminReply::dispatch($session->token, 'Soporte', $texto, now()->format('H:i'));
        } catch (\Throwable) {
            // Sin WebSocket no pasa nada: el widget consulta los mensajes cada pocos segundos
        }

        return $msg;
    }
}
