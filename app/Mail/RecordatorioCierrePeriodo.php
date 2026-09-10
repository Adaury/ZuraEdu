<?php

namespace App\Mail;

use App\Mail\Concerns\UsaPlantilla;
use App\Models\CalendarioAcademico;
use App\Models\ConfigInstitucional;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCierrePeriodo extends Mailable
{
    use Queueable, SerializesModels, UsaPlantilla;

    public function __construct(
        public User $destinatario,
        public CalendarioAcademico $evento,
        public int $diasRestantes
    ) {
        $cuando = $diasRestantes === 0 ? 'HOY' : "en {$diasRestantes} día(s)";

        $this->resolverPlantilla('cierre_periodo_recordatorio', [
            'centro'         => ConfigInstitucional::get('nombre_institucion') ?: config('app.name'),
            'docente'        => $destinatario->name,
            'evento'         => $evento->titulo,
            'dias_restantes' => (string) $diasRestantes,
            'cuando'         => $cuando,
            'fecha_limite'   => \Carbon\Carbon::parse($evento->fecha_inicio)->format('d/m/Y'),
        ]);
    }

    public function envelope(): Envelope
    {
        $cuando = $this->diasRestantes === 0 ? 'HOY' : "en {$this->diasRestantes} día(s)";
        return new Envelope(
            subject: $this->tplAsunto ?? "[SGE] Recordatorio: Entrega de notas — {$this->evento->titulo} vence {$cuando}",
        );
    }

    public function content(): Content
    {
        return $this->contenidoPlantilla() ?? new Content(
            view: 'emails.recordatorio_cierre_periodo',
        );
    }
}
