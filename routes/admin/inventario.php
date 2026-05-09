<?php

use App\Http\Controllers\Admin\InventarioController;

// ── Módulo de Inventario Escolar ──────────────────────────────────────────
Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/',                                          [InventarioController::class, 'index'])->name('index');
    Route::get('/nuevo',                                     [InventarioController::class, 'create'])->name('create');
    Route::post('/',                                         [InventarioController::class, 'store'])->name('store');
    Route::get('/historial',                                 [InventarioController::class, 'historialGlobal'])->name('historial');
    Route::get('/historial/excel',                           [InventarioController::class, 'historialExcel'])->name('historial.excel');
    Route::get('/alertas',                                   [InventarioController::class, 'alertas'])->name('alertas');
    Route::get('/reporte/pdf',                               [InventarioController::class, 'inventarioPdf'])->name('pdf');
    Route::get('/reporte/excel',                             [InventarioController::class, 'inventarioExcel'])->name('excel');
    Route::get('/{articulo}/editar',                         [InventarioController::class, 'edit'])->name('edit');
    Route::put('/{articulo}',                                [InventarioController::class, 'update'])->name('update');
    Route::delete('/{articulo}',                             [InventarioController::class, 'destroy'])->name('destroy');
    Route::get('/{articulo}/movimientos',                    [InventarioController::class, 'movimientos'])->name('movimientos');
    Route::post('/{articulo}/movimientos',                   [InventarioController::class, 'registrarMovimiento'])->name('movimientos.store');
    Route::get('/{articulo}/movimientos/pdf',                [InventarioController::class, 'movimientosPdf'])->name('movimientos.pdf');
    Route::get('/{articulo}/movimientos/excel',              [InventarioController::class, 'movimientosExcel'])->name('movimientos.excel');
});
