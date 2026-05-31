<?php

use App\Http\Controllers\Admin\IntegracionesController;
use App\Http\Controllers\Admin\SigerdController;

// Centro de integraciones — visible para Admin y Registro
Route::get('integraciones', [IntegracionesController::class, 'index'])->name('integraciones.index');

// SIGERD — accesible por Admin, Director y roles de Registro
Route::prefix('integraciones/sigerd')->name('sigerd.')
    ->middleware('role:Administrador|Director|Coordinador Académico|Registrador Académico|Encargado de Registro Académico')
    ->group(function () {
        Route::get('/',               [SigerdController::class, 'index'])->name('index');
        Route::get('/historial',      [SigerdController::class, 'historial'])->name('historial');
        Route::get('/validar',        [SigerdController::class, 'validar'])->name('validar');
        Route::post('/exportar',      [SigerdController::class, 'exportar'])->name('exportar');

        // Configuración — solo Administrador y Director
        Route::middleware('role:Administrador|Director')->group(function () {
            Route::get('/configuracion',  [SigerdController::class, 'configuracion'])->name('configuracion');
            Route::post('/configuracion', [SigerdController::class, 'guardarConfiguracion'])->name('configuracion.guardar');
        });
    });
