<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckTenantFeature;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SiembraFilaDeEjemplo;
use Tests\TestCase;

/**
 * Igual que PantallasDetalleAdminTest pero para los portales de docente, padre y estudiante: cada rol abre solo las pantallas de
 * detalle con identificadores que le PERTENECEN (su asignación, su hijo, su matrícula), y ninguna debe dar error de servidor.
 */
class PantallasDetallePortalesTest extends TestCase
{
    use RefreshDatabase;
    use SiembraFilaDeEjemplo;

    private const EXCLUIR = '/pdf|excel|csv|descargar|download|exportar|logout|imprimir|zip|foto|archivo|qr|ics|stream|sse|chat|broadcast|adjunto/i';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_pantallas_de_detalle_de_los_portales_no_devuelven_500(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Portales',
            'dominio'            => 'colegioportales' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        foreach (array_keys((new \ReflectionClass(CheckTenantFeature::class))->getConstants()['LABELS']) as $feature) {
            TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => $feature, 'activo' => true]);
        }
        app()->instance('tenant', $tenant);
        $t = $tenant->id;

        $sy      = SchoolYear::create(['nombre' => '2026-PT', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => '2do de Secundaria PT', 'nivel' => 2, 'orden' => 2, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $docUser = User::factory()->create(['activo' => true, 'tenant_id' => $t]);
        $docUser->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $docUser->id]);
        $asignatura = Asignatura::create(['codigo' => 'ESPPT', 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica']);

        $estUser = User::factory()->create(['activo' => true, 'tenant_id' => $t]);
        $estUser->assignRole('Estudiante');
        $estudiante = Estudiante::factory()->create(['user_id' => $estUser->id]);
        $matricula = Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2026-08-15', 'numero_orden' => 1, 'estado' => 'activa']);

        $repUser = User::factory()->create(['activo' => true, 'tenant_id' => $t]);
        $repUser->assignRole('Representante');
        $rep = Representante::factory()->create(['user_id' => $repUser->id]);
        DB::table('estudiante_representante')->insert(['estudiante_id' => $estudiante->id, 'representante_id' => $rep->id, 'parentesco' => 'madre', 'es_principal' => true, 'created_at' => now(), 'updated_at' => now()]);

        $ctxs = [
            'docente'    => ['prefijo' => 'portal.docente.', 'user' => $docUser, 'docente_id' => $docente->id, 'asignacion_id' => $asignacion->id, 'grupo_id' => $grupo->id, 'matricula_id' => $matricula->id, 'estudiante_id' => $estudiante->id],
            'estudiante' => ['prefijo' => 'portal.estudiante.', 'user' => $estUser, 'estudiante_id' => $estudiante->id, 'matricula_id' => $matricula->id, 'grupo_id' => $grupo->id, 'asignacion_id' => $asignacion->id],
            'padre'      => ['prefijo' => 'portal.padre.', 'user' => $repUser, 'representante_id' => $rep->id, 'estudiante_id' => $estudiante->id, 'matricula_id' => $matricula->id, 'grupo_id' => $grupo->id, 'asignacion_id' => $asignacion->id],
        ];
        $columnasContexto = ['asignacion_id', 'matricula_id', 'estudiante_id', 'grupo_id', 'docente_id', 'representante_id'];
        $propias = ['Asignacion' => 'asignacion_id', 'Matricula' => 'matricula_id', 'Estudiante' => 'estudiante_id', 'Grupo' => 'grupo_id', 'Docente' => 'docente_id', 'Representante' => 'representante_id'];

        $resolver = function (string $clase, array $c) use ($t, $columnasContexto, $propias): mixed {
            $m = new $clase;
            $tabla = $m->getTable();
            $clave = $m->getKeyName();
            $nombre = class_basename($clase);
            if (isset($propias[$nombre])) {
                return empty($c[$propias[$nombre]]) ? null : $c[$propias[$nombre]];
            }
            $base = fn () => DB::table($tabla)->when(Schema::hasColumn($tabla, 'tenant_id'), fn ($q) => $q->where('tenant_id', $t));
            $buscar = function () use ($base, $c, $columnasContexto, $tabla, $clave) {
                $con = $base();
                foreach ($columnasContexto as $col) {
                    if (! empty($c[$col]) && Schema::hasColumn($tabla, $col)) {
                        $con->where($col, $c[$col]);
                    }
                }

                return $con->orderBy($clave)->first() ?? $base()->orderBy($clave)->first();
            };
            $fila = $buscar();
            if (! $fila) {
                $this->crearFilaDeEjemplo($tabla, $t);
                $fila = $buscar();
            }

            return $fila->{$clave} ?? null;
        };

        $revisadas = [];
        $fallos = [];

        foreach ($ctxs as $rol => $c) {
            foreach (Route::getRoutes() as $r) {
                $nombre = $r->getName() ?? '';
                if (! in_array('GET', $r->methods(), true) || ! str_starts_with($nombre, $c['prefijo'])) {
                    continue;
                }
                $params = $r->parameterNames();
                if (count($params) < 1 || count($params) > 3 || preg_match(self::EXCLUIR, $nombre . $r->uri())) {
                    continue;
                }
                $modelos = [];
                foreach ($r->signatureParameters(UrlRoutable::class) as $p) {
                    $modelos[$p->getName()] = $p->getType()?->getName();
                }
                $valores = [];
                foreach ($params as $param) {
                    $clase = $modelos[$param] ?? null;
                    $id = ($clase && class_exists($clase)) ? $resolver($clase, $c) : null;
                    if ($id === null) {
                        continue 2;
                    }
                    $valores[$param] = $id;
                }
                $url = '/' . ltrim(preg_replace_callback('/\{([^}?]+)\??\}/', fn ($mm) => $valores[$mm[1]] ?? '1', $r->uri()), '/');
                $revisadas[$rol] = ($revisadas[$rol] ?? 0) + 1;

                try {
                    $resp = $this->actingAs($c['user'])->get($url);
                    if ($resp->getStatusCode() >= 500) {
                        $fallos["$rol: $nombre"] = "HTTP {$resp->getStatusCode()} en {$url}: " . ($resp->exception ? get_class($resp->exception) . ' — ' . mb_substr(preg_replace('/\s+/', ' ', $resp->exception->getMessage()), 0, 220) : '');
                    }
                } catch (\Throwable $e) {
                    $fallos["$rol: $nombre"] = "excepción en {$url}: " . get_class($e) . ' — ' . mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 220);
                }
            }
        }

        // Descargas excluidas arriba que fallaban con un estudiante sin nota/registro en un período (clave inexistente en una colección)
        $this->crearFilaDeEjemplo('periodos', $t);
        $asignacion->update(['area' => 'tecnica']);
        foreach (['portal.docente.conducta.pdf', 'portal.docente.calificaciones.exportar-excel'] as $nombre) {
            try {
                $resp = $this->actingAs($docUser)->get(route($nombre, $asignacion));
                if ($resp->getStatusCode() >= 500) {
                    $fallos["docente: $nombre"] = "HTTP {$resp->getStatusCode()}: " . ($resp->exception ? get_class($resp->exception) . ' — ' . mb_substr($resp->exception->getMessage(), 0, 200) : '');
                }
            } catch (\Throwable $e) {
                $fallos["docente: $nombre"] = get_class($e) . ' — ' . mb_substr($e->getMessage(), 0, 200);
            }
        }

        fwrite(STDERR, "\nPantallas abiertas por portal: " . json_encode($revisadas) . "\n");
        $this->assertGreaterThan(30, array_sum($revisadas), 'se abrieron muy pocas pantallas de portales: ¿falló el sembrado?');
        $this->assertSame([], $fallos, "Pantallas de portales con error de servidor:\n  " . implode("\n  ", array_map(fn ($k, $v) => "$k → $v", array_keys($fallos), $fallos)));
    }
}
