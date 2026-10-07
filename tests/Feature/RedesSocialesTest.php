<?php

namespace Tests\Feature;

use App\Models\PaginaSeccion;
use App\Support\Marca;
use Tests\TestCase;

/**
 * Iconos de redes sociales: los de ZuraEdu en el pie de la portada (config brand.redes) y los de cada colegio en su sección de
 * contacto. Las URLs son texto libre, así que solo http/https llegan al href.
 */
class RedesSocialesTest extends TestCase
{
    public function test_url_segura_acepta_http_https_y_completa_sin_esquema(): void
    {
        $this->assertSame('https://instagram.com/zuraedu', Marca::urlSegura('instagram.com/zuraedu'));
        $this->assertSame('https://facebook.com/zuraedu', Marca::urlSegura('  https://facebook.com/zuraedu '));
        $this->assertSame('http://youtube.com/@zuraedu', Marca::urlSegura('http://youtube.com/@zuraedu'));
    }

    public function test_url_segura_rechaza_esquemas_peligrosos_y_basura(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,x', 'vbscript:x', 'https://', '', '   ', 'a b.com', "https://a.com/\"onmouseover=x"] as $malo) {
            $this->assertNull(Marca::urlSegura($malo), "debía rechazar: {$malo}");
        }
        $this->assertNull(Marca::urlSegura(null));
    }

    public function test_el_pie_solo_dibuja_las_redes_configuradas(): void
    {
        config(['brand.redes' => ['instagram' => 'https://instagram.com/zuraedu', 'facebook' => null, 'youtube' => 'youtube.com/@zuraedu']]);

        $html = view('partials.marca.redes')->render();

        $this->assertStringContainsString('href="https://instagram.com/zuraedu"', $html);
        $this->assertStringContainsString('href="https://youtube.com/@zuraedu"', $html);
        $this->assertStringNotContainsString('Facebook', $html);
    }

    public function test_sin_redes_configuradas_no_dibuja_nada(): void
    {
        config(['brand.redes' => ['instagram' => '', 'facebook' => null, 'youtube' => 'javascript:alert(1)']]);

        $this->assertSame('', trim(view('partials.marca.redes')->render()));
    }

    public function test_la_portada_incluye_los_iconos_en_el_pie(): void
    {
        $this->assertStringContainsString("@include('partials.marca.redes')", file_get_contents(resource_path('views/landing.blade.php')));
    }

    public function test_la_seccion_de_contacto_del_colegio_muestra_youtube_y_bloquea_javascript(): void
    {
        $seccion = new PaginaSeccion(['tipo' => 'contacto', 'contenido' => [
            'titulo'    => 'Contacto',
            'youtube'   => 'https://youtube.com/@colegio',
            'instagram' => 'javascript:alert(1)',
            'facebook'  => 'facebook.com/colegio',
        ]]);

        $html = view('public.sitio-secciones.contacto', ['seccion' => $seccion])->render();

        $this->assertStringContainsString('href="https://youtube.com/@colegio"', $html);
        $this->assertStringContainsString('bi-youtube', $html);
        $this->assertStringContainsString('href="https://facebook.com/colegio"', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('bi-instagram', $html);
    }
}
