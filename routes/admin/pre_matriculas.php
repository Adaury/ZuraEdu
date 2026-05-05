<?php

use App\Http\Controllers\Admin\PreMatriculaAdminController;

// ── Pre-matrículas ────────────────────────────────────────────────────────────
Route::prefix('pre-matriculas')->name('pre-matriculas.')->group(function () {
    Route::get('/',                              [PreMatriculaAdminController::class, 'index'])->name('index');
    Route::get('/{preMatricula}',                [PreMatriculaAdminController::class, 'show'])->name('show');
    Route::post('/{preMatricula}/aprobar',       [PreMatriculaAdminController::class, 'aprobar'])->name('aprobar');
    Route::post('/{preMatricula}/rechazar',      [PreMatriculaAdminController::class, 'rechazar'])->name('rechazar');
    Route::delete('/{preMatricula}',             [PreMatriculaAdminController::class, 'destroy'])->name('destroy');
});
