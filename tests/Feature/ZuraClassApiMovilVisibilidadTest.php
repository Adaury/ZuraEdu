<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API móvil de Classroom: estudiantes y padres no ven materiales programados a futuro ni
 * entran a un aula desactivada por id; el docente dueño sí ve todo.
 */
class ZuraClassApiMovilVisibilidadTest extends TestCase
{
    use RefreshDatabase;

    private Estudiante $estudiante;
    private Docente $docente;
    private ClaseVirtual $clase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $sy      = SchoolYear::create(['nombre' => '2026-ApiVis', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado AV', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $this->docente = Docente::factory()->create();
        $this->docente->user->assignRole('Docente');
        $this->docente->user->update(['activo' => true]);

        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'AV' . random_int(1000, 9999), 'nombre' => 'Ciencias', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $this->clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula API', 'activo' => true]);
    }

    private function material(string $titulo, array $extra = []): MaterialClase
    {
        return MaterialClase::create(array_merge([
            'clase_virtual_id' => $this->clase->id, 'titulo' => $titulo, 'tipo' => 'tarea', 'puntos' => 10, 'publicado' => true,
        ], $extra));
    }

    public function test_el_estudiante_no_ve_materiales_en_borrador_ni_programados(): void
    {
        $this->material('Visible');
        $this->material('Borrador', ['publicado' => false]);
        $this->material('Programado', ['publicar_en' => now()->addDays(2)]);
        $this->material('Ya paso su fecha', ['publicar_en' => now()->subHour()]);

        Sanctum::actingAs($this->estudiante->user);
        $titulos = collect($this->getJson("/api/v1/classroom/{$this->clase->id}/materiales")
            ->assertOk()->json('materiales'))->pluck('titulo')->sort()->values()->all();

        $this->assertSame(['Visible', 'Ya paso su fecha'], $titulos);
    }

    public function test_el_docente_dueno_ve_todo_incluido_borrador_y_programado(): void
    {
        $this->material('Visible');
        $this->material('Borrador', ['publicado' => false]);
        $this->material('Programado', ['publicar_en' => now()->addDays(2)]);

        Sanctum::actingAs($this->docente->user);
        $this->getJson("/api/v1/classroom/{$this->clase->id}/materiales")
            ->assertOk()
            ->assertJsonCount(3, 'materiales');
    }

    public function test_aula_desactivada_no_se_abre_por_id_para_estudiante(): void
    {
        $this->clase->update(['activo' => false]);

        Sanctum::actingAs($this->estudiante->user);
        $this->getJson("/api/v1/classroom/{$this->clase->id}/materiales")->assertForbidden();
    }
}
