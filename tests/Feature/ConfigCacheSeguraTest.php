<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * deploy.sh ejecuta `php artisan optimize` (config:cache). Con la configuración cacheada
 * env() fuera de config/ devuelve null: HORARIO_* y HORIZON_ALLOWED_EMAILS se ignoraban en
 * producción y los boletines caían a un nombre de colegio fijo en el código. Estos tests
 * impiden que vuelva a pasar.
 */
class ConfigCacheSeguraTest extends TestCase
{
    public function test_no_se_usa_env_fuera_de_config(): void
    {
        $raiz = dirname(__DIR__, 2);
        $violaciones = [];

        foreach (['app', 'resources', 'routes', 'bootstrap', 'database'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$raiz}/{$dir}", \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                $ruta = str_replace('\\', '/', $archivo->getPathname());
                if (! str_ends_with($ruta, '.php') || str_contains($ruta, 'bootstrap/cache/')) {
                    continue;
                }
                foreach (file($ruta) as $n => $linea) {
                    $codigo = ltrim($linea);
                    // Ignora comentarios; busca llamadas reales a env(
                    if (str_starts_with($codigo, '//') || str_starts_with($codigo, '*') || str_starts_with($codigo, '#')) {
                        continue;
                    }
                    if (preg_match('/(?<![\w>:$])env\(/', $linea)) {
                        $violaciones[] = str_replace($raiz . '/', '', $ruta) . ':' . ($n + 1);
                    }
                }
            }
        }

        $this->assertSame([], $violaciones, "env() fuera de config/ (devuelve null con config:cache):\n" . implode("\n", $violaciones));
    }

    public function test_el_generador_de_horarios_lee_su_configuracion(): void
    {
        // Tipos y rangos, no valores exactos: un .env local puede sobrescribir HORARIO_*.
        $this->assertIsInt(config('horarios.max_iter'));
        $this->assertGreaterThan(0, config('horarios.max_iter'));
        $this->assertIsFloat(config('horarios.max_time'));
        $this->assertGreaterThan(0, config('horarios.max_time'));
        $this->assertIsBool(config('horarios.debug'));
    }

    public function test_marca_de_la_plataforma_disponible_por_config(): void
    {
        $this->assertNotEmpty(config('app.product_name'));
    }

    public function test_el_cache_por_defecto_respeta_cache_store(): void
    {
        $this->assertSame('array', config('cache.default'));   // phpunit.xml: CACHE_STORE=array
    }
}
