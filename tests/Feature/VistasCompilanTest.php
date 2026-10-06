<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Compila TODAS las vistas Blade y comprueba que el PHP resultante no tiene errores de sintaxis.
 *
 * La pantalla «Editar encuesta» nunca abrió desde que se creó: un @json(...) de varias líneas con una función flecha producía
 * «Unclosed '[' ... does not match ')'». Ninguna prueba la veía porque ninguna la renderizaba, y RouteSmokeTest solo carga pantallas
 * sin parámetros. Esta prueba no necesita datos ni rutas: compila cada archivo.
 */
class VistasCompilanTest extends TestCase
{
    public function test_todas_las_vistas_blade_compilan_a_php_valido(): void
    {
        $errores = [];
        $revisadas = 0;
        $base = resource_path('views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $ruta = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            $revisadas++;

            try {
                $php = Blade::compileString(file_get_contents($f->getPathname()));
                token_get_all($php, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $errores[] = "$ruta: " . $e->getMessage() . ' (línea ' . $e->getLine() . ' del PHP compilado)';
            } catch (\Throwable $e) {
                $errores[] = "$ruta: no se pudo compilar — " . $e->getMessage();
            }
        }

        $this->assertGreaterThan(300, $revisadas, 'se revisaron menos vistas de las esperadas: ¿cambió la carpeta?');
        $this->assertSame([], $errores, "Vistas con errores de sintaxis (la pantalla daría error 500 al abrirla):\n  " . implode("\n  ", $errores));
    }
}
