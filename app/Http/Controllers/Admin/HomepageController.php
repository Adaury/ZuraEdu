<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomepageController extends Controller
{
    /** Claves de sección ordenables en /sitio, en el orden por defecto. */
    public const SECCIONES_ORDENABLES = ['hero', 'about', 'stats', 'features', 'contacto'];

    private array $sections = [
        'hero'     => ['titulo' => 'Sección Hero (Portada)'],
        'about'    => ['titulo' => 'Sobre la Institución'],
        'stats'    => ['titulo' => 'Estadísticas'],
        'features' => ['titulo' => 'Características'],
        'contacto' => ['titulo' => 'Contacto y Redes'],
        'branding' => ['titulo' => 'Logo y Colores'],
    ];

    public function edit()
    {
        $config = ConfigInstitucional::all()->pluck('valor', 'clave')->toArray();
        $orden  = $this->ordenActual();
        return view('admin.homepage.edit', compact('config', 'orden'));
    }

    /**
     * Orden guardado en hp_orden (string CSV, ej. "hero,about,stats,features,contacto").
     * Se sanea contra SECCIONES_ORDENABLES por si quedó una clave obsoleta:
     * las válidas van primero en el orden guardado, cualquier sección nueva
     * que falte se agrega al final.
     */
    public static function ordenActual(): array
    {
        $guardado = array_filter(explode(',', ConfigInstitucional::get('hp_orden', '') ?: ''));
        $validas  = array_values(array_intersect($guardado, self::SECCIONES_ORDENABLES));
        $faltantes = array_values(array_diff(self::SECCIONES_ORDENABLES, $validas));

        return array_merge($validas, $faltantes) ?: self::SECCIONES_ORDENABLES;
    }

    public function moverOrden(Request $request, string $seccion, string $direccion)
    {
        abort_unless(in_array($seccion, self::SECCIONES_ORDENABLES), 404);
        abort_unless(in_array($direccion, ['arriba', 'abajo']), 404);

        $orden = $this->ordenActual();
        $pos   = array_search($seccion, $orden);
        $vecino = $direccion === 'arriba' ? $pos - 1 : $pos + 1;

        if ($vecino >= 0 && $vecino < count($orden)) {
            [$orden[$pos], $orden[$vecino]] = [$orden[$vecino], $orden[$pos]];
            ConfigInstitucional::set('hp_orden', implode(',', $orden));
        }

        return redirect()->route('admin.homepage.edit');
    }

    public function update(Request $request)
    {
        $request->validate([
            'hp_hero_titulo'       => 'nullable|string|max:200',
            'hp_hero_subtitulo'    => 'nullable|string|max:500',
            'hp_hero_btn_texto'    => 'nullable|string|max:80',
            'hp_hero_btn2_texto'   => 'nullable|string|max:80',
            'hp_about_titulo'      => 'nullable|string|max:200',
            'hp_about_texto'       => 'nullable|string|max:1000',
            'hp_contacto_direccion'=> 'nullable|string|max:200',
            'hp_contacto_telefono' => 'nullable|string|max:50',
            'hp_contacto_email'    => 'nullable|email|max:100',
            'hp_color_primario'    => 'nullable|string|max:7',
            'hp_color_secundario'  => 'nullable|string|max:7',
            'logo'                 => 'nullable|image|max:2048',
            'system_name'          => 'nullable|string|max:200',
            'system_abbr'          => 'nullable|string|max:10',
            'system_sub'           => 'nullable|string|max:80',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $old = ConfigInstitucional::get('hp_logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('branding', 'public');
            ConfigInstitucional::set('hp_logo_path', $path);
        }

        // Checkboxes de visibilidad: un checkbox desmarcado NO se envía en el
        // POST, así que $request->has($key) sería siempre false al apagarlo y
        // el bloque nunca se ocultaría. Se guardan explícitamente en cada
        // submit ('1' si viene marcado, '0' si no) en vez de con has().
        $visibleKeys = [
            'hp_hero_visible', 'hp_about_visible', 'hp_stats_visible',
            'hp_features_visible', 'hp_contacto_visible',
        ];
        foreach ($visibleKeys as $key) {
            ConfigInstitucional::set($key, $request->has($key) ? '1' : '0');
        }

        // Save all text fields
        $keys = [
            'hp_hero_titulo', 'hp_hero_subtitulo', 'hp_hero_btn_texto', 'hp_hero_btn2_texto',
            'hp_about_titulo', 'hp_about_texto',
            'hp_stat1_numero', 'hp_stat1_label',
            'hp_stat2_numero', 'hp_stat2_label',
            'hp_stat3_numero', 'hp_stat3_label',
            'hp_stat4_numero', 'hp_stat4_label',
            'hp_features_titulo',
            'hp_contacto_direccion', 'hp_contacto_telefono', 'hp_contacto_email',
            'hp_social_facebook', 'hp_social_instagram', 'hp_social_twitter',
            'hp_color_primario', 'hp_color_secundario',
            'nombre_institucion',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                ConfigInstitucional::set($key, $request->input($key) ?? '');
            }
        }

        // Guardar system settings (sidebar branding)
        foreach (['system_name', 'system_abbr', 'system_sub'] as $key) {
            if ($request->filled($key)) {
                \App\Helpers\Setting::set($key, $request->input($key));
            }
        }

        return redirect()->route('admin.homepage.edit')
            ->with('success', 'Página principal actualizada correctamente.');
    }
}
