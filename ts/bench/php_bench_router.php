<?php
// SOLO PARA BENCHMARK (no está en el repo). Router de `php -S` que arranca Laravel como public/index.php
// y añade una ruta JSON equivalente al listado de la API TypeScript: mismo token Sanctum, mismo tenant por usuario,
// mismo orden y paginación de 15. Sirve para comparar PHP y Node haciendo el MISMO trabajo.
define('LARAVEL_START', microtime(true));
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Route::middleware(['auth:sanctum', 'api.tenant'])
    ->get('/api/v1/_bench/estudiantes', function () {
        $p = App\Models\Estudiante::query()
            ->select(['id', 'numero_matricula', 'cedula', 'nombres', 'apellidos', 'sexo', 'estado'])
            ->orderBy('apellidos')->orderBy('nombres')->orderBy('id')
            ->paginate((int) request('perPage', 15));
        return response()->json([
            'data' => collect($p->items())->map(fn ($e) => [
                'id' => $e->id, 'numeroMatricula' => $e->numero_matricula, 'cedula' => $e->cedula,
                'nombres' => $e->nombres, 'apellidos' => $e->apellidos, 'sexo' => $e->sexo, 'estado' => $e->estado,
            ]),
            'meta' => ['total' => $p->total(), 'page' => $p->currentPage(), 'perPage' => $p->perPage(), 'lastPage' => $p->lastPage()],
        ]);
    });

$response =$kernel->handle($request = Illuminate\Http\Request::capture());
$response->send();
$kernel->terminate($request, $response);
