<?php

use App\Http\Controllers\Admin\PagoController;

// ── Módulo de Pagos / Colegiaturas ────────────────────────────────────────

// Lectura — Caja/Finanzas + Admin/Director
Route::prefix('pagos')->name('pagos.')->middleware('can:ver-pagos')->group(function () {
    Route::get('/dashboard',             [PagoController::class, 'dashboard'])->name('dashboard');
    Route::get('/',                      [PagoController::class, 'index'])->name('index');
    Route::get('/matricula/{matricula}', [PagoController::class, 'porEstudiante'])->name('por-estudiante');
    Route::get('/matricula/{matricula}/pdf', [PagoController::class, 'estadoCuentaPdf'])->name('estado-cuenta-pdf');
    Route::get('/{pago}/recibo',         [PagoController::class, 'reciboPdf'])->name('recibo');
    Route::get('/resumen-mensual/pdf',   [PagoController::class, 'resumenMensualPdf'])->name('resumen-mensual-pdf');
    Route::get('/resumen-mensual/excel', [PagoController::class, 'resumenMensualExcel'])->name('resumen-mensual-excel');
    Route::get('/lista/pdf',             [PagoController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/lista/excel',           [PagoController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/deudores',              [PagoController::class, 'deudores'])->name('deudores');
    Route::get('/deudores/pdf',          [PagoController::class, 'deudoresPdf'])->name('deudores.pdf');
    Route::get('/deudores/excel',        [PagoController::class, 'deudoresExcel'])->name('deudores.excel');
    Route::get('/conceptos',             [PagoController::class, 'conceptos'])->name('conceptos');
});

// Gestión — solo usuarios con permiso gestionar-pagos
Route::prefix('pagos')->name('pagos.')->middleware('can:gestionar-pagos')->group(function () {
    Route::get('/nuevo',                          [PagoController::class, 'create'])->name('create');
    Route::post('/',                              [PagoController::class, 'store'])->name('store');
    Route::get('/{pago}/editar',                  [PagoController::class, 'edit'])->name('edit');
    Route::put('/{pago}',                         [PagoController::class, 'update'])->name('update');
    Route::patch('/{pago}/pagar',                 [PagoController::class, 'marcarPagado'])->name('pagar');
    Route::delete('/{pago}',                      [PagoController::class, 'destroy'])->name('destroy');
    Route::post('/generar-cuotas',                [PagoController::class, 'generarCuotas'])->name('generar-cuotas');
    Route::post('/deudores/recordatorio',         [PagoController::class, 'recordatorio'])->name('deudores.recordatorio');
    Route::post('/conceptos',                     [PagoController::class, 'storeConcepto'])->name('conceptos.store');
    Route::put('/conceptos/{conceptoPago}',       [PagoController::class, 'updateConcepto'])->name('conceptos.update');
    Route::delete('/conceptos/{conceptoPago}',    [PagoController::class, 'destroyConcepto'])->name('conceptos.destroy');
});

// Configuración de pasarelas (Stripe/CardNet) — solo Administrador
Route::prefix('pagos')->name('pagos.')->middleware('role:Administrador')->group(function () {
    Route::get('/config',  [PagoController::class, 'configIndex'])->name('config');
    Route::post('/config', [PagoController::class, 'configUpdate'])->name('config.update');
});
