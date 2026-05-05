<?php

use App\Http\Controllers\Admin\AvisoEmergenciaController;

// ── Avisos de Emergencia Masivos ──────────────────────────────────────────────
Route::prefix('avisos-emergencia')->name('avisos-emergencia.')->group(function () {

    Route::get('/',      [AvisoEmergenciaController::class, 'index'])->name('index');
    Route::get('/nuevo', [AvisoEmergenciaController::class, 'create'])->name('create');
    Route::post('/',     [AvisoEmergenciaController::class, 'store'])->name('store');
});
