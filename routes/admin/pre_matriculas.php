<?php

use App\Http\Controllers\Admin\PreMatriculaAdminController;

// ── Pre-matrículas ────────────────────────────────────────────────────────────
Route::prefix('pre-matriculas')->name('pre-matriculas.')->group(function () {
    Route::get('/',                              [PreMatriculaAdminController::class, 'index'])->name('index');
    Route::get('/lista/excel',                   [PreMatriculaAdminController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',                     [PreMatriculaAdminController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/{preMatricula}',                [PreMatriculaAdminController::class, 'show'])->name('show');
    Route::post('/{preMatricula}/aprobar',       [PreMatriculaAdminController::class, 'aprobar'])->name('aprobar');
    Route::post('/{preMatricula}/rechazar',      [PreMatriculaAdminController::class, 'rechazar'])->name('rechazar');
    Route::get('/{preMatricula}/convertir',      [PreMatriculaAdminController::class, 'formConvertir'])->name('form-convertir');
    Route::post('/{preMatricula}/convertir',     [PreMatriculaAdminController::class, 'convertir'])->name('ejecutar-convertir');
    Route::delete('/{preMatricula}',             [PreMatriculaAdminController::class, 'destroy'])->name('destroy');
});
