<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\HomepageController;
use App\Models\ConfigInstitucional;
use Illuminate\Support\Facades\Storage;

/**
 * Sitio público institucional del tenant actual (roadmap de producto:
 * "portal público por centro"). Lee, de solo lectura, el contenido que
 * Admin\HomepageController ya permite configurar en ConfigInstitucional
 * (claves hp_*) — no crea ningún dato nuevo, solo lo renderiza fuera del
 * panel admin. Se sirve en /sitio, listado en las RUTAS_PUBLICAS de
 * ResolveTenant para que sea visible incluso si el tenant está suspendido.
 */
class PublicSiteController extends Controller
{
    public function show()
    {
        $tenant = app('tenant');

        // Feature flag "modo_publico" (SuperAdmin\TenantController::ALL_FEATURES)
        // — existía sin usar antes de esta fase. Si el centro no lo tiene
        // activo, se muestra una página informativa en vez del contenido.
        if (! $tenant->can('modo_publico')) {
            return view('public.sitio-no-disponible', compact('tenant'));
        }

        $orden = HomepageController::ordenActual();

        $stats = collect(range(1, 4))
            ->map(fn ($i) => [
                'numero' => ConfigInstitucional::get("hp_stat{$i}_numero", ''),
                'label'  => ConfigInstitucional::get("hp_stat{$i}_label", ''),
            ])
            ->filter(fn ($s) => filled($s['numero']) || filled($s['label']))
            ->values();

        $logoPath = ConfigInstitucional::get('hp_logo_path');

        $config = [
            'hero_visible'    => ConfigInstitucional::get('hp_hero_visible', '1') == '1',
            'hero_titulo'     => ConfigInstitucional::get('hp_hero_titulo', ''),
            'hero_subtitulo'  => ConfigInstitucional::get('hp_hero_subtitulo', ''),
            'hero_btn_texto'  => ConfigInstitucional::get('hp_hero_btn_texto', ''),
            'hero_btn2_texto' => ConfigInstitucional::get('hp_hero_btn2_texto', ''),

            'about_visible'   => ConfigInstitucional::get('hp_about_visible', '1') == '1',
            'about_titulo'    => ConfigInstitucional::get('hp_about_titulo', ''),
            'about_texto'     => ConfigInstitucional::get('hp_about_texto', ''),

            'stats_visible'   => ConfigInstitucional::get('hp_stats_visible', '1') == '1',
            'stats'           => $stats,

            'features_visible' => ConfigInstitucional::get('hp_features_visible', '1') == '1',
            'features_titulo'  => ConfigInstitucional::get('hp_features_titulo', ''),

            'contacto_visible'   => ConfigInstitucional::get('hp_contacto_visible', '1') == '1',
            'contacto_direccion' => ConfigInstitucional::get('hp_contacto_direccion', ''),
            'contacto_telefono'  => ConfigInstitucional::get('hp_contacto_telefono', ''),
            'contacto_email'     => ConfigInstitucional::get('hp_contacto_email', ''),

            'social_facebook'  => ConfigInstitucional::get('hp_social_facebook', ''),
            'social_instagram' => ConfigInstitucional::get('hp_social_instagram', ''),
            'social_twitter'   => ConfigInstitucional::get('hp_social_twitter', ''),

            'color_primario'   => ConfigInstitucional::get('hp_color_primario', $tenant->color_primario ?? '#0d6efd'),
            'color_secundario' => ConfigInstitucional::get('hp_color_secundario', $tenant->color_secundario ?? '#6c757d'),
            'logo_url'         => $logoPath ? Storage::url($logoPath) : $tenant->logo_url,
            'nombre'           => ConfigInstitucional::get('nombre_institucion', '') ?: $tenant->nombre_institucion,
        ];

        return view('public.sitio', compact('tenant', 'config', 'orden'));
    }
}
