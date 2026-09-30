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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perfilado previo a la migración de stack: admin/asistencia hacía 2 consultas
 * POR asignación desde la vista (375 consultas con ~180 asignaciones) y
 * admin/calificaciones/resumen declaraba funciones con nombre dentro del Blade
 * (fatal "Cannot redeclare" al renderizar dos veces en el mismo proceso, p. ej.
 * con Octane). Estos tests fijan ambos comportamientos.
 */
class AsistenciaIndexConsultasTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{tenant: Tenant, admin: User, sy: SchoolYear} */
    private function contexto(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Asistencia Index',
            'dominio'            => 'colegioasistidx' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        $sy    = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        return compact('tenant', 'admin', 'sy');
    }

    /** Crea un grupo con $n asignaciones y $alumnos matrículas activas; devuelve las asignaciones. */
    private function grupoConAsignaciones(SchoolYear $sy, int $n, int $alumnos): array
    {
        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado AI' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $matriculas = [];
        for ($i = 1; $i <= $alumnos; $i++) {
            $matriculas[] = Matricula::create([
                'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
                'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => $i, 'estado' => 'activa',
            ]);
        }

        $asignaciones = [];
        for ($i = 0; $i < $n; $i++) {
            $asignatura = Asignatura::create(['codigo' => 'AI' . random_int(10000, 99999), 'nombre' => 'Materia AI ' . $i, 'activo' => true]);
            $asignaciones[] = Asignacion::create([
                'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
                'docente_id' => null, 'activo' => true, 'tipo_evaluacion' => 'componentes',
            ]);
        }

        return ['asignaciones' => $asignaciones, 'matriculas' => $matriculas];
    }

    private function contarConsultas(User $admin, string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get($url)->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    }

    public function test_index_asistencia_no_hace_consultas_por_asignacion(): void
    {
        ['admin' => $admin, 'sy' => $sy] = $this->contexto();

        $this->grupoConAsignaciones($sy, 2, 2);
        // Calentamiento: la primera petición llena cachés (permisos, settings, tenant).
        $this->actingAs($admin)->get(route('admin.asistencia.index'))->assertOk();
        $pocas = $this->contarConsultas($admin, route('admin.asistencia.index'));

        $this->grupoConAsignaciones($sy, 10, 2);
        $muchas = $this->contarConsultas($admin, route('admin.asistencia.index'));

        // 6x más asignaciones: el número de consultas debe ser constante (antes crecía +2 por asignación).
        $this->assertSame($pocas, $muchas, "Consultas con 2 asignaciones: {$pocas}; con 12: {$muchas}");
    }

    public function test_index_asistencia_muestra_conteos_correctos(): void
    {
        ['admin' => $admin, 'sy' => $sy] = $this->contexto();

        $g = $this->grupoConAsignaciones($sy, 1, 3);
        // Un retirado no debe contar como estudiante activo.
        $retirado = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $g['matriculas'][0]->grupo_id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 9, 'estado' => 'retirada',
        ]);
        $this->assertNotNull($retirado->id);

        foreach ([['presente', 0], ['ausente', 1], ['tarde', 2]] as [$estado, $idx]) {
            Asistencia::create([
                'asignacion_id' => $g['asignaciones'][0]->id, 'matricula_id' => $g['matriculas'][$idx]->id,
                'fecha' => now()->format('Y-m-d'), 'estado' => $estado, 'registrado_por' => $admin->id,
            ]);
        }

        $html = $this->actingAs($admin)->get(route('admin.asistencia.index'))->assertOk()->getContent();

        $this->assertStringContainsString('3 est.', $html);
        $this->assertStringContainsString('Tomada hoy', $html);
        $this->assertStringNotContainsString('Pendiente hoy', $html);
        // Contadores P / A / T (antes el de tardanzas buscaba 'tardanza', valor que ya no existe en el enum: siempre 0).
        $this->assertMatchesRegularExpression('#stat-p"><i class="bi bi-check2"></i> 1</span>#', $html);
        $this->assertMatchesRegularExpression('#stat-a"><i class="bi bi-x"></i> 1</span>#', $html);
        $this->assertMatchesRegularExpression('#stat-t"><i class="bi bi-clock"></i> 1</span>#', $html);
    }

    public function test_resumen_calificaciones_se_renderiza_dos_veces_en_el_mismo_proceso(): void
    {
        ['admin' => $admin, 'sy' => $sy] = $this->contexto();
        $this->grupoConAsignaciones($sy, 1, 1);

        $url = route('admin.calificaciones.resumen');
        $this->actingAs($admin)->get($url)->assertOk();
        // Antes: "Cannot redeclare getNotaFinal()/notaClass()" en el segundo render.
        $this->actingAs($admin)->get($url)->assertOk();
    }
}
