<?php

use App\Http\Controllers\Admin\ProyectoController;

// ── Módulo de Proyectos Escolares ─────────────────────────────────────────────
Route::prefix('proyectos')->name('proyectos.')->group(function () {

    // Dashboard
    Route::get('/dashboard',                 [ProyectoController::class, 'dashboard'])->name('dashboard');

    // CRUD principal
    Route::get('/',                          [ProyectoController::class, 'index'])->name('index');
    Route::get('/lista/excel',               [ProyectoController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',                 [ProyectoController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/nuevo',                     [ProyectoController::class, 'create'])->name('create');
    Route::post('/',                         [ProyectoController::class, 'store'])->name('store');
    Route::get('/{proyecto}',                [ProyectoController::class, 'show'])->name('show');
    Route::get('/{proyecto}/editar',         [ProyectoController::class, 'edit'])->name('edit');
    Route::put('/{proyecto}',                [ProyectoController::class, 'update'])->name('update');
    Route::delete('/{proyecto}',             [ProyectoController::class, 'destroy'])->name('destroy');

    // Integrantes
    Route::post('/{proyecto}/integrantes',                           [ProyectoController::class, 'agregarIntegrante'])->name('integrantes.agregar');
    Route::delete('/{proyecto}/integrantes/{integrante}',            [ProyectoController::class, 'quitarIntegrante'])->name('integrantes.quitar');

    // Fases
    Route::post('/{proyecto}/fases',                                 [ProyectoController::class, 'addFase'])->name('fases.store');
    Route::patch('/{proyecto}/fases/{fase}/toggle',                  [ProyectoController::class, 'toggleFase'])->name('fases.toggle');

    // Certificado PDF
    Route::get('/{proyecto}/certificado/{estudiante}',               [ProyectoController::class, 'certificadoPdf'])->name('certificado');
});
