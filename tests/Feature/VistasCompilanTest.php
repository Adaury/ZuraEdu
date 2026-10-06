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

    /**
     * `Estudiante` solo tiene `nombres`, `apellidos` y `numero_matricula`. La pantalla de deudores leía `apellido`, `nombre` y `matricula`
     * (no existen) y mostraba nombres vacíos desde mayo; once PDF de certificados y recibos imprimían «Matrícula: —» siempre.
     */
    public function test_ninguna_vista_lee_campos_que_el_estudiante_no_tiene(): void
    {
        $errores = [];
        $base = resource_path('views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $ruta = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            foreach (file($f->getPathname()) as $n => $linea) {
                if (preg_match('/(?:estudiante|\$est)\??->matricula(?![\w(])/', $linea)
                    || preg_match('/estudiante\??->(?:apellido|nombre)\s*\}\}/', $linea)) {
                    $errores[] = "$ruta:" . ($n + 1);
                }
            }
        }

        $this->assertSame([], $errores, "Vistas que leen un campo que Estudiante no tiene (usar numero_matricula, apellidos, nombres):\n  " . implode("\n  ", $errores));
    }

    /**
     * `array_max()` (que no existe en PHP) dejó sin abrir «Estadísticas de asistencia» del docente: un error de tipeo que solo explota al
     * abrir la pantalla. Se buscan llamadas a funciones inexistentes en el PHP compilado de cada vista.
     */
    public function test_ninguna_vista_llama_a_una_funcion_php_que_no_existe(): void
    {
        $errores = [];
        $base = resource_path('views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $ruta = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            $php = Blade::compileString(file_get_contents($f->getPathname()));
            $tokens =array_values(array_filter(token_get_all($php), fn ($t) => ! (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML], true))));

            // Funciones que la propia vista declara (dentro de @php … @endphp)
            $propias = [];
            foreach ($tokens as $i => $tk) {
                if (is_array($tk) && $tk[0] === T_FUNCTION && isset($tokens[$i + 1]) && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING) {
                    $propias[strtolower($tokens[$i + 1][1])] = true;
                }
            }

            foreach ($tokens as $i => $tk) {
                if (! is_array($tk) || $tk[0] !== T_STRING || ($tokens[$i + 1] ?? null) !== '(') {
                    continue;
                }
                $prev = $tokens[$i - 1] ?? null;
                if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_NS_SEPARATOR, T_STRING], true)) {
                    continue;   // método, función estática, declaración, clase o nombre calificado
                }
                $nombre = $tk[1];
                if (! function_exists($nombre) && ! isset($propias[strtolower($nombre)])
                    && ! in_array(strtolower($nombre), ['array', 'list', 'isset', 'empty', 'unset', 'exit', 'die', 'echo', 'print', 'if', 'elseif', 'while', 'for', 'foreach', 'switch', 'match', 'fn', 'function', 'catch', 'use', 'static', 'self', 'parent'], true)) {
                    $errores[] = "$ruta: $nombre()";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($errores)), "Vistas que llaman a una función PHP que no existe (error 500 al abrirlas):\n  " . implode("\n  ", array_unique($errores)));
    }
}
