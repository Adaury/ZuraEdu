<?php

namespace App\Mail;

use App\Models\CalendarioAcademico;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCierrePeriodo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $destinatario,
        public CalendarioAcademico $evento,
        public int $diasRestantes
    ) {}

    public function envelope(): Envelope
    {
        $cuando = $this->diasRestantes === 0 ? 'HOY' : "en {$this->diasRestantes} día(s)";
        return new Envelope(
            subject: "[SGE] Recordatorio: Entrega de notas — {$this->evento->titulo} vence {$cuando}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio_cierre_periodo',
        );
    }
}
