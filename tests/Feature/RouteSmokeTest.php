<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Red de seguridad para el upgrade de Laravel 10→13 (ver plan en
 * C:\Users\Usuario\.claude\plans\lovely-dazzling-allen.md, Fase 0).
 *
 * No valida lógica de negocio — recorre todas las rutas GET del panel admin
 * sin parámetros requeridos, con ~8 perfiles de rol representativos, y solo
 * verifica que ninguna devuelva 500. Es exactamente lo que atrapa un upgrade
 * de framework roto (firmas cambiadas, providers faltantes, deserialización
 * fallida) que 69 tests de negocio no cubren por construcción.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\DetectaAlpineHuerfano;

    /** Rutas con GET idempotente en apariencia pero con efecto secundario real —
     *  se excluyen del smoke para no ensuciar el estado entre iteraciones. */
    private const EXCLUIR = [
        'admin.demo.limpiar',
        'admin.horarios.generar',
        'admin.cierre-ano.ejecutar',
    ];

    private const ROLES = [
        'Administrador',
        'Secretaría',
        'Caja / Finanzas',
        'Coordinador Académico',
        'Registrador Académico',
        'Personal Administrativo',
        'Biblioteca',
        'Docente',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_rutas_admin_get_sin_parametros_no_devuelven_500(): void
    {
        $rutas = collect(Route::getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods())
                && str_starts_with($r->getName() ?? '', 'admin.')
                && empty($r->parameterNames())
                && ! in_array($r->getName(), self::EXCLUIR))
            ->map(fn ($r) => $r->getName())
            ->values();

        $this->assertGreaterThan(
            300,
            $rutas->count(),
            'El número de rutas admin sin parámetros cayó por debajo de lo esperado — '
            . 'revisar si el filtro de rutas sigue siendo válido tras el upgrade.'
        );

        $fallos = [];
        $huerfanos = [];

        foreach (self::ROLES as $rol) {
            $user = User::factory()->create(['activo' => true]);
            $user->assignRole($rol);

            if ($rol === 'Docente') {
                Docente::factory()->create(['user_id' => $user->id]);
            }

            foreach ($rutas as $nombre) {
                $resp = $this->actingAs($user)->get(route($nombre));
                $status = $resp->getStatusCode();

                if ($status >= 500) {
                    $fallos[] = "{$nombre} ({$rol}): HTTP {$status}";
                }

                // Pantallas completas del Administrador: ningún botón/formulario de Alpine puede quedar fuera de un x-data
                if ($rol === 'Administrador' && $status === 200 && str_contains((string) $resp->headers->get('Content-Type'), 'text/html')) {
                    foreach ($this->alpineHuerfanos((string) $resp->getContent()) as $h) {
                        $huerfanos[] = "{$nombre}: {$h}";
                    }
                }
            }
        }

        $this->assertEmpty(
            $fallos,
            "Rutas que devolvieron 5xx:\n" . implode("\n", $fallos)
        );

        $this->assertEmpty(
            $huerfanos,
            "Alpine no procesa esto (está fuera de un x-data; el botón o formulario no hará nada):\n" . implode("\n", array_unique($huerfanos))
        );
    }
}
