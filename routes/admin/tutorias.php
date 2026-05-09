<?php

use App\Http\Controllers\Admin\TutoriaController;

// ── Tutorías ──────────────────────────────────────────────────────────────────
Route::prefix('tutorias')->name('tutorias.')->group(function () {

    // Asignación de tutores
    Route::get('/',                         [TutoriaController::class, 'index'])->name('index');
    Route::get('/crear',                    [TutoriaController::class, 'create'])->name('create');
    Route::post('/',                        [TutoriaController::class, 'store'])->name('store');
    Route::get('/{tutoria}/editar',         [TutoriaController::class, 'edit'])->name('edit');
    Route::put('/{tutoria}',                [TutoriaController::class, 'update'])->name('update');
    Route::patch('/{tutoria}/toggle',       [TutoriaController::class, 'toggleActivo'])->name('toggle');
    Route::delete('/{tutoria}',             [TutoriaController::class, 'destroy'])->name('destroy');

    // Sesiones
    Route::get('/{tutoria}/sesiones',                               [TutoriaController::class, 'sesiones'])->name('sesiones');
    Route::get('/{tutoria}/sesiones/nueva',                         [TutoriaController::class, 'crearSesion'])->name('sesiones.create');
    Route::post('/{tutoria}/sesiones',                              [TutoriaController::class, 'crearSesion'])->name('sesiones.store');
    Route::get('/{tutoria}/sesiones/{sesion}/editar',               [TutoriaController::class, 'editarSesion'])->name('sesiones.edit');
    Route::put('/{tutoria}/sesiones/{sesion}',                      [TutoriaController::class, 'editarSesion'])->name('sesiones.update');
    Route::delete('/{tutoria}/sesiones/{sesion}',                   [TutoriaController::class, 'eliminarSesion'])->name('sesiones.destroy');

    // PDF
    Route::get('/{tutoria}/informe-pdf', [TutoriaController::class, 'informePdf'])->name('informe-pdf');

    // Excel / PDF
    Route::get('/lista/excel',           [TutoriaController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/lista/pdf',             [TutoriaController::class, 'listaPdf'])->name('lista-pdf');
});
