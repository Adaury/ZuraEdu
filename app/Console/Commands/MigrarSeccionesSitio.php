<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\ConfigInstitucional;
use App\Models\PaginaSeccion;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Backfill de una sola vez (Portal Público Fase 2): convierte la
 * configuración hp_* de cada tenant (7 secciones fijas, orden en un CSV
 * hp_orden) en filas equivalentes de pagina_secciones (N bloques,
 * reordenables). Idempotente -- si un tenant ya tiene filas, se salta. Las
 * claves hp_* NO se tocan ni se borran; siguen siendo la red de rollback.
 *
 * IMPORTANTE: usa ConfigInstitucional::forTenant($id), nunca ::get() -- ese
 * helper cachea por tenant_id() del contenedor, que no existe en contexto
 * de consola (no hay request/ResolveTenant corriendo).
 */
class MigrarSeccionesSitio extends Command
{
    protected $signature   = 'sitio:migrar-secciones {--dry-run : Solo mostrar qué se haría, sin guardar}';
    protected $description = 'Backfill: convierte la config hp_* de cada tenant en filas de pagina_secciones';

    /** Mismo orden que HomepageController::SECCIONES_ORDENABLES (const eliminada en el cutover). */
    private const TIPOS_LEGACY = ['hero', 'carrusel', 'about', 'stats', 'features', 'noticias', 'contacto'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $tenants = Tenant::withTrashed()->get();
        $procesados = 0;

        foreach ($tenants as $tenant) {
            if (PaginaSeccion::forTenant($tenant->id)->exists()) {
                continue;
            }

            $cfg = ConfigInstitucional::forTenant($tenant->id)->pluck('valor', 'clave');
            if ($cfg->isEmpty()) {
                continue;
            }

            $this->info("Tenant {$tenant->id} ({$tenant->nombre_institucion}):");
            $procesados++;

            $orden = $this->ordenDesdeCsv($cfg->get('hp_orden', ''));

            foreach ($orden as $i => $tipo) {
                $activo = ($cfg->get("hp_{$tipo}_visible", '1') == '1');
                $contenido = $this->contenidoParaTipo($tipo, $cfg, $tenant);

                $this->line("  → {$tipo} (activo=" . ($activo ? 'si' : 'no') . ')');

                if (! $dryRun) {
                    PaginaSeccion::create([
                        'tenant_id' => $tenant->id,
                        'tipo'      => $tipo,
                        'orden'     => $i + 1,
                        'activo'    => $activo,
                        'contenido' => $contenido,
                    ]);
                }
            }
        }

        if ($procesados === 0) {
            $this->info('Todos los tenants ya tienen secciones migradas (o no tienen config previa). Nada que hacer.');
        } else {
            $this->info($dryRun ? 'Dry-run: no se guardó nada.' : "Listo. {$procesados} tenant(s) migrado(s).");
        }

        return 0;
    }

    /** Reimplementa HomepageController::ordenActual() sin depender de esa clase (se elimina en el cutover). */
    private function ordenDesdeCsv(string $csv): array
    {
        $guardado  = array_filter(explode(',', $csv));
        $validos   = array_values(array_intersect($guardado, self::TIPOS_LEGACY));
        $faltantes = array_values(array_diff(self::TIPOS_LEGACY, $validos));

        return array_merge($validos, $faltantes) ?: self::TIPOS_LEGACY;
    }

    private function contenidoParaTipo(string $tipo, $cfg, Tenant $tenant): array
    {
        return match ($tipo) {
            'hero' => [
                'titulo'     => $cfg->get('hp_hero_titulo', ''),
                'subtitulo'  => $cfg->get('hp_hero_subtitulo', ''),
                'btn_texto'  => $cfg->get('hp_hero_btn_texto', ''),
                'btn_url'    => $cfg->get('hp_hero_btn_url', ''),
                'btn2_texto' => $cfg->get('hp_hero_btn2_texto', ''),
                'btn2_url'   => $cfg->get('hp_hero_btn2_url', ''),
            ],
            'about' => [
                'titulo' => $cfg->get('hp_about_titulo', ''),
                'texto'  => $cfg->get('hp_about_texto', ''),
            ],
            'stats' => [
                'items' => collect(range(1, 4))
                    ->map(fn ($n) => ['numero' => $cfg->get("hp_stat{$n}_numero", ''), 'label' => $cfg->get("hp_stat{$n}_label", '')])
                    ->filter(fn ($s) => filled($s['numero']) || filled($s['label']))
                    ->values()->all(),
            ],
            'features' => [
                'titulo' => $cfg->get('hp_features_titulo', ''),
                'items'  => [],
            ],
            'carrusel' => [
                'album_id' => Album::forTenant($tenant->id)
                    ->where('mostrar_en_sitio', true)->where('activo', true)
                    ->value('id'),
            ],
            'noticias' => [
                'titulo' => '',
                'limite' => 6,
            ],
            'contacto' => [
                'titulo'    => '',
                'direccion' => $cfg->get('hp_contacto_direccion', ''),
                'telefono'  => $cfg->get('hp_contacto_telefono', ''),
                'email'     => $cfg->get('hp_contacto_email', ''),
                'facebook'  => $cfg->get('hp_social_facebook', ''),
                'instagram' => $cfg->get('hp_social_instagram', ''),
                'twitter'   => $cfg->get('hp_social_twitter', ''),
            ],
            default => [],
        };
    }
}
