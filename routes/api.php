<?php

use App\Http\Controllers\Api\AsistenciaApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CalendarioApiController;
use App\Http\Controllers\Api\CalificacionesApiController;
use App\Http\Controllers\Api\ClassroomApiController;
use App\Http\Controllers\Api\ComunicadosApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DocenteApiController;
use App\Http\Controllers\Api\HorarioApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PagosApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ZuraEdu SGE v1
|--------------------------------------------------------------------------
| Base URL : /api/v1/
| Auth     : Bearer Token (Sanctum)
| Tenant   : resuelto automáticamente desde user.tenant_id en rutas protegidas
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Mapa de la API ────────────────────────────────────────────────
    Route::get('/', fn () => response()->json([
        'api'     => 'ZuraEdu SGE',
        'version' => 'v1',
        'docs'    => url('/api/v1'),
        'endpoints' => [
            'auth'         => ['POST /auth/login', 'POST /auth/logout', 'GET /auth/me'],
            'dashboard'    => ['GET /dashboard'],
            'calificaciones'=> ['GET /calificaciones', 'GET /calificaciones/hijo/{id}'],
            'asistencia'   => ['GET /asistencia', 'GET /asistencia/hijo/{id}'],
            'horario'      => ['GET /horario', 'GET /horario/hijo/{id}'],
            'notificaciones'=> ['GET /notificaciones', 'PATCH /notificaciones/{id}/leer', 'POST /notificaciones/leer-todas'],
            'comunicados'  => ['GET /comunicados', 'GET /comunicados/{id}'],
            'calendario'   => ['GET /calendario'],
            'pagos'        => ['GET /pagos', 'GET /pagos/hijo/{id}'],
            'classroom'    => ['GET /classroom', 'GET /classroom/{id}/materiales'],
            'docente'      => ['GET /docente/grupos', 'GET /docente/asistencia/{id}', 'POST /docente/asistencia'],
            'tutor_ia'     => ['POST /ai/chat'],
        ],
    ]))->name('api.v1.index');

    // ── Auth pública (sin tenant) ─────────────────────────────────────
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('login',  [AuthApiController::class, 'login'])->name('api.auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
            Route::get('me',      [AuthApiController::class, 'me'])->name('api.auth.me');
            Route::post('refresh-token', [AuthApiController::class, 'refreshToken'])->name('api.auth.refresh');
        });
    });

    // ── Rutas autenticadas + tenant resuelto ──────────────────────────
    Route::middleware(['auth:sanctum', 'api.tenant', 'throttle:120,1'])->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('api.dashboard');

        // Calificaciones
        Route::prefix('calificaciones')->name('api.calificaciones.')->group(function () {
            Route::get('/',                    [CalificacionesApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [CalificacionesApiController::class, 'hijo'])->name('hijo');
        });

        // Asistencia
        Route::prefix('asistencia')->name('api.asistencia.')->group(function () {
            Route::get('/',                    [AsistenciaApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [AsistenciaApiController::class, 'hijo'])->name('hijo');
        });

        // Horario
        Route::prefix('horario')->name('api.horario.')->group(function () {
            Route::get('/',                    [HorarioApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [HorarioApiController::class, 'hijo'])->name('hijo');
        });

        // Notificaciones
        Route::prefix('notificaciones')->name('api.notificaciones.')->group(function () {
            Route::get('/',                          [NotificacionApiController::class, 'index'])->name('index');
            Route::post('leer-todas',                [NotificacionApiController::class, 'marcarTodas'])->name('leer-todas');
            Route::patch('{notificacion}/leer',      [NotificacionApiController::class, 'marcar'])->name('leer');
        });

        // Comunicados
        Route::prefix('comunicados')->name('api.comunicados.')->group(function () {
            Route::get('/',                    [ComunicadosApiController::class, 'index'])->name('index');
            Route::get('{comunicado}',         [ComunicadosApiController::class, 'show'])->name('show');
        });

        // Calendario
        Route::get('calendario',               [CalendarioApiController::class, 'index'])->name('api.calendario');

        // Pagos
        Route::prefix('pagos')->name('api.pagos.')->group(function () {
            Route::get('/',                    [PagosApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [PagosApiController::class, 'hijo'])->name('hijo');
        });

        // Classroom (LMS)
        Route::prefix('classroom')->name('api.classroom.')->group(function () {
            Route::get('/',                          [ClassroomApiController::class, 'index'])->name('index');
            Route::get('{claseVirtual}/materiales',  [ClassroomApiController::class, 'materiales'])->name('materiales');
        });

        // Docente — gestión de grupos y asistencia
        Route::prefix('docente')->name('api.docente.')->group(function () {
            Route::get('grupos',               [DocenteApiController::class, 'grupos'])->name('grupos');
            Route::get('asistencia/{id}',      [DocenteApiController::class, 'consultarAsistencia'])->name('asistencia.consultar');
            Route::post('asistencia',          [DocenteApiController::class, 'registrarAsistencia'])->name('asistencia.registrar');
        });

        // Academic Risk Score
        Route::prefix('riesgo')->name('api.riesgo.')->group(function () {
            Route::get('mi-score',           [\App\Http\Controllers\Api\RiesgoApiController::class, 'miScore'])->name('mi-score');
            Route::get('hijo/{estudiante}',  [\App\Http\Controllers\Api\RiesgoApiController::class, 'hijoScore'])->name('hijo');
        });

        // Tutor IA — chat académico (Estudiante, Representante, Docente)
        Route::post('ai/chat', [\App\Http\Controllers\Api\TutorIaApiController::class, 'chat'])
             ->middleware('throttle:30,1')
             ->name('api.ai.chat');
    });
});
