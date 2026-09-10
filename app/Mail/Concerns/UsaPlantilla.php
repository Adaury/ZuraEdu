<?php

namespace App\Mail\Concerns;

use App\Models\ConfigInstitucional;
use App\Services\PlantillaComunicacionService;
use Illuminate\Mail\Mailables\Content;

/**
 * Permite a un Mailable usar la plantilla personalizada del tenant (si
 * existe) en vez de su diseño hardcodeado -- ver Fase 5 "Plantillas de
 * Comunicación". Los Mailables se encolan y se renderizan en el worker,
 * donde el tenant puede no estar bindeado (envelope()/content() corren
 * ahí) -- por eso resolverPlantilla() debe llamarse al FINAL del
 * constructor del Mailable (donde el caller SÍ tiene el tenant), nunca
 * dentro de envelope()/content(). El resultado viaja serializado como
 * propiedades públicas de texto plano.
 */
trait UsaPlantilla
{
    public ?string $tplAsunto = null;
    public ?string $tplCuerpo = null;
    public string $tplCentro = '';

    protected function resolverPlantilla(string $evento, array $variables): void
    {
        $this->tplCentro = (string) (ConfigInstitucional::get('nombre_institucion') ?: config('app.name'));

        if ($tpl = PlantillaComunicacionService::renderEmail($evento, $variables)) {
            $this->tplAsunto = filled($tpl['asunto']) ? $tpl['asunto'] : null;
            $this->tplCuerpo = filled($tpl['cuerpo']) ? $tpl['cuerpo'] : null;
        }
    }

    protected function contenidoPlantilla(): ?Content
    {
        if ($this->tplCuerpo === null) {
            return null;
        }

        return new Content(view: 'emails.plantilla', with: [
            'cuerpo' => $this->tplCuerpo,
            'centro' => $this->tplCentro,
        ]);
    }
}
