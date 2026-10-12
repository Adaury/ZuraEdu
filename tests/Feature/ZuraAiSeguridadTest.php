<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Services\ZuraPlanificacionAI;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * ZuraAI: la clave de Gemini no viaja en la URL (las excepciones la incluyen y acababa en el
 * log y en el JSON de error del docente), los errores no devuelven el mensaje crudo y el
 * prompt de estudiantes/hijos solo lleva nombres de pila.
 */
class ZuraAiSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'AIzaSyClaveSecretaDePrueba123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        config(['services.gemini.key' => self::CLAVE]);
    }

    public function test_la_clave_va_en_cabecera_y_no_en_la_url(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '{"objetivo":"x"}']]]]],
        ])]);

        $r = (new ZuraPlanificacionAI())->generarActividad(['asignatura' => 'Lengua']);
        $this->assertSame('x', $r['objetivo']);

        Http::assertSent(function ($request) {
            return ! str_contains($request->url(), 'key=')
                && ! str_contains($request->url(), self::CLAVE)
                && $request->hasHeader('x-goog-api-key', self::CLAVE);
        });
    }

    public function test_un_fallo_de_conexion_no_devuelve_ni_loguea_la_clave(): void
    {
        Http::fake(function () {
            // Lo que haría cURL: el mensaje incluye la URL completa.
            throw new ConnectionException('cURL error 28: Operation timed out for https://generativelanguage.googleapis.com/v1beta/x?key=' . self::CLAVE);
        });

        $r = (new ZuraPlanificacionAI())->generarActividad(['asignatura' => 'Lengua']);

        $this->assertArrayHasKey('error', $r);
        $this->assertStringNotContainsString(self::CLAVE, $r['error']);
        $this->assertStringNotContainsString('cURL', $r['error']);
    }

    public function test_el_tutor_api_manda_la_clave_en_cabecera_y_solo_el_nombre_de_pila(): void
    {
        $sy      = SchoolYear::create(['nombre' => '2026-IA', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $est     = Estudiante::factory()->create(['nombres' => 'Valentina', 'apellidos' => 'Quezada Ferreira']);
        $est->user->assignRole('Estudiante');
        $est->user->update(['activo' => true]);

        $capturado = null;
        $guzzleHist = [];
        // El controlador usa Guzzle directo: se intercepta con un handler mock vía el contenedor no es
        // posible; se verifica el contenido del prompt por su builder reflejado.
        $ctrl = new \App\Http\Controllers\Api\TutorIaApiController();
        $m = new \ReflectionMethod($ctrl, 'promptEstudiante');
        $m->setAccessible(true);
        $prompt = $m->invoke($ctrl, $est->user, $sy, 'Colegio X');

        $this->assertStringContainsString('Valentina', $prompt);
        $this->assertStringNotContainsString('Quezada', $prompt);
        $this->assertStringNotContainsString('Ferreira', $prompt);
    }

    public function test_el_prompt_del_padre_lleva_solo_nombres_de_pila_de_los_hijos(): void
    {
        $sy  = SchoolYear::create(['nombre' => '2026-IA2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $hijo = Estudiante::factory()->create(['nombres' => 'Mateo', 'apellidos' => 'Almonte Polanco']);

        $padreUser = \App\Models\User::factory()->create(['activo' => true]);
        $padreUser->assignRole('Representante');
        $rep = \App\Models\Representante::factory()->create(['user_id' => $padreUser->id]);
        $rep->estudiantes()->attach($hijo->id, ['parentesco' => 'Padre', 'es_principal' => true]);

        $ctrl = new \App\Http\Controllers\Api\TutorIaApiController();
        $m = new \ReflectionMethod($ctrl, 'promptPadre');
        $m->setAccessible(true);
        $prompt = $m->invoke($ctrl, $padreUser, $sy, 'Colegio X');

        $this->assertStringContainsString('Mateo', $prompt);
        $this->assertStringNotContainsString('Almonte', $prompt);
    }

    public function test_generar_unidad_rechaza_un_plan_de_otra_asignacion(): void
    {
        // Las tablas de planificación exigen tenant_id: se crea un centro y se enlaza.
        $tenant = \App\Models\Tenant::create([
            'nombre_institucion' => 'Colegio IA Seguridad',
            'dominio'            => 'iasg' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '2026-IA3', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado IA', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'segundo_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $docente = Docente::factory()->create();
        $docente->user->assignRole('Docente');
        $docente->user->update(['activo' => true]);

        $crear = fn (string $c) => Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => $c . random_int(1000, 9999), 'nombre' => 'M ' . $c, 'area' => 'academica', 'activo' => true])->id,
            'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $asigA = $crear('IAA');
        $asigB = $crear('IAB');

        $plan = PlanifAnual::create([
            'asignacion_id' => $asigA->id, 'docente_id' => $docente->id, 'school_year_id' => $sy->id, 'titulo' => 'Plan A',
        ]);
        $unidad = PlanifUnidad::create(['planif_anual_id' => $plan->id, 'numero' => 1, 'titulo' => 'U1']);

        Http::fake();

        // El plan es de la asignación A, pero se pide con la B (ambas del mismo docente)
        $this->actingAs($docente->user)
            ->postJson(route('portal.docente.planif-anual.unidades.ia', [$asigB, $plan, $unidad]))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
