<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acceso (login): en el dominio de la PLATAFORMA muestra la marca ZuraEdu; solo con el dominio propio de un colegio muestra el
 * logo y el nombre de ese colegio. Antes, entrar por la dirección general (p. ej. un túnel de pruebas) mostraba el logo del
 * colegio por defecto (el del PSAC) en vez del de ZuraEdu.
 */
class LoginMarcaPorDominioTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_el_dominio_general_el_acceso_es_de_zuraedu_y_no_del_colegio_por_defecto(): void
    {
        // El colegio por defecto (id 1) tiene logo y nombre propios
        $colegio = Tenant::find(1) ?? Tenant::create(['nombre_institucion' => 'Colegio por defecto', 'dominio' => 'defecto', 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        $colegio->update(['nombre_institucion' => 'Politécnico Ejemplo', 'logo' => 'logos/logo-del-colegio.png']);
        app()->instance('tenant', $colegio);
        ConfigInstitucional::set('hp_logo_path', 'branding/logo-del-colegio.jpg');
        app()->forgetInstance('tenant');

        $html = $this->get('http://localhost/login')->assertOk()->getContent();

        $this->assertStringContainsString('brand/zuraedu-icono.svg', $html, 'insignia de ZuraEdu');
        $this->assertStringNotContainsString('logo-del-colegio', $html, 'sin el logo de ningún colegio');
        $this->assertStringNotContainsString('Politécnico Ejemplo', $html, 'sin el nombre de ningún colegio');
        $this->assertStringContainsString('<title>Iniciar Sesión — ZuraEdu</title>', $html);
    }

    public function test_con_el_dominio_propio_de_un_colegio_el_acceso_muestra_su_logo_y_su_nombre(): void
    {
        $colegio = Tenant::create([
            'nombre_institucion' => 'Colegio Propio', 'dominio' => 'propio' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
            'dominio_personalizado' => 'acceso.colegiopropio.test', 'logo' => 'logos/logo-propio.png',
        ]);

        $html = $this->get('http://acceso.colegiopropio.test/login')->assertOk()->getContent();

        $this->assertStringContainsString('logos/logo-propio.png', $html, 'logo del colegio');
        $this->assertStringContainsString('Colegio Propio', $html);
        $this->assertStringContainsString('brand/zuraedu-logo-blanco.svg', $html, 'la marca de la plataforma sigue en el pie');
    }
}
