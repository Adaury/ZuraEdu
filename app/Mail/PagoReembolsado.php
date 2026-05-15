<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PagoReembolsado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant       $tenant,
        public Subscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Reembolso procesado — Tu plan ha sido revertido a Free',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pago-reembolsado');
    }
}
