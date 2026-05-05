<?php

use App\Http\Controllers\Admin\TransporteController;

// ── Módulo de Transporte Escolar ──────────────────────────────────────────────
Route::prefix('transporte')->name('transporte.')->group(function () {

    Route::get('/',                                           [TransporteController::class, 'index'])->name('index');
    Route::get('/nueva',                                      [TransporteController::class, 'create'])->name('create');
    Route::post('/',                                          [TransporteController::class, 'store'])->name('store');
    Route::get('/{ruta}',                                     [TransporteController::class, 'show'])->name('show');
    Route::get('/{ruta}/editar',                              [TransporteController::class, 'edit'])->name('edit');
    Route::put('/{ruta}',                                     [TransporteController::class, 'update'])->name('update');
    Route::delete('/{ruta}',                                  [TransporteController::class, 'destroy'])->name('destroy');

    // Paradas
    Route::post('/{ruta}/paradas',                            [TransporteController::class, 'storeParada'])->name('paradas.store');
    Route::put('/{ruta}/paradas/{parada}',                    [TransporteController::class, 'updateParada'])->name('paradas.update');
    Route::delete('/{ruta}/paradas/{parada}',                 [TransporteController::class, 'destroyParada'])->name('paradas.destroy');
    Route::post('/{ruta}/paradas/reordenar',                  [TransporteController::class, 'reordenarParadas'])->name('paradas.reordenar');

    // Estudiantes
    Route::post('/{ruta}/estudiantes',                        [TransporteController::class, 'asignarEstudiante'])->name('estudiantes.store');
    Route::delete('/{ruta}/estudiantes/{asignacion}',         [TransporteController::class, 'desasignarEstudiante'])->name('estudiantes.destroy');

    // PDF
    Route::get('/{ruta}/pasajeros-pdf',                       [TransporteController::class, 'pasajerosPdf'])->name('pasajeros.pdf');
});
