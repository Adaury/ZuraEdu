<?php

namespace App\Mail;

use App\Models\Estudiante;
use App\Models\Asignacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaInasistencia extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Estudiante $estudiante,
        public Asignacion $asignacion,
        public int $totalAusencias,
        public float $porcentajeAsistencia
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Alerta de inasistencias — {$this->estudiante->nombre_completo}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alerta-inasistencia');
    }
}
