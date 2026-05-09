<?php

use App\Http\Controllers\Admin\GamificacionController;

// ── Módulo de Gamificación ────────────────────────────────────────────────────
Route::prefix('gamificacion')->name('gamificacion.')->group(function () {
    Route::get('/',                                       [GamificacionController::class, 'index'])->name('index');
    Route::get('/ranking/pdf',                            [GamificacionController::class, 'rankingPdf'])->name('ranking-pdf');
    Route::get('/ranking/excel',                          [GamificacionController::class, 'rankingExcel'])->name('ranking-excel');
    Route::get('/detalle/{matricula}',                    [GamificacionController::class, 'detalle'])->name('detalle');
    Route::post('/asignar-puntos',                        [GamificacionController::class, 'asignarPuntos'])->name('asignar-puntos');
    Route::post('/generar-puntos',                        [GamificacionController::class, 'generarPuntos'])->name('generar-puntos');
    Route::delete('/puntos/{punto}',                      [GamificacionController::class, 'eliminarPunto'])->name('puntos.destroy');
});
