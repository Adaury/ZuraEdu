<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\PuntoEstudiante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Gamificación: los puntos automáticos se otorgan una sola vez por matrícula (no por día),
 * "sin faltas" mira el año escolar y exige datos suficientes, las notas sin publicar no
 * cuentan, un admin no toca puntos de otro centro, quitar puntos revoca las insignias por
 * acumulado, todo queda auditado y la escritura respeta el módulo.
 */
class GamificacionIntegridadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function tenant(string $nombre, bool $conGamificacion = true): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => 'gm' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        if ($conGamificacion) {
            $tenant->features()->create(['feature' => 'gamificacion', 'activo' => true]);
        }
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    /** @return array{sy: SchoolYear, grupo: Grupo, asig: Asignacion, admin: User} */
    private function centro(Tenant $tenant): array
    {
        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-G', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado GM' . random_int(1, 99999), 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $docente = Docente::factory()->create();
        $asig    = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => 'GM' . random_int(10000, 99999), 'nombre' => 'Materia', 'area' => 'academica', 'activo' => true])->id,
            'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        return compact('sy', 'grupo', 'asig', 'admin');
    }

    private function matricular(array $c, int $orden = 1): Matricula
    {
        return Matricula::create([
            'school_year_id' => $c['sy']->id, 'estudiante_id' => Estudiante::factory()->create()->id, 'grupo_id' => $c['grupo']->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => 'activa',
        ]);
    }

    private function asistencias(Matricula $m, Asignacion $asig, int $n, string $estado = 'presente'): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= $n; $i++) {
            Asistencia::create([
                'matricula_id' => $m->id, 'asignacion_id' => $asig->id, 'fecha' => now()->subDays($i)->toDateString(),
                'estado' => $estado, 'registrado_por' => $user->id,
            ]);
        }
    }

    private function nota(Matricula $m, Asignacion $asig, SchoolYear $sy, float $nota, bool $publicado = true): void
    {
        $c = CalificacionAcademica::create([
            'matricula_id' => $m->id, 'asignacion_id' => $asig->id, 'school_year_id' => $sy->id, 'nota_final' => $nota,
        ]);
        $c->forceFill(['publicado' => $publicado])->save();
    }

    private function generar(array $c)
    {
        return $this->actingAs($c['admin'])->post(route('admin.gamificacion.generar-puntos'), ['grupo_id' => $c['grupo']->id]);
    }

    private function total(Matricula $m): int
    {
        return (int) PuntoEstudiante::where('matricula_id', $m->id)->sum('puntos');
    }

    public function test_generar_puntos_otro_dia_no_vuelve_a_dar_los_mismos_puntos(): void
    {
        $c = $this->centro($this->tenant('Colegio Gami A'));
        $m = $this->matricular($c);
        $this->asistencias($m, $c['asig'], 25);
        $this->nota($m, $c['asig'], $c['sy'], 95);

        Carbon::setTestNow(Carbon::parse('2026-03-02 10:00:00'));
        $this->generar($c)->assertRedirect();
        $primero = $this->total($m);
        $this->assertSame(110, $primero); // 50 promedio + 40 asistencia + 20 sin faltas

        Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));
        $this->generar($c)->assertRedirect();
        Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
        $this->generar($c)->assertRedirect();

        $this->assertSame($primero, $this->total($m), 'Pulsar el botón otro día no debe volver a sumar');
        $this->assertFalse(InsigniaEstudiante::where('matricula_id', $m->id)->where('tipo', 'quinientos_puntos')->exists());
        Carbon::setTestNow();
    }

    public function test_sin_faltas_mira_el_ano_escolar_y_pide_datos_suficientes(): void
    {
        $c = $this->centro($this->tenant('Colegio Gami B'));

        // Con una falta de un año anterior: SÍ cuenta "sin faltas" (antes la falta eterna lo impedía)
        $mViejaFalta = $this->matricular($c, 1);
        $this->asistencias($mViejaFalta, $c['asig'], 25);
        FaltaDisciplinaria::create([
            'estudiante_id' => $mViejaFalta->estudiante_id, 'tipo' => 'falta_leve', 'descripcion' => 'x', 'fecha' => '2023-03-01',
        ]);

        // Con una falta dentro del año: NO cuenta
        $mConFalta = $this->matricular($c, 2);
        $this->asistencias($mConFalta, $c['asig'], 25);
        FaltaDisciplinaria::create([
            'estudiante_id' => $mConFalta->estudiante_id, 'tipo' => 'falta_leve', 'descripcion' => 'x', 'fecha' => '2025-10-10',
        ]);

        // Con 3 días de datos: no hay base para premiar asistencia ni conducta
        $mPocosDatos = $this->matricular($c, 3);
        $this->asistencias($mPocosDatos, $c['asig'], 3);

        $this->generar($c)->assertRedirect();

        $this->assertTrue(PuntoEstudiante::where('matricula_id', $mViejaFalta->id)->where('concepto', 'Sin faltas disciplinarias')->exists());
        $this->assertFalse(PuntoEstudiante::where('matricula_id', $mConFalta->id)->where('concepto', 'Sin faltas disciplinarias')->exists());
        $this->assertSame(0, $this->total($mPocosDatos));
        $this->assertFalse(InsigniaEstudiante::where('matricula_id', $mPocosDatos->id)->where('tipo', 'asistencia_perfecta')->exists());
    }

    public function test_tarde_y_excusa_cuentan_para_la_asistencia_y_las_notas_sin_publicar_no(): void
    {
        $c = $this->centro($this->tenant('Colegio Gami C'));
        $m = $this->matricular($c);
        $this->asistencias($m, $c['asig'], 13, 'presente');
        // desplazar fechas para no repetir las ya usadas
        $user = User::factory()->create();
        foreach (range(14, 25) as $i) {
            Asistencia::create([
                'matricula_id' => $m->id, 'asignacion_id' => $c['asig']->id, 'fecha' => now()->subDays($i)->toDateString(),
                'estado' => $i % 2 ? 'tarde' : 'excusa', 'registrado_por' => $user->id,
            ]);
        }
        $this->nota($m, $c['asig'], $c['sy'], 97, publicado: false);

        $this->generar($c)->assertRedirect();

        $conceptos = PuntoEstudiante::where('matricula_id', $m->id)->pluck('concepto')->all();
        $this->assertContains('Asistencia ≥ 95%', $conceptos, 'tarde y excusa son asistencia');
        $this->assertNotContains('Promedio académico ≥ 90', $conceptos, 'una nota sin publicar no da puntos');
    }

    public function test_un_admin_no_asigna_ni_elimina_puntos_de_otro_centro(): void
    {
        $a = $this->centro($this->tenant('Colegio Gami D1'));
        $tenantA = app('tenant');

        $tenantB = $this->tenant('Colegio Gami D2');
        $b  = $this->centro($tenantB);
        $mB = $this->matricular($b);
        $puntoB = PuntoEstudiante::create(['matricula_id' => $mB->id, 'concepto' => 'x', 'categoria' => 'extra', 'puntos' => 10, 'fecha' => today()]);

        app()->instance('tenant', $tenantA);

        $this->actingAs($a['admin'])->post(route('admin.gamificacion.asignar-puntos'), [
            'matricula_id' => $mB->id, 'concepto' => 'Intruso', 'categoria' => 'extra', 'puntos' => 50, 'fecha' => today()->toDateString(),
        ])->assertSessionHasErrors('matricula_id');   // la regla exists: ya filtra por centro; findOrFail es la 2.ª barrera

        $this->actingAs($a['admin'])->delete(route('admin.gamificacion.puntos.destroy', $puntoB))->assertNotFound();

        $this->assertSame(1, PuntoEstudiante::where('matricula_id', $mB->id)->count());
        $this->assertSame(10, $this->total($mB));
    }

    public function test_quitar_puntos_revoca_la_insignia_por_acumulado_y_queda_auditado(): void
    {
        $c = $this->centro($this->tenant('Colegio Gami E'));
        $m = $this->matricular($c);

        $this->actingAs($c['admin'])->post(route('admin.gamificacion.asignar-puntos'), [
            'matricula_id' => $m->id, 'concepto' => 'Proyecto destacado', 'categoria' => 'extra', 'puntos' => 120, 'fecha' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertTrue(InsigniaEstudiante::where('matricula_id', $m->id)->where('tipo', 'cien_puntos')->exists());
        $this->assertDatabaseHas('activity_logs', ['accion' => 'gamificacion.puntos_asignados', 'user_id' => $c['admin']->id]);

        $punto = PuntoEstudiante::where('matricula_id', $m->id)->firstOrFail();
        $this->actingAs($c['admin'])->delete(route('admin.gamificacion.puntos.destroy', $punto))->assertRedirect();

        $this->assertFalse(InsigniaEstudiante::where('matricula_id', $m->id)->where('tipo', 'cien_puntos')->exists());
        $this->assertDatabaseHas('activity_logs', ['accion' => 'gamificacion.punto_eliminado', 'user_id' => $c['admin']->id]);
    }

    public function test_la_api_no_asigna_puntos_si_el_modulo_no_esta_en_el_plan(): void
    {
        $c = $this->centro($this->tenant('Colegio Gami F', conGamificacion: false));
        $m = $this->matricular($c);

        $docente = Docente::where('id', $c['asig']->docente_id)->firstOrFail();
        $docente->user->assignRole('Docente');
        $docente->user->update(['activo' => true, 'tenant_id' => app('tenant')->id]);

        Sanctum::actingAs($docente->user);
        $this->postJson("/api/v1/gamificacion/grupo/{$c['asig']->id}/asignar", [
            'matricula_id' => $m->id, 'concepto' => 'Participó', 'categoria' => 'participacion', 'puntos' => 5, 'fecha' => today()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, PuntoEstudiante::count());
    }
}
