<?php

use App\Http\Controllers\Admin\CafeteriaController;

// ── Módulo de Cafetería / Canteen ─────────────────────────────────────────
Route::prefix('cafeteria')->name('cafeteria.')->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────────
    Route::get('/',                              [CafeteriaController::class, 'dashboard'])->name('dashboard');

    // ── Productos ──────────────────────────────────────────────────────────
    Route::prefix('productos')->name('productos.')->group(function () {
        Route::get('/',                          [CafeteriaController::class, 'indexProductos'])->name('index');
        Route::get('/nuevo',                     [CafeteriaController::class, 'createProducto'])->name('create');
        Route::post('/',                         [CafeteriaController::class, 'storeProducto'])->name('store');
        Route::get('/{producto}/editar',         [CafeteriaController::class, 'editProducto'])->name('edit');
        Route::put('/{producto}',                [CafeteriaController::class, 'updateProducto'])->name('update');
        Route::delete('/{producto}',             [CafeteriaController::class, 'destroyProducto'])->name('destroy');
    });

    // ── Ventas / Movimientos ───────────────────────────────────────────────
    Route::get('/ventas',                        [CafeteriaController::class, 'ventas'])->name('ventas');
    Route::post('/ventas/registrar',             [CafeteriaController::class, 'registrarVenta'])->name('ventas.store');
    Route::post('/recargas/registrar',           [CafeteriaController::class, 'registrarRecarga'])->name('recargas.store');
    Route::post('/ajustes/registrar',            [CafeteriaController::class, 'registrarAjuste'])->name('ajustes.store');

    // ── Balance por estudiante ─────────────────────────────────────────────
    Route::get('/balance/{estudiante}',          [CafeteriaController::class, 'balanceEstudiante'])->name('balance');

    // ── Reportes ───────────────────────────────────────────────────────────
    Route::get('/reporte-pdf',                   [CafeteriaController::class, 'reportePdf'])->name('reporte-pdf');
    Route::get('/reporte-excel',                 [CafeteriaController::class, 'reporteExcel'])->name('reporte-csv');
});
