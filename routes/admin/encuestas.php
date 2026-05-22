<?php

use App\Http\Controllers\Admin\EncuestaController;

// ── Encuestas de Satisfacción ─────────────────────────────────────────────
Route::prefix('encuestas')->name('encuestas.')->group(function () {
    Route::get('/dashboard',                     [EncuestaController::class, 'dashboard'])->name('dashboard');
    Route::get('/',                             [EncuestaController::class, 'index'])->name('index');
    Route::get('/lista/excel',                  [EncuestaController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',                    [EncuestaController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/create',                       [EncuestaController::class, 'create'])->name('create');
    Route::post('/',                            [EncuestaController::class, 'store'])->name('store');
    Route::get('/{encuesta}',                   [EncuestaController::class, 'show'])->name('show');
    Route::get('/{encuesta}/edit',              [EncuestaController::class, 'edit'])->name('edit');
    Route::put('/{encuesta}',                   [EncuestaController::class, 'update'])->name('update');
    Route::delete('/{encuesta}',                [EncuestaController::class, 'destroy'])->name('destroy');
    Route::patch('/{encuesta}/toggle-activo',   [EncuestaController::class, 'toggleActivo'])->name('toggle-activo');
    Route::get('/{encuesta}/resultados/excel',  [EncuestaController::class, 'resultadosExcel'])->name('resultados-excel');
});
