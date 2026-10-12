<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ArchivoEntrega;
use App\Models\ArchivoMaterial;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Administración de aulas: cada alta, cambio, desactivación y borrado queda auditado; no se
 * puede mover un aula con entregas a otra asignación (las notas irían a otra materia); y al
 * borrarla se limpian sus archivos del disco.
 */
class ZuraClassAdminAulasTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $sy;
    private Grupo $grupo;
    private Docente $docente;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-AdmAula', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado AA', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $this->grupo = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $this->docente = Docente::factory()->create();

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('Administrador');
    }

    private function asignacion(string $codigo): Asignacion
    {
        $asignatura = Asignatura::create(['codigo' => $codigo . random_int(100, 999), 'nombre' => 'Materia ' . $codigo, 'area' => 'academica', 'activo' => true]);

        return Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $this->grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
    }

    private function aulaConEntrega(Asignacion $asig): array
    {
        $clase = ClaseVirtual::create(['asignacion_id' => $asig->id, 'nombre' => 'Aula con notas', 'activo' => true]);
        $material = MaterialClase::create([
            'clase_virtual_id' => $clase->id, 'titulo' => 'Tarea', 'tipo' => 'tarea', 'puntos' => 10, 'publicado' => true,
        ]);
        $est = Estudiante::factory()->create();
        $mat = Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $this->grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $entrega = EntregaClassroom::create([
            'material_id' => $material->id, 'matricula_id' => $mat->id, 'estado' => 'calificado',
            'calificacion' => 9, 'contenido' => 'x', 'fecha_entrega' => now(),
        ]);

        return compact('clase', 'material', 'entrega');
    }

    public function test_crear_actualizar_desactivar_y_eliminar_quedan_auditados(): void
    {
        $asig = $this->asignacion('AA1');

        $this->actingAs($this->admin)->post(route('admin.classroom.store'), [
            'asignacion_id' => $asig->id, 'nombre' => 'Aula nueva', 'portada_color' => '#1e3a6e',
        ])->assertRedirect();
        $clase = ClaseVirtual::where('nombre', 'Aula nueva')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', ['accion' => 'classroom.creada', 'modelo_id' => $clase->id]);

        $this->actingAs($this->admin)->put(route('admin.classroom.update', $clase), [
            'asignacion_id' => $asig->id, 'nombre' => 'Aula renombrada', 'portada_color' => '#1e3a6e', 'activo' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['accion' => 'classroom.actualizada', 'modelo_id' => $clase->id]);

        $this->actingAs($this->admin)->patch(route('admin.classroom.toggle-activo', $clase))->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['accion' => 'classroom.estado_cambiado', 'modelo_id' => $clase->id]);

        $this->actingAs($this->admin)->delete(route('admin.classroom.destroy', $clase))->assertRedirect();
        $log = ActivityLog::where('accion', 'classroom.eliminada')->where('modelo_id', $clase->id)->firstOrFail();
        $this->assertStringContainsString('Aula renombrada', $log->descripcion);
        $this->assertNull(ClaseVirtual::find($clase->id));
    }

    public function test_no_se_mueve_un_aula_con_entregas_a_otra_asignacion(): void
    {
        $asig  = $this->asignacion('AA2');
        $otra  = $this->asignacion('AA3');
        $datos = $this->aulaConEntrega($asig);

        $this->actingAs($this->admin)->put(route('admin.classroom.update', $datos['clase']), [
            'asignacion_id' => $otra->id, 'nombre' => 'Aula con notas', 'portada_color' => '#1e3a6e', 'activo' => 1,
        ])->assertSessionHasErrors('asignacion_id');

        $this->assertSame($asig->id, (int) $datos['clase']->fresh()->asignacion_id);

        // Sin cambiar de asignación sí se puede editar
        $this->actingAs($this->admin)->put(route('admin.classroom.update', $datos['clase']), [
            'asignacion_id' => $asig->id, 'nombre' => 'Solo nombre', 'portada_color' => '#1e3a6e', 'activo' => 1,
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('Solo nombre', $datos['clase']->fresh()->nombre);
    }

    public function test_eliminar_un_aula_borra_sus_archivos_del_disco(): void
    {
        Storage::fake('public');
        $datos = $this->aulaConEntrega($this->asignacion('AA4'));

        Storage::disk('public')->put('classroom/x/material.pdf', 'a');
        Storage::disk('public')->put('entregas/x/entrega.pdf', 'b');
        ArchivoMaterial::create(['material_id' => $datos['material']->id, 'nombre_original' => 'material.pdf', 'ruta' => 'classroom/x/material.pdf', 'tipo_mime' => 'application/pdf']);
        ArchivoEntrega::create(['entrega_id' => $datos['entrega']->id, 'nombre_original' => 'entrega.pdf', 'ruta' => 'entregas/x/entrega.pdf', 'tipo_mime' => 'application/pdf', 'tamanio' => 1]);

        $this->actingAs($this->admin)->delete(route('admin.classroom.destroy', $datos['clase']))->assertRedirect();

        Storage::disk('public')->assertMissing('classroom/x/material.pdf');
        Storage::disk('public')->assertMissing('entregas/x/entrega.pdf');
        $this->assertDatabaseHas('activity_logs', ['accion' => 'classroom.eliminada']);
    }
}
