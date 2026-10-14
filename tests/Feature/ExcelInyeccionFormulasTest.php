<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaCafeteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Los exportes a Excel escriben texto de usuarios (nombres, descripciones, motivos): lo que empieza por
 * "=" no debe convertirse en fórmula (ver App\Support\ValueBinderSeguro).
 */
class ExcelInyeccionFormulasTest extends TestCase
{
    use RefreshDatabase;

    private function ciclo(Spreadsheet $s): Spreadsheet
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl_') . '.xlsx';
        (new Xlsx($s))->save($tmp);
        $leido = IOFactory::load($tmp);
        @unlink($tmp);

        return $leido;
    }

    public function test_un_texto_que_empieza_por_igual_se_guarda_como_texto_y_no_como_formula(): void
    {
        $s = new Spreadsheet();
        $h = $s->getActiveSheet();
        $h->setCellValue('A1', '=HYPERLINK("http://sitio-malo.example","Click")');
        $h->setCellValue('A2', '+cmd|calc!A0');
        $h->setCellValue('A3', '@SUM(1+1)');
        $h->setCellValue('A4', "-2+3");
        $h->setCellValue('A5', 'Texto normal');
        $h->setCellValue('A6', -5);
        $h->setCellValue('A7', '85.5');
        $h->fromArray([['=1+1', 'ok']], null, 'C1');

        $r = $this->ciclo($s)->getActiveSheet();

        foreach (['A1', 'A2', 'A3', 'A4', 'C1'] as $coord) {
            $this->assertSame(DataType::TYPE_STRING, $r->getCell($coord)->getDataType(), "{$coord} debe ser texto");
        }
        $this->assertSame('=HYPERLINK("http://sitio-malo.example","Click")', $r->getCell('A1')->getValue());
        $this->assertSame('=1+1', $r->getCell('C1')->getValue());

        // Lo demás sigue igual: números como números, texto normal intacto
        $this->assertSame('Texto normal', $r->getCell('A5')->getValue());
        $this->assertEquals(-5, $r->getCell('A6')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $r->getCell('A6')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $r->getCell('A7')->getDataType());
    }

    public function test_el_reporte_excel_de_cafeteria_no_ejecuta_una_descripcion_con_formula(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Excel', 'dominio' => 'xl' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        $tenant->features()->create(['feature' => 'cafeteria', 'activo' => true]);

        $est = Estudiante::factory()->create();
        VentaCafeteria::registrarMovimiento($est->id, 'recarga', 100, '=HYPERLINK("http://x.example","cobro")', null, null);

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $r = $this->actingAs($admin)->get(route('admin.cafeteria.reporte-csv', ['fecha' => today()->toDateString()]));
        $r->assertOk();

        $archivo = $r->baseResponse->getFile()->getPathname();
        $hoja = IOFactory::load($archivo)->getActiveSheet();

        $encontrado = false;
        foreach ($hoja->getRowIterator(4) as $fila) {
            $celda = $hoja->getCell('E' . $fila->getRowIndex());
            if (str_starts_with((string) $celda->getValue(), '=HYPERLINK')) {
                $encontrado = true;
                $this->assertSame(DataType::TYPE_STRING, $celda->getDataType());
            }
        }
        $this->assertTrue($encontrado, 'La descripción debe aparecer como texto');
    }

    public function test_fecha_invalida_en_los_reportes_de_cafeteria_da_error_de_validacion(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Excel 2', 'dominio' => 'xl' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        $tenant->features()->create(['feature' => 'cafeteria', 'activo' => true]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->get(route('admin.cafeteria.reporte-csv', ['fecha' => 'no-es-fecha']))
            ->assertSessionHasErrors('fecha');
    }
}
