<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\ConfigInstitucional;
use App\Models\PaginaSeccion;
use App\Models\Publicacion;
use Illuminate\Support\Facades\Storage;

/**
 * Sitio público institucional del tenant actual (roadmap de producto:
 * "portal público por centro"). Renderiza los bloques que el propio centro
 * arma en el constructor visual (Admin\PaginaSeccionController, tabla
 * pagina_secciones) — no crea ningún dato nuevo, solo lo lee. Se sirve en
 * /sitio, listado en las RUTAS_PUBLICAS de ResolveTenant para que sea
 * visible incluso si el tenant está suspendido.
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

        $secciones = PaginaSeccion::tiposValidos()->activas()->ordenadas()->get();

        // Hidratar los tipos "query-backed" -- el JSON solo guarda la
        // referencia (album_id / limite), los datos reales siempre se leen
        // por acá para que sus scopes (visibilidad/moderación) se respeten
        // aunque el bloque exista.
        $albumIds = $secciones->where('tipo', 'carrusel')
            ->pluck('contenido.album_id')->filter()->unique();

        $albumesPorId = $albumIds->isNotEmpty()
            ? Album::with(['fotos' => fn ($q) => $q->orderBy('orden')])
                ->carruselDelSitio()->whereIn('id', $albumIds)->get()->keyBy('id')
            : collect();

        $albumPorDefecto = null;

        foreach ($secciones as $seccion) {
            if ($seccion->tipo === 'carrusel') {
                $albumId = $seccion->dato('album_id');
                $seccion->datos = $albumId ? $albumesPorId->get($albumId) : null;

                if (! $seccion->datos) {
                    // Compatibilidad con el comportamiento previo al backfill:
                    // sin álbum explícito, cae al único álbum marcado para el sitio.
                    $albumPorDefecto ??= Album::with(['fotos' => fn ($q) => $q->orderBy('orden')])
                        ->carruselDelSitio()->first();
                    $seccion->datos = $albumPorDefecto;
                }
            } elseif ($seccion->tipo === 'noticias') {
                $seccion->datos = Publicacion::visiblesPublico()->recientes()
                    ->limit($seccion->dato('limite', 6))->get();
            }
        }

        $secciones = $secciones->filter(fn ($s) => $s->tieneContenido())->values();

        $logoPath = ConfigInstitucional::get('hp_logo_path');

        $config = [
            'nombre'           => ConfigInstitucional::get('nombre_institucion', '') ?: $tenant->nombre_institucion,
            'logo_url'         => $logoPath ? Storage::url($logoPath) : $tenant->logo_url,
            'color_primario'   => ConfigInstitucional::get('hp_color_primario', $tenant->color_primario ?? '#0d6efd'),
            'color_secundario' => ConfigInstitucional::get('hp_color_secundario', $tenant->color_secundario ?? '#6c757d'),

            // Código de anuncios de terceros (Google AdSense u otro) que el
            // propio centro configura — se imprime tal cual, sin sanear (ver
            // SanitizeInput::$allowedEmbedFields), es contenido de su propio
            // tenant, no cruza a otros centros.
            'ads_izquierda' => ConfigInstitucional::get('hp_ads_izquierda', ''),
            'ads_derecha'   => ConfigInstitucional::get('hp_ads_derecha', ''),
        ];

        return view('public.sitio', compact('tenant', 'config', 'secciones'));
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
