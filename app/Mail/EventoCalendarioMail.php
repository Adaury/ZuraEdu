<?php

namespace App\Mail;

use App\Models\CalendarioAcademico;
use App\Models\User;
use App\Services\Calendario\IcsBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso de un evento del calendario escolar, con el .ics adjunto y el enlace "Agregar a Google Calendar". */
class EventoCalendarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CalendarioAcademico $evento,
        public User $destinatario,
        public bool $actualizacion = false,
        public string $institucion = '',
    ) {
        $this->institucion = $institucion ?: (config('tenant.nombre') ?: config('app.name'));
    }

    public function envelope(): Envelope
    {
        $cuando = $this->evento->fecha_inicio->format('d/m/Y');

        return new Envelope(
            subject: ($this->actualizacion ? '🔄 Evento actualizado: ' : '📅 ') . $this->evento->titulo . ' — ' . $cuando,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.evento-calendario', with: [
            'evento'       => $this->evento,
            'nombre'       => $this->destinatario->name,
            'institucion'  => $this->institucion,
            'googleUrl'    => $this->evento->enlaceGoogleCalendar(),
            'tipoLabel'    => CalendarioAcademico::tiposLabels()[$this->evento->tipo] ?? 'Evento',
            'intervalo'    => $this->evento->intervalo(),
            'actualizacion' => $this->actualizacion,
        ]);
    }

    public function attachments(): array
    {
        $ics = (new IcsBuilder())->evento($this->evento);

        return [
            Attachment::fromData(fn () => $ics, 'evento-' . $this->evento->id . '.ics')
                ->withMime('text/calendar; charset=UTF-8; method=PUBLISH'),
        ];
    }
}
