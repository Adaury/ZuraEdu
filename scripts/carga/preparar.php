<?php
/**
 * Prepara una base SEMBRADA (php artisan db:seed) para la prueba de carga:
 *  1) le da al centro un host propio (dominio_personalizado) para que las peticiones lo resuelvan;
 *  2) crea una cuenta de usuario por estudiante y por representante (la siembra solo trae unas pocas) con su rol;
 *  3) fabrica una sesión web ya autenticada por persona, con el propio código de sesión de Laravel (el login real solo admite 5 por minuto y IP).
 *
 * SOLO para una base desechable de pruebas: modifica usuarios. Se niega a correr si la base no se llama como se indica en CARGA_BASE_PERMITIDA
 * (por defecto: sge_carga) para que nunca se ejecute por error sobre una base real.
 *
 * Uso (raíz del proyecto): php scripts/carga/preparar.php sesiones.json [host]
 */
chdir(dirname(__DIR__, 2));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$permitida = getenv('CARGA_BASE_PERMITIDA') ?: 'sge_carga';
$base = config('database.connections.mysql.database');
if ($base !== $permitida) {
    fwrite(STDERR, "ABORTADO: la base es '{$base}' y esto solo corre sobre '{$permitida}'.\n");
    exit(1);
}

$salida = $argv[1] ?? 'sesiones.json';
$host   = $argv[2] ?? 'carga.local';

$tenant = Tenant::orderBy('id')->firstOrFail();
$tenant->forceFill(['dominio_personalizado' => $host, 'estado' => 'activo'])->save();
app()->instance('tenant', $tenant);
app()->instance(Tenant::class, $tenant);
config(['tenant.id' => $tenant->id, 'tenant.nombre' => $tenant->nombre_institucion]);

$clave = Hash::make('carga-no-se-usa');   // una sola vez: el hash es lo caro y las sesiones se fabrican sin contraseña
$nuevoUsuario = function (string $email, string $nombre, string $rol) use ($clave, $tenant): User {
    $u = (new User())->forceFill(['name' => $nombre, 'email' => $email, 'password' => $clave, 'activo' => true, 'tenant_id' => $tenant->id]);
    $u->save();
    $u->assignRole($rol);

    return $u;
};

// Una cuenta por estudiante (si no la tiene) y una por representante (la siembra reutiliza muy pocas).
foreach (Estudiante::whereNull('user_id')->cursor() as $e) {
    $u = $nuevoUsuario("est{$e->id}@carga.test", trim("{$e->nombres} {$e->apellidos}") ?: "Estudiante {$e->id}", 'Estudiante');
    $e->forceFill(['user_id' => $u->id])->save();
}
foreach (Representante::cursor() as $r) {
    $u = $nuevoUsuario("rep{$r->id}@carga.test", trim("{$r->nombres} {$r->apellidos}") ?: "Representante {$r->id}", 'Representante');
    $r->forceFill(['user_id' => $u->id])->save();
}

$nombre   = config('session.cookie');
$enc      = app('encrypter');
$handler  = app('session')->driver()->getHandler();
$claveAut = app('auth')->guard('web')->getName();

$crear = function (int $userId) use ($nombre, $enc, $handler, $claveAut): array {
    $id = Str::random(40);
    $token = Str::random(40);
    $s = new Store($nombre, $handler, $id);
    $s->start();
    $s->put('_token', $token);
    $s->put($claveAut, $userId);
    $s->save();

    return ['cookie' => $nombre . '=' . rawurlencode($enc->encrypt(CookieValuePrefix::create($nombre, $enc->getKey()) . $id, false)), 'csrf' => $token];
};

$lista = [];
$porRol = function (string $rol) {
    return User::role($rol)->where('activo', true)->orderBy('id')->get(['id']);
};
foreach (['Estudiante' => 'estudiante', 'Docente' => 'docente', 'Administrador' => 'admin'] as $rolSistema => $rolCarga) {
    foreach ($porRol($rolSistema) as $u) {
        $lista[] = ['rol' => $rolCarga, 'userId' => $u->id] + $crear($u->id);
    }
}
// Padres: la sesión es del usuario del representante y se anota cuál es su primer hijo (las páginas /portal/padre/hijo/{id}).
foreach (Representante::with('estudiantes')->orderBy('id')->cursor() as $r) {
    $hijo = $r->estudiantes->first();
    if ($hijo) {
        $lista[] = ['rol' => 'padre', 'userId' => $r->user_id, 'hijoId' => $hijo->id] + $crear($r->user_id);
    }
}

file_put_contents($salida, json_encode($lista, JSON_UNESCAPED_UNICODE));
$cuenta = array_count_values(array_column($lista, 'rol'));
fwrite(STDERR, 'Sesiones: ' . json_encode($cuenta) . " -> {$salida} (host {$host})\n");
fwrite(STDERR, 'Datos: ' . DB::table('estudiantes')->count() . ' estudiantes, ' . DB::table('calificaciones_academicas')->count()
    . ' calificaciones, ' . DB::table('asistencias')->count() . ' asistencias, ' . DB::table('pagos')->count() . " pagos\n");
