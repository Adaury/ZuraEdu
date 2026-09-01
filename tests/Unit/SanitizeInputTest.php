<?php

namespace Tests\Unit;

use App\Http\Middleware\SanitizeInput;
use Tests\TestCase;

/**
 * stripDangerousTags() debe bloquear javascript: aunque el navegador lo
 * normalice a partir de una variante con espacios/control chars intercalados
 * dentro de la propia palabra (no solo entre "javascript" y ":").
 */
class SanitizeInputTest extends TestCase
{
    private function strip(string $value): string
    {
        $middleware = new SanitizeInput();
        $method = new \ReflectionMethod($middleware, 'stripDangerousTags');
        $method->setAccessible(true);

        return $method->invoke($middleware, $value);
    }

    private function assertNoJavascriptScheme(string $output): void
    {
        $normalizado = str_replace(["\t", "\n", "\r"], '', $output);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $normalizado);
    }

    public function test_bloquea_tab_intercalado_dentro_de_la_palabra(): void
    {
        $this->assertNoJavascriptScheme(
            $this->strip("<a href=java\tscript:alert(1)>click</a>")
        );
    }

    public function test_bloquea_salto_de_linea_intercalado_dentro_de_la_palabra(): void
    {
        $this->assertNoJavascriptScheme(
            $this->strip("<a href=\"java\nscript:alert(1)\">click</a>")
        );
    }

    public function test_bloquea_espacio_entre_la_palabra_y_los_dos_puntos(): void
    {
        $this->assertNoJavascriptScheme(
            $this->strip('<a href="javascript :alert(1)">click</a>')
        );
    }

    public function test_bloquea_javascript_sin_espacios(): void
    {
        $this->assertNoJavascriptScheme(
            $this->strip('<a href="javascript:alert(1)">click</a>')
        );
    }

    public function test_no_afecta_mencion_textual_de_la_palabra(): void
    {
        $texto = 'Este comunicado no usa javascript ni nada raro.';

        $this->assertSame($texto, $this->strip($texto));
    }

    public function test_no_afecta_contenido_multilinea_legitimo(): void
    {
        $texto = "Primera línea.\nSegunda línea.\nTercera línea.";

        $this->assertSame($texto, $this->strip($texto));
    }
}
