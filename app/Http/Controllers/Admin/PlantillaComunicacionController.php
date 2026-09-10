<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantillaComunicacion;
use App\Services\PlantillaComunicacionService;
use Illuminate\Http\Request;

/**
 * CRUD de Plantillas de Comunicación. A diferencia de PaginaSeccionController
 * (constructor de bloques del sitio público), el índice lista el CATÁLOGO
 * (PlantillaComunicacion::EVENTOS), no filas -- la mayoría de pares
 * evento+canal no tienen fila todavía (sin personalizar = usa el hardcode).
 * Las rutas se llavean por {evento}/{canal}, nunca por ID de modelo: esto
 * elimina de raíz el problema de aislamiento cross-tenant que
 * PaginaSeccionController::autorizar() resuelve a mano (por
 * SubstituteBindings corriendo antes que ResolveTenant) -- aquí nunca se
 * resuelve un modelo por ID de URL, la fila siempre se localiza con una
 * query ya scopeada por tenant.
 */
class PlantillaComunicacionController extends Controller
{
    private function validarCatalogo(string $evento, string $canal): array
    {
        $def = PlantillaComunicacion::EVENTOS[$evento] ?? null;
        abort_unless($def && in_array($canal, $def['canales'], true), 404);

        return $def;
    }

    public function index()
    {
        $filas = PlantillaComunicacion::all()->keyBy(fn ($p) => "{$p->evento}|{$p->canal}");

        $catalogo = collect(PlantillaComunicacion::EVENTOS)->map(function (array $def, string $evento) use ($filas) {
            return collect($def['canales'])->map(function (string $canal) use ($def, $evento, $filas) {
                $fila = $filas->get("{$evento}|{$canal}");

                return [
                    'evento'          => $evento,
                    'canal'           => $canal,
                    'canal_label'     => PlantillaComunicacion::CANALES[$canal],
                    'label'           => $def['label'],
                    'grupo'           => $def['grupo'],
                    'personalizada'   => (bool) $fila,
                    'activa'          => $fila ? $fila->activa : false,
                ];
            });
        })->flatten(1);

        return view('admin.plantillas.index', [
            'catalogo' => $catalogo,
            'grupos'   => PlantillaComunicacion::GRUPOS,
        ]);
    }

    public function edit(string $evento, string $canal)
    {
        $def = $this->validarCatalogo($evento, $canal);

        $fila = PlantillaComunicacion::firstOrNew(['evento' => $evento, 'canal' => $canal]);

        return view('admin.plantillas.edit', [
            'def'      => $def,
            'evento'   => $evento,
            'canal'    => $canal,
            'fila'     => $fila,
            'ejemplos' => PlantillaComunicacion::ejemplosDe($evento),
            'default'  => PlantillaComunicacion::textoDefault($evento, $canal),
        ]);
    }

    public function update(Request $request, string $evento, string $canal)
    {
        $def = $this->validarCatalogo($evento, $canal);

        $rules = [
            'cuerpo' => ['nullable', 'string', 'max:' . ($canal === 'whatsapp' ? 1500 : 20000)],
            'activa' => 'nullable|boolean',
        ];
        if ($canal === 'email') {
            $rules['asunto'] = 'nullable|string|max:200';
        }

        $request->validate($rules);

        $cuerpo = PlantillaComunicacionService::normalizar((string) $request->input('cuerpo', ''));

        if ($canal === 'whatsapp') {
            // El middleware SanitizeInput ya limpió el HTML peligroso (campo
            // "cuerpo" está en $allowedRichFields) -- WhatsApp es texto
            // plano puro, así que se quita cualquier etiqueta que sobreviva.
            $cuerpo = strip_tags($cuerpo);
        }

        $asunto = $canal === 'email'
            ? PlantillaComunicacionService::normalizar((string) $request->input('asunto', ''))
            : null;

        if (filled($cuerpo)) {
            $desconocidas = PlantillaComunicacionService::variablesDesconocidas($cuerpo, $evento);
            if ($canal === 'email' && filled($asunto)) {
                $desconocidas = array_unique(array_merge(
                    $desconocidas,
                    PlantillaComunicacionService::variablesDesconocidas($asunto, $evento)
                ));
            }

            if (! empty($desconocidas)) {
                $validas = implode(', ', array_map(fn ($v) => "{{{$v}}}", PlantillaComunicacion::variablesDe($evento)));

                return back()->withInput()->withErrors([
                    'cuerpo' => 'Variable(s) no reconocida(s): ' . implode(', ', array_map(fn ($v) => "{{{$v}}}", $desconocidas))
                        . '. Disponibles para este evento: ' . $validas,
                ]);
            }

            $requeridas = PlantillaComunicacion::variablesRequeridas($evento);
            $faltantes = array_filter($requeridas, fn ($v) => ! str_contains($cuerpo, '{{' . $v . '}}'));
            if (! empty($faltantes)) {
                return back()->withInput()->withErrors([
                    'cuerpo' => 'Este mensaje debe incluir ' . implode(' y ', array_map(fn ($v) => "{{{$v}}}", $faltantes))
                        . ' o el destinatario no podrá completar la acción.',
                ]);
            }
        }

        PlantillaComunicacion::updateOrCreate(
            ['tenant_id' => app('tenant')->id, 'evento' => $evento, 'canal' => $canal],
            ['asunto' => $asunto, 'cuerpo' => $cuerpo ?: null, 'activa' => $request->boolean('activa')]
        );

        return redirect()->route('admin.plantillas.index')
            ->with('success', 'Plantilla de "' . $def['label'] . '" guardada.');
    }

    public function toggle(string $evento, string $canal)
    {
        $this->validarCatalogo($evento, $canal);

        $fila = PlantillaComunicacion::where('evento', $evento)->where('canal', $canal)->first();
        abort_unless($fila, 404);

        $fila->update(['activa' => ! $fila->activa]);

        return response()->json(['ok' => true, 'activa' => $fila->activa]);
    }

    public function destroy(string $evento, string $canal)
    {
        $this->validarCatalogo($evento, $canal);

        // ->delete() sobre una INSTANCIA (no el query builder) a propósito:
        // un delete masivo vía Builder::delete() no dispara el evento
        // 'deleted' de Eloquent, y PlantillaComunicacion::booted() depende
        // de ese evento para invalidar la caché del servicio -- sin esto,
        // "Restaurar predeterminado" seguiría sirviendo el texto viejo
        // hasta que expire el caché de 300s.
        PlantillaComunicacion::where('evento', $evento)->where('canal', $canal)->first()?->delete();

        return redirect()->route('admin.plantillas.index')
            ->with('success', 'Plantilla restaurada al texto predeterminado.');
    }
}
