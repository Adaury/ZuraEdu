<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La portada muestra la sección de videos (presentación, publicitario y uno por módulo) solo con los MP4 que existen en
 * public/videos; sin videos no sale ningún reproductor roto.
 */
class LandingVideosTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBRES = [
        'zuraedu-presentacion', 'zuraedu-promo', 'modulo-inscripcion-matricula', 'modulo-asistencia', 'modulo-notas-boletines',
        'modulo-pagos', 'modulo-cafeteria', 'modulo-comunicados', 'modulo-portal-docente', 'modulo-portal-familias',
    ];

    private function hayVideosReales(): bool
    {
        foreach (self::NOMBRES as $n) {
            if (is_file(public_path("videos/{$n}.mp4"))) {
                return true;
            }
        }
        return false;
    }

    public function test_sin_videos_la_portada_no_muestra_la_seccion(): void
    {
        if ($this->hayVideosReales()) {
            $this->markTestSkipped('Ya hay videos reales en public/videos.');
        }

        $this->get('/')->assertOk()->assertDontSee('id="videos"', false)->assertDontSee('zv-modal', false);
    }

    public function test_con_un_video_aparece_solo_ese_y_su_boton_de_modulo(): void
    {
        if ($this->hayVideosReales()) {
            $this->markTestSkipped('Ya hay videos reales en public/videos.');
        }

        $dir = public_path('videos');
        $creoDir = ! is_dir($dir);
        if ($creoDir) {
            mkdir($dir, 0775, true);
        }
        $archivos = [$dir . '/zuraedu-presentacion.mp4', $dir . '/modulo-asistencia.mp4'];

        try {
            foreach ($archivos as $a) {
                file_put_contents($a, 'x');
            }

            $r = $this->get('/')->assertOk();
            $r->assertSee('id="videos"', false);
            $r->assertSee('videos/zuraedu-presentacion.mp4', false);
            $r->assertSee('data-zv-t="Asistencia"', false);
            $r->assertDontSee('zuraedu-promo.mp4', false);       // no existe: no se ofrece
            $r->assertDontSee('modulo-pagos.mp4', false);
        } finally {
            foreach ($archivos as $a) {
                @unlink($a);
            }
            if ($creoDir) {
                @rmdir($dir);
            }
        }
    }
}
