<?php

use App\Http\Controllers\Admin\EvaluacionDocenteController;

// ── Evaluaciones de Desempeño Docente ──────────────────────────────────────
Route::middleware('can:gestionar-docentes')->group(function () {
    Route::get('evaluaciones-docentes/dashboard',            [EvaluacionDocenteController::class, 'dashboard'])->name('evaluaciones-docentes.dashboard');
    Route::get('evaluaciones-docentes/{evaluacionDocente}/pdf', [EvaluacionDocenteController::class, 'pdf'])->name('evaluaciones-docentes.pdf');
    Route::resource('evaluaciones-docentes', EvaluacionDocenteController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['evaluaciones-docentes' => 'evaluacionDocente']);
});
