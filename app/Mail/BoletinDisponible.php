<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\Periodo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoletinDisponible extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public string $portalUrl;

    public function __construct(
        public Estudiante $estudiante,
        public Periodo $periodo,
        string $portalUrl
    ) {
        $this->portalUrl = $portalUrl;

        $this->resolverPlantilla('boletin_disponible', [
            'centro'      => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'estudiante'  => $estudiante->nombre_completo,
            'periodo'     => $periodo->nombre,
            'url_boletin' => $portalUrl,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tplAsunto ?? "📋 Boletín disponible — {$this->estudiante->nombre_completo} ({$this->periodo->nombre})"
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(
            view: 'emails.boletin-disponible',
            with: ['institucion' => $this->estudiante->tenant?->nombre_institucion ?? config('app.name')],
        );
    }
}
