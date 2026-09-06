<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\HomepageController;
use App\Models\Album;
use App\Models\ConfigInstitucional;
use App\Models\Publicacion;
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
    /**
     * Ruta raíz "/". ResolveTenant marca tenant.dominio_propio=true solo
     * cuando el host identificó a ESTA institución específica (subdominio o
     * dominio personalizado, o sesión de SuperAdmin gestionándola) — no
     * cuando cayó al tenant por defecto genérico de un host no reconocido.
     * En ese segundo caso se sigue mostrando el landing de marketing del
     * SaaS; solo el dominio propio de una institución real muestra su sitio.
     */
    public function raiz()
    {
        $dominioPropio = app()->bound('tenant.dominio_propio') && app('tenant.dominio_propio');

        if (! $dominioPropio) {
            return view('landing');
        }

        return $this->show();
    }

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

        $carrusel = Album::with(['fotos' => fn ($q) => $q->orderBy('orden')])
            ->carruselDelSitio()
            ->first();

        $noticias = Publicacion::visiblesPublico()->recientes()->limit(6)->get();

        $config = [
            'hero_visible'    => ConfigInstitucional::get('hp_hero_visible', '1') == '1',
            'hero_titulo'     => ConfigInstitucional::get('hp_hero_titulo', ''),
            'hero_subtitulo'  => ConfigInstitucional::get('hp_hero_subtitulo', ''),
            'hero_btn_texto'  => ConfigInstitucional::get('hp_hero_btn_texto', ''),
            'hero_btn_url'    => ConfigInstitucional::get('hp_hero_btn_url', '') ?: route('login'),
            'hero_btn2_texto' => ConfigInstitucional::get('hp_hero_btn2_texto', ''),
            'hero_btn2_url'   => ConfigInstitucional::get('hp_hero_btn2_url', '') ?: route('inscripcion'),

            'about_visible'   => ConfigInstitucional::get('hp_about_visible', '1') == '1',
            'about_titulo'    => ConfigInstitucional::get('hp_about_titulo', ''),
            'about_texto'     => ConfigInstitucional::get('hp_about_texto', ''),

            'stats_visible'   => ConfigInstitucional::get('hp_stats_visible', '1') == '1',
            'stats'           => $stats,

            'features_visible' => ConfigInstitucional::get('hp_features_visible', '1') == '1',
            'features_titulo'  => ConfigInstitucional::get('hp_features_titulo', ''),

            'carrusel_visible' => ConfigInstitucional::get('hp_carrusel_visible', '1') == '1',
            'carrusel'         => $carrusel,

            'noticias_visible' => ConfigInstitucional::get('hp_noticias_visible', '1') == '1',
            'noticias'         => $noticias,

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

    /** Listado completo, paginado, de publicaciones ("Ver todas las noticias"). */
    public function noticias()
    {
        $tenant = app('tenant');

        if (! $tenant->can('modo_publico')) {
            return view('public.sitio-no-disponible', compact('tenant'));
        }

        $publicaciones = Publicacion::visiblesPublico()->recientes()->paginate(12);
        $nombre = ConfigInstitucional::get('nombre_institucion', '') ?: $tenant->nombre_institucion;
        $colorPrimario = ConfigInstitucional::get('hp_color_primario', $tenant->color_primario ?? '#0d6efd');
        $logoPath = ConfigInstitucional::get('hp_logo_path');
        $logoUrl  = $logoPath ? Storage::url($logoPath) : $tenant->logo_url;

        return view('public.noticias-index', compact('publicaciones', 'nombre', 'colorPrimario', 'logoUrl'));
    }

    /** Detalle público de una publicación — respeta el mismo criterio de visibilidad que la portada. */
    public function noticiaShow(Publicacion $publicacion)
    {
        $tenant = app('tenant');

        if (! $tenant->can('modo_publico')) {
            return view('public.sitio-no-disponible', compact('tenant'));
        }

        // El scope automático de BelongsToTenant no basta aquí: SubstituteBindings
        // (donde Laravel resuelve {publicacion} vía route model binding) corre
        // ANTES que ResolveTenant en el pipeline de middleware, así que el scope
        // se aplica con el tenant de la request ANTERIOR (o ninguno) — se verifica
        // el tenant_id explícitamente, mismo patrón usado en el resto del código
        // para no depender solo del scope automático en verificaciones de acceso.
        abort_unless(
            $publicacion->tenant_id === $tenant->id
                && $publicacion->estado === 'publicado' && $publicacion->visible
                && $publicacion->fecha->lte(now()->toDateString()),
            404
        );

        $nombre = ConfigInstitucional::get('nombre_institucion', '') ?: $tenant->nombre_institucion;
        $colorPrimario = ConfigInstitucional::get('hp_color_primario', $tenant->color_primario ?? '#0d6efd');
        $logoPath = ConfigInstitucional::get('hp_logo_path');
        $logoUrl  = $logoPath ? Storage::url($logoPath) : $tenant->logo_url;

        return view('public.noticia-show', compact('publicacion', 'nombre', 'colorPrimario', 'logoUrl'));
    }
}
