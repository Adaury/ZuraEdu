<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\ConfigInstitucional;
use App\Models\PreMatricula;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreMatriculaConfirmacion extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(public PreMatricula $preMatricula)
    {
        $this->resolverPlantilla('pre_matricula_recibida', [
            'centro'      => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'solicitante' => $preMatricula->nombre_representante,
            'estudiante'  => trim($preMatricula->nombres . ' ' . $preMatricula->apellidos),
            'codigo'      => $preMatricula->codigo,
            'grado'       => $preMatricula->grado_solicitado,
            'fecha'       => optional($preMatricula->created_at)->format('d/m/Y') ?? now()->format('d/m/Y'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tplAsunto ?? '✅ Solicitud de Pre-matrícula Recibida — ' . config('app.name')
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(view: 'emails.pre-matricula-confirmacion');
    }
}
