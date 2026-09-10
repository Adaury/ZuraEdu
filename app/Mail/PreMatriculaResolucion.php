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

class PreMatriculaResolucion extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(public PreMatricula $preMatricula)
    {
        $this->resolverPlantilla('pre_matricula_resolucion', [
            'centro'      => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'solicitante' => $preMatricula->nombre_representante,
            'estudiante'  => trim($preMatricula->nombres . ' ' . $preMatricula->apellidos),
            'estado'      => $preMatricula->estado === 'aprobada' ? 'Aprobada' : 'No Aprobada',
            'motivo'      => '',
            'codigo'      => $preMatricula->codigo,
        ]);
    }

    public function envelope(): Envelope
    {
        $accion = $this->preMatricula->estado === 'aprobada' ? '✅ Aprobada' : '❌ No Aprobada';

        return new Envelope(
            subject: $this->tplAsunto ?? "{$accion} — Solicitud de Pre-matrícula | " . config('app.name')
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(view: 'emails.pre-matricula-resolucion');
    }
}
