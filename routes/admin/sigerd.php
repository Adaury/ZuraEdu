<?php

use App\Http\Controllers\Admin\IntegracionesController;
use App\Http\Controllers\Admin\SigerdController;

Route::get('integraciones', [IntegracionesController::class, 'index'])->name('integraciones.index');

Route::prefix('integraciones/sigerd')->name('sigerd.')->group(function () {
    Route::get('/',              [SigerdController::class, 'index'])->name('index');
    Route::get('/configuracion', [SigerdController::class, 'configuracion'])->name('configuracion');
    Route::post('/configuracion', [SigerdController::class, 'guardarConfiguracion'])->name('configuracion.guardar');
    Route::post('/exportar',      [SigerdController::class, 'exportar'])->name('exportar');
    Route::get('/validar',        [SigerdController::class, 'validar'])->name('validar');
    Route::get('/historial',      [SigerdController::class, 'historial'])->name('historial');
});
