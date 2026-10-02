<?php

namespace Tests\Feature;

use App\Support\Marca;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

/**
 * Marca ZuraEdu — pruebas ESTÁTICAS (no usan la base de datos, corren en segundos).
 * Sirven de guardia: si alguien crea una página, un PDF o un correo nuevo sin el pie de marca, o escribe el nombre de un colegio a mano
 * en una vista, esta prueba falla y dice cuál.
 */
class MarcaZuraEduEstaticoTest extends TestCase
{
    /** Marcas que prueban que una página tiene el pie de la plataforma. */
    private const MARCAS_DE_PIE = ['x-marca.pie', 'marca.pie-pdf', 'marca.pie-correo', 'Marca::copyright()'];

    /** Diseños de página completa o de formato físico donde el pie NO corresponde (decisión documentada en docs/PROCESO_MARCA_ZURAEDU.md). */
    private const SIN_PIE_POR_DISENO = [
        'admin/carnet/pdf_carnet.blade.php',
        'admin/carnet/pdf_grupo.blade.php',
        'admin/grupos/carnets_pdf.blade.php',
        'admin/reconocimientos/diploma_pdf.blade.php',
        'admin/proyectos/certificado_pdf.blade.php',
    ];

    /** @return array<string,string> ruta relativa => contenido */
    private function vistas(): array
    {
        $base = resource_path('views');
        $out = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)) as $f) {
            if (str_ends_with($f->getFilename(), '.blade.php')) {
                $out[str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1))] = file_get_contents($f->getPathname());
            }
        }
        ksort($out);

        return $out;
    }

    public function test_toda_pagina_html_completa_lleva_el_pie_de_marca(): void
    {
        $sinPie = [];
        foreach ($this->vistas() as $ruta => $t) {
            if (! str_contains($t, '<html') || in_array($ruta, self::SIN_PIE_POR_DISENO, true)) {
                continue;
            }
            if (str_contains($t, '@extends(')) {
                continue;   // hereda una plantilla base, que ya lo trae
            }
            $tiene = false;
            foreach (self::MARCAS_DE_PIE as $m) {
                if (str_contains($t, $m)) {
                    $tiene = true;
                    break;
                }
            }
            if (! $tiene) {
                $sinPie[] = $ruta;
            }
        }

        $this->assertSame([], $sinPie, "Páginas HTML sin el pie de marca ZuraEdu (usa <x-marca.pie />, @include('partials.marca.pie-pdf') o 'partials.marca.pie-correo'):\n  " . implode("\n  ", $sinPie));
    }

    public function test_las_tres_plantillas_base_llevan_el_pie_y_el_icono(): void
    {
        foreach (['layouts/admin.blade.php', 'layouts/portal.blade.php', 'layouts/superadmin.blade.php'] as $l) {
            $t = $this->vistas()[$l];
            $this->assertStringContainsString('<x-marca.pie', $t, "$l sin pie de marca");
            $this->assertStringContainsString('partials.marca.head', $t, "$l sin los íconos de pestaña de la marca");
        }
    }

    public function test_ninguna_vista_ni_correo_tiene_el_nombre_de_un_colegio_escrito_a_mano(): void
    {
        $hallazgos = [];
        foreach ($this->vistas() as $ruta => $t) {
            foreach (preg_split('/\R/', $t) as $i => $linea) {
                if (preg_match('/Arquides|Salesiano|PSAC|AprendeTic/i', $linea) && ! preg_match('/placeholder/i', $linea)) {
                    $hallazgos[] = "$ruta:" . ($i + 1);
                }
            }
        }

        $this->assertSame([], $hallazgos, "Nombre de un colegio escrito a mano (lo verían todos los centros): usa config('tenant.nombre') o \$institucion:\n  " . implode("\n  ", $hallazgos));
    }

    public function test_ninguna_vista_dice_sge_como_nombre_del_producto_en_el_titulo(): void
    {
        $malas = [];
        foreach ($this->vistas() as $ruta => $t) {
            if (preg_match('/<title>[^<]*\bSGE\b[^<]*<\/title>/', $t)) {
                $malas[] = $ruta;
            }
        }

        $this->assertSame([], $malas, 'El producto se llama ZuraEdu; títulos con «SGE»: ' . implode(', ', $malas));
    }

    // ── texto legal ─────────────────────────────────────────────────────────

    public function test_el_texto_legal_dice_derechos_reservados_por_zuraedu(): void
    {
        config(['brand.anio_inicio' => (int) date('Y')]);
        $this->assertSame('© ' . date('Y') . ' ZuraEdu. Todos los derechos reservados.', Marca::copyright());
    }

    public function test_el_pie_muestra_un_rango_de_anios_despues_del_primer_anio(): void
    {
        config(['brand.anio_inicio' => (int) date('Y') - 2]);
        $this->assertSame('© ' . ((int) date('Y') - 2) . '–' . date('Y') . ' ZuraEdu. Todos los derechos reservados.', Marca::copyright());
    }

    // ── recursos de la marca ────────────────────────────────────────────────

    public function test_los_recursos_del_logo_existen_y_tienen_las_medidas_correctas(): void
    {
        $esperados = [
            'public/brand/zuraedu-logo.png' => [1200, null], 'public/brand/zuraedu-logo-blanco.png' => [1200, null],
            'public/brand/zuraedu-logo-300.png' => [300, null], 'public/brand/zuraedu-icono-512.png' => [512, 512],
            'public/brand/zuraedu-icono-192.png' => [192, 192], 'public/brand/apple-touch-icon.png' => [180, 180],
            'public/brand/favicon-32.png' => [32, 32], 'public/brand/og-image.png' => [1200, 630],
            'mobile/assets/icon.png' => [1024, 1024], 'mobile/assets/adaptive-icon.png' => [1024, 1024],
            'mobile/assets/splash-icon.png' => [512, 512], 'mobile/assets/notification-icon.png' => [96, 96],
        ];
        foreach ($esperados as $ruta => [$ancho, $alto]) {
            $archivo = base_path($ruta);
            $this->assertFileExists($archivo, $ruta);
            $info = getimagesize($archivo);
            $this->assertSame($ancho, $info[0], "$ruta: ancho");
            if ($alto) {
                $this->assertSame($alto, $info[1], "$ruta: alto");
            }
        }
    }

    public function test_los_svg_son_xml_valido_con_viewbox(): void
    {
        foreach (['zuraedu-logo', 'zuraedu-logo-blanco', 'zuraedu-icono', 'zuraedu-icono-blanco', 'favicon'] as $n) {
            $xml = simplexml_load_file(public_path("brand/{$n}.svg"));
            $this->assertNotFalse($xml, "$n.svg no es XML válido");
            $this->assertNotEmpty((string) $xml['viewBox'], "$n.svg sin viewBox");
        }
    }

    public function test_el_favicon_ico_es_un_ico_valido_con_tres_tamanos(): void
    {
        $b = file_get_contents(public_path('favicon.ico'));
        $cab = unpack('vreservado/vtipo/vcantidad', substr($b, 0, 6));
        $this->assertSame([0, 1, 3], [$cab['reservado'], $cab['tipo'], $cab['cantidad']]);
    }

    public function test_el_logo_png_se_convierte_a_data_uri_para_los_pdf(): void
    {
        $uri = Marca::logoDataUri('png');
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertNotFalse(base64_decode(substr($uri, strlen('data:image/png;base64,')), true));
    }

    // ── PDF: el pie nunca cambia la paginación ──────────────────────────────

    private function paginas(string $cuerpo): int
    {
        $pdf = Pdf::loadHTML('<html><head><style>@page{margin:20px;}body{margin:0;}</style></head><body>' . $cuerpo . '</body></html>')->setPaper('a4')->output();

        return (int) preg_match_all('/\/Type\s*\/Page[^s]/', $pdf);
    }

    public function test_el_pie_de_pdf_no_agrega_una_pagina_a_un_documento_que_cabe_justo(): void
    {
        $lineas = str_repeat('<p style="margin:0 0 6px;font-family:DejaVu Sans;font-size:10pt;">Línea de contenido de prueba del documento.</p>', 40);   // llena una página A4

        $this->assertSame(1, $this->paginas($lineas), 'precondición: sin pie cabe en una página');
        $this->assertSame(1, $this->paginas($lineas . view('partials.marca.pie-pdf')->render()), 'con el pie debe seguir siendo UNA página');
    }

    public function test_el_pie_de_pdf_lleva_el_texto_y_el_logo(): void
    {
        $html = view('partials.marca.pie-pdf')->render();
        $this->assertStringContainsString(Marca::copyright(), $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('position:absolute', $html, 'va posicionado al fondo, no en el flujo');
    }

    public function test_el_pie_de_correo_lleva_logo_png_por_url_absoluta_y_el_texto(): void
    {
        $html = view('partials.marca.pie-correo')->render();
        $this->assertStringContainsString(Marca::copyright(), $html);
        $this->assertMatchesRegularExpression('#<img src="https?://[^"]+/brand/zuraedu-logo-300\.png"#', $html);
    }
}
