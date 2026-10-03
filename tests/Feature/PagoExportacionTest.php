<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PagoController;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Exportaciones de la lista de pagos, medidas en un navegador real con 2.160 pagos:
 * - Excel: 9,7 s porque se creaban 2 estilos por fila (PhpSpreadsheet compara cada uno contra todos los anteriores) → ahora el formato va
 *   aplicado una vez y el color por estado como formato condicional (2,9 s);
 * - PDF: 60 s (el límite de la mayoría de servidores web, error 504) → pasado un tope de filas se pide filtrar.
 */
class PagoExportacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tenant $tenant;
    private Matricula $matricula;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $tenant = $this->tenant = Tenant::create(['nombre_institucion' => 'Colegio Exportación', 'dominio' => 'colegioexp' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $tenant);
        $tenant->enableFeature('pagos');
        $this->admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $this->admin->assignRole('Administrador');

        $sy = SchoolYear::create(['nombre' => '2095-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Exp', 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);
        $this->matricula = Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create(['apellidos' => 'Pérez', 'nombres' => 'Ana'])->id, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa']);
        app()->forgetInstance('tenant');
    }

    private function crearPagos(int $n, string $estado = 'pendiente'): void
    {
        app()->instance('tenant', $this->tenant);   // (Tenant::first() sería el colegio demo que crean las migraciones)
        for ($i = 0; $i < $n; $i++) {
            Pago::create([
                'matricula_id' => $this->matricula->id, 'concepto' => 'Mensualidad ' . ($i + 1), 'monto' => 1500 + $i,
                'fecha_vencimiento' => now()->addYear()->addDays($i)->toDateString(),   // lejos: sincronizarVencidos() no lo toca
                'estado' => $estado, 'fecha_pago' => $estado === 'pagado' ? now()->toDateString() : null,
            ]);
        }
        app()->forgetInstance('tenant');
    }

    public function test_el_excel_trae_todas_las_filas_con_el_formato_correcto(): void
    {
        $this->crearPagos(40, 'pendiente');
        $this->crearPagos(10, 'pagado');

        $r = $this->actingAs($this->admin)->get(route('admin.pagos.lista-excel'))->assertOk();
        $hoja = IOFactory::load($r->baseResponse->getFile()->getPathname());
        $s = $hoja->getActiveSheet();

        $this->assertSame(52, $s->getHighestRow(), '2 filas de encabezado + 50 pagos');
        $this->assertSame('Pérez, Ana', $s->getCell('B3')->getValue());
        $this->assertSame('Mensualidad 1', $s->getCell('D3')->getValue());
        $this->assertSame('#,##0.00', $s->getStyle('E10')->getNumberFormat()->getFormatCode(), 'formato de moneda en toda la columna de montos');
        $this->assertSame('Pendiente', $s->getCell('H3')->getValue());
    }

    public function test_el_color_por_estado_va_como_formato_condicional_y_no_estilo_por_fila(): void
    {
        $this->crearPagos(120, 'pendiente');

        $r = $this->actingAs($this->admin)->get(route('admin.pagos.lista-excel'))->assertOk();
        $hoja = IOFactory::load($r->baseResponse->getFile()->getPathname());

        $condiciones = array_sum(array_map('count', $hoja->getActiveSheet()->getConditionalStylesCollection()));
        $this->assertSame(3, $condiciones, 'pagado / pendiente / vencido');
        $this->assertLessThan(15, count($hoja->getCellXfCollection()), 'con 120 pagos antes había ~240 estilos; ahora son unos pocos y no crecen con las filas');
    }

    public function test_el_pdf_se_genera_cuando_la_lista_es_razonable(): void
    {
        $this->crearPagos(5, 'pendiente');

        $r = $this->actingAs($this->admin)->get(route('admin.pagos.lista-pdf'))->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $r->getContent());
    }

    public function test_el_pdf_pide_filtrar_cuando_hay_demasiados_pagos_en_vez_de_colgarse(): void
    {
        $this->crearPagos(PagoController::MAX_FILAS_PDF + 1, 'pendiente');
        $this->crearPagos(3, 'pagado');

        $r = $this->actingAs($this->admin)->from(route('admin.pagos.index'))->get(route('admin.pagos.lista-pdf'));

        $r->assertRedirect(route('admin.pagos.index'));
        $r->assertSessionHas('error');
        $this->assertStringContainsString('Filtra por mes, estado o grupo', session('error'));

        // Con un filtro que deja pocas filas sí se genera
        $filtrado = $this->actingAs($this->admin)->get(route('admin.pagos.lista-pdf', ['estado' => 'pagado']))->assertOk();
        $this->assertStringStartsWith('%PDF', $filtrado->getContent());
    }

    public function test_el_excel_no_tiene_ese_tope(): void
    {
        $this->crearPagos(PagoController::MAX_FILAS_PDF + 5, 'pendiente');

        $r = $this->actingAs($this->admin)->get(route('admin.pagos.lista-excel'))->assertOk();

        $this->assertSame(PagoController::MAX_FILAS_PDF + 5 + 2, IOFactory::load($r->baseResponse->getFile()->getPathname())->getActiveSheet()->getHighestRow());
    }
}
