<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\BoletinConfig;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Recomendación 20 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: bloqueo real
 * (opcional por institución) de impresión/exportación de boletines cuando el
 * estudiante tiene pagos vencidos. Antes de esta recomendación solo existía
 * una alerta informativa que nunca bloqueaba nada.
 *
 * Reglas acordadas con el usuario:
 *   - Apagado por defecto (BoletinConfig::bloquear_por_deuda).
 *   - Solo bloquea impresión/PDF/ZIP — ver() en pantalla nunca se bloquea.
 *   - Administrador/Director pueden forzar con ?forzar=1.
 */
class BoletinBloqueoDeudaTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Cache::flush();
    }

    private function crearEscenario(): array
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false,
        ]);

        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado D' . self::$nivel, 'nivel' => self::$nivel, 'orden' => self::$nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'BD' . $grupo->id, 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        $periodo = Periodo::create([
            'school_year_id' => $schoolYear->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);

        // tenant_id explícito: ver CalificacionRegressionTest::crearImportacion()
        // para la explicación de por qué (tenant se vincula recién en la
        // primera petición HTTP del test, no al crear los fixtures).
        $config = BoletinConfig::create([
            'tenant_id' => 1, 'school_year_id' => $schoolYear->id,
            'nombre_institucion' => '', 'nivel_educativo' => 'Nivel Secundario',
            'titulo_director' => 'Lic.', 'titulo_encargado' => 'Lic.',
            'mostrar_indicadores' => true, 'mostrar_asistencia' => true,
            'color_primario' => '#1e3a6e', 'color_secundario' => '#c0392b',
            'logo_ancho' => 68, 'logo_alto' => 58, 'tamano_fuente' => '9pt',
            'bloquear_por_deuda' => false,
        ]);

        return compact('schoolYear', 'grado', 'seccion', 'grupo', 'estudiante', 'matricula', 'asignatura', 'asignacion', 'periodo', 'config');
    }

    private function crearPagoVencido(Matricula $matricula, float $monto = 2500): Pago
    {
        return Pago::create([
            'tenant_id' => 1,
            'matricula_id' => $matricula->id,
            'concepto' => 'Colegiatura',
            'monto' => $monto,
            'fecha_vencimiento' => now()->subMonth()->toDateString(),
            'estado' => 'vencido',
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');
        return $user;
    }

    private function secretaria(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Secretaría');
        return $user;
    }

    // =========================================================================
    //  Apagado por defecto — no cambia el comportamiento existente
    // =========================================================================

    public function test_con_el_toggle_apagado_se_puede_imprimir_aunque_haya_deuda_vencida(): void
    {
        $e = $this->crearEscenario();
        $this->crearPagoVencido($e['matricula']);
        // bloquear_por_deuda queda false (default de crearEscenario).

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // =========================================================================
    //  Encendido — bloquea impresión, nunca la consulta en pantalla
    // =========================================================================

    public function test_con_el_toggle_encendido_bloquea_el_pdf_individual_a_un_rol_sin_bypass(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_con_el_toggle_encendido_bloquea_el_pdf_anual_a_un_rol_sin_bypass(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.pdf-anual', $e['matricula']))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_ver_en_pantalla_nunca_se_bloquea_aunque_haya_deuda_y_el_toggle_este_encendido(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.ver', [$e['matricula'], $e['periodo']]))
            ->assertOk();
    }

    public function test_sin_deuda_vencida_el_pdf_funciona_normal_aunque_el_toggle_este_encendido(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        // Sin ningún Pago vencido creado.

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertOk();
    }

    // =========================================================================
    //  Excepción: Administrador/Director pueden forzar con ?forzar=1
    // =========================================================================

    public function test_administrador_sin_forzar_tambien_queda_bloqueado(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->admin())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_administrador_con_forzar_puede_imprimir_pese_a_la_deuda(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->admin())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]) . '?forzar=1')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** Secretaría no tiene el rol de excepción — forzar=1 no le sirve de nada. */
    public function test_forzar_no_funciona_para_un_rol_sin_permiso_de_excepcion(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.pdf', [$e['matricula'], $e['periodo']]) . '?forzar=1')
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // =========================================================================
    //  ZIP masivo: omite a los estudiantes bloqueados en vez de bloquear todo el grupo
    // =========================================================================

    public function test_zip_omite_solo_al_estudiante_con_deuda_y_deja_pasar_al_resto(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        // Segundo estudiante del mismo grupo, sin deuda.
        $estudiante2 = Estudiante::factory()->create();
        Matricula::create([
            'school_year_id' => $e['schoolYear']->id, 'estudiante_id' => $estudiante2->id, 'grupo_id' => $e['grupo']->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 2, 'estado' => 'activa',
        ]);

        $this->actingAs($this->secretaria())
            ->get(route('admin.boletines.zip', ['grupo_id' => $e['grupo']->id, 'periodo_id' => $e['periodo']->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertSessionHas('warning');
    }

    public function test_zip_con_forzar_incluye_tambien_a_los_estudiantes_con_deuda(): void
    {
        $e = $this->crearEscenario();
        $e['config']->update(['bloquear_por_deuda' => true]);
        $this->crearPagoVencido($e['matricula']);

        $this->actingAs($this->admin())
            ->get(route('admin.boletines.zip', ['grupo_id' => $e['grupo']->id, 'periodo_id' => $e['periodo']->id]) . '&forzar=1')
            ->assertOk()
            ->assertSessionMissing('warning');
    }
}
