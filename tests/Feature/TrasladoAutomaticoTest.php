<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CierreAno\TrasladoAutomaticoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Traslado automático de fin de año: promovidos → grado siguiente, no promovidos → mismo grado y sección, último grado egresa,
 * sin decisión no se mueve; respeta capacidad, continúa la numeración de lista y no duplica al repetirse.
 */
class TrasladoAutomaticoTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $base;
    private SchoolYear $nuevo;
    /** @var array<int,Grado> por nivel */
    private array $grados = [];
    /** @var array<string,Grupo> "año-nivel-seccion" */
    private array $grupos = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
        $this->montarAnios();
    }

    private function montarAnios(int $capacidad = 35): void
    {
        $this->base  = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false]);
        $this->nuevo = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        foreach ([1, 2, 3] as $n) {
            $this->grados[$n] = Grado::create(['nombre' => "Grado {$n}", 'nivel' => $n, 'orden' => $n, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        }
        foreach (['A', 'B'] as $i => $s) {
            $sec = Seccion::firstOrCreate(['nombre' => $s], ['orden' => $i + 1]);
            foreach ([$this->base, $this->nuevo] as $anio) {
                foreach ($this->grados as $n => $g) {
                    $this->grupos["{$anio->id}-{$n}-{$s}"] = Grupo::create([
                        'school_year_id' => $anio->id, 'grado_id' => $g->id, 'seccion_id' => $sec->id, 'activo' => true, 'capacidad' => $capacidad,
                    ]);
                }
            }
        }
    }

    private function grupo(SchoolYear $a, int $nivel, string $sec): Grupo
    {
        return $this->grupos["{$a->id}-{$nivel}-{$sec}"];
    }

    private function matricular(SchoolYear $a, int $nivel, string $sec, string $estado, int $orden = 1): Matricula
    {
        return Matricula::create([
            'school_year_id' => $a->id, 'estudiante_id' => Estudiante::factory()->create()->id, 'grupo_id' => $this->grupo($a, $nivel, $sec)->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => $estado,
        ]);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('Administrador');

        return $u;
    }

    private function trasladar(?User $u = null)
    {
        return $this->actingAs($u ?? $this->admin())->post(route('admin.cierre-ano.trasladar-automatico'), [
            'ano_base_id' => $this->base->id, 'ano_nuevo_id' => $this->nuevo->id,
        ]);
    }

    /** Dónde quedó el estudiante en el año nuevo: [nivel, sección] o null. */
    private function destino(Matricula $origen): ?array
    {
        $m = Matricula::where('school_year_id', $this->nuevo->id)->where('estudiante_id', $origen->estudiante_id)->with('grupo.grado', 'grupo.seccion')->first();

        return $m ? [$m->grupo->grado->nivel, $m->grupo->seccion->nombre] : null;
    }

    public function test_promovidos_avanzan_repetidores_se_quedan_y_el_ultimo_grado_egresa(): void
    {
        $avanza1  = $this->matricular($this->base, 1, 'A', 'promovida');
        $avanza2B = $this->matricular($this->base, 2, 'B', 'promovida');
        $repite2  = $this->matricular($this->base, 2, 'A', 'no_promovida');
        $repite3B = $this->matricular($this->base, 3, 'B', 'no_promovida');
        $egresa   = $this->matricular($this->base, 3, 'A', 'promovida');
        $pendiente = $this->matricular($this->base, 1, 'B', 'activa');

        $this->trasladar()->assertRedirect(route('admin.cierre-ano.index'))->assertSessionHas('success');

        $this->assertSame([2, 'A'], $this->destino($avanza1));
        $this->assertSame([3, 'B'], $this->destino($avanza2B));
        $this->assertSame([2, 'A'], $this->destino($repite2), 'el no promovido repite el MISMO grado y sección');
        $this->assertSame([3, 'B'], $this->destino($repite3B), 'también repite el último grado');
        $this->assertNull($this->destino($egresa), 'el último grado promovido egresa: no se matricula');
        $this->assertNull($this->destino($pendiente), 'sin decisión de promoción no se mueve');
        $this->assertSame(4, Matricula::where('school_year_id', $this->nuevo->id)->count());
        $this->assertSame('activa', Matricula::where('school_year_id', $this->nuevo->id)->first()->estado);
    }

    public function test_si_la_seccion_destino_esta_llena_va_al_otro_grupo_del_mismo_grado(): void
    {
        $this->grupo($this->nuevo, 2, 'A')->update(['capacidad' => 1]);
        $a = $this->matricular($this->base, 1, 'A', 'promovida', 1);
        $b = $this->matricular($this->base, 1, 'A', 'promovida', 2);

        $this->trasladar();

        $this->assertSame([2, 'A'], $this->destino($a));
        $this->assertSame([2, 'B'], $this->destino($b), 'la sección A del grado 2 tiene cupo 1: el segundo va a B');
    }

    public function test_si_todo_esta_lleno_se_matricula_igual_en_su_seccion_y_se_avisa(): void
    {
        foreach (['A', 'B'] as $s) {
            $this->grupo($this->nuevo, 2, $s)->update(['capacidad' => 1]);
        }
        $m = [];
        foreach ([1, 2, 3] as $o) {
            $m[] = $this->matricular($this->base, 1, 'A', 'promovida', $o);
        }

        $plan = (new TrasladoAutomaticoService())->planificar($this->base, $this->nuevo);
        $this->assertSame(3, $plan['avanzan']);
        $this->assertSame(1, $plan['exceden_cupo']);

        $this->trasladar()->assertSessionHas('warning');
        $this->assertSame(3, Matricula::where('school_year_id', $this->nuevo->id)->count());
    }

    public function test_la_numeracion_de_lista_continua_despues_de_los_que_ya_estan(): void
    {
        // Ya hay alguien en 2º A del año nuevo con el número 5
        $this->matricular($this->nuevo, 2, 'A', 'activa', 5);
        $a = $this->matricular($this->base, 1, 'A', 'promovida');
        $b = $this->matricular($this->base, 1, 'A', 'promovida', 2);

        $this->trasladar();

        $ordenes = Matricula::where('school_year_id', $this->nuevo->id)->where('grupo_id', $this->grupo($this->nuevo, 2, 'A')->id)->orderBy('numero_orden')->pluck('numero_orden')->all();
        $this->assertSame([5, 6, 7], $ordenes);
    }

    public function test_ejecutarlo_dos_veces_no_duplica(): void
    {
        $this->matricular($this->base, 1, 'A', 'promovida');
        $this->matricular($this->base, 2, 'A', 'no_promovida');

        $admin = $this->admin();
        $this->trasladar($admin);
        $this->trasladar($admin);

        $this->assertSame(2, Matricula::where('school_year_id', $this->nuevo->id)->count());
    }

    public function test_la_simulacion_no_escribe_nada(): void
    {
        $this->matricular($this->base, 1, 'A', 'promovida');

        $plan = (new TrasladoAutomaticoService())->planificar($this->base, $this->nuevo);

        $this->assertSame(1, $plan['avanzan']);
        $this->assertSame(0, Matricula::where('school_year_id', $this->nuevo->id)->count());
    }

    public function test_no_se_traslada_si_el_ano_de_origen_sigue_activo(): void
    {
        $this->matricular($this->base, 1, 'A', 'promovida');
        $this->base->update(['activo' => true]);

        $this->trasladar()->assertSessionHas('error');

        $this->assertSame(0, Matricula::where('school_year_id', $this->nuevo->id)->count());
    }

    public function test_un_usuario_sin_rol_de_direccion_no_puede(): void
    {
        $this->matricular($this->base, 1, 'A', 'promovida');
        // Secretaría entra al panel admin pero no tiene acceso a Dirección (gate 'acceso-direccion'): 403.
        $secretaria = User::factory()->create(['activo' => true]);
        $secretaria->assignRole('Secretaría');
        $this->trasladar($secretaria)->assertForbidden();

        // A un docente EnsureAdminAccess lo manda a su portal antes de llegar al gate: no llega a ejecutar nada.
        $docente = User::factory()->create(['activo' => true]);
        $docente->assignRole('Docente');
        $this->trasladar($docente)->assertRedirect();

        $this->assertSame(0, Matricula::where('school_year_id', $this->nuevo->id)->count());
    }

    public function test_no_se_puede_apuntar_a_un_ano_de_otro_colegio(): void
    {
        $a = Tenant::create(['nombre_institucion' => 'Colegio A', 'dominio' => 'a' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        $b = Tenant::create(['nombre_institucion' => 'Colegio B', 'dominio' => 'b' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);

        app()->instance('tenant', $b);
        $anioAjeno = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false]);

        app()->instance('tenant', $a);
        $anioPropio = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.cierre-ano.trasladar-automatico'), [
            'ano_base_id' => $anioAjeno->id, 'ano_nuevo_id' => $anioPropio->id,
        ])->assertSessionHasErrors('ano_base_id');

        $this->assertSame(0, Matricula::where('school_year_id', $anioPropio->id)->count());
    }

    public function test_la_pantalla_de_traslado_muestra_la_vista_previa(): void
    {
        $this->matricular($this->base, 1, 'A', 'promovida');
        $this->matricular($this->base, 2, 'A', 'no_promovida');

        $this->actingAs($this->admin())
            ->get(route('admin.cierre-ano.trasladar', ['ano_nuevo' => $this->nuevo->id, 'ano_base' => $this->base->id]))
            ->assertOk()
            ->assertSee('Traslado automático')
            ->assertSee('repiten el mismo curso')
            ->assertSee('Trasladar automáticamente a 2 estudiante(s)');
    }
}
