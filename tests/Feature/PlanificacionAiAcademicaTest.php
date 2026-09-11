<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Roadmap de producto (docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md, punto 10):
 * ZuraPlanificacionAI solo servía a la línea técnica (RA/MF/UC). Se agregó
 * ZuraPlanificacionAI::generarUnidad() + PlanificacionAIController::generarUnidad()
 * para la línea académica (PlanifAnual/PlanifUnidad).
 *
 * Al escribir este endpoint se encontró que los 3 métodos existentes
 * (generarRA/generarActividad/mejorarTexto) no verificaban que la Asignacion
 * recibida por parámetro de ruta perteneciera al docente autenticado -- solo
 * quedaban aislados por tenant (BelongsToTenant). Se corrigió en los 4
 * métodos del controlador (mismo patrón ya usado en PlanifAnualController).
 */
class PlanificacionAiAcademicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearTenant(string $nombre): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    /** Tenant + docente + asignación + PlanifAnual + PlanifUnidad, dentro de ese tenant. */
    private function crearEscenario(string $codigo): array
    {
        $tenant = $this->crearTenant('Colegio IA Academica ' . $codigo);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-IA', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado ' . $codigo, 'nivel' => 1, 'orden' => 1, 'ciclo' => 'segundo_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $docenteUser = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $docenteUser->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $docenteUser->id]);

        $asignatura = Asignatura::create(['codigo' => $codigo, 'nombre' => 'Materia ' . $codigo, 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica', 'docente_id' => $docente->id,
        ]);

        $plan = PlanifAnual::create([
            'asignacion_id' => $asignacion->id, 'school_year_id' => $sy->id,
            'docente_id' => $docente->id, 'titulo' => 'Plan Anual ' . $codigo,
        ]);
        $unidad = PlanifUnidad::create([
            'planif_anual_id' => $plan->id, 'numero' => 1, 'titulo' => 'Fracciones', 'periodo' => 'P1',
        ]);

        return compact('tenant', 'sy', 'docenteUser', 'docente', 'asignacion', 'plan', 'unidad');
    }

    private function fakeGeminiUnidad(array $overrides = []): void
    {
        $data = array_merge([
            'objetivos'    => 'Resolver operaciones con fracciones en contextos cotidianos.',
            'competencias' => ['Pensamiento Lógico y Resolución de Problemas', 'Comunicativa', 'Competencia Inventada'],
            'indicadores'  => "- Suma fracciones homogéneas\n- Resuelve problemas contextualizados",
            'contenidos'   => "- Fracciones homogéneas\n- Fracciones heterogéneas",
            'estrategias'  => "- Trabajo en parejas\n- Material manipulativo",
            'recursos'     => "- Fichas de fracciones\n- Pizarra",
            'evaluacion'   => "- Prueba escrita\n- Lista de cotejo",
        ], $overrides);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($data)]]]],
                ],
            ], 200),
        ]);
    }

    public function test_el_docente_dueno_genera_contenido_de_la_unidad(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $ctx = $this->crearEscenario('IA1');
        $this->fakeGeminiUnidad();
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->postJson(
            route('portal.docente.planif-anual.unidades.ia', [$ctx['asignacion'], $ctx['plan'], $ctx['unidad']]),
            ['contexto' => 'Cuarto grado, primer período']
        );

        $response->assertOk();
        $response->assertJsonPath('objetivos', 'Resolver operaciones con fracciones en contextos cotidianos.');
        $response->assertJsonCount(2, 'competencias'); // se filtra "Competencia Inventada"
        $response->assertJsonFragment(['competencias' => ['Pensamiento Lógico y Resolución de Problemas', 'Comunicativa']]);
    }

    public function test_un_docente_que_no_es_dueno_de_la_asignacion_no_puede_generar(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $ctx = $this->crearEscenario('IA2');
        $this->fakeGeminiUnidad();

        $otroUser = User::factory()->create(['activo' => true, 'tenant_id' => $ctx['tenant']->id]);
        $otroUser->assignRole('Docente');
        Docente::factory()->create(['user_id' => $otroUser->id]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($otroUser)->postJson(
            route('portal.docente.planif-anual.unidades.ia', [$ctx['asignacion'], $ctx['plan'], $ctx['unidad']])
        );

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_un_docente_de_otro_tenant_no_puede_acceder(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $ctxA = $this->crearEscenario('IA3A');
        $ctxB = $this->crearEscenario('IA3B');
        $this->fakeGeminiUnidad();

        $response = $this->actingAs($ctxB['docenteUser'])->postJson(
            route('portal.docente.planif-anual.unidades.ia', [$ctxA['asignacion'], $ctxA['plan'], $ctxA['unidad']])
        );

        $response->assertNotFound();
    }

    public function test_sin_clave_de_gemini_devuelve_error_controlado(): void
    {
        config(['services.gemini.key' => null]);
        $ctx = $this->crearEscenario('IA4');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->postJson(
            route('portal.docente.planif-anual.unidades.ia', [$ctx['asignacion'], $ctx['plan'], $ctx['unidad']])
        );

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    public function test_el_prompt_enviado_a_gemini_incluye_el_contexto_academico_no_tecnico(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $ctx = $this->crearEscenario('IA5');
        $this->fakeGeminiUnidad();
        app()->forgetInstance('tenant');

        $this->actingAs($ctx['docenteUser'])->postJson(
            route('portal.docente.planif-anual.unidades.ia', [$ctx['asignacion'], $ctx['plan'], $ctx['unidad']]),
            ['titulo_hint' => 'Fracciones equivalentes']
        );

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'] ?? '';
            return str_contains($prompt, 'NO técnico-profesional')
                && str_contains($prompt, 'Fracciones equivalentes')
                && str_contains($prompt, 'Pensamiento Lógico y Resolución de Problemas');
        });
    }
}
