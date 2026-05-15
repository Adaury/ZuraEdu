<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuscripcionActivada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant  $tenant,
        public string  $planSlug,
        public string  $ciclo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Tu suscripción ZuraEdu fue activada — ' . ucfirst($this->planSlug),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.suscripcion-activada');
    }
}
