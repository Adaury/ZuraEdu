<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\Asignacion;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaRiesgoAcademico extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(
        public Estudiante $estudiante,
        public Asignacion $asignacion,
        public float $nota,
        public string $inst
    ) {
        $this->resolverPlantilla('riesgo_academico', [
            'centro'     => ConfigInstitucional::get('nombre_institucion') ?: $inst,
            'estudiante' => $estudiante->nombre_completo,
            'asignatura' => $asignacion->asignatura?->nombre ?? '',
            'nota'       => (string) $nota,
            'grupo'      => $asignacion->grupo?->nombre ?? '',
            'url_portal' => config('app.url'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tplAsunto ?? "⚠️ Alerta académica — {$this->estudiante->nombre_completo}"
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(view: 'emails.alerta-riesgo-academico');
    }
}
