<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\PuntoEstudiante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** gamificacion:consolidar-duplicados — simulación por defecto, deja el más antiguo, no toca manuales ni otros centros. */
class ConsolidarPuntosGamificacionTest extends TestCase
{
    use RefreshDatabase;

    private function centro(string $nombre): array
    {
        $t = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => 'cp' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-CP', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado CP' . random_int(1, 99999), 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $mat     = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return [$t, $mat];
    }

    private function punto(Matricula $m, string $concepto, int $pts, string $fecha, string $cat = 'academico'): PuntoEstudiante
    {
        return PuntoEstudiante::create(['matricula_id' => $m->id, 'concepto' => $concepto, 'categoria' => $cat, 'puntos' => $pts, 'fecha' => $fecha]);
    }

    public function test_simulacion_no_borra_y_apply_deja_el_mas_antiguo_sin_tocar_manuales(): void
    {
        [, $mat] = $this->centro('Centro CP A');

        $primero = $this->punto($mat, 'Promedio académico ≥ 90', 50, '2026-03-01');
        $this->punto($mat, 'Promedio académico ≥ 90', 50, '2026-03-02');
        $this->punto($mat, 'Promedio académico ≥ 90', 50, '2026-03-03');
        $this->punto($mat, 'Asistencia ≥ 95%', 40, '2026-03-01', 'asistencia');
        $this->punto($mat, 'Asistencia ≥ 95%', 40, '2026-03-02', 'asistencia');
        $manual1 = $this->punto($mat, 'Participó en feria', 10, '2026-03-02', 'participacion');
        $manual2 = $this->punto($mat, 'Participó en feria', 10, '2026-03-03', 'participacion');
        InsigniaEstudiante::create(['matricula_id' => $mat->id, 'tipo' => 'cien_puntos', 'fecha_obtencion' => '2026-03-03']);
        app()->forgetInstance('tenant');

        // Simulación: nada cambia
        $this->artisan('gamificacion:consolidar-duplicados')->assertSuccessful();
        $this->assertSame(7, PuntoEstudiante::count());

        // Aplicar: quedan 1 de promedio, 1 de asistencia y los 2 manuales idénticos (no son automáticos)
        $this->artisan('gamificacion:consolidar-duplicados', ['--apply' => true])->assertSuccessful();
        app()->instance('tenant', Tenant::first());

        $this->assertSame(4, PuntoEstudiante::count());
        $this->assertTrue(PuntoEstudiante::where('id', $primero->id)->exists(), 'Se conserva el más antiguo');
        $this->assertTrue(PuntoEstudiante::where('id', $manual1->id)->exists());
        $this->assertTrue(PuntoEstudiante::where('id', $manual2->id)->exists());

        // Total = 50 + 40 + 10 + 10 = 110 → conserva la insignia de 100 puntos
        $this->assertSame(110, (int) PuntoEstudiante::sum('puntos'));
        $this->assertTrue(InsigniaEstudiante::where('matricula_id', $mat->id)->where('tipo', 'cien_puntos')->exists());
    }

    public function test_apply_revoca_la_insignia_si_el_total_baja_del_umbral(): void
    {
        [, $mat] = $this->centro('Centro CP B');

        $this->punto($mat, 'Sin faltas disciplinarias', 20, '2026-03-01', 'conducta');
        foreach (range(2, 6) as $d) {
            $this->punto($mat, 'Sin faltas disciplinarias', 20, "2026-03-0{$d}", 'conducta');
        }
        // 6 × 20 = 120 → había "comprado" la insignia de 100
        InsigniaEstudiante::create(['matricula_id' => $mat->id, 'tipo' => 'cien_puntos', 'fecha_obtencion' => '2026-03-06']);
        app()->forgetInstance('tenant');

        $this->artisan('gamificacion:consolidar-duplicados', ['--apply' => true])->assertSuccessful();
        app()->instance('tenant', Tenant::first());

        $this->assertSame(20, (int) PuntoEstudiante::sum('puntos'));
        $this->assertFalse(InsigniaEstudiante::where('matricula_id', $mat->id)->where('tipo', 'cien_puntos')->exists());
    }
}
