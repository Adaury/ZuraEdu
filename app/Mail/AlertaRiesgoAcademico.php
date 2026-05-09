<?php

namespace App\Mail;

use App\Models\Estudiante;
use App\Models\Asignacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaRiesgoAcademico extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Estudiante $estudiante,
        public Asignacion $asignacion,
        public float $nota,
        public string $inst
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Alerta académica — {$this->estudiante->nombre_completo}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alerta-riesgo-academico');
    }
}
