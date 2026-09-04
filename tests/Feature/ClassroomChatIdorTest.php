<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\ClassroomMessage;
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
 * ClassroomChatController::togglePin/destroy — hallazgo Alto de la ronda
 * de auditoría de módulos grandes del 2026-09-03 (ZuraClass, API móvil y
 * chat no cubiertos por la auditoría del día anterior): ninguno de los
 * dos métodos verificaba que $message->clase_virtual_id realmente
 * perteneciera a la $claseVirtual de la URL — solo que el usuario fuera
 * docente/autor en ALGUNA clase. Un docente autorizado en SU PROPIA clase
 * podía fijar o eliminar un mensaje de la clase de OTRO docente
 * construyendo la URL con su propia claseVirtual y el ID de un mensaje
 * ajeno.
 */
class ClassroomChatIdorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Chat', 'dominio' => 'colegiochat' . random_int(1000, 9999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
    }

    private function crearClase(SchoolYear $sy, Grupo $grupo, string $codigoAsig): array
    {
        $docenteUser = User::factory()->create(['activo' => true, 'tenant_id' => app('tenant')->id]);
        $docenteUser->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $docenteUser->id]);

        $asignatura = Asignatura::create(['codigo' => $codigoAsig, 'nombre' => 'Materia ' . $codigoAsig, 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica', 'docente_id' => $docente->id,
        ]);
        $clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula ' . $codigoAsig, 'activo' => true]);

        return compact('docenteUser', 'docente', 'clase');
    }

    public function test_un_docente_no_puede_fijar_un_mensaje_de_la_clase_de_otro_docente(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-Chat', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Chat', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $miClase   = $this->crearClase($sy, $grupo, 'CH1');
        $otraClase = $this->crearClase($sy, $grupo, 'CH2');

        $mensajeAjeno = ClassroomMessage::create([
            'clase_virtual_id' => $otraClase['clase']->id,
            'user_id' => $otraClase['docenteUser']->id,
            'mensaje' => 'Mensaje de la otra clase', 'tipo' => 'general', 'fijado' => false,
        ]);

        $response = $this->actingAs($miClase['docenteUser'])
            ->patch(route('portal.docente.classroom.chat.pin', [$miClase['clase'], $mensajeAjeno]));

        $response->assertNotFound();
        $this->assertFalse($mensajeAjeno->fresh()->fijado, 'El mensaje ajeno no debe quedar fijado.');
    }

    public function test_un_docente_no_puede_eliminar_un_mensaje_de_la_clase_de_otro_docente(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-Chat2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Chat2', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $miClase   = $this->crearClase($sy, $grupo, 'CH3');
        $otraClase = $this->crearClase($sy, $grupo, 'CH4');

        $mensajeAjeno = ClassroomMessage::create([
            'clase_virtual_id' => $otraClase['clase']->id,
            'user_id' => $otraClase['docenteUser']->id,
            'mensaje' => 'Mensaje de la otra clase', 'tipo' => 'general', 'fijado' => false,
        ]);

        $response = $this->actingAs($miClase['docenteUser'])
            ->delete(route('portal.docente.classroom.chat.destroy', [$miClase['clase'], $mensajeAjeno]));

        $response->assertNotFound();
        $this->assertNotNull(ClassroomMessage::find($mensajeAjeno->id), 'El mensaje ajeno no debe eliminarse.');
    }
}
