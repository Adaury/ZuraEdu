<?php

namespace Tests\Feature;

use App\Support\Traductor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Selector de idioma: el cookie «idioma» fija el idioma de la interfaz; el HTML se traduce en el servidor con
 * lang/{codigo}.json, sin tocar scripts, estilos ni datos, y en español no cambia nada.
 */
class IdiomaInterfazTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_espanol_el_login_queda_igual_y_ofrece_el_selector(): void
    {
        $r = $this->get('/login')->assertOk();

        $r->assertSee('Bienvenido');
        $r->assertDontSee('Welcome');
        $r->assertSee('selector-idioma', false);
    }

    public function test_con_el_cookie_en_ingles_la_pantalla_se_traduce_y_los_nombres_de_idioma_no(): void
    {
        $r = $this->withCookie('idioma', 'en')->get('/login')->assertOk();

        $r->assertSee('Welcome');
        $r->assertDontSee('Bienvenido');
        $r->assertSee('Espa&ntilde;ol', false);   // el nombre de cada idioma se muestra siempre en su propio idioma
        $this->assertStringContainsString('lang="en"', $r->getContent());
    }

    public function test_cada_idioma_disponible_traduce_el_login(): void
    {
        $esperado = ['en' => 'Welcome', 'fr' => 'Bienvenue', 'pt' => 'Bem-vindo'];

        foreach ($esperado as $codigo => $texto) {
            $this->withCookie('idioma', $codigo)->get('/login')->assertOk()->assertSee($texto);
        }
    }

    public function test_cambiar_idioma_guarda_el_cookie_y_vuelve_a_la_pagina(): void
    {
        $r = $this->from('/login')->post(route('idioma.cambiar', 'fr'));

        $r->assertRedirect('/login');
        $r->assertCookie('idioma', 'fr');
    }

    public function test_un_idioma_no_soportado_se_rechaza(): void
    {
        $this->post(route('idioma.cambiar', 'xx'))->assertNotFound();
        $this->post('/idioma/espanol')->assertNotFound();
    }

    public function test_un_cookie_con_un_idioma_desconocido_cae_en_espanol(): void
    {
        $this->withCookie('idioma', 'zz')->get('/login')->assertOk()->assertSee('Bienvenido');
    }

    public function test_el_traductor_solo_toca_texto_y_atributos_visibles(): void
    {
        $html = '<html lang="es"><body><script>var t = "Estudiantes";</script><style>.x::after{content:"Estudiantes"}</style>'
            . '<textarea>Estudiantes</textarea><td> Estudiantes </td><input placeholder="Buscar" value="Estudiantes">'
            . '<h1>Hola, Ana</h1><p>Texto libre que no está en el diccionario</p></body></html>';

        $salida = Traductor::html($html, 'en');

        $this->assertStringContainsString('<script>var t = "Estudiantes";</script>', $salida);
        $this->assertStringContainsString('content:"Estudiantes"', $salida);
        $this->assertStringContainsString('<textarea>Estudiantes</textarea>', $salida);
        $this->assertStringContainsString('<td> Students </td>', $salida);
        $this->assertStringContainsString('placeholder="Search"', $salida);
        $this->assertStringContainsString('value="Estudiantes"', $salida);
        $this->assertStringContainsString('<h1>Hello, Ana</h1>', $salida);
        $this->assertStringContainsString('Texto libre que no está en el diccionario', $salida);
        $this->assertStringContainsString('<html lang="en">', $salida);
    }

    public function test_el_diccionario_cubre_todos_los_idiomas_con_las_mismas_claves(): void
    {
        $en = array_keys(Traductor::diccionario('en'));
        $this->assertGreaterThan(300, count($en));
        $this->assertSame($en, array_keys(Traductor::diccionario('fr')));
        $this->assertSame($en, array_keys(Traductor::diccionario('pt')));

        foreach (['en', 'fr', 'pt'] as $codigo) {
            foreach (Traductor::diccionario($codigo) as $clave => $valor) {
                $this->assertNotSame('', trim($valor), "Traducción vacía ({$codigo}): {$clave}");
            }
        }
    }

    public function test_la_coincidencia_no_distingue_mayusculas_y_conserva_el_estilo(): void
    {
        $this->assertSame('View all', Traductor::texto('Ver todos', 'en'));
        $this->assertSame('VIEW ALL', Traductor::texto('VER TODOS', 'en'));
        $this->assertSame('Active students', Traductor::texto('ESTUDIANTES ACTIVOS', 'en') === 'ACTIVE STUDENTS' ? 'Active students' : 'x');
        $this->assertNull(Traductor::texto('Un texto cualquiera de un usuario', 'en'));
    }

    public function test_los_titulos_compuestos_y_los_emojis_se_traducen_por_partes(): void
    {
        // «Título — ZuraEdu»: se traduce el título y la marca queda igual
        $this->assertSame('My messages — ZuraEdu', Traductor::texto('Mis Mensajes — ZuraEdu', 'en'));
        // Un nombre propio en medio no se toca
        $this->assertSame('Teacher portal — Ana Pérez — ZuraEdu', Traductor::texto('Portal Docente — Ana Pérez — ZuraEdu', 'en'));
        // El adorno delante del texto se conserva
        $this->assertSame('📝 Exams', Traductor::texto('📝 Exámenes', 'en'));
        $this->assertSame('📝 Examens', Traductor::texto('📝 Exámenes', 'fr'));
        // Con parte variable
        $this->assertSame('Welcome, Rep', Traductor::texto('Bienvenido, Rep', 'en'));
        $this->assertSame('Année scolaire : 2026-2027', Traductor::texto('Año escolar: 2026-2027', 'fr'));
        // Un título totalmente propio no se inventa
        $this->assertNull(Traductor::texto('Reunión de claustro — Colegio X', 'en'));
    }

    public function test_las_fechas_salen_en_el_idioma_elegido(): void
    {
        app()->setLocale('fr');
        $this->assertSame('octobre', \Illuminate\Support\Carbon::create(2026, 10, 15)->isoFormat('MMMM'));

        app()->setLocale('en');
        $this->assertSame('October', \Illuminate\Support\Carbon::create(2026, 10, 15)->isoFormat('MMMM'));
    }

    public function test_los_mensajes_de_validacion_salen_en_el_idioma_elegido(): void
    {
        app()->setLocale('es');
        $this->assertSame('El campo email es obligatorio.', validator(['email' => ''], ['email' => 'required'])->errors()->first('email'));

        app()->setLocale('fr');
        $this->assertSame('Le champ email est obligatoire.', validator(['email' => ''], ['email' => 'required'])->errors()->first('email'));
    }
}
