<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\CalificacionesApiController;
use App\Http\Controllers\Api\AsistenciaApiController;
use App\Http\Controllers\Api\HorarioApiController;
use App\Http\Controllers\Api\NotificacionApiController;

/*
|--------------------------------------------------------------------------
| API Routes — SGE Mobile v1
|--------------------------------------------------------------------------
| Base URL: /api/v1/
| Auth: Sanctum Bearer Token
*/

Route::prefix('v1')->group(function () {

    // ── Auth pública ──────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthApiController::class, 'login'])->name('api.auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
            Route::get('me',      [AuthApiController::class, 'me'])->name('api.auth.me');
        });
    });

    // ── Rutas autenticadas ────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Dashboard (rol-based)
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('api.dashboard');

        // Calificaciones
        Route::get('calificaciones',                    [CalificacionesApiController::class, 'index'])->name('api.calificaciones');
        Route::get('calificaciones/hijo/{estudiante}',  [CalificacionesApiController::class, 'hijo'])->name('api.calificaciones.hijo');

        // Asistencia
        Route::get('asistencia',                        [AsistenciaApiController::class, 'index'])->name('api.asistencia');
        Route::get('asistencia/hijo/{estudiante}',      [AsistenciaApiController::class, 'hijo'])->name('api.asistencia.hijo');

        // Horario
        Route::get('horario',                           [HorarioApiController::class, 'index'])->name('api.horario');
        Route::get('horario/hijo/{estudiante}',         [HorarioApiController::class, 'hijo'])->name('api.horario.hijo');

        // Notificaciones
        Route::get('notificaciones',                            [NotificacionApiController::class, 'index'])->name('api.notificaciones');
        Route::post('notificaciones/leer-todas',                [NotificacionApiController::class, 'marcarTodas'])->name('api.notificaciones.leer-todas');
        Route::patch('notificaciones/{notificacion}/leer',      [NotificacionApiController::class, 'marcar'])->name('api.notificaciones.leer');
    });
});
