<?php

use App\Http\Controllers\Admin\DisciplinaController;

// ── Disciplina Escolar ─────────────────────────────────────────────────────

// Expediente PDF (ruta específica antes del resource para evitar colisiones)
Route::get('disciplina/{estudiante}/expediente-pdf',
    [DisciplinaController::class, 'expedientePdf'])
    ->name('disciplina.expediente-pdf');

// Excel / PDF lista
Route::get('disciplina/lista/excel',
    [DisciplinaController::class, 'listaExcel'])
    ->name('disciplina.lista-excel');

Route::get('disciplina/lista/pdf',
    [DisciplinaController::class, 'listaPdf'])
    ->name('disciplina.lista-pdf');

// Toggle resuelto (AJAX / PATCH)
Route::patch('disciplina/{disciplina}/toggle-resuelto',
    [DisciplinaController::class, 'toggleResuelto'])
    ->name('disciplina.toggle-resuelto');

// CRUD completo
Route::resource('disciplina', DisciplinaController::class)
    ->parameters(['disciplina' => 'disciplina'])
    ->except(['show']);
