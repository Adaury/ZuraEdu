<?php

namespace App\Mail;

use App\Models\PreMatricula;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreMatriculaConfirmacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PreMatricula $preMatricula) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Solicitud de Pre-matrícula Recibida — ' . config('app.name')
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pre-matricula-confirmacion');
    }
}
