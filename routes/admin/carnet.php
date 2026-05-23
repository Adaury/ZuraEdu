<?php

use App\Http\Controllers\Admin\CarnetController;
use App\Http\Controllers\Admin\CarnetCheckinController;
use App\Http\Controllers\Admin\CarnetHistorialController;
use App\Http\Controllers\Admin\CarnetReportesController;

// ── ZuraEdu Carnet+ ───────────────────────────────────────────────────────────
Route::prefix('carnet')->name('carnet.')->group(function () {

    // Index + acciones CRUD
    Route::get('/',                           [CarnetController::class, 'index'])->name('index');
    Route::post('/generar-masivo',            [CarnetController::class, 'generarMasivo'])->name('generar-masivo');
    Route::get('/{carnet}/pdf',               [CarnetController::class, 'pdf'])->name('pdf');
    Route::get('/pdf-grupo',                  [CarnetController::class, 'pdfGrupo'])->name('pdf-grupo');
    Route::patch('/{carnet}/suspender',       [CarnetController::class, 'suspender'])->name('suspender');
    Route::delete('/{carnet}',                [CarnetController::class, 'destroy'])->name('destroy');

    // Kiosco / Check-in
    Route::get('/checkin',                    [CarnetCheckinController::class, 'kiosko'])->name('checkin');
    Route::post('/scan',                      [CarnetCheckinController::class, 'scan'])->name('scan');

    // Historial
    Route::get('/historial',                  [CarnetHistorialController::class, 'index'])->name('historial');
    Route::get('/historial/pdf',              [CarnetHistorialController::class, 'pdf'])->name('historial.pdf');
    Route::get('/historial/excel',            [CarnetHistorialController::class, 'excel'])->name('historial.excel');

    // Reportes
    Route::get('/reportes',                   [CarnetReportesController::class, 'index'])->name('reportes');
    Route::get('/reportes/chart-data',        [CarnetReportesController::class, 'chartData'])->name('reportes.chart-data');

    // Zonas
    Route::get('/zonas',                      [CarnetController::class, 'zonas'])->name('zonas');
    Route::post('/zonas',                     [CarnetController::class, 'zonaStore'])->name('zonas.store');
    Route::patch('/zonas/{zona}/toggle',      [CarnetController::class, 'zonaToggle'])->name('zonas.toggle');
    Route::delete('/zonas/{zona}',            [CarnetController::class, 'zonaDestroy'])->name('zonas.destroy');
});
