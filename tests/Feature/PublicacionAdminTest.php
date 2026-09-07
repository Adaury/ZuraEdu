<?php

namespace Tests\Feature;

use App\Models\Publicacion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Evolución del portal público: "Noticias y Publicaciones" — modelo nuevo,
 * deliberadamente separado de Comunicado (que es interno/autenticado).
 * CRUD en /admin/publicaciones, permiso gestionar-configuracion (mismo que
 * Branding).
 */
class PublicacionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Storage::fake('public');
    }

    private function crearAdmin(string $nombreTenant): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombreTenant,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreTenant)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_crear_una_publicacion(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Crear');

        $response = $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo'      => 'noticia',
            'titulo'    => 'Feria de Ciencias 2026',
            'contenido' => 'Contenido de la noticia.',
            'fecha'     => now()->toDateString(),
            'estado'    => 'publicado',
            'visible'   => '1',
            'imagen_destacada' => UploadedFile::fake()->image('noticia.jpg'),
        ]);

        $response->assertRedirect(route('admin.publicaciones.index'));
        $publicacion = Publicacion::first();
        $this->assertNotNull($publicacion);
        $this->assertSame('Feria de Ciencias 2026', $publicacion->titulo);
        $this->assertSame('publicado', $publicacion->estado);
        $this->assertTrue($publicacion->visible);
        $this->assertNotEmpty($publicacion->imagen_destacada);
        $this->assertSame($user->id, $publicacion->creado_por);
    }

    public function test_imagen_alineacion_por_defecto_es_centro(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Alineacion Default');

        $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'noticia', 'titulo' => 'X', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
        ]);

        $this->assertSame('centro', Publicacion::first()->imagen_alineacion);
    }

    public function test_imagen_alineacion_izquierda_o_derecha_se_guarda(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Alineacion Izq');

        $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'noticia', 'titulo' => 'X', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
            'imagen_alineacion' => 'izquierda',
        ]);

        $this->assertSame('izquierda', Publicacion::first()->imagen_alineacion);
    }

    public function test_imagen_alineacion_invalida_es_rechazada(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Alineacion Invalida');

        $response = $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'noticia', 'titulo' => 'X', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
            'imagen_alineacion' => 'arriba',
        ]);

        $response->assertSessionHasErrors('imagen_alineacion');
        $this->assertSame(0, Publicacion::withoutTenant()->count());
    }

    public function test_el_resumen_recorta_el_contenido_sin_html(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Resumen');

        $largo = str_repeat('Palabra ', 60);
        $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'noticia', 'titulo' => 'X',
            'contenido' => "<p><b>{$largo}</b></p>",
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
        ]);

        $resumen = Publicacion::first()->resumen;
        $this->assertLessThanOrEqual(Publicacion::RESUMEN_LARGO + 3, strlen($resumen)); // +3 por el "..." de Str::limit
        $this->assertStringNotContainsString('<', $resumen);
    }

    public function test_desmarcar_visible_al_guardar_lo_deja_oculto(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Oculto');

        // Crear visible.
        $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'aviso', 'titulo' => 'Aviso', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado', 'visible' => '1',
        ]);
        $publicacion = Publicacion::first();

        // Editar sin marcar "visible" (checkbox desmarcado no se envía).
        $this->actingAs($user)->put(route('admin.publicaciones.update', $publicacion), [
            'tipo' => 'aviso', 'titulo' => 'Aviso', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
        ]);

        $this->assertFalse($publicacion->fresh()->visible);
    }

    public function test_un_rol_sin_gestionar_configuracion_no_puede_crear_publicaciones(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Pub Sin Permiso',
            'dominio'            => 'colegiopubsinpermiso' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Coordinador Académico');

        $response = $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'noticia', 'titulo' => 'X', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado',
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Publicacion::withoutTenant()->count());
    }

    public function test_eliminar_publicacion_borra_tambien_la_imagen(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Pub Eliminar');

        $this->actingAs($user)->post(route('admin.publicaciones.store'), [
            'tipo' => 'logro', 'titulo' => 'Logro', 'contenido' => 'x',
            'fecha' => now()->toDateString(), 'estado' => 'publicado', 'visible' => '1',
            'imagen_destacada' => UploadedFile::fake()->image('logro.png'),
        ]);
        $publicacion = Publicacion::first();
        $ruta = $publicacion->imagen_destacada;
        Storage::disk('public')->assertExists($ruta);

        $this->actingAs($user)->delete(route('admin.publicaciones.destroy', $publicacion));

        Storage::disk('public')->assertMissing($ruta);
        $this->assertNull(Publicacion::find($publicacion->id));
    }
}
