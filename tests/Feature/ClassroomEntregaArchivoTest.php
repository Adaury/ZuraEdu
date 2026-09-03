<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * ClassroomEstudianteController::entregarTarea — hallazgo Medio de la
 * auditoría del módulo ZuraClass (ver
 * [[project_auditoria_sigerd_whatsapp_2026_09_02.md]] y la ronda de
 * módulos grandes del 2026-09-03): la validación de archivos de entrega
 * solo comprobaba el tamaño (`max:20480`), sin `mimes:` — un estudiante
 * podía subir cualquier tipo de archivo (incluyendo .html/.svg) como
 * entrega. El mismo hueco existía en 3 sitios más de subida de materiales
 * del lado docente en ClassroomDocenteController, corregidos igual.
 */
class ClassroomEntregaArchivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearEscenario(): array
    {
        $sy = SchoolYear::create(['nombre' => '2026-Test', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado CE', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create();
        $estudiante->user->assignRole('Estudiante');
        $estudiante->user->update(['activo' => true]);

        $matricula = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $asignatura = Asignatura::create(['codigo' => 'CE1', 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);
        Periodo::create([
            'school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);
        $clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula Virtual', 'activo' => true]);
        $material = MaterialClase::create([
            'clase_virtual_id' => $clase->id, 'titulo' => 'Tarea 1', 'tipo' => 'tarea', 'publicado' => true,
        ]);

        return compact('estudiante', 'matricula', 'clase', 'material');
    }

    public function test_rechaza_un_archivo_html_como_entrega(): void
    {
        $e = $this->crearEscenario();

        $response = $this->actingAs($e['estudiante']->user)
            ->post(route('portal.estudiante.classroom.entregar', [$e['clase'], $e['material']]), [
                'archivos' => [UploadedFile::fake()->create('malicioso.html', 10, 'text/html')],
            ]);

        $response->assertSessionHasErrors('archivos.0');
    }

    public function test_acepta_un_pdf_como_entrega(): void
    {
        $e = $this->crearEscenario();

        $response = $this->actingAs($e['estudiante']->user)
            ->post(route('portal.estudiante.classroom.entregar', [$e['clase'], $e['material']]), [
                'archivos' => [UploadedFile::fake()->create('tarea.pdf', 100, 'application/pdf')],
            ]);

        $response->assertSessionDoesntHaveErrors();
    }
}
