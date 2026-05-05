<?php

namespace App\Mail;

use App\Models\PreMatricula;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreMatriculaResolucion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PreMatricula $preMatricula) {}

    public function envelope(): Envelope
    {
        $accion = $this->preMatricula->estado === 'aprobada' ? '✅ Aprobada' : '❌ No Aprobada';

        return new Envelope(
            subject: "{$accion} — Solicitud de Pre-matrícula | " . config('app.name')
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pre-matricula-resolucion');
    }
}
