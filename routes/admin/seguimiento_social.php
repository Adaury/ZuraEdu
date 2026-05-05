<?php

use App\Http\Controllers\Admin\SeguimientoSocialController;

// ── Seguimiento Social ────────────────────────────────────────────────────────
Route::prefix('seguimiento-social')->name('seguimiento-social.')->group(function () {

    Route::get('/',                            [SeguimientoSocialController::class, 'index'])->name('index');
    Route::get('/crear',                       [SeguimientoSocialController::class, 'create'])->name('create');
    Route::post('/',                           [SeguimientoSocialController::class, 'store'])->name('store');
    Route::get('/{caso}',                      [SeguimientoSocialController::class, 'show'])->name('show');
    Route::put('/{caso}',                      [SeguimientoSocialController::class, 'update'])->name('update');
    Route::delete('/{caso}',                   [SeguimientoSocialController::class, 'destroy'])->name('destroy');

    // Intervenciones
    Route::post('/{caso}/intervenciones',      [SeguimientoSocialController::class, 'addIntervencion'])->name('intervenciones.store');

    // Cerrar caso
    Route::patch('/{caso}/cerrar',             [SeguimientoSocialController::class, 'cerrarCaso'])->name('cerrar');

    // PDF
    Route::get('/{caso}/informe-pdf',          [SeguimientoSocialController::class, 'informePdf'])->name('informe-pdf');
});
