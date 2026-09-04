<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo Medio de la auditoría completa 2026-09-04: PromedioEstudianteService
 * (el servicio consolidado que prioriza notas académicas y cae a técnica solo
 * si el estudiante no tiene ninguna nota académica) no se usaba en 6
 * controladores/15 sitios — reimplementaban avg('nota_final') directo sobre
 * CalificacionAcademica, así que un estudiante 100% técnico veía el promedio
 * en blanco. Este test cubre el sitio más visible (perfil de estudiante,
 * admin) para probar el patrón de la corrección.
 */
class PromedioTecnicoRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_perfil_de_estudiante_muestra_promedio_de_un_estudiante_100_por_ciento_tecnico(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Técnico Test',
            'dominio'            => 'colegiotecnico' . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::create([
            'nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true,
        ]);
        $periodo = Periodo::create([
            'school_year_id' => $schoolYear->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-10-31', 'activo' => true, 'cerrado' => false,
        ]);
        $grado   = Grado::create(['nombre' => 'Bach. Técnico 1', 'nivel' => 10, 'ciclo' => 'bachillerato', 'orden' => 10, 'activo' => true]);
        $seccion = Seccion::create(['nombre' => 'T' . random_int(1, 9), 'orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create(['numero_matricula' => 'TEC-' . random_int(10000, 99999)]);
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2026-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'TEC' . $grupo->id, 'nombre' => 'Electrónica', 'area' => 'tecnica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'tecnica',
        ]);

        // Nota técnica — SIN ninguna fila en CalificacionAcademica.
        Calificacion::create([
            'matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id,
            'nota_final' => 88, 'publicado' => true,
        ]);

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.perfiles.estudiante', $estudiante));

        $response->assertOk();
        $response->assertViewHas('promedio', 88.0);
        $response->assertViewHas('estado', fn ($estado) => $estado !== 'riesgo' && $estado !== 'baja');
    }
}
