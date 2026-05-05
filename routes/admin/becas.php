<?php

use App\Http\Controllers\Admin\BecaController;

// ── Módulo de Becas y Descuentos ──────────────────────────────────────────
Route::prefix('becas')->name('becas.')->group(function () {

    // CRUD de becas
    Route::get('/',            [BecaController::class, 'index'])->name('index');
    Route::get('/nueva',       [BecaController::class, 'create'])->name('create');
    Route::post('/',           [BecaController::class, 'store'])->name('store');
    Route::get('/{beca}/editar', [BecaController::class, 'edit'])->name('edit');
    Route::put('/{beca}',      [BecaController::class, 'update'])->name('update');
    Route::delete('/{beca}',   [BecaController::class, 'destroy'])->name('destroy');

    // Asignaciones
    Route::get('/becados',                              [BecaController::class, 'listaBecados'])->name('becados');
    Route::post('/asignar',                             [BecaController::class, 'asignarBeca'])->name('asignar');
    Route::delete('/revocar/{becaEstudiante}',          [BecaController::class, 'revocarBeca'])->name('revocar');

    // Reporte PDF
    Route::get('/reporte-pdf',  [BecaController::class, 'reportePdf'])->name('reporte-pdf');
});
