<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Helpers\Setting;
use App\Services\AcademicAlertService;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CarnetRiskScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Carnet+ — vigencia y baja del estudiante se validan al escanear, la matrícula
 * del carnet se renueva cada año, la hora de entrada es configurable, la lista
 * "hoy" del docente (API) funciona y el riesgo cuenta días, no escaneos.
 */
class CarnetVigenciaYMatriculaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private SchoolYear $sy;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Carnet Vigencia',
            'dominio'            => 'cv' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $this->tenant);

        $this->sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-V', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado    = Grado::create(['nombre' => 'Grado CV' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion  = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $this->grupo = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
    }

    private function usuario(string $rol): User
    {
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id]);
        $u->assignRole($rol);

        return $u;
    }

    /** @return array{0: CarnetIdentidad, 1: Matricula} */
    private function estudianteConCarnet(array $carnet = [], array $matricula = []): array
    {
        $user = $this->usuario('Estudiante');
        $est  = Estudiante::factory()->create(['user_id' => $user->id]);
        $mat  = Matricula::create(array_merge([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $this->grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => random_int(1, 9999), 'estado' => 'activa',
        ], $matricula));

        $c = CarnetIdentidad::create(array_merge([
            'tipo' => 'estudiante', 'user_id' => $user->id, 'matricula_id' => $mat->id,
            'numero_carnet' => 'ZR-T-' . random_int(10000, 99999),
            'qr_token' => 'qr_' . bin2hex(random_bytes(8)),
            'estado' => 'activo', 'vigencia_hasta' => now()->addYear(),
        ], $carnet));

        return [$c, $mat];
    }

    private function escanear(CarnetIdentidad $c)
    {
        return $this->actingAs($this->usuario('Recepción'))
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $c->qr_token, 'tipo_evento' => 'entrada']);
    }

    public function test_carnet_con_vigencia_vencida_no_abre(): void
    {
        [$c] = $this->estudianteConCarnet(['vigencia_hasta' => now()->subDay()]);

        $this->escanear($c)->assertNotFound();
        $this->assertSame(0, CarnetAcceso::count());
    }

    public function test_carnet_vigente_si_abre(): void
    {
        [$c] = $this->estudianteConCarnet();

        $this->escanear($c)->assertOk()->assertJson(['success' => true]);
    }

    public function test_estudiante_retirado_o_transferido_no_entra_con_su_qr(): void
    {
        foreach (['retirada', 'transferida'] as $estado) {
            [$c] = $this->estudianteConCarnet([], ['estado' => $estado]);

            $this->escanear($c)->assertNotFound();
        }
        $this->assertSame(0, CarnetAcceso::count());
    }

    public function test_hora_de_entrada_configurable_define_la_tardanza(): void
    {
        Setting::set('carnet_hora_entrada', '14:00');

        $this->assertSame('presente', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 13:59:00')));
        $this->assertSame('tardanza', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 14:01:00')));
    }

    public function test_hora_de_entrada_por_defecto_es_0730_y_un_valor_invalido_la_respeta(): void
    {
        $this->assertSame('presente', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 07:29:00')));
        $this->assertSame('tardanza', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 07:31:00')));

        Setting::set('carnet_hora_entrada', 'no-es-una-hora');
        $this->assertSame('tardanza', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 07:31:00')));
    }

    public function test_generar_masivo_renueva_matricula_y_vigencia_y_crea_carnets_de_docente(): void
    {
        // Carnet de un año anterior: apunta a una matrícula vieja y ya venció.
        $user = $this->usuario('Estudiante');
        $est  = Estudiante::factory()->create(['user_id' => $user->id]);
        $syViejo = SchoolYear::create(['nombre' => '2019-2020', 'fecha_inicio' => '2019-08-01', 'fecha_fin' => '2020-06-30', 'activo' => false]);
        $matVieja = Matricula::create([
            'school_year_id' => $syViejo->id, 'estudiante_id' => $est->id, 'grupo_id' => $this->grupo->id,
            'fecha_matricula' => '2019-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $matNueva = Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $this->grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 2, 'estado' => 'activa',
        ]);
        $carnet = CarnetIdentidad::create([
            'tipo' => 'estudiante', 'user_id' => $user->id, 'matricula_id' => $matVieja->id,
            'numero_carnet' => 'ZR-OLD-1', 'qr_token' => 'qr_old_' . bin2hex(random_bytes(6)),
            'estado' => 'activo', 'vigencia_hasta' => now()->subMonth(),
        ]);

        $userDoc = $this->usuario('Docente');
        Docente::factory()->create(['user_id' => $userDoc->id, 'estado' => 'activo']);

        $this->actingAs($this->usuario('Administrador'))
            ->post(route('admin.carnet.generar-masivo'))
            ->assertRedirect();

        $carnet->refresh();
        $this->assertSame($matNueva->id, $carnet->matricula_id);
        $this->assertTrue($carnet->vigencia_hasta->gte(today()));
        $this->escanear($carnet)->assertOk();

        $this->assertDatabaseHas('carnet_identidades', ['user_id' => $userDoc->id, 'tipo' => 'docente']);
    }

    public function test_api_docente_carnet_hoy_responde_con_sus_grupos(): void
    {
        $userDoc = $this->usuario('Docente');
        $docente = Docente::factory()->create(['user_id' => $userDoc->id, 'estado' => 'activo']);
        $asig    = Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $this->grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => 'CV' . random_int(10000, 99999), 'nombre' => 'Materia CV', 'activo' => true])->id,
            'docente_id' => $docente->id, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);

        [$c] = $this->estudianteConCarnet();
        CarnetAcceso::create(['carnet_identidad_id' => $c->id, 'tipo_evento' => 'entrada', 'estado' => 'presente']);

        Sanctum::actingAs($userDoc);
        $this->getJson('/api/v1/docente/carnet')
            ->assertOk()
            ->assertJson(['total' => 1, 'entradas' => 1, 'presentes' => 1, 'ausentes' => 0]);
    }

    public function test_umbrales_guardados_en_la_pantalla_los_usan_los_servicios_y_la_hora_del_carnet(): void
    {
        // Sin nada guardado en la pantalla: valores por defecto.
        $this->assertEquals(60.0, AcademicAlertService::umbralNota());
        $this->assertEquals(75.0, AcademicAlertService::umbralAsistencia());

        $this->actingAs($this->usuario('Administrador'))
            ->post(route('admin.sistema.email-notif.update'), [
                'alerta_nota_minima'       => 70,
                'alerta_asistencia_minima' => 85,
                'carnet_hora_entrada'      => '08:15',
            ])
            ->assertRedirect();

        Setting::flush();
        $this->assertEquals(70.0, AcademicAlertService::umbralNota());
        $this->assertEquals(85.0, AcademicAlertService::umbralAsistencia());
        $this->assertSame('presente', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 08:14:00')));
        $this->assertSame('tardanza', CarnetAcceso::estadoParaEntrada(Carbon::parse('2026-03-10 08:16:00')));
    }

    public function test_hora_de_entrada_invalida_en_la_pantalla_se_rechaza(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->from(route('admin.sistema.email-notif'))
            ->post(route('admin.sistema.email-notif.update'), ['carnet_hora_entrada' => '25:99'])
            ->assertSessionHasErrors('carnet_hora_entrada');
    }

    public function test_listado_muestra_carnets_de_docentes_con_el_filtro_tipo(): void
    {
        [$estudiante] = $this->estudianteConCarnet();
        $userDoc = $this->usuario('Docente');
        $docente = CarnetIdentidad::create([
            'tipo' => 'docente', 'user_id' => $userDoc->id, 'matricula_id' => null,
            'numero_carnet' => 'ZR-D-' . random_int(10000, 99999), 'qr_token' => 'qr_d_' . bin2hex(random_bytes(6)),
            'estado' => 'activo',
        ]);
        $admin = $this->usuario('Administrador');

        $porDefecto = $this->actingAs($admin)->get(route('admin.carnet.index'));
        $this->assertSame([$estudiante->id], $porDefecto->viewData('carnets')->pluck('id')->all());

        $docentes = $this->actingAs($admin)->get(route('admin.carnet.index', ['tipo' => 'docente']));
        $this->assertSame([$docente->id], $docentes->viewData('carnets')->pluck('id')->all());
    }

    public function test_riesgo_cuenta_dias_y_no_escaneos(): void
    {
        [$c] = $this->estudianteConCarnet();
        // Dos entradas el mismo día (fuera de la ventana de dedupe) = 1 presencia.
        CarnetAcceso::create(['carnet_identidad_id' => $c->id, 'tipo_evento' => 'entrada', 'estado' => 'presente']);
        CarnetAcceso::create(['carnet_identidad_id' => $c->id, 'tipo_evento' => 'entrada', 'estado' => 'presente']);

        $r = CarnetRiskScoreService::calcular($c->fresh());

        $this->assertSame(1, $r['presencias']);
    }
}
