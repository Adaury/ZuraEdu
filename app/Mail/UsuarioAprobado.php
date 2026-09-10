<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsuarioAprobado extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(public User $usuario)
    {
        $institucion = $usuario->tenant?->nombre_institucion ?? config('app.name');

        $this->resolverPlantilla('usuario_aprobado', [
            'centro'    => $institucion,
            'usuario'   => $usuario->name,
            'email'     => $usuario->email,
            'rol'       => $usuario->getRoleNames()->first() ?? '',
            'url_login' => url('/login'),
        ]);
    }

    public function envelope(): Envelope
    {
        $institucion = $this->usuario->tenant?->nombre_institucion ?? config('app.name');

        return new Envelope(subject: $this->tplAsunto ?? "✅ Tu acceso a {$institucion} ha sido aprobado");
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(
            view: 'emails.usuario-aprobado',
            with: ['institucion' => $this->usuario->tenant?->nombre_institucion ?? config('app.name')],
        );
    }
}
