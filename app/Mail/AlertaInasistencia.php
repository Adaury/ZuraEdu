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

class AlertaInasistencia extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(
        public Estudiante $estudiante,
        public Asignacion $asignacion,
        public int $totalAusencias,
        public float $porcentajeAsistencia
    ) {
        $this->resolverPlantilla('inasistencia_alerta', [
            'centro'                => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'estudiante'            => $estudiante->nombre_completo,
            'asignatura'            => $asignacion->asignatura?->nombre ?? '',
            'total_ausencias'       => (string) $totalAusencias,
            'porcentaje_asistencia' => number_format($porcentajeAsistencia, 0) . '%',
            'url_portal'            => config('app.url'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tplAsunto ?? "⚠️ Alerta de inasistencias — {$this->estudiante->nombre_completo}"
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(
            view: 'emails.alerta-inasistencia',
            with: ['institucion' => $this->estudiante->tenant?->nombre_institucion ?? config('app.name')],
        );
    }
}
