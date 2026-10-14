<?php

namespace Tests\Feature;

use App\Models\Comunicado;
use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La pantalla «Comunicados» del docente en la app móvil llamaba a /api/v1/docente/comint, que no existía (404).
 * Aquí se comprueba que la API sirve los comunicados internos con la misma regla que el portal web y que el
 * acuse de lectura solo funciona sobre lo que el usuario puede ver.
 */
class ComintApiMovilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function docente(): Docente
    {
        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $d->user->update(['activo' => true]);

        return $d;
    }

    private function comunicado(array $extra = []): Comunicado
    {
        return Comunicado::create(array_merge([
            'titulo' => 'Reunión de claustro', 'cuerpo' => 'Viernes 3 p. m.', 'tipo_destinatarios' => 'docentes',
            'autor_id' => \App\Models\User::factory()->create()->id,
            'published_at' => now()->subHour(), 'activo' => true, 'es_interno' => true,
        ], $extra));
    }

    public function test_el_docente_ve_los_comunicados_internos_publicados_para_docentes(): void
    {
        $this->comunicado();
        $this->comunicado(['titulo' => 'Solo coordinadores', 'tipo_destinatarios' => 'coordinadores']);
        $this->comunicado(['titulo' => 'Borrador futuro', 'published_at' => now()->addDay()]);
        $this->comunicado(['titulo' => 'Para padres (no interno)', 'es_interno' => false]);

        Sanctum::actingAs($this->docente()->user);
        $r = $this->getJson(route('api.docente.comint'))->assertOk();

        $r->assertJsonFragment(['titulo' => 'Reunión de claustro', 'leido' => false]);
        $contenido = $r->getContent();
        $this->assertStringNotContainsString('Solo coordinadores', $contenido);
        $this->assertStringNotContainsString('Borrador futuro', $contenido);
        $this->assertStringNotContainsString('Para padres', $contenido);
    }

    public function test_el_acuse_de_lectura_se_registra_una_sola_vez_y_se_refleja_en_el_listado(): void
    {
        $c = $this->comunicado();
        $docente = $this->docente();
        Sanctum::actingAs($docente->user);

        $this->postJson(route('api.docente.comint.leer', $c->id))->assertOk()->assertJson(['ok' => true]);
        $this->postJson(route('api.docente.comint.leer', $c->id))->assertOk();

        $this->assertSame(1, $c->lecturas()->where('user_id', $docente->user->id)->count());
        $this->getJson(route('api.docente.comint'))->assertJsonFragment(['titulo' => 'Reunión de claustro', 'leido' => true]);
    }

    public function test_no_se_puede_dar_acuse_de_un_comunicado_que_el_docente_no_ve(): void
    {
        $coord = $this->comunicado(['tipo_destinatarios' => 'coordinadores']);
        $futuro = $this->comunicado(['published_at' => now()->addDay()]);
        $noInterno = $this->comunicado(['es_interno' => false]);
        Sanctum::actingAs($this->docente()->user);

        foreach ([$coord, $futuro, $noInterno] as $c) {
            $this->postJson(route('api.docente.comint.leer', $c->id))->assertNotFound();
        }
        $this->assertSame(0, \App\Models\ComunicadoLectura::count());
    }

    public function test_un_estudiante_no_accede_a_los_comunicados_internos_del_personal(): void
    {
        $c = $this->comunicado(['tipo_destinatarios' => 'todos']);
        $est = Estudiante::factory()->create();
        $est->user->assignRole('Estudiante');
        $est->user->update(['activo' => true]);
        Sanctum::actingAs($est->user);

        $this->getJson(route('api.docente.comint'))->assertForbidden();
        $this->postJson(route('api.docente.comint.leer', $c->id))->assertForbidden();
    }
}
