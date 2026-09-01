<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BoletinController;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use App\Policies\BoletinPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Recomendación 12 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: cobertura de
 * boletines y RBAC. Cubre en particular dos regresiones que hasta ahora solo
 * se habían verificado manualmente (Playwright), sin test automatizado:
 *   1. BoletinPolicy::ver() bloqueaba con 403 a roles no-docente distintos de
 *      Administrador/Director aunque tuvieran el permiso ver-boletines.
 *   2. BoletinController::grupo()/zipGrupo() no verificaban que un docente
 *      tuviera asignación real en el grupo solicitado por query string.
 * Y el split de permisos ver-boletines / imprimir-boletines (recomendación #3).
 *
 * Nota: los escenarios de docente (Docente/Docente Académico/Técnico/Guía) se
 * prueban invocando la Policy y el helper privado puedeVerGrupo() directamente
 * (vía Reflection, mismo patrón que HorarioIntegrityTest), no vía HTTP —
 * EnsureAdminAccess redirige siempre cualquier rol docente fuera de
 * /admin/boletines/* hacia su portal antes de llegar al controlador, así que
 * esa ruta no es alcanzable por un docente real; la lógica de scoping en sí
 * sigue siendo código real que vale la pena cubrir directamente.
 */
class BoletinAccessRegressionTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Cache::flush();
    }

    /** Año escolar + grupo + estudiante matriculado + una asignación académica activa, sin docente asignado aún. */
    private function crearEscenario(): array
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false,
        ]);

        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado B' . self::$nivel, 'nivel' => self::$nivel, 'orden' => self::$nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'BL' . $grupo->id, 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        $periodo = Periodo::create([
            'school_year_id' => $schoolYear->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);

        return compact('schoolYear', 'grado', 'seccion', 'grupo', 'estudiante', 'matricula', 'asignatura', 'asignacion', 'periodo');
    }

    private function docente(): array
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $user->id]);
        return [$user, $docente];
    }

    private function puedeVerGrupo(Grupo $grupo): bool
    {
        $ref = new \ReflectionMethod(BoletinController::class, 'puedeVerGrupo');
        $ref->setAccessible(true);
        return $ref->invoke(new BoletinController(), $grupo);
    }

    // =========================================================================
    //  BoletinPolicy — boletín individual por estudiante
    // =========================================================================

    public function test_policy_permite_a_docente_con_asignacion_activa_en_el_grupo(): void
    {
        $e = $this->crearEscenario();
        [$user, $docente] = $this->docente();
        $e['asignacion']->update(['docente_id' => $docente->id]);

        $this->assertTrue((new BoletinPolicy())->ver($user, $e['matricula']));
    }

    /** Regresión: docente sin ninguna asignación en el grupo no debe ver el boletín (antes esto solo verificaba el rol hardcodeado). */
    public function test_policy_bloquea_a_docente_sin_asignacion_en_el_grupo(): void
    {
        $e = $this->crearEscenario();
        [$user] = $this->docente(); // sin asignación en absoluto

        $this->assertFalse((new BoletinPolicy())->ver($user, $e['matricula']));
    }

    public function test_policy_permite_al_tutor_del_grupo_aunque_no_tenga_asignacion(): void
    {
        $e = $this->crearEscenario();
        [$user] = $this->docente();
        $e['grupo']->update(['tutor_id' => $user->id]);

        $this->assertTrue((new BoletinPolicy())->ver($user, $e['matricula']));
    }

    /** La regresión original: esto solo dejaba pasar a Administrador/Director hardcodeados, bloqueando al resto de roles con ver-boletines. */
    public function test_policy_permite_a_rol_no_docente_con_ver_boletines_ver_cualquier_boletin(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Encargado de Área');

        $this->assertTrue((new BoletinPolicy())->ver($user, $e['matricula']));
    }

    public function test_policy_bloquea_a_cualquier_rol_sin_ver_boletines(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Caja / Finanzas'); // no tiene ver-boletines

        $this->assertFalse((new BoletinPolicy())->ver($user, $e['matricula']));
    }

    /** Ruta HTTP real: un rol no-docente con ver-boletines sí llega y ve cualquier grupo (no bloqueado por EnsureAdminAccess). */
    public function test_rol_no_docente_con_ver_boletines_puede_ver_boletin_via_http(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Encargado de Área');

        $this->actingAs($user)
            ->get(route('admin.boletines.ver', [$e['matricula'], $e['periodo']]))
            ->assertOk();
    }

    /**
     * Regresión (auditoría 2026-09-01): admin/boletines/grupo.blade.php:397
     * llamaba $matricula->estudiante->representantes()->first() (método, no
     * propiedad cargada) dentro de un @foreach — una query nueva por cada
     * estudiante del grupo. El fix eager-carga estudiante.representantes en
     * BoletinController::grupo() y la vista usa ahora ->representantes->first()
     * (propiedad). Con Model::preventLazyLoading() activo fuera de producción,
     * si el eager-load faltara esto lanzaría LazyLoadingViolationException
     * (500), exactamente el patrón de los bugs de horarios de esta sesión.
     */
    public function test_vista_de_grupo_no_lanza_lazy_loading_violation_con_representante(): void
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);

        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado LL' . self::$nivel, 'nivel' => self::$nivel, 'orden' => self::$nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante   = Estudiante::factory()->create();
        $representante = Representante::factory()->create(['telefono' => '8091234567']);
        $estudiante->representantes()->attach($representante->id, ['es_principal' => true]);

        Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $periodo = Periodo::create([
            'school_year_id' => $schoolYear->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');

        $this->actingAs($user)
            ->get(route('admin.boletines.grupo', ['grupo_id' => $grupo->id, 'periodo_id' => $periodo->id]))
            ->assertOk();
    }

    // =========================================================================
    //  BoletinController::puedeVerGrupo() — scoping por asignación real, no solo por rol
    // =========================================================================

    public function test_puede_ver_grupo_bloquea_docente_sin_asignacion_en_ese_grupo(): void
    {
        $e = $this->crearEscenario();
        [$user] = $this->docente(); // sin asignación en este grupo
        $this->actingAs($user);

        $this->assertFalse($this->puedeVerGrupo($e['grupo']));
    }

    public function test_puede_ver_grupo_permite_docente_con_asignacion_activa(): void
    {
        $e = $this->crearEscenario();
        [$user, $docente] = $this->docente();
        $e['asignacion']->update(['docente_id' => $docente->id]);
        $this->actingAs($user);

        $this->assertTrue($this->puedeVerGrupo($e['grupo']));
    }

    public function test_puede_ver_grupo_permite_a_cualquier_rol_no_docente_con_ver_boletines(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Secretaría');
        $this->actingAs($user);

        $this->assertTrue($this->puedeVerGrupo($e['grupo']));
    }

    // =========================================================================
    //  Split ver-boletines / imprimir-boletines (recomendación #3)
    // =========================================================================

    /** Encargado de Área tiene ver-boletines pero no imprimir-boletines. */
    public function test_rol_con_solo_ver_boletines_recibe_403_al_intentar_imprimir(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Encargado de Área');

        $this->actingAs($user)
            ->get(route('admin.boletines.ver', [$e['matricula'], $e['periodo']]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.boletines.zip', ['grupo_id' => $e['grupo']->id, 'periodo_id' => $e['periodo']->id]))
            ->assertForbidden();
    }

    /** Secretaría tiene ambos permisos y sí puede imprimir. */
    public function test_rol_con_ambos_permisos_puede_descargar_el_pdf(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Secretaría');

        $this->actingAs($user)
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
