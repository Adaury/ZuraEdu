<?php

namespace Tests\Feature;

use App\Jobs\ImportarCalificacionesJob;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\ImportacionCalificacion;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Recomendación 12 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: cobertura de
 * calificaciones y RBAC. Cubre en particular la carga masiva en cola
 * (recomendación #6) — que nunca tuvo un test automatizado, solo
 * verificación manual — incluyendo la regresión de numero_matricula/cedula
 * (columnas de Estudiante, no de Matricula) que se corrigió en esa misma
 * recomendación.
 */
class CalificacionRegressionTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        // El driver de caché de test (array) vive todo el proceso de la suite;
        // sin esto, SchoolYear::actual()/getPeriodos() de un test anterior
        // pueden filtrarse a este test vía la misma clave de caché.
        Cache::flush();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
    }

    /** Crea un año escolar activo, un grupo, una asignación académica y un estudiante matriculado. */
    private function crearEscenario(bool $schoolYearActivo = true): array
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => $schoolYearActivo,
        ]);

        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado ' . self::$nivel, 'nivel' => self::$nivel, 'orden' => self::$nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create(['numero_matricula' => '2025-' . str_pad((string) self::$nivel, 5, '0', STR_PAD_LEFT)]);
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'CR' . $grupo->id, 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        return compact('schoolYear', 'grado', 'seccion', 'grupo', 'estudiante', 'matricula', 'asignatura', 'asignacion');
    }

    private function crearImportacion(Asignacion $asignacion, string $csvContenido, ?int $userId = null): ImportacionCalificacion
    {
        $path = Storage::disk('local')->putFileAs(
            'imports/calificaciones',
            UploadedFile::fake()->createWithContent('lote.csv', $csvContenido),
            'lote_test.csv'
        );

        return ImportacionCalificacion::create([
            // tenant_id explícito: ResolveTenant solo vincula el tenant en el
            // contenedor cuando corre una petición HTTP real. Si esta fila se
            // crea antes de la primera petición del test y luego se consulta
            // en una petición HTTP posterior dentro del mismo método (que sí
            // deja el tenant vinculado), el scope de BelongsToTenant la
            // filtraría por tenant_id=NULL y el route-model-binding daría 404.
            'tenant_id'        => 1,
            'user_id'          => $userId ?? User::factory()->create()->id,
            'asignacion_id'    => $asignacion->id,
            'archivo_original' => 'lote.csv',
            'archivo_path'     => $path,
            'estado'           => 'pendiente',
        ]);
    }

    // =========================================================================
    //  JOB DE IMPORTACIÓN EN COLA (recomendación #6)
    // =========================================================================

    /** Regresión clave: el emparejamiento por numero_matricula debe navegar la relación estudiante, no leerla de Matricula directamente. */
    public function test_import_resuelve_estudiante_por_numero_matricula_y_recalcula_promedios(): void
    {
        $e = $this->crearEscenario();
        $csv = "numero_matricula,cedula,nombres,apellidos,p1_comp1,p1_comp2,p1_comp3,p1_comp4\n"
             . $e['estudiante']->numero_matricula . ",,x,y,90,80,70,60\n";
        $importacion = $this->crearImportacion($e['asignacion'], $csv);

        (new ImportarCalificacionesJob($importacion->id))->handle();

        $importacion->refresh();
        $this->assertSame('completado', $importacion->estado);
        $this->assertSame(1, $importacion->importados);
        $this->assertSame(0, $importacion->omitidos);

        $cal = CalificacionAcademica::where('matricula_id', $e['matricula']->id)->first();
        $this->assertNotNull($cal);
        $this->assertEquals(90.0, $cal->comp1_p1);
        $this->assertNotNull($cal->prom_comp1, 'recalcularPromedios() debe haberse ejecutado tras la carga.');

        $this->assertFalse(Storage::disk('local')->exists($importacion->archivo_path), 'El archivo temporal debe borrarse al terminar.');
        $this->assertTrue(
            Notificacion::withoutGlobalScopes()->where('user_id', $importacion->user_id)->where('titulo', 'Importación de calificaciones')->exists(),
            'Debe notificar al usuario que subió el archivo.'
        );
    }

    public function test_import_resuelve_estudiante_por_cedula_cuando_no_hay_numero_matricula(): void
    {
        $e = $this->crearEscenario();
        $e['estudiante']->update(['cedula' => '001-1234567-8']);
        $csv = "numero_matricula,cedula,nombres,apellidos,p1_comp1,p1_comp2,p1_comp3,p1_comp4\n"
             . ",001-1234567-8,x,y,75,75,75,75\n";
        $importacion = $this->crearImportacion($e['asignacion'], $csv);

        (new ImportarCalificacionesJob($importacion->id))->handle();

        $this->assertSame(1, $importacion->fresh()->importados);
        $this->assertEquals(75.0, CalificacionAcademica::where('matricula_id', $e['matricula']->id)->first()->comp1_p1);
    }

    public function test_import_omite_fila_de_estudiante_no_encontrado(): void
    {
        $e = $this->crearEscenario();
        $csv = "numero_matricula,cedula,nombres,apellidos,p1_comp1,p1_comp2,p1_comp3,p1_comp4\n"
             . "9999999,,Nadie,Desconocido,80,80,80,80\n";
        $importacion = $this->crearImportacion($e['asignacion'], $csv);

        (new ImportarCalificacionesJob($importacion->id))->handle();

        $importacion->refresh();
        $this->assertSame(0, $importacion->importados);
        $this->assertSame(1, $importacion->omitidos);
        $this->assertNotEmpty($importacion->errores);
        $this->assertStringContainsString('estudiante no encontrado', $importacion->errores[0]);
    }

    public function test_import_omite_nota_fuera_de_rango(): void
    {
        $e = $this->crearEscenario();
        $csv = "numero_matricula,cedula,nombres,apellidos,p1_comp1,p1_comp2,p1_comp3,p1_comp4\n"
             . $e['estudiante']->numero_matricula . ",,x,y,150,,,\n";
        $importacion = $this->crearImportacion($e['asignacion'], $csv);

        (new ImportarCalificacionesJob($importacion->id))->handle();

        $importacion->refresh();
        $this->assertSame(0, $importacion->importados);
        $this->assertSame(1, $importacion->omitidos);
        $this->assertStringContainsString('fuera de rango', $importacion->errores[0]);
    }

    /** El área técnica usa nota_final por período (tabla Calificacion), no CalificacionAcademica. */
    public function test_import_area_tecnica_usa_nota_final_por_periodo(): void
    {
        $e = $this->crearEscenario();
        $e['asignacion']->update(['area' => 'tecnica']);
        $periodo = \App\Models\Periodo::create([
            'school_year_id' => $e['schoolYear']->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);
        $csv = "numero_matricula,cedula,periodo,nota_final\n"
             . $e['estudiante']->numero_matricula . ",,1,95\n";
        $importacion = $this->crearImportacion($e['asignacion'], $csv);

        (new ImportarCalificacionesJob($importacion->id))->handle();

        $importacion->refresh();
        $this->assertSame('completado', $importacion->estado);
        $this->assertSame(1, $importacion->importados);
        $cal = \App\Models\Calificacion::where('matricula_id', $e['matricula']->id)->where('periodo_id', $periodo->id)->first();
        $this->assertNotNull($cal);
        $this->assertEquals(95.0, $cal->nota_final);
    }

    public function test_import_store_despacha_el_job_y_redirige_a_pagina_de_estado(): void
    {
        Queue::fake();
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Coordinador Académico'); // importStore requiere ingresar-calificaciones, no ver-calificaciones

        $archivo = UploadedFile::fake()->createWithContent('notas.csv', "numero_matricula,cedula\n1,2\n");

        $response = $this->actingAs($user)->post(route('admin.calificaciones.importStore'), [
            'archivo'       => $archivo,
            'asignacion_id' => $e['asignacion']->id,
        ]);

        $importacion = ImportacionCalificacion::first();
        $this->assertNotNull($importacion, 'La importación debió crearse.');
        $response->assertRedirect(route('admin.calificaciones.import.estado', $importacion));
        Queue::assertPushed(ImportarCalificacionesJob::class, fn ($job) => $job->importacionId === $importacion->id);
    }

    /** El dueño y ciertos roles de coordinación pueden ver el estado; otro rol administrativo ajeno no. */
    public function test_import_estado_solo_accesible_por_el_dueno_o_coordinacion(): void
    {
        $e = $this->crearEscenario();
        $dueno = User::factory()->create(['activo' => true]);
        $dueno->assignRole('Secretaría');
        $importacion = $this->crearImportacion($e['asignacion'], "a,b\n1,2\n", $dueno->id);

        $this->actingAs($dueno)
            ->get(route('admin.calificaciones.import.estado', $importacion))
            ->assertOk();

        $otroUsuario = User::factory()->create(['activo' => true]);
        $otroUsuario->assignRole('Encargado de Área');
        $this->actingAs($otroUsuario)
            ->get(route('admin.calificaciones.import.estado', $importacion))
            ->assertForbidden();

        $coordinador = User::factory()->create(['activo' => true]);
        $coordinador->assignRole('Coordinador Académico');
        $this->actingAs($coordinador)
            ->get(route('admin.calificaciones.import.estado', $importacion))
            ->assertOk();
    }

    /** Regresión: el mismo bug de numero_matricula/cedula existía también al generar la plantilla de ejemplo. */
    public function test_plantilla_descargable_incluye_el_numero_matricula_real_del_estudiante(): void
    {
        $e = $this->crearEscenario();
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');

        $response = $this->actingAs($user)->get(route('admin.calificaciones.plantilla.descargar', [
            'asignacion_id' => $e['asignacion']->id,
        ]));

        $response->assertOk();
        $this->assertStringContainsString($e['estudiante']->numero_matricula, $response->getContent());
    }

    // =========================================================================
    //  RBAC — acceso al módulo de calificaciones
    //  (Nota: los roles Docente/Docente Académico/Técnico/Guía nunca llegan a
    //  /admin/calificaciones — EnsureAdminAccess los redirige siempre a su
    //  portal antes de esta ruta, así que la rama docente de index() no es
    //  alcanzable vía HTTP con las rutas actuales; no se prueba aquí.)
    // =========================================================================

    public function test_usuario_sin_ver_calificaciones_no_puede_acceder_al_index(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Caja / Finanzas'); // no tiene ver-calificaciones

        $this->actingAs($user)
            ->get(route('admin.calificaciones.index'))
            ->assertForbidden();
    }

    public function test_rol_no_docente_con_ver_calificaciones_puede_acceder_al_index(): void
    {
        $this->crearEscenario(); // año escolar activo requerido por el index
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Secretaría');

        $this->actingAs($user)
            ->get(route('admin.calificaciones.index'))
            ->assertOk();
    }
}
