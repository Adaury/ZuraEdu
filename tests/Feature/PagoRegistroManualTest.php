<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Registro manual de un pago (PagoController::store). Un doble clic enviaba el formulario dos veces y creaba dos pagos idénticos
 * (si eran 'pagado', un ingreso duplicado). Ahora el formulario lleva un código de envío único que el servidor acepta una sola vez.
 */
class PagoRegistroManualTest extends TestCase
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

    private function matricula(): Matricula
    {
        $sy = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado RM', 'nivel' => 131, 'orden' => 131, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);

        return Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2026-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
    }

    private function datos(Matricula $m, array $extra = []): array
    {
        return array_merge([
            'matricula_id' => $m->id, 'concepto' => 'Inscripción', 'monto' => 2500, 'fecha_vencimiento' => '2026-09-30',
            'estado' => 'pagado', 'metodo_pago' => 'efectivo',
        ], $extra);
    }

    public function test_el_mismo_envio_dos_veces_registra_un_solo_pago(): void
    {
        $m = $this->matricula();
        $admin = $this->admin();
        $datos = $this->datos($m, ['token_envio' => (string) Str::uuid()]);

        $this->actingAs($admin)->post(route('admin.pagos.store'), $datos)->assertRedirect(route('admin.pagos.index'))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.pagos.store'), $datos)->assertRedirect(route('admin.pagos.index'))->assertSessionHas('warning');

        $this->assertSame(1, Pago::where('matricula_id', $m->id)->count(), 'el doble clic no duplica el ingreso');
    }

    public function test_dos_pagos_iguales_con_codigos_distintos_si_se_registran(): void
    {
        $m = $this->matricula();
        $admin = $this->admin();

        // Dos pagos legítimos idénticos (p. ej. dos abonos del mismo monto): cada formulario trae su propio código.
        $this->actingAs($admin)->post(route('admin.pagos.store'), $this->datos($m, ['token_envio' => (string) Str::uuid()]));
        $this->actingAs($admin)->post(route('admin.pagos.store'), $this->datos($m, ['token_envio' => (string) Str::uuid()]));

        $this->assertSame(2, Pago::where('matricula_id', $m->id)->count());
    }

    public function test_sin_codigo_de_envio_funciona_como_antes(): void
    {
        $m = $this->matricula();

        $this->actingAs($this->admin())->post(route('admin.pagos.store'), $this->datos($m))->assertSessionHas('success');

        $this->assertSame(1, Pago::where('matricula_id', $m->id)->count());
    }

    public function test_un_error_de_validacion_no_quema_el_codigo(): void
    {
        $m = $this->matricula();
        $admin = $this->admin();
        $token = (string) Str::uuid();

        $this->actingAs($admin)->post(route('admin.pagos.store'), $this->datos($m, ['monto' => 0, 'token_envio' => $token]))
            ->assertSessionHasErrors('monto');
        $this->assertSame(0, Pago::count());

        // El usuario corrige el monto y reenvía el mismo formulario (mismo código): debe registrarse.
        $this->actingAs($admin)->post(route('admin.pagos.store'), $this->datos($m, ['monto' => 2500, 'token_envio' => $token]))
            ->assertSessionHas('success');
        $this->assertSame(1, Pago::count());
    }

    public function test_un_codigo_que_no_es_uuid_se_rechaza(): void
    {
        $m = $this->matricula();

        $this->actingAs($this->admin())->post(route('admin.pagos.store'), $this->datos($m, ['token_envio' => 'no-es-un-uuid']))
            ->assertSessionHasErrors('token_envio');

        $this->assertSame(0, Pago::count());
    }

    public function test_el_formulario_de_nuevo_pago_incluye_un_codigo_distinto_en_cada_carga(): void
    {
        $this->matricula();
        $admin = $this->admin();

        $leer = fn () => preg_match('/name="token_envio" value="([0-9a-f-]{36})"/', $this->actingAs($admin)->get(route('admin.pagos.create'))->getContent(), $x) ? $x[1] : null;

        $a = $leer();
        $b = $leer();
        $this->assertNotNull($a);
        $this->assertNotSame($a, $b);
    }
}
