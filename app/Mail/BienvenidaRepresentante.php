<?php

namespace App\Mail;

use App\Models\Matricula;
use App\Models\Representante;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaRepresentante extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Representante $representante,
        public User $user,
        public string $tempPassword,
        public Matricula $matricula,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '✅ Matrícula confirmada — Acceso al Portal de Representantes');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bienvenida-representante');
    }
}
