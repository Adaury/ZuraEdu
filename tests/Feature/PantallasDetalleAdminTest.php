<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckTenantFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SiembraFilaDeEjemplo;
use Tests\TestCase;

/**
 * RouteSmokeTest solo abre pantallas SIN parÃ¡metros; las de detalle/ediciÃ³n (ficha del estudiante, intento de evaluaciÃ³nâ€¦)
 * no tenÃ­an ninguna prueba y de ahÃ­ salieron varios errores 500 reales. Esta prueba abre cada GET del admin que recibe 1 o 2
 * modelos, con una fila de ejemplo creada para cada modelo, y verifica que ninguna dÃ© error de servidor.
 */
class PantallasDetalleAdminTest extends TestCase
{
    use RefreshDatabase;
    use SiembraFilaDeEjemplo;

    /** Acciones con efecto de descarga/archivo o que dependen de servicios externos: no son pantallas. */
    private const EXCLUIR = '/pdf|excel|csv|descargar|download|exportar|logout|imprimir|zip|foto|archivo|qr|ics|soporte\.chat|adjunto/i';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_pantallas_de_detalle_del_admin_no_devuelven_500(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Detalle',
            'dominio'            => 'colegiodetalle' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        foreach (array_keys((new \ReflectionClass(CheckTenantFeature::class))->getConstants()['LABELS']) as $feature) {
            TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => $feature, 'activo' => true]);
        }
        app()->instance('tenant', $tenant);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        $revisadas = 0;
        $fallos = [];
        $sinDatos = [];

        foreach (Route::getRoutes() as $r) {
            $nombre = $r->getName() ?? '';
            // secciones.edit elige la vista según el «tipo» (valores reales); con el texto inventado del sembrado no existe esa vista.
            if ($nombre === 'admin.secciones.edit') {
                continue;
            }
            if (! in_array('GET', $r->methods(), true) || ! str_starts_with($nombre, 'admin.')) {
                continue;
            }
            $params = $r->parameterNames();
            if (count($params) < 1 || count($params) > 2 || preg_match(self::EXCLUIR, $nombre . $r->uri())) {
                continue;
            }

            $modelos = [];
            foreach ($r->signatureParameters(UrlRoutable::class) as $p) {
                $modelos[$p->getName()] = $p->getType()?->getName();
            }

            $valores = [];
            foreach ($params as $param) {
                $clase = $modelos[$param] ?? null;
                if (! $clase || ! class_exists($clase)) {
                    $sinDatos[$nombre] = "parÃ¡metro {$param} sin modelo";
                    continue 2;
                }
                $m = new $clase;
                $tabla = $m->getTable();
                $consulta = fn () => $clase::withoutGlobalScopes()
                    ->when(Schema::hasColumn($tabla, 'tenant_id'), fn ($q) => $q->where($tabla . '.tenant_id', $tenant->id))
                    ->orderBy($m->getKeyName())->first();
                $fila = $consulta();
                if (! $fila) {
                    $this->crearFilaDeEjemplo($tabla, $tenant->id);
                    $fila = $consulta();
                }
                if (! $fila) {
                    $sinDatos[$nombre] = class_basename($clase) . ' sin filas';
                    continue 2;
                }
                $valores[$param] = $fila->getRouteKey();
            }

            $url = '/' . ltrim(preg_replace_callback('/\{([^}?]+)\??\}/', fn ($mm) => $valores[$mm[1]] ?? '1', $r->uri()), '/');
            $revisadas++;

            try {
                $resp = $this->actingAs($user)->get($url);
                if ($resp->getStatusCode() >= 500) {
                    $fallos[$nombre] = "HTTP {$resp->getStatusCode()} en {$url}: " . ($resp->exception ? get_class($resp->exception) . ' â€” ' . mb_substr(preg_replace('/\s+/', ' ', $resp->exception->getMessage()), 0, 200) : '');
                }
            } catch (\Throwable $e) {
                $fallos[$nombre] = "excepciÃ³n en {$url}: " . get_class($e) . ' â€” ' . mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 200);
            }
        }

        fwrite(STDERR, "\nPantallas abiertas: {$revisadas} | sin datos: " . count($sinDatos) . "\n");
        $this->assertGreaterThan(50, $revisadas, 'se abrieron muy pocas pantallas de detalle: Â¿cambiÃ³ el filtro o fallÃ³ el sembrado?');
        $this->assertSame([], $fallos, "Pantallas de detalle con error de servidor:\n  " . implode("\n  ", array_map(fn ($k, $v) => "$k â†’ $v", array_keys($fallos), $fallos)));
    }
}

