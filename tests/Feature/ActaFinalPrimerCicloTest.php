<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acta Final de Calificaciones (formato oficial MINERD) + bloque de
 * Competencias Fundamentales del Boletín anual -- Primer Ciclo de
 * Secundaria (1ro-3ro) únicamente. Pedido explícito del usuario, imágenes
 * de referencia "Acta Final Calificacion.PNG" y "Boletin de Nota.PNG".
 *
 * Decisiones de diseño verificadas aquí (aprobadas por el usuario):
 * - Situación Final = Promovido SOLO si todas las asignaturas del
 *   estudiante están en situacion='A'; si falta alguna nota, ninguna
 *   casilla se marca (nunca se infiere).
 * - Sin módulo "Bachillerato General" nuevo: las columnas de asignatura se
 *   arman dinámicamente desde las Asignacion reales del grupo.
 * - Datos institucionales nuevos viven en ConfigInstitucional.
 */
class ActaFinalPrimerCicloTest extends TestCase
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

    /** Tenant + año + grupo (ciclo indicado) + docente con 2 asignaturas + 1 estudiante matriculado. */
    private function crearEscenario(string $codigo, string $ciclo = 'primer_ciclo'): array
    {
        $tenant = $this->crearTenant('Colegio Acta ' . $codigo);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-AC', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => '2do de Secundaria ' . $codigo, 'nivel' => 2, 'orden' => 2, 'ciclo' => $ciclo, 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $docenteUser = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $docenteUser->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $docenteUser->id]);

        $asigEspanol = Asignatura::create(['codigo' => 'ESP' . $codigo, 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asigMate    = Asignatura::create(['codigo' => 'MAT' . $codigo, 'nombre' => 'Matemática', 'area' => 'academica', 'activo' => true]);

        $asigEsp = Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asigEspanol->id, 'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica']);
        $asigMat = Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asigMate->id, 'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica']);

        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return compact('tenant', 'sy', 'grado', 'grupo', 'docenteUser', 'docente', 'asigEsp', 'asigMat', 'estudiante', 'matricula');
    }

    private function calificacion(array $e, Asignacion $asignacion, ?string $situacion, ?float $notaFinal = 80): CalificacionAcademica
    {
        return CalificacionAcademica::create([
            'matricula_id' => $e['matricula']->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $e['sy']->id,
            'nota_final' => $notaFinal, 'situacion' => $situacion,
        ]);
    }

    // ── Situación Final consolidada ──────────────────────────────────────

    public function test_promovido_solo_si_todas_las_asignaturas_estan_en_a(): void
    {
        $e = $this->crearEscenario('SF1');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'A');
        app()->forgetInstance('tenant');

        $acta = app(\App\Services\ActaFinalService::class)->construirActaGrupo($e['grupo'], $e['sy']);

        $this->assertSame('A', $acta['filas'][0]['situacion_final']);
    }

    public function test_una_sola_asignatura_en_r_reprueba_el_acta_completa(): void
    {
        $e = $this->crearEscenario('SF2');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'R', 50);
        app()->forgetInstance('tenant');

        $acta = app(\App\Services\ActaFinalService::class)->construirActaGrupo($e['grupo'], $e['sy']);

        $this->assertSame('R', $acta['filas'][0]['situacion_final']);
    }

    public function test_si_falta_la_nota_de_una_asignatura_no_se_marca_ninguna_casilla(): void
    {
        $e = $this->crearEscenario('SF3');
        $this->calificacion($e, $e['asigEsp'], 'A');
        // Matemática sin CalificacionAcademica -- sin notas aún.
        app()->forgetInstance('tenant');

        $acta = app(\App\Services\ActaFinalService::class)->construirActaGrupo($e['grupo'], $e['sy']);

        $this->assertNull($acta['filas'][0]['situacion_final']);
    }

    public function test_la_situacion_final_respeta_la_situacion_ya_calculada_y_completivo_muestra_la_nota_cruda(): void
    {
        // situacion='A' aquí simula que CalificacionAcademica::recalcularPromedios()
        // ya aplicó la regla nota_extraordinaria ?? nota_completiva ?? nota_final --
        // el servicio del Acta no debe recalcularla, solo confiar en el campo.
        // La columna "Completivo" del Acta es la nota CRUDA del examen (nota_cc),
        // no el resultado ponderado (nota_completiva) -- confirmado contra la
        // imagen oficial de referencia.
        $e = $this->crearEscenario('SF4');
        CalificacionAcademica::create([
            'matricula_id' => $e['matricula']->id, 'asignacion_id' => $e['asigEsp']->id, 'school_year_id' => $e['sy']->id,
            'nota_final' => 60, 'nota_cc' => 90, 'nota_completiva' => 75, 'situacion' => 'A',
        ]);
        $this->calificacion($e, $e['asigMat'], 'A');
        app()->forgetInstance('tenant');

        $acta = app(\App\Services\ActaFinalService::class)->construirActaGrupo($e['grupo'], $e['sy']);

        $this->assertSame('A', $acta['filas'][0]['situacion_final']);
        $this->assertSame(90.0, $acta['filas'][0]['asignaturas'][$e['asigEsp']->id]['completivo']);
    }

    // ── Alcance: solo Primer Ciclo ───────────────────────────────────────

    public function test_admin_no_puede_generar_el_acta_de_un_grupo_de_segundo_ciclo(): void
    {
        $e = $this->crearEscenario('SC1', 'segundo_ciclo');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.acta-final.pdf', $e['grupo']));

        $response->assertNotFound();
    }

    public function test_docente_no_puede_ver_el_acta_de_un_grupo_de_segundo_ciclo(): void
    {
        $e = $this->crearEscenario('SC2', 'segundo_ciclo');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->get(route('portal.docente.grupo.acta-final', $e['grupo']));

        $response->assertNotFound();
    }

    // ── Autorización ──────────────────────────────────────────────────────

    public function test_admin_puede_generar_el_acta_final_pdf(): void
    {
        $e = $this->crearEscenario('AU1');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'A');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.acta-final.pdf', $e['grupo']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_docente_con_asignacion_en_el_grupo_ve_la_vista_web_editable(): void
    {
        $e = $this->crearEscenario('AU2');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'A');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->get(route('portal.docente.grupo.acta-final', $e['grupo']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        // El docente creado en crearEscenario() es dueño de AMBAS asignaturas
        // del escenario (asigEsp y asigMat) -- ambas columnas deben ser editables.
        // (No se busca la palabra "disabled" a secas: el propio JS de la
        // página la usa en "el.disabled", dando un falso positivo.)
        $response->assertSee('data-campo="nota_cc"', false);
        preg_match_all('/<input[^>]*class="celda-input[^"]*"[^>]*>/', $response->getContent(), $inputs);
        $this->assertNotEmpty($inputs[0]);
        foreach ($inputs[0] as $inputTag) {
            $this->assertStringNotContainsString('disabled', $inputTag, "Input editable no debería tener disabled: {$inputTag}");
        }
    }

    public function test_docente_puede_descargar_el_pdf_del_acta_final(): void
    {
        $e = $this->crearEscenario('AU2B');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'A');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->get(route('portal.docente.grupo.acta-final.pdf', $e['grupo']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_docente_sin_asignacion_en_el_grupo_no_puede_ver_el_acta(): void
    {
        $e = $this->crearEscenario('AU3');

        $otroUser = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $otroUser->assignRole('Docente');
        Docente::factory()->create(['user_id' => $otroUser->id]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($otroUser)->get(route('portal.docente.grupo.acta-final', $e['grupo']));

        $response->assertForbidden();
    }

    public function test_un_docente_de_otro_tenant_no_puede_acceder_al_grupo(): void
    {
        $eA = $this->crearEscenario('AU4A');
        $eB = $this->crearEscenario('AU4B');

        $response = $this->actingAs($eB['docenteUser'])->get(route('portal.docente.grupo.acta-final', $eA['grupo']));

        $response->assertNotFound();
    }

    // ── Configuración institucional nueva ────────────────────────────────

    public function test_administrador_puede_guardar_los_datos_del_acta_final(): void
    {
        $tenant = $this->crearTenant('Colegio Config Acta');
        $admin  = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->post(route('admin.sistema.institucional.update'), [
            'tanda' => 'matutina', 'sector' => 'publico', 'zona' => 'urbana',
            'director_distrito' => 'Juan Pérez', 'secretario_docente' => 'María López',
        ]);

        $response->assertRedirect();
        $this->assertSame('matutina', \App\Models\ConfigInstitucional::get('tanda'));
        $this->assertSame('publico', \App\Models\ConfigInstitucional::get('sector'));
        $this->assertSame('urbana', \App\Models\ConfigInstitucional::get('zona'));
        $this->assertSame('Juan Pérez', \App\Models\ConfigInstitucional::get('director_distrito'));
        $this->assertSame('María López', \App\Models\ConfigInstitucional::get('secretario_docente'));
    }

    public function test_valores_invalidos_de_tanda_sector_zona_son_rechazados(): void
    {
        $tenant = $this->crearTenant('Colegio Config Acta Invalida');
        $admin  = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->post(route('admin.sistema.institucional.update'), [
            'tanda' => 'no-existe',
        ]);

        $response->assertSessionHasErrors('tanda');
    }

    // ── Vista web editable + guardado de celda (pedido del usuario 2026-09-12) ──

    public function test_admin_ve_la_vista_web_con_columnas_editables(): void
    {
        $e = $this->crearEscenario('WEB1');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.acta-final.ver', $e['grupo']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        $response->assertSee('data-campo="nota_cc"', false);
        $response->assertSee('data-campo="nota_ce"', false);
    }

    public function test_admin_puede_guardar_completivo_de_cualquier_materia(): void
    {
        $e = $this->crearEscenario('SAVE1');
        // guardarCelda() llama recalcularPromedios(), que SIEMPRE recalcula
        // nota_final desde comp1-4_p1-4 (nunca preserva un nota_final puesto
        // a mano sin datos de período detrás) -- se siembran las 4
        // competencias en 60 para que nota_final quede en 60 de verdad.
        $datosComp = [];
        foreach ([1, 2, 3, 4] as $c) { $datosComp["comp{$c}_p1"] = 60; }
        $cal = CalificacionAcademica::create([
            'matricula_id' => $e['matricula']->id, 'asignacion_id' => $e['asigEsp']->id, 'school_year_id' => $e['sy']->id,
            'situacion' => 'A',
        ] + $datosComp);
        $cal->recalcularPromedios();
        $this->assertSame(60.0, $cal->fresh()->nota_final, 'Precondición: nota_final debe quedar en 60 tras sembrar comp1-4.');

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-celda-academica'), [
            'matricula_id'  => $e['matricula']->id,
            'asignacion_id' => $e['asigEsp']->id,
            'campo'         => 'nota_cc',
            'valor'         => 90,
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $this->assertSame(90.0, $cal->fresh()->nota_cc);
        // Completivo = 50% NF + 50% CC = 0.5*60 + 0.5*90 = 75
        $this->assertSame(75.0, $cal->fresh()->nota_completiva);
    }

    public function test_guardar_celda_rechaza_campos_fuera_de_completivo_extraordinario(): void
    {
        $e = $this->crearEscenario('SAVE2');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        // No debe poder tocar comp1_p1 (eso es exclusivo de la Planilla
        // Académica) -- protege contra un guardado parcial que borre datos.
        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-celda-academica'), [
            'matricula_id'  => $e['matricula']->id,
            'asignacion_id' => $e['asigEsp']->id,
            'campo'         => 'comp1_p1',
            'valor'         => 90,
        ]);

        $response->assertStatus(422);
    }

    public function test_docente_puede_guardar_extraordinario_de_su_propia_materia(): void
    {
        $e = $this->crearEscenario('SAVE3');
        $cal = $this->calificacion($e, $e['asigMat'], 'A', 65);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->patchJson(
            route('portal.docente.calificaciones.acad.celda', $e['asigMat']),
            ['matricula_id' => $e['matricula']->id, 'campo' => 'nota_ce', 'valor' => 80]
        );

        $response->assertOk();
        $this->assertSame(80.0, $cal->fresh()->nota_ce);
    }

    public function test_docente_no_puede_guardar_celda_de_una_materia_que_no_es_suya(): void
    {
        $e = $this->crearEscenario('SAVE4');
        $this->calificacion($e, $e['asigEsp'], 'A');

        $otroUser = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $otroUser->assignRole('Docente');
        Docente::factory()->create(['user_id' => $otroUser->id]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($otroUser)->patchJson(
            route('portal.docente.calificaciones.acad.celda', $e['asigEsp']),
            ['matricula_id' => $e['matricula']->id, 'campo' => 'nota_cc', 'valor' => 90]
        );

        $response->assertForbidden();
    }

    // ── Encabezado institucional editable inline en el Acta (pedido del usuario 2026-09-12) ──

    public function test_admin_ve_el_encabezado_institucional_como_editable_y_docente_como_solo_lectura(): void
    {
        $e = $this->crearEscenario('HDR1');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $respAdmin = $this->actingAs($admin)->get(route('admin.acta-final.ver', $e['grupo']));
        $respAdmin->assertOk();
        $respAdmin->assertSee('data-campo="nombre_institucion"', false);
        $respAdmin->assertSee('data-campo="tanda"', false);

        $respDocente = $this->actingAs($e['docenteUser'])->get(route('portal.docente.grupo.acta-final', $e['grupo']));
        $respDocente->assertOk();
        $respDocente->assertDontSee('data-campo="nombre_institucion"', false);
        $respDocente->assertDontSee('data-campo="tanda"', false);
    }

    public function test_admin_puede_guardar_un_campo_institucional_desde_el_acta(): void
    {
        $tenant = $this->crearTenant('Colegio Header Acta');
        $admin  = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->postJson(route('admin.sistema.institucional.guardar-campo'), [
            'campo' => 'nombre_director', 'valor' => 'Ana Ramírez',
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $this->assertSame('Ana Ramírez', \App\Models\ConfigInstitucional::get('nombre_director'));
    }

    public function test_guardar_campo_institucional_rechaza_claves_fuera_de_la_whitelist(): void
    {
        $tenant = $this->crearTenant('Colegio Header Acta Invalido');
        $admin  = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->postJson(route('admin.sistema.institucional.guardar-campo'), [
            'campo' => 'rnc', 'valor' => '999999999',
        ]);

        $response->assertStatus(422);
    }

    public function test_docente_no_puede_usar_el_endpoint_de_guardado_institucional(): void
    {
        // Un docente ni siquiera llega al Gate solo-administrador: el
        // middleware EnsureAdminAccess lo redirige fuera de todo /admin/*
        // antes de evaluar el permiso (mismo comportamiento que el resto
        // del panel admin para roles de portal).
        $e = $this->crearEscenario('HDR2');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->post(route('admin.sistema.institucional.guardar-campo'), [
            'campo' => 'nombre_director', 'valor' => 'Intento Docente',
        ]);

        $response->assertRedirect(route('portal.docente.dashboard'));
        $this->assertNotSame('Intento Docente', \App\Models\ConfigInstitucional::get('nombre_director'));
    }

    // ── Boletín de Nota (Competencias Fundamentales) editable inline ────────

    public function test_admin_ve_el_boletin_anual_con_completivo_extraordinario_editables(): void
    {
        $e = $this->crearEscenario('BOL1');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $this->calificacion($e, $e['asigMat'], 'A');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.boletines.ver-anual', $e['matricula']->id));

        $response->assertOk();
        $response->assertSee('data-campo="nota_cc"', false);
        $response->assertSee('data-campo="nota_ce"', false);
        preg_match_all('/<input[^>]*class="celda-input[^"]*"[^>]*>/', $response->getContent(), $inputs);
        $this->assertNotEmpty($inputs[0]);
        foreach ($inputs[0] as $inputTag) {
            $this->assertStringNotContainsString('disabled', $inputTag, "Input editable no debería tener disabled: {$inputTag}");
        }
    }

    public function test_admin_puede_guardar_completivo_desde_el_boletin_anual(): void
    {
        $e = $this->crearEscenario('BOL2');
        $datosComp = [];
        foreach ([1, 2, 3, 4] as $c) { $datosComp["comp{$c}_p1"] = 60; }
        $cal = CalificacionAcademica::create([
            'matricula_id' => $e['matricula']->id, 'asignacion_id' => $e['asigEsp']->id, 'school_year_id' => $e['sy']->id,
            'situacion' => 'A',
        ] + $datosComp);
        $cal->recalcularPromedios();

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-celda-academica'), [
            'matricula_id'  => $e['matricula']->id,
            'asignacion_id' => $e['asigEsp']->id,
            'campo'         => 'nota_ce',
            'valor'         => 85,
        ]);

        $response->assertOk();
        $this->assertSame(85.0, $cal->fresh()->nota_ce);
    }

    public function test_admin_puede_editar_prueba_especial_desde_el_boletin_anual(): void
    {
        // Pedido del usuario 2026-09-12: réplica del Boletin de Nota.PNG con
        // Prueba Especial (eval_cf/eval_ce) también editable, pero exclusivo
        // del Administrador -- el endpoint del docente (guardarCeldaAcad)
        // sigue sin aceptar estos 2 campos.
        $e = $this->crearEscenario('BOL4');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-celda-academica'), [
            'matricula_id'  => $e['matricula']->id,
            'asignacion_id' => $e['asigEsp']->id,
            'campo'         => 'eval_cf',
            'valor'         => 72,
        ]);

        $response->assertOk();
        $this->assertSame(72.0, CalificacionAcademica::where('matricula_id', $e['matricula']->id)
            ->where('asignacion_id', $e['asigEsp']->id)->first()->eval_cf);
    }

    public function test_docente_no_puede_guardar_prueba_especial_via_su_propio_endpoint(): void
    {
        $e = $this->crearEscenario('BOL5');
        $this->calificacion($e, $e['asigEsp'], 'A');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($e['docenteUser'])->patchJson(
            route('portal.docente.calificaciones.acad.celda', $e['asigEsp']),
            ['matricula_id' => $e['matricula']->id, 'campo' => 'eval_cf', 'valor' => 90]
        );

        $response->assertStatus(422);
    }

    public function test_admin_puede_guardar_el_coordinador_pedagogico(): void
    {
        $tenant = $this->crearTenant('Colegio Coordinador Pedagogico');
        $admin  = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->postJson(route('admin.sistema.institucional.guardar-campo'), [
            'campo' => 'coordinador_pedagogico', 'valor' => 'María Elizabeth Domínguez',
        ]);

        $response->assertOk();
        $this->assertSame('María Elizabeth Domínguez', \App\Models\ConfigInstitucional::get('coordinador_pedagogico'));
    }

    public function test_boletin_anual_de_segundo_ciclo_no_muestra_tabla_de_competencias(): void
    {
        $e = $this->crearEscenario('BOL3', 'segundo_ciclo');
        $this->calificacion($e, $e['asigEsp'], 'A');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $admin->assignRole('Administrador');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.boletines.ver-anual', $e['matricula']->id));

        $response->assertOk();
        $response->assertDontSee('Competencias Fundamentales');
    }
}
