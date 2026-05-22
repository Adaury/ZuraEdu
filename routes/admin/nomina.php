<?php

use App\Http\Controllers\Admin\NominaController;

// ── Módulo de Nómina ──────────────────────────────────────────────────────
Route::prefix('nomina')->name('nomina.')->group(function () {
    Route::get('/dashboard',                           [NominaController::class, 'dashboard'])->name('dashboard');
    Route::get('/',                                    [NominaController::class, 'index'])->name('index');
    Route::get('/nuevo',                               [NominaController::class, 'create'])->name('create');
    Route::post('/',                                   [NominaController::class, 'store'])->name('store');
    Route::get('/excel',                               [NominaController::class, 'excel'])->name('excel');
    Route::get('/pdf',                                 [NominaController::class, 'nominaPdf'])->name('pdf');
    Route::get('/reporte-csv',                         [NominaController::class, 'reporteCsv'])->name('reporte-csv');
    Route::get('/resumen-anual',                       [NominaController::class, 'resumenAnual'])->name('resumen-anual');
    Route::get('/resumen-anual/pdf',                   [NominaController::class, 'resumenAnualPdf'])->name('resumen-anual.pdf');
    Route::post('/procesar-mes',                       [NominaController::class, 'procesarMes'])->name('procesar-mes');
    Route::post('/marcar-todos-pagados',               [NominaController::class, 'marcarTodosPagados'])->name('marcar-todos-pagados');
    Route::get('/{nomina}',                            [NominaController::class, 'show'])->name('show');
    Route::get('/{nomina}/editar',                     [NominaController::class, 'edit'])->name('edit');
    Route::put('/{nomina}',                            [NominaController::class, 'update'])->name('update');
    Route::delete('/{nomina}',                         [NominaController::class, 'destroy'])->name('destroy');
    Route::post('/{nomina}/generar-recibo',            [NominaController::class, 'generarRecibo'])->name('generar-recibo');
    Route::get('/{nomina}/recibo-pdf',                 [NominaController::class, 'reciboPdf'])->name('recibo-pdf');
    Route::post('/{nomina}/procesar-solo',              [NominaController::class, 'procesarSolo'])->name('procesar-solo');
    Route::post('/{nomina}/guardar-pago',              [NominaController::class, 'guardarPago'])->name('guardar-pago');
    Route::post('/{nomina}/marcar-pagado',             [NominaController::class, 'marcarPagado'])->name('marcar-pagado');
});
