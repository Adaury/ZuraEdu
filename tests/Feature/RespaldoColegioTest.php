<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Estudiante;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RespaldoColegioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * Copia de los datos del propio colegio (ZIP de CSV): aislada por tenant, sin secretos y con los datos sensibles solo para la Dirección.
 */
class RespaldoColegioTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $a;
    private Tenant $b;
    private User $adminA;
    private User $directorA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->a = $this->tenant('Colegio Alfa');
        $this->b = $this->tenant('Colegio Beta');

        app()->instance('tenant', $this->a);
        Estudiante::factory()->create(['apellidos' => 'Alfaestudiante', 'nombres' => 'Uno']);
        $this->adminA = $this->usuario($this->a, 'Administrador');
        $this->directorA = $this->usuario($this->a, 'Director');

        app()->instance('tenant', $this->b);
        Estudiante::factory()->create(['apellidos' => 'Betaestudiante', 'nombres' => 'Dos']);
        app()->forgetInstance('tenant');
    }

    private function tenant(string $nombre): Tenant
    {
        return Tenant::create(['nombre_institucion' => $nombre, 'dominio' => 'rc' . random_int(100000, 999999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
    }

    private function usuario(Tenant $t, string $rol): User
    {
        return User::factory()->create(['activo' => true, 'tenant_id' => $t->id])->assignRole($rol);
    }

    /** @return array{zip: ZipArchive, entradas: string[]} */
    private function abrir($respuesta): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($respuesta->baseResponse->getFile()->getPathname()) === true, 'el archivo es un ZIP válido');
        $entradas = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entradas[] = $zip->getNameIndex($i);
        }

        return ['zip' => $zip, 'entradas' => $entradas];
    }

    public function test_el_zip_trae_solo_los_datos_del_colegio_del_usuario(): void
    {
        $r = $this->actingAs($this->adminA)->post(route('admin.respaldo-colegio.descargar'))->assertOk();
        ['zip' => $zip, 'entradas' => $e] = $this->abrir($r);

        $this->assertContains('datos/estudiantes.csv', $e);
        $csv = $zip->getFromName('datos/estudiantes.csv');
        $this->assertStringContainsString('Alfaestudiante', $csv);
        $this->assertStringNotContainsString('Betaestudiante', $csv, 'jamás filas de otro colegio');
        $this->assertContains('LEEME.txt', $e);
        $this->assertContains('manifest.json', $e);
    }

    public function test_nunca_salen_contrasenas_tokens_ni_tablas_de_secretos(): void
    {
        $r = $this->actingAs($this->adminA)->post(route('admin.respaldo-colegio.descargar'))->assertOk();
        ['zip' => $zip, 'entradas' => $e] = $this->abrir($r);

        $this->assertContains('datos/users.csv', $e);
        $cabecera = strtok($zip->getFromName('datos/users.csv'), "\n");
        $this->assertStringContainsString('email', $cabecera);
        $this->assertDoesNotMatchRegularExpression(RespaldoColegioService::COLUMNAS_SECRETAS, $cabecera, 'sin password / remember_token');

        foreach (RespaldoColegioService::TABLAS_EXCLUIDAS as $tabla) {
            $this->assertNotContains("datos/{$tabla}.csv", $e, $tabla);
        }
    }

    public function test_el_administrador_no_puede_exportar_datos_sensibles_aunque_lo_pida(): void
    {
        $r = $this->actingAs($this->adminA)->post(route('admin.respaldo-colegio.descargar'), ['incluir_sensibles' => 1])->assertOk();
        ['zip' => $zip, 'entradas' => $e] = $this->abrir($r);

        foreach (RespaldoColegioService::TABLAS_SENSIBLES as $tabla) {
            $this->assertNotContains("datos/{$tabla}.csv", $e, $tabla);
        }
        $this->assertFalse(json_decode($zip->getFromName('manifest.json'), true)['incluye_datos_sensibles']);
    }

    public function test_el_director_incluye_los_sensibles_solo_si_los_pide(): void
    {
        $sin = $this->actingAs($this->directorA)->post(route('admin.respaldo-colegio.descargar'))->assertOk();
        $this->assertNotContains('datos/fichas_salud.csv', $this->abrir($sin)['entradas']);

        $con = $this->actingAs($this->directorA)->post(route('admin.respaldo-colegio.descargar'), ['incluir_sensibles' => 1])->assertOk();
        ['zip' => $zip, 'entradas' => $e] = $this->abrir($con);
        $this->assertContains('datos/fichas_salud.csv', $e);
        $this->assertTrue(json_decode($zip->getFromName('manifest.json'), true)['incluye_datos_sensibles']);
    }

    public function test_quien_no_tiene_el_permiso_no_entra(): void
    {
        // Secretaría llega al panel admin pero no tiene el permiso → 403
        $secretaria = $this->usuario($this->a, 'Secretaría');
        $this->actingAs($secretaria)->get(route('admin.respaldo-colegio.index'))->assertForbidden();
        $this->actingAs($secretaria)->post(route('admin.respaldo-colegio.descargar'))->assertForbidden();

        // El docente ni siquiera entra al panel admin (lo redirige su middleware)
        $docente = $this->usuario($this->a, 'Docente');
        $this->assertNotSame(200, $this->actingAs($docente)->post(route('admin.respaldo-colegio.descargar'))->getStatusCode());
    }

    public function test_la_descarga_queda_en_el_log_de_actividad(): void
    {
        $this->actingAs($this->directorA)->post(route('admin.respaldo-colegio.descargar'), ['incluir_sensibles' => 1])->assertOk();

        $log = ActivityLog::withoutGlobalScopes()->where('accion', 'respaldo_colegio')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->directorA->id, (int) $log->user_id);
        $this->assertStringContainsString('incluye datos sensibles', $log->descripcion);
    }

    public function test_las_celdas_que_parecen_formulas_se_neutralizan(): void
    {
        app()->instance('tenant', $this->a);
        Estudiante::factory()->create(['apellidos' => '=HYPERLINK("http://x")', 'nombres' => 'Formula']);
        app()->forgetInstance('tenant');

        $r = $this->actingAs($this->adminA)->post(route('admin.respaldo-colegio.descargar'))->assertOk();
        $csv = $this->abrir($r)['zip']->getFromName('datos/estudiantes.csv');

        $this->assertStringContainsString("\"'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
    }

    public function test_la_pantalla_explica_que_incluye_y_el_boton_de_sensibles_es_solo_de_la_direccion(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.respaldo-colegio.index'))
            ->assertOk()->assertSee('Copia de mis datos')->assertDontSee('name="incluir_sensibles"', false);

        $this->actingAs($this->directorA)->get(route('admin.respaldo-colegio.index'))
            ->assertOk()->assertSee('name="incluir_sensibles"', false);
    }
}
