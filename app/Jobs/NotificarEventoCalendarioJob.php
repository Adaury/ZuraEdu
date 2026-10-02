<?php

namespace App\Jobs;

use App\Mail\EventoCalendarioMail;
use App\Models\CalendarioAcademico;
use App\Models\CalendarioDestinatario;
use App\Models\Mensaje;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Avisa de un evento del calendario a un lote de personas, por tres vías:
 *  1) mensajería interna (bandeja "Mensajes" del portal), 2) notificación dentro del sistema, 3) correo con el .ics adjunto.
 * Cada persona se procesa por separado: si el correo de una falla (dirección inválida, SMTP caído) las demás siguen y
 * el mensaje interno y la notificación de esa misma persona ya quedaron enviados.
 * Corre en cola con el tenant restaurado (TenantJob); los modelos se buscan con el scope de tenant activo.
 */
class NotificarEventoCalendarioJob extends TenantJob
{
    public int $tries   = 3;
    public int $backoff = 30;
    public int $timeout = 180;

    /** @param array<int,int> $userIds */
    public function __construct(
        public readonly int   $calendarioId,
        public readonly array $userIds,
        public readonly bool  $actualizacion = false,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $evento = CalendarioAcademico::find($this->calendarioId);
        if (! $evento || ! $evento->activo) {
            return;
        }

        $remitenteId = $evento->creado_por;
        $cuando      = $evento->fecha_inicio->format('d/m/Y') . ($evento->hora_inicio ? ' ' . substr((string) $evento->hora_inicio, 0, 5) : '');
        $asunto      = ($this->actualizacion ? 'Evento actualizado: ' : 'Nuevo evento: ') . $evento->titulo;
        $tipo        = CalendarioAcademico::tiposLabels()[$evento->tipo] ?? 'Evento';
        $cuerpo      = "{$evento->titulo}\n{$tipo} · {$cuando}"
            . ($evento->descripcion ? "\n\n{$evento->descripcion}" : '')
            . "\n\nPuedes agregarlo a tu Google Calendar desde el correo que te enviamos o desde tu portal (Calendario).";

        foreach (User::whereIn('id', $this->userIds)->get() as $user) {
            $fila = CalendarioDestinatario::where('calendario_id', $evento->id)->where('user_id', $user->id)->first();
            if (! $fila || $fila->notificado_at) {
                continue; // ya avisado (reintento del job) o ya no es destinatario
            }

            try {
                Mensaje::create([
                    'remitente_id'    => $remitenteId,
                    'destinatario_id' => $user->id,
                    'asunto'          => Str::limit($asunto, 190),
                    'cuerpo'          => $cuerpo,
                    'tipo'            => 'individual',
                ]);
                Cache::forget('t' . ($evento->tenant_id ?? 0) . "_user_{$user->id}_msg_unread");

                Notificacion::enviar($user->id, 'general', Str::limit($asunto, 120), "{$tipo} · {$cuando}", ['calendario_id' => $evento->id]);
            } catch (\Throwable $e) {
                Log::warning('Calendario: no se pudo crear mensaje/notificación interna', ['evento' => $evento->id, 'user' => $user->id, 'error' => $e->getMessage()]);
            }

            $correoOk = null;
            if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($user->email)->send(new EventoCalendarioMail($evento, $user, $this->actualizacion));
                    $correoOk = now();
                } catch (\Throwable $e) {
                    Log::warning('Calendario: falló el correo', ['evento' => $evento->id, 'user' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            $fila->update(['notificado_at' => now(), 'correo_enviado_at' => $correoOk]);
        }
    }
}
