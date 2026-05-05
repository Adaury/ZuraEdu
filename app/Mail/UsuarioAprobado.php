<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsuarioAprobado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $usuario) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '✅ Tu acceso al sistema PSAC ha sido aprobado');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.usuario-aprobado');
    }
}
