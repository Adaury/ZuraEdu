<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría Don Bosco (Sección 4): "ver" y "exportar" estaban empaquetados
 * en ver-calificaciones/ver-pagos -- solo boletines tenía el permiso de
 * exportación separado (ver-boletines vs imprimir-boletines). Se agregaron
 * exportar-calificaciones/exportar-pagos, asignados a los MISMOS roles que
 * ya tenían ver-calificaciones/ver-pagos -- nadie pierde acceso hoy, solo
 * queda revocable por separado en el futuro.
 *
 * Nota importante descubierta al escribir este test: los 4 roles Docente
 * (User::ROLES_DOCENTE) nunca llegan a /admin/calificaciones/* en absoluto
 * -- EnsureAdminAccess::handle() los redirige incondicionalmente a
 * portal.docente.dashboard antes de que cualquier gate `can:` se evalúe
 * (routes/web.php:624, middleware 'admin.access'). La verificación HTTP
 * de "sigue teniendo acceso" usa roles admin-realm reales (Coordinador
 * Académico, Caja / Finanzas); para Docente solo se verifica el permiso a
 * nivel de rol (Spatie), que es lo único que le aplica.
 */
class ExportarPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{tenant: Tenant, sy: SchoolYear, asignacion: Asignacion} */
    private function crearEscenarioAcademico(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Exportar',
            'dominio'            => 'colegioexportar' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        $sy      = SchoolYear::create(['nombre' => '2025-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado Exp', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'EX1', 'nombre' => 'Materia Exp', 'activo' => true]);
        $docente = Docente::create([
            'cedula' => (string) random_int(100000000, 999999999), 'nombres' => 'Docente', 'apellidos' => 'Exportar',
            'estado' => 'activo',
        ]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $docente->id, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);
        app()->forgetInstance('tenant');

        return compact('tenant', 'sy', 'asignacion');
    }

    private function usuario(Tenant $tenant, string $rol): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);
        return $user;
    }

    // ── Seeder: los roles con ver-* también tienen exportar-* ───────────

    public function test_docente_tiene_exportar_calificaciones_igual_que_ver_calificaciones(): void
    {
        $docente = \Spatie\Permission\Models\Role::findByName('Docente');
        $this->assertTrue($docente->hasPermissionTo('ver-calificaciones'));
        $this->assertTrue($docente->hasPermissionTo('exportar-calificaciones'), 'No debe perder acceso a exportar lo que ya podía exportar.');
    }

    public function test_caja_finanzas_tiene_exportar_pagos_igual_que_ver_pagos(): void
    {
        $caja = \Spatie\Permission\Models\Role::findByName('Caja / Finanzas');
        $this->assertTrue($caja->hasPermissionTo('ver-pagos'));
        $this->assertTrue($caja->hasPermissionTo('exportar-pagos'));
    }

    public function test_un_rol_sin_ver_calificaciones_tampoco_tiene_exportar(): void
    {
        $caja = \Spatie\Permission\Models\Role::findByName('Caja / Finanzas');
        $this->assertFalse($caja->hasPermissionTo('ver-calificaciones'));
        $this->assertFalse($caja->hasPermissionTo('exportar-calificaciones'));
    }

    // ── HTTP: un rol admin-realm que ya tenía ver-calificaciones sigue exportando ──

    public function test_coordinador_academico_sigue_pudiendo_exportar_el_acta_pdf(): void
    {
        $e = $this->crearEscenarioAcademico();
        $user = $this->usuario($e['tenant'], 'Coordinador Académico');

        $response = $this->actingAs($user)->get(route('admin.calificaciones.acta-pdf', $e['asignacion']));

        $response->assertOk();
    }

    public function test_coordinador_academico_sigue_viendo_la_grilla(): void
    {
        $e = $this->crearEscenarioAcademico();
        $user = $this->usuario($e['tenant'], 'Coordinador Académico');

        $response = $this->actingAs($user)->get(route('admin.calificaciones.grilla', [
            'asignacion_id' => $e['asignacion']->id, 'periodo_id' => 1,
        ]));

        // Puede fallar por falta de un periodo real (404/500 de negocio),
        // pero NUNCA por el gate de permisos (403).
        $this->assertNotEquals(403, $response->status());
    }

    public function test_un_rol_sin_ver_calificaciones_no_puede_exportar_el_acta(): void
    {
        $e = $this->crearEscenarioAcademico();
        $user = $this->usuario($e['tenant'], 'Caja / Finanzas');

        $response = $this->actingAs($user)->get(route('admin.calificaciones.acta-pdf', $e['asignacion']));

        $response->assertForbidden();
    }

    // ── HTTP: Caja/Finanzas sigue pudiendo exportar deudores ────────────

    public function test_caja_finanzas_sigue_pudiendo_exportar_deudores_pdf(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Exportar Pagos',
            'dominio'            => 'colegioexportarpagos' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        // El módulo Pagos también está gateado por TenantFeature (Fase 0
        // del roadmap) -- sin esto, CheckTenantFeature bloquea antes de
        // llegar al gate de permisos que este test quiere verificar.
        \App\Models\TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'pagos', 'activo' => true]);
        $user = $this->usuario($tenant, 'Caja / Finanzas');

        $response = $this->actingAs($user)->get(route('admin.pagos.deudores.pdf'));

        $response->assertOk();
    }

    public function test_un_rol_sin_ver_pagos_no_puede_exportar_deudores_pdf(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Exportar Pagos Sin Permiso',
            'dominio'            => 'colegioexportarpagossinpermiso' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        \App\Models\TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'pagos', 'activo' => true]);
        $user = $this->usuario($tenant, 'Coordinador Académico');

        $response = $this->actingAs($user)->get(route('admin.pagos.deudores.pdf'));

        $response->assertForbidden();
    }
}
