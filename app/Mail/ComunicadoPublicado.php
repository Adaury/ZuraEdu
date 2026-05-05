<?php

namespace App\Mail;

use App\Models\Comunicado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComunicadoPublicado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Comunicado $comunicado) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📢 ' . $this->comunicado->titulo
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.comunicado-publicado');
    }
}
