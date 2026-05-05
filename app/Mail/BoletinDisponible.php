<?php

namespace App\Mail;

use App\Models\Estudiante;
use App\Models\Periodo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoletinDisponible extends Mailable
{
    use Queueable, SerializesModels;

    public string $portalUrl;

    public function __construct(
        public Estudiante $estudiante,
        public Periodo $periodo,
        string $portalUrl
    ) {
        $this->portalUrl = $portalUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📋 Boletín disponible — {$this->estudiante->nombre_completo} ({$this->periodo->nombre})"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.boletin-disponible');
    }
}
