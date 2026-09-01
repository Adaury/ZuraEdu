<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\SistemaController;
use Tests\TestCase;

/**
 * SistemaController::sanitizeSvg() se usa al subir el logo/favicon
 * institucional (que se sirve directo por URL) — debe neutralizar
 * <script>, <foreignObject>, atributos on*= y javascript: sin dañar
 * el contenido gráfico legítimo del SVG.
 */
class SanitizeSvgTest extends TestCase
{
    private function sanitize(string $svg): string
    {
        $controller = new SistemaController();
        $method = new \ReflectionMethod($controller, 'sanitizeSvg');
        $method->setAccessible(true);

        return $method->invoke($controller, $svg);
    }

    public function test_elimina_script_embebido(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="5"/></svg>';

        $limpio = $this->sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('<script', $limpio);
        $this->assertStringContainsString('<circle', $limpio);
    }

    public function test_elimina_atributos_de_eventos(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect onclick="alert(2)" width="10" height="10"/></svg>';

        $limpio = $this->sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('onload', $limpio);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $limpio);
        $this->assertStringContainsString('<rect', $limpio);
    }

    public function test_elimina_javascript_uri_en_atributo(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>click</text></a></svg>';

        $limpio = $this->sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $limpio);
    }

    public function test_elimina_foreign_object_con_html_embebido(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></body></foreignObject><circle r="5"/></svg>';

        $limpio = $this->sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('foreignObject', $limpio);
        $this->assertStringNotContainsStringIgnoringCase('<script', $limpio);
        $this->assertStringContainsString('<circle', $limpio);
    }

    public function test_conserva_svg_legitimo_sin_cambios_de_contenido(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="25" cy="25" r="20" fill="blue"/></svg>';

        $limpio = $this->sanitize($svg);

        $this->assertStringContainsString('<circle', $limpio);
        $this->assertStringContainsString('fill="blue"', $limpio);
    }

    public function test_rechaza_xml_malformado(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->sanitize('<svg><rect></svg');
    }
}
