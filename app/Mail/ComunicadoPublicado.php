<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\ConfigInstitucional;
use App\Models\Comunicado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComunicadoPublicado extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(public Comunicado $comunicado)
    {
        $this->resolverPlantilla('comunicado_publicado', [
            'centro'     => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'titulo'     => $comunicado->titulo,
            'contenido'  => $comunicado->cuerpo,
            'fecha'      => optional($comunicado->published_at)->format('d/m/Y') ?? now()->format('d/m/Y'),
            'url_portal' => config('app.url'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->tplAsunto ?? '📢 ' . $this->comunicado->titulo
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(view: 'emails.comunicado-publicado');
    }
}
