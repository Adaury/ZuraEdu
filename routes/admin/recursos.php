<?php

use App\Http\Controllers\Admin\RecursoController;

// ── Módulo de Gestión de Recursos y Aulas ─────────────────────────────────
Route::prefix('recursos')->name('recursos.')->group(function () {

    // Vista general de disponibilidad (debe ir antes del {recurso} param)
    Route::get('/disponibilidad', [RecursoController::class, 'disponibilidad'])->name('disponibilidad');

    // CRUD recursos físicos
    Route::get('/',                    [RecursoController::class, 'index'])->name('index');
    Route::get('/nuevo',               [RecursoController::class, 'create'])->name('create');
    Route::post('/',                   [RecursoController::class, 'store'])->name('store');
    Route::get('/{recurso}/editar',    [RecursoController::class, 'edit'])->name('edit');
    Route::put('/{recurso}',           [RecursoController::class, 'update'])->name('update');
    Route::delete('/{recurso}',        [RecursoController::class, 'destroy'])->name('destroy');

    // Reservas por recurso
    Route::get('/{recurso}/reservas',           [RecursoController::class, 'reservas'])->name('reservas');
    Route::get('/{recurso}/reservas/nueva',     [RecursoController::class, 'crearReserva'])->name('reservas.create');
    Route::post('/{recurso}/reservas',          [RecursoController::class, 'crearReserva'])->name('reservas.store');

    // Acciones sobre una reserva (sin pasar el recurso en la URL)
    Route::patch('/reserva/{reserva}/aprobar',  [RecursoController::class, 'aprobar'])->name('reserva.aprobar');
    Route::patch('/reserva/{reserva}/rechazar', [RecursoController::class, 'rechazar'])->name('reserva.rechazar');
    Route::delete('/reserva/{reserva}',         [RecursoController::class, 'cancelar'])->name('reserva.cancelar');
});
