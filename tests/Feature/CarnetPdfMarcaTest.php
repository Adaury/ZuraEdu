<?php

namespace Tests\Feature;

use App\Models\CarnetIdentidad;
use App\Models\User;
use App\Services\CarnetQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

/**
 * Carnets en PDF: con el logo de ZuraEdu, QR local y sin desbordar a una 2.ª página.
 * Antes los tres PDF usaban display:flex y degradados (dompdf no los soporta: texto blanco sobre blanco), el papel era de 80×50 mm en
 * vertical y el QR se pedía a un servicio externo que dompdf tiene bloqueado (enable_remote=false): salía un cuadro en blanco.
 */
class CarnetPdfMarcaTest extends TestCase
{
    /** CR80 horizontal: 85.6 × 53.98 mm. Con un arreglo NO se pasa 'landscape' (dompdf intercambiaría ancho y alto). */
    private const CR80 = [0, 0, 242.65, 153.02];

    private function carnet(string $nombre): CarnetIdentidad
    {
        $c = new CarnetIdentidad(['tipo' => 'estudiante', 'numero_carnet' => 'CN-000123', 'estado' => 'activo']);
        $c->id = 1;
        $c->qr_token = 'tok_' . md5($nombre);
        $c->setRelation('user', new User(['name' => $nombre]));
        $c->setRelation('matricula', null);

        return $c;
    }

    private function paginas(string $pdf): int
    {
        return (int) preg_match_all('/\/Type\s*\/Page[^s]/', $pdf);
    }

    public function test_el_carnet_individual_cabe_en_una_pagina_cr80_horizontal(): void
    {
        $pdf = Pdf::loadView('admin.carnet.pdf_carnet', ['carnet' => $this->carnet('María Fernanda Pérez Gómez'), 'qrContent' => 'https://x.test/checkin/scan/abc'])
            ->setPaper(self::CR80)->output();

        $this->assertSame(1, $this->paginas($pdf));
        $this->assertMatchesRegularExpression('/MediaBox\s*\[\s*0(\.0+)?\s+0(\.0+)?\s+242\.6\d*\s+153\.0\d*\s*\]/', $pdf, 'horizontal, 85.6 × 53.98 mm');
    }

    public function test_el_controlador_usa_el_papel_cr80_horizontal_sin_landscape(): void
    {
        $fuente = file_get_contents(app_path('Http/Controllers/Admin/CarnetController.php'));

        $this->assertSame(2, substr_count($fuente, 'setPaper([0, 0, 242.65, 153.02])'), 'individual y masivo');
        $this->assertStringNotContainsString('setPaper([0, 0, 226.77', $fuente, 'el tamaño anterior era de 80 × 50 mm');
    }

    public function test_el_carnet_trae_logo_y_qr_incrustados_sin_pedir_nada_a_internet(): void
    {
        $html = view('admin.carnet.pdf_carnet', ['carnet' => $this->carnet('Ana Gil'), 'qrContent' => 'https://x.test/checkin/scan/abc'])->render();

        $this->assertStringNotContainsString('quickchart', $html, 'dompdf no carga imágenes remotas: el QR debe ir incrustado');
        $this->assertSame(2, substr_count($html, 'src="data:image/png;base64,'), 'logo de ZuraEdu + QR, ambos incrustados');
        $this->assertStringNotContainsString('display:flex', $html);
        $this->assertStringNotContainsString('gradient', $html);
    }

    public function test_el_qr_local_es_un_png_valido(): void
    {
        $uri = CarnetQrService::qrDataUri('https://x.test/checkin/scan/abc');

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $bin = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
        $this->assertNotFalse($bin);
        $this->assertGreaterThan(40, getimagesizefromstring($bin)[0]);
    }

    public function test_el_pdf_masivo_da_una_pagina_por_carnet_y_una_sola_si_no_hay(): void
    {
        $tres = Pdf::loadView('admin.carnet.pdf_grupo', ['carnets' => collect([$this->carnet('Ana'), $this->carnet('Luis'), $this->carnet('Zoe')])])
            ->setPaper(self::CR80)->output();
        $vacio = Pdf::loadView('admin.carnet.pdf_grupo', ['carnets' => collect()])->setPaper(self::CR80)->output();

        $this->assertSame(3, $this->paginas($tres));
        $this->assertSame(1, $this->paginas($vacio));
    }

    public function test_la_hoja_de_carnets_del_grupo_va_en_cuadricula_de_tres_con_el_logo(): void
    {
        $mats = collect(range(1, 7))->map(fn ($i) => (object) ['estudiante' => (object) ['nombres' => "Nombre$i", 'apellidos' => "Apellido$i", 'matricula' => "M00$i", 'cedula' => null]]);
        $grupo = (object) [
            'grado' => (object) ['nombre' => '5to'], 'seccion' => (object) ['nombre' => 'A'],
            'schoolYear' => (object) ['nombre' => '2025-2026'], 'matriculas' => $mats,
        ];
        $datos = ['grupo' => $grupo, 'inst' => 'Colegio de Prueba', 'logoUrl' => null, 'config' => null];

        $html = view('admin.grupos.carnets_pdf', $datos)->render();
        $this->assertSame(7, substr_count($html, 'CARNET ESTUDIANTIL'));
        $this->assertSame(7, substr_count($html, 'src="data:image/png;base64,'), 'el logo de ZuraEdu en cada carnet');

        $pdf = Pdf::loadView('admin.grupos.carnets_pdf', $datos)->setPaper('letter', 'portrait')->output();
        $this->assertSame(1, $this->paginas($pdf), '7 carnets en tres columnas caben en una hoja');
    }

    public function test_las_pantallas_de_acceso_no_muestran_siglas_sino_la_insignia_cuando_no_hay_logo(): void
    {
        foreach (['login', 'forgot-password', 'register', 'reset-password'] as $v) {
            $t = file_get_contents(resource_path("views/auth/{$v}.blade.php"));
            $this->assertStringNotContainsString("strtoupper(substr(\$ls['system_abbr']", $t, "$v: ya no cae a siglas «SGE»");
            $this->assertStringContainsString('brand/zuraedu-icono.svg', $t, "$v: insignia de ZuraEdu como respaldo");
        }
    }
}
