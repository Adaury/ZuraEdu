<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaCafeteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Saldo de cafetería. Hallado con peticiones simultáneas reales sobre una copia de la base:
 *  - 20 ventas de 50 a la vez con saldo 100 se aprobaron TODAS (RD$1.000 gastados con RD$100);
 *  - 20 recargas de 10 a la vez dejaron el saldo visible en 10 en vez de 200 (cada una partía de una lectura vieja).
 * Ahora cada movimiento bloquea al estudiante y calcula el saldo dentro del bloqueo; el saldo se lee por id, no por created_at.
 */
class CafeteriaSaldoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('Administrador');

        return $u;
    }

    private function mover(int $estudianteId, string $tipo, float $monto): ?VentaCafeteria
    {
        return VentaCafeteria::registrarMovimiento($estudianteId, $tipo, $monto, "prueba {$tipo}", null, null);
    }

    public function test_recarga_venta_y_ajuste_encadenan_el_saldo(): void
    {
        $e = Estudiante::factory()->create();

        $r = $this->mover($e->id, 'recarga', 100);
        $v = $this->mover($e->id, 'venta', 30);
        $a = $this->mover($e->id, 'ajuste', -20);   // ajuste negativo resta
        $b = $this->mover($e->id, 'ajuste', 5.5);

        $this->assertEquals([0, 100], [(float) $r->saldo_anterior, (float) $r->saldo_nuevo]);
        $this->assertEquals([100, 70], [(float) $v->saldo_anterior, (float) $v->saldo_nuevo]);
        $this->assertEquals([70, 50], [(float) $a->saldo_anterior, (float) $a->saldo_nuevo]);
        $this->assertEquals(20.0, (float) $a->monto, 'el ajuste guarda el monto en positivo');
        $this->assertEquals([50, 55.5], [(float) $b->saldo_anterior, (float) $b->saldo_nuevo]);
        $this->assertEquals(55.5, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_una_venta_sin_saldo_suficiente_no_se_registra(): void
    {
        $e = Estudiante::factory()->create();
        $this->mover($e->id, 'recarga', 100);

        $this->assertNull($this->mover($e->id, 'venta', 100.01));
        $this->assertSame(1, VentaCafeteria::where('estudiante_id', $e->id)->count(), 'no se crea ninguna fila');
        $this->assertEquals(100.0, VentaCafeteria::saldoEstudiante($e->id));

        $exacta = $this->mover($e->id, 'venta', 100);   // el saldo exacto sí alcanza y deja 0
        $this->assertNotNull($exacta);
        $this->assertEquals(0.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_el_saldo_se_lee_por_id_aunque_dos_movimientos_tengan_el_mismo_created_at(): void
    {
        $e = Estudiante::factory()->create();
        $mismoSegundo = now()->startOfSecond();
        $fila = fn (float $anterior, float $nuevo) => DB::table('ventas_cafeteria')->insert([
            'tenant_id' => app()->bound('tenant') ? app('tenant')->id : 1, 'estudiante_id' => $e->id, 'tipo' => 'venta', 'monto' => 10,
            'saldo_anterior' => $anterior, 'saldo_nuevo' => $nuevo, 'created_at' => $mismoSegundo, 'updated_at' => $mismoSegundo,
        ]);
        $fila(100, 90);
        $fila(90, 80);   // el último por id; antes el empate de created_at dejaba el saldo a elección del motor

        $this->assertEquals(80.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_bloquea_la_fila_del_estudiante_antes_de_leer_el_saldo(): void
    {
        $e = Estudiante::factory()->create();
        $consultas = [];
        DB::listen(function ($q) use (&$consultas) {
            $consultas[] = strtolower($q->sql);
        });

        $this->mover($e->id, 'recarga', 10);

        $posBloqueo = collect($consultas)->search(fn ($s) => str_contains($s, 'from `estudiantes`') && str_contains($s, 'for update'));
        $posSaldo   = collect($consultas)->search(fn ($s) => str_contains($s, 'from `ventas_cafeteria`') && str_contains($s, 'order by'));

        $this->assertNotFalse($posBloqueo, 'debe bloquear al estudiante (FOR UPDATE)');
        $this->assertNotFalse($posSaldo);
        $this->assertLessThan($posSaldo, $posBloqueo, 'el bloqueo va ANTES de leer el saldo: si no, la lectura es de antes del bloqueo y el saldo es viejo');
    }

    public function test_un_estudiante_de_otro_colegio_no_recibe_movimientos(): void
    {
        $a = Tenant::create(['nombre_institucion' => 'A', 'dominio' => 'a' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        $b = Tenant::create(['nombre_institucion' => 'B', 'dominio' => 'b' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $b);
        $ajeno = Estudiante::factory()->create();

        app()->instance('tenant', $a);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $this->mover($ajeno->id, 'recarga', 50);
        } finally {
            app()->instance('tenant', $b);
            $this->assertSame(0, VentaCafeteria::where('estudiante_id', $ajeno->id)->count());
        }
    }

    // ── a través de la pantalla ─────────────────────────────────────────────

    public function test_la_venta_sin_saldo_da_error_y_no_registra_nada(): void
    {
        $e = Estudiante::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.cafeteria.ventas.store'), ['estudiante_id' => $e->id, 'monto' => 25])
            ->assertSessionHas('error');

        $this->assertSame(0, VentaCafeteria::where('estudiante_id', $e->id)->count());
    }

    public function test_recarga_y_venta_desde_la_pantalla_actualizan_el_saldo(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.cafeteria.recargas.store'), ['estudiante_id' => $e->id, 'monto' => 200])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.cafeteria.ventas.store'), ['estudiante_id' => $e->id, 'monto' => 75, 'descripcion' => 'Almuerzo'])->assertSessionHas('success');

        $this->assertEquals(125.0, VentaCafeteria::saldoEstudiante($e->id));
        $this->assertSame($admin->id, VentaCafeteria::where('estudiante_id', $e->id)->where('tipo', 'venta')->value('created_by_id'));
    }
}
