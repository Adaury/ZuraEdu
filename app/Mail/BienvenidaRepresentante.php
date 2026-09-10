<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\ConfigInstitucional;
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
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(
        public Representante $representante,
        public User $user,
        public string $tempPassword,
        public Matricula $matricula,
    ) {
        $grado = trim(($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? ''));

        $this->resolverPlantilla('bienvenida_representante', [
            'centro'           => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'representante'    => $representante->nombre_completo,
            'estudiante'       => $matricula->estudiante->nombre_completo,
            'numero_matricula' => $matricula->estudiante->numero_matricula,
            'grado'            => $grado ?: '—',
            'ano_escolar'      => $matricula->schoolYear->nombre ?? '—',
            'usuario'          => $user->email,
            'clave_temporal'   => $tempPassword,
            'url_login'        => url('/login'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tplAsunto ?? '✅ Matrícula confirmada — Acceso al Portal de Representantes');
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(view: 'emails.bienvenida-representante');
    }
}
