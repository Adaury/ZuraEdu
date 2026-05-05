<?php

use App\Http\Controllers\Admin\EventoController;

// ── Eventos Extracurriculares ──────────────────────────────────────────────
Route::prefix('eventos')->name('eventos.')->group(function () {

    // PDF de inscritos (antes del resource para evitar colisión)
    Route::get('{evento}/inscritos-pdf', [EventoController::class, 'inscritosPdf'])
         ->name('inscritos-pdf');

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
