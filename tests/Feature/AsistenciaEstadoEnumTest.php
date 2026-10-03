<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * asistencias.estado es ENUM('presente','ausente','tarde','excusa','retiro') desde la
 * migración 2026_03_17_000071. Varios módulos seguían comparando contra 'tardanza' /
 * 'justificado' (enum viejo): los contadores daban 0 y "presentes" excluía a los 'tarde'.
 * La importación aceptaba esos sinónimos y los guardaba tal cual ("Data truncated").
 */
class AsistenciaEstadoEnumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{admin: User, asig: Asignacion, mats: Matricula[]} */
    private function escenario(int $alumnos): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Enum Asistencia',
            'dominio'            => 'colegioenum' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy    = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $grado   = Grado::create(['nombre' => 'Grado EN' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'EN' . random_int(10000, 99999), 'nombre' => 'Materia EN', 'activo' => true]);
        $asig = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => null, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);

        $mats = [];
        for ($i = 1; $i <= $alumnos; $i++) {
            $mats[] = Matricula::create([
                'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
                'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => $i, 'estado' => 'activa',
            ]);
        }

        return compact('admin', 'asig', 'mats');
    }

    public function test_importacion_normaliza_sinonimos_del_enum_anterior(): void
    {
        $e = $this->escenario(3);

        $csv = "numero_matricula,fecha,estado\n";
        $estados = ['tardanza', 'justificado', 'presente'];
        foreach ($e['mats'] as $i => $m) {
            $csv .= $m->estudiante->numero_matricula . ',2025-09-10,' . $estados[$i] . "\n";
        }

        $this->actingAs($e['admin'])->post(route('admin.asistencia.importStore'), [
            'asignacion_id' => $e['asig']->id,
            'archivo'       => UploadedFile::fake()->createWithContent('asistencia.csv', $csv),
        ])->assertSessionHasNoErrors();

        $guardados = Asistencia::where('asignacion_id', $e['asig']->id)
            ->pluck('estado', 'matricula_id')->all();

        $this->assertSame('tarde',    $guardados[$e['mats'][0]->id] ?? null);
        $this->assertSame('excusa',   $guardados[$e['mats'][1]->id] ?? null);
        $this->assertSame('presente', $guardados[$e['mats'][2]->id] ?? null);
    }
}
