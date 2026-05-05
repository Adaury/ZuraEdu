<?php

use App\Http\Controllers\Admin\GamificacionController;

// ── Módulo de Gamificación ────────────────────────────────────────────────────
Route::prefix('gamificacion')->name('gamificacion.')->group(function () {
    Route::get('/',                      [GamificacionController::class, 'index'])->name('index');
    Route::post('/asignar-puntos',       [GamificacionController::class, 'asignarPuntos'])->name('asignar-puntos');
    Route::post('/generar-puntos',       [GamificacionController::class, 'generarPuntos'])->name('generar-puntos');
});
