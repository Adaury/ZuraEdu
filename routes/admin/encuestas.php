<?php

use App\Http\Controllers\Admin\EncuestaController;

// ── Encuestas de Satisfacción ─────────────────────────────────────────────
Route::prefix('encuestas')->name('encuestas.')->group(function () {
    Route::get('/',                             [EncuestaController::class, 'index'])->name('index');
    Route::get('/create',                       [EncuestaController::class, 'create'])->name('create');
    Route::post('/',                            [EncuestaController::class, 'store'])->name('store');
    Route::get('/{encuesta}',                   [EncuestaController::class, 'show'])->name('show');
    Route::delete('/{encuesta}',                [EncuestaController::class, 'destroy'])->name('destroy');
    Route::patch('/{encuesta}/toggle-activo',   [EncuestaController::class, 'toggleActivo'])->name('toggle-activo');
});
