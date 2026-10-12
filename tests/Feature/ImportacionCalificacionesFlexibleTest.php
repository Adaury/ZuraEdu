<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Jobs\ImportarCalificacionesJob;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\ImportacionCalificacion;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use App\Support\EncabezadosImportacion;
use App\Support\EscalaLiteral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Importación de calificaciones desde el Excel PROPIO del docente: título del instrumento arriba,
 * encabezados con otros nombres ("Matrícula", "C1 P1"), coma decimal, solo nombres, hoja de portada,
 * letras (opcional) y un mensaje claro cuando el archivo no se reconoce.
 */
class ImportacionCalificacionesFlexibleTest extends TestCase
{
    use RefreshDatabase;

    private Asignacion $asig;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Storage::fake('local');

        $t = Tenant::create([
            'nombre_institucion' => 'Colegio Importa',
            'dominio'            => 'imp' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-I', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado IM' . random_int(1, 99999), 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'segundo_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $this->asig = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => 'IM' . random_int(10000, 99999), 'nombre' => 'Matemática', 'area' => 'academica', 'activo' => true])->id,
            'activo' => true, 'area' => 'academica',
        ]);
        $this->user = User::factory()->create(['activo' => true, 'tenant_id' => $t->id]);
    }

    private function estudiante(string $nombres, string $apellidos, int $orden): array
    {
        $e = Estudiante::factory()->create([
            'nombres' => $nombres, 'apellidos' => $apellidos,
            'numero_matricula' => 'M-' . str_pad((string) $orden, 4, '0', STR_PAD_LEFT),
            'cedula' => '001-000000' . $orden . '-1',
        ]);
        $m = Matricula::create([
            'school_year_id' => $this->asig->school_year_id, 'estudiante_id' => $e->id, 'grupo_id' => $this->asig->grupo_id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => 'activa',
        ]);

        return [$e, $m];
    }

    /** @param array<int,array<int,mixed>> $hojaDatos filas de la hoja de datos */
    private function importar(array $hojaDatos, bool $conPortada = true): ImportacionCalificacion
    {
        $libro = new Spreadsheet();
        if ($conPortada) {
            $libro->getActiveSheet()->setTitle('PORTADA')->fromArray([
                ['INSTRUMENTO DE EVALUACIÓN - SECUNDARIO'], ['Centro educativo', 'Colegio Importa'], ['Docente', 'Luis Medina'],
            ], null, 'A1');
            $hoja = $libro->createSheet()->setTitle('PANEL');
        } else {
            $hoja = $libro->getActiveSheet()->setTitle('PANEL');
        }
        $hoja->fromArray($hojaDatos, null, 'A1');

        $tmp = tempnam(sys_get_temp_dir(), 'imp_') . '.xlsx';
        (new Xlsx($libro))->save($tmp);
        Storage::disk('local')->put('imports/calificaciones/test.xlsx', file_get_contents($tmp));
        @unlink($tmp);

        $imp = ImportacionCalificacion::create([
            'user_id' => $this->user->id, 'asignacion_id' => $this->asig->id,
            'archivo_original' => 'mi_instrumento.xlsx', 'archivo_path' => 'imports/calificaciones/test.xlsx', 'estado' => 'pendiente',
        ]);

        (new ImportarCalificacionesJob($imp->id))->handle();

        return $imp->fresh();
    }

    private function nota(Matricula $m): ?CalificacionAcademica
    {
        return CalificacionAcademica::where('matricula_id', $m->id)->where('asignacion_id', $this->asig->id)->first();
    }

    // ── Reconocimiento de encabezados ────────────────────────────────────────

    public function test_reconoce_las_variantes_de_encabezados(): void
    {
        $casos = [
            'Matrícula' => 'numero_matricula', 'Nº Matrícula' => 'numero_matricula', 'Cédula' => 'cedula',
            'Nombre completo' => 'nombre_completo', 'Estudiante' => 'nombre_completo', 'Apellido' => 'apellidos',
            'C1 P1' => 'comp1_p1', 'c2p3' => 'comp2_p3', 'P2 C4' => 'comp4_p2', 'Competencia 3 - Período 1' => 'comp3_p1',
            'comp1_p4' => 'comp1_p4', 'Periodo 2 Competencia 2' => 'comp2_p2',
            'Nota final' => 'nota_final', 'Promedio' => 'nota_final', 'Período' => 'periodo',
        ];
        foreach ($casos as $texto => $esperado) {
            $this->assertSame($esperado, EncabezadosImportacion::canonico($texto), "«{$texto}»");
        }
    }

    public function test_detecta_la_fila_de_encabezados_debajo_de_titulos(): void
    {
        $matriz = [
            ['INSTRUMENTO DE EVALUACIÓN'], [], ['Docente', 'X'],
            ['Matrícula', 'Estudiante', 'C1 P1', 'C2 P1'],
            ['M-1', 'Ana', 90, 80],
        ];
        [$fila, $mapa] = EncabezadosImportacion::detectar($matriz);

        $this->assertSame(3, $fila);
        $this->assertSame(['numero_matricula', 'nombre_completo', 'comp1_p1', 'comp2_p1'], array_values($mapa));
    }

    // ── Importación de punta a punta ─────────────────────────────────────────

    public function test_importa_el_excel_del_docente_con_portada_titulos_y_nombres_distintos(): void
    {
        [, $m1] = $this->estudiante('Juan', 'Pérez', 1);
        [, $m2] = $this->estudiante('María', 'González', 2);

        $imp = $this->importar([
            ['INSTRUMENTO DE EVALUACIÓN - SECUNDARIO'],
            [],
            ['Matrícula', 'Estudiante', 'C1 P1', 'C2 P1', 'Competencia 1 - Período 2'],
            ['M-0001', 'Pérez Juan', '85,5', 90, '70%'],
            ['M-0002', 'González María', 100, '88', 60],
            [],
            ['PROMEDIO', '', 92, 89, 65],   // fila de totales: se ignora (sin identificador de estudiante)
        ]);

        $this->assertSame('completado', $imp->estado);
        $a = $this->nota($m1);
        $this->assertEquals(85.5, (float) $a->comp1_p1);
        $this->assertEquals(90.0, (float) $a->comp2_p1);
        $this->assertEquals(70.0, (float) $a->comp1_p2, 'El 70% se interpreta como 70');
        $b = $this->nota($m2);
        $this->assertEquals(100.0, (float) $b->comp1_p1);
        $this->assertEquals(60.0, (float) $b->comp1_p2);
        $this->assertSame(2, (int) $imp->importados);
    }

    public function test_solo_con_nombres_empata_por_conjunto_de_palabras_y_avisa_si_es_ambiguo(): void
    {
        [, $m1]  = $this->estudiante('Juan', 'Pérez', 1);
        [, $mam] = $this->estudiante('Rosa', 'Díaz', 2);
        [, $mam2] = $this->estudiante('Rosa', 'Díaz', 3);   // homónima

        $imp = $this->importar([
            ['Nombre completo', 'C1 P1'],
            ['Pérez, Juan', 90],
            ['Rosa Díaz', 80],
            ['Pedro Inexistente', 70],
        ], conPortada: false);

        $this->assertEquals(90.0, (float) $this->nota($m1)->comp1_p1);
        $this->assertNull($this->nota($mam));
        $this->assertNull($this->nota($mam2));
        $errores = implode(' | ', $imp->errores ?? []);
        $this->assertStringContainsString('coincide con 2 estudiantes', $errores);
        $this->assertStringContainsString('Pedro Inexistente', $errores);
    }

    public function test_las_letras_solo_se_aceptan_si_el_centro_activo_la_escala(): void
    {
        [, $m] = $this->estudiante('Luis', 'Santos', 1);

        $imp = $this->importar([['Matrícula', 'C1 P1', 'C2 P1'], ['M-0001', 'B+', 85]], conPortada: false);
        $this->assertNull($this->nota($m)?->comp1_p1, 'Sin escala activa la letra no es válida');
        $this->assertEquals(85.0, (float) $this->nota($m)->comp2_p1);

        Setting::set('escala_literal_activa', '1');
        $this->importar([['Matrícula', 'C1 P1', 'C2 P1'], ['M-0001', 'b+', 'A']], conPortada: false);
        $this->assertEquals(90.0, (float) $this->nota($m)->fresh()->comp1_p1);
        $this->assertEquals(95.0, (float) $this->nota($m)->fresh()->comp2_p1);
    }

    public function test_un_archivo_irreconocible_falla_con_un_mensaje_que_dice_que_se_encontro(): void
    {
        $this->estudiante('Luis', 'Santos', 1);

        $imp = $this->importar([['Hola', 'Mundo'], ['x', 'y']], conPortada: false);

        $this->assertSame('fallido', $imp->estado);
        $this->assertStringContainsString('No se encontró una fila de encabezados reconocible', $imp->error_fatal);
        $this->assertStringContainsString('Hola', $imp->error_fatal);
    }

    public function test_escala_literal_convierte_en_ambos_sentidos(): void
    {
        $this->assertSame(90.0, EscalaLiteral::aNumero('B+'));
        $this->assertNull(EscalaLiteral::aNumero('Z'));
        $this->assertSame('A+', EscalaLiteral::aLetra(100));
        $this->assertSame('A', EscalaLiteral::aLetra(97));
        $this->assertSame('B', EscalaLiteral::aLetra(86));
        $this->assertSame('D', EscalaLiteral::aLetra(65));
        $this->assertSame('F', EscalaLiteral::aLetra(40));
    }
}
