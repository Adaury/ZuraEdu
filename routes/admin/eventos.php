<?php

use App\Http\Controllers\Admin\EventoController;

// ── Eventos Extracurriculares ──────────────────────────────────────────────
Route::prefix('eventos')->name('eventos.')->group(function () {

    // Dashboard
    Route::get('dashboard', [EventoController::class, 'dashboard'])->name('dashboard');

    // Lista Excel / PDF de todos los eventos
    Route::get('lista/excel', [EventoController::class, 'listaExcel'])->name('lista-excel');
    Route::get('lista/pdf',   [EventoController::class, 'listaPdf'])->name('lista-pdf');

    // PDF / Excel de inscritos (antes del resource para evitar colisión)
    Route::get('{evento}/inscritos-pdf',   [EventoController::class, 'inscritosPdf'])->name('inscritos-pdf');
    Route::get('{evento}/inscritos-excel', [EventoController::class, 'inscritosExcel'])->name('inscritos-excel');

    // Toggle activo
    Route::patch('{evento}/toggle', [EventoController::class, 'toggleActivo'])
         ->name('toggle');

    // Inscripción masiva / individual
    Route::post('{evento}/inscribir', [EventoController::class, 'inscribir'])
         ->name('inscribir');

    // Marcar asistencia (batch)
    Route::patch('{evento}/asistencia', [EventoController::class, 'marcarAsistencia'])
         ->name('asistencia');

    // Desinscribir estudiante individual
    Route::delete('{evento}/inscritos/{estudiante}', [EventoController::class, 'desinscribir'])
         ->name('desinscribir');

    // CRUD estándar
    Route::resource('', EventoController::class)
         ->parameters(['' => 'evento'])
         ->except(['show']);

    // Show (con buscador de disponibles)
    Route::get('{evento}', [EventoController::class, 'show'])
         ->name('show');
});
