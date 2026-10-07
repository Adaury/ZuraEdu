<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hallazgo N3 (auditoría 2026-10-06): los promedios por área se calculaban con DB::table sin
 * tenant_id, solo por school_year_id. Si otro colegio tenía filas con el mismo school_year_id,
 * entraban en el promedio. Ahora cada consulta filtra por el colegio actual.
 */
class RendimientoAislamientoTenantTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $n): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $n, 'dominio' => strtolower(str_replace(' ', '', $n)) . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    private function nota(Tenant $t, int $syId, int $n, float $nota): void
    {
        $now = now();
        $asig = DB::table('asignaciones')->insertGetId([
            'tenant_id' => $t->id, 'school_year_id' => $syId, 'grupo_id' => 900 + $n, 'asignatura_id' => 900 + $n,
            'area' => 'academica', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $mat = DB::table('matriculas')->insertGetId([
            'tenant_id' => $t->id, 'school_year_id' => $syId, 'estudiante_id' => 900 + $n, 'grupo_id' => 900 + $n,
            'fecha_matricula' => '2025-08-15', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('calificaciones_academicas')->insert([
            'tenant_id' => $t->id, 'school_year_id' => $syId, 'asignacion_id' => $asig, 'matricula_id' => $mat,
            'nota_final' => $nota, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_promedio_por_area_no_mezcla_notas_de_otro_colegio(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $a = $this->tenant('Colegio Rend A');
        $b = $this->tenant('Colegio Rend B');
        app()->instance('tenant', $a);
        $sy = SchoolYear::create(['nombre' => '2026-R', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $this->nota($a, $sy->id, 1, 90);
            // Otro colegio con el MISMO school_year_id (colisión): no debe contar.
            $this->nota($b, $sy->id, 2, 10);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');

        $r = $this->actingAs($admin)->get(route('admin.rendimiento.porArea'))->assertOk();

        $academica = $r->viewData('academica');
        $this->assertSame(1, (int) $academica->total);
        $this->assertEquals(90.0, (float) $academica->promedio);
    }
}
