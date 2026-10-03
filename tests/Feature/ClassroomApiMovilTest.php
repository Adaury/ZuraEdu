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
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Classroom en la API móvil (/api/v1/classroom): el docente publica, el estudiante y el padre ven solo lo publicado de SU grupo,
 * y nadie más puede ver ni modificar el aula.
 */
class ClassroomApiMovilTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;
    protected Docente $docente;
    protected Estudiante $estudiante;
    protected ClaseVirtual $clase;
    protected SchoolYear $sy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-Api', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grupo = $this->grupo('Grado Api');
        $this->docente = $this->docente();
        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $asig = Asignatura::create(['codigo' => 'API1', 'nombre' => 'Ciencias', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asig->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $this->clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula Ciencias', 'activo' => true]);
    }

    private function grupo(string $nombre): Grupo
    {
        $grado = Grado::create(['nombre' => $nombre, 'nivel' => 200 + (++self::$nivel), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);

        return Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);
    }

    private function docente(): Docente
    {
        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $d->user->update(['activo' => true]);

        return $d;
    }

    public function test_el_docente_publica_y_el_estudiante_solo_ve_lo_publicado(): void
    {
        Sanctum::actingAs($this->docente->user);
        $this->getJson(route('api.classroom.index'))->assertOk()->assertJsonFragment(['nombre' => 'Aula Ciencias']);

        $this->postJson(route('api.classroom.materiales.store', $this->clase), ['titulo' => 'Guía publicada', 'tipo' => 'material', 'publicado' => true])->assertCreated();
        $this->postJson(route('api.classroom.materiales.store', $this->clase), ['titulo' => 'Borrador oculto', 'tipo' => 'tarea', 'puntos' => 10, 'publicado' => false])->assertCreated();

        Sanctum::actingAs($this->estudiante->user);
        $this->getJson(route('api.classroom.index'))->assertOk()->assertJsonFragment(['nombre' => 'Aula Ciencias']);
        $r = $this->getJson(route('api.classroom.materiales', $this->clase))->assertOk();
        $r->assertJsonFragment(['titulo' => 'Guía publicada']);
        $this->assertStringNotContainsString('Borrador oculto', $r->getContent());

        // El docente publica el borrador con el interruptor y entonces el estudiante lo ve
        $borrador = MaterialClase::where('titulo', 'Borrador oculto')->firstOrFail();
        Sanctum::actingAs($this->docente->user);
        $this->patchJson(route('api.classroom.materiales.publicar', $borrador))->assertOk()->assertJson(['publicado' => true]);
        Sanctum::actingAs($this->estudiante->user);
        $this->getJson(route('api.classroom.materiales', $this->clase))->assertJsonFragment(['titulo' => 'Borrador oculto']);
    }

    public function test_un_estudiante_no_puede_publicar_ni_cambiar_el_estado_de_un_material(): void
    {
        $m = MaterialClase::create(['clase_virtual_id' => $this->clase->id, 'titulo' => 'Material', 'tipo' => 'material', 'publicado' => true]);
        Sanctum::actingAs($this->estudiante->user);

        $this->postJson(route('api.classroom.materiales.store', $this->clase), ['titulo' => 'Intruso', 'tipo' => 'material'])->assertForbidden();
        $this->patchJson(route('api.classroom.materiales.publicar', $m))->assertForbidden();
        $this->assertTrue((bool) $m->fresh()->publicado);
    }

    public function test_un_docente_ajeno_no_publica_en_el_aula_de_otro_ni_alterna_sus_materiales(): void
    {
        $m = MaterialClase::create(['clase_virtual_id' => $this->clase->id, 'titulo' => 'Material', 'tipo' => 'material', 'publicado' => true]);
        Sanctum::actingAs($this->docente()->user);

        $this->postJson(route('api.classroom.materiales.store', $this->clase), ['titulo' => 'Intruso', 'tipo' => 'material'])->assertForbidden();
        $this->patchJson(route('api.classroom.materiales.publicar', $m))->assertForbidden();
        $this->getJson(route('api.classroom.materiales', $this->clase))->assertForbidden();
        $this->assertTrue((bool) $m->fresh()->publicado);
        $this->assertSame(1, MaterialClase::where('clase_virtual_id', $this->clase->id)->count());
    }

    public function test_un_estudiante_de_otro_grupo_no_ve_los_materiales_del_aula(): void
    {
        $otro = Estudiante::factory()->create();
        $otro->user->assignRole('Estudiante');
        $otro->user->update(['activo' => true]);
        Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $otro->id, 'grupo_id' => $this->grupo('Grado Otro')->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        Sanctum::actingAs($otro->user);

        $this->getJson(route('api.classroom.materiales', $this->clase))->assertForbidden();
    }

    public function test_el_padre_ve_las_aulas_de_su_hijo_pero_no_las_de_otra_familia(): void
    {
        $mk = function (?Estudiante $hijo) {
            $u = User::factory()->create(['activo' => true, 'tenant_id' => $this->estudiante->user->tenant_id]);   // la API exige tenant en el usuario
            $u->assignRole('Representante');
            $rep = Representante::create(['user_id' => $u->id, 'cedula' => (string) random_int(100000000, 999999999), 'nombres' => 'Rep', 'apellidos' => 'Api' . random_int(1, 9999), 'telefono' => '8090000000']);
            if ($hijo) {
                $rep->estudiantes()->attach($hijo->id, ['es_principal' => true]);
            }

            return $u;
        };
        $padre = $mk($this->estudiante);
        $ajeno = $mk(null);

        Sanctum::actingAs($padre);
        $this->getJson(route('api.classroom.materiales', $this->clase))->assertOk();

        Sanctum::actingAs($ajeno);
        $this->getJson(route('api.classroom.materiales', $this->clase))->assertForbidden();
    }
}
