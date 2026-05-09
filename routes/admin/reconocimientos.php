<?php

use App\Http\Controllers\Admin\ReconocimientoController;

// ── Módulo de Diplomas y Reconocimientos ──────────────────────────────────────
Route::prefix('reconocimientos')->name('reconocimientos.')->group(function () {

    // CRUD principal
    Route::get('/',                      [ReconocimientoController::class, 'index'])->name('index');
    Route::get('/nuevo',                 [ReconocimientoController::class, 'create'])->name('create');
    Route::post('/',                     [ReconocimientoController::class, 'store'])->name('store');
    Route::get('/{reconocimiento}/editar', [ReconocimientoController::class, 'edit'])->name('edit');
    Route::put('/{reconocimiento}',      [ReconocimientoController::class, 'update'])->name('update');
    Route::delete('/{reconocimiento}',   [ReconocimientoController::class, 'destroy'])->name('destroy');

    // Acciones especiales
    Route::patch('/{reconocimiento}/entregar', [ReconocimientoController::class, 'marcarEntregado'])->name('entregar');
    Route::get('/{reconocimiento}/diploma-pdf', [ReconocimientoController::class, 'diplomaPdf'])->name('diploma-pdf');
    Route::get('/lista/excel', [ReconocimientoController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',   [ReconocimientoController::class, 'listaPdf'])->name('lista-pdf');
});

// Historial por estudiante (fuera del prefix para URL más limpia)
Route::get('estudiantes/{estudiante}/reconocimientos',
    [ReconocimientoController::class, 'historialEstudiante']
)->name('reconocimientos.historial-estudiante');
