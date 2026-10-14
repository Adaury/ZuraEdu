<?php

namespace Tests\Unit;

use App\Http\Middleware\SanitizeInput;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Los campos «ricos» (cuerpo de comunicados, etc.) se muestran sin escapar: el saneador no puede dejarse
 * engañar anidando una etiqueta dentro de otra, que al quitar la interna vuelve a formar la peligrosa.
 */
class SanitizeInputAnidadoTest extends TestCase
{
    private function sanear(string $cuerpo): string
    {
        $req = Request::create('/', 'POST', ['cuerpo' => $cuerpo]);
        $salida = null;
        (new SanitizeInput())->handle($req, function (Request $r) use (&$salida) {
            $salida = $r->input('cuerpo');

            return new \Symfony\Component\HttpFoundation\Response('ok');
        });

        return (string) $salida;
    }

    public static function cargas(): array
    {
        return [
            'script anidado'          => ['<scr<script>ipt>alert(1)</scr<script>ipt>'],
            'script anidado 2 niveles' => ['<scr<scr<script>ipt>ipt>alert(1)</scr<scr<script>ipt>ipt>'],
            'img anidada con onerror' => ['<im<img>g src=x on<img>error=alert(1)>'],
            'iframe anidado'          => ['<ifr<iframe>ame src="https://malo.example"></ifr<iframe>ame>'],
            'javascript: partido'     => ['<a href="javajavascript:script:alert(1)">x</a>'],
            'manejador on anidado'    => ['<p ononclick=click=alert(1)>hola</p>'],
        ];
    }

    #[DataProvider('cargas')]
    public function test_una_carga_anidada_no_sobrevive_al_saneado(string $carga): void
    {
        $limpio = strtolower($this->sanear($carga));

        $this->assertStringNotContainsString('<script', $limpio);
        $this->assertStringNotContainsString('<iframe', $limpio);
        $this->assertStringNotContainsString('<img', $limpio);
        $this->assertDoesNotMatchRegularExpression('/\bon\w+\s*=/', $limpio);
        $this->assertStringNotContainsString('javascript:', $limpio);
    }

    public function test_el_formato_normal_no_se_altera(): void
    {
        $html = '<p>Reunión el <strong>viernes</strong> a las 3 p. m. <a href="https://colegio.example/acta">Acta</a></p>';

        $this->assertSame($html, $this->sanear($html));
    }
}
