<?php

use App\Http\Controllers\Admin\EquipoController;

// ── Módulo de Préstamos de Equipos ────────────────────────────────────────
Route::prefix('equipos')->name('equipos.')->group(function () {

    // ── Equipos ───────────────────────────────────────────────────────────
    Route::get('/',                       [EquipoController::class, 'index'])->name('index');
    Route::get('/crear',                  [EquipoController::class, 'create'])->name('create');
    Route::post('/',                      [EquipoController::class, 'store'])->name('store');
    Route::get('/{equipo}/editar',        [EquipoController::class, 'edit'])->name('edit');
    Route::put('/{equipo}',               [EquipoController::class, 'update'])->name('update');
    Route::delete('/{equipo}',            [EquipoController::class, 'destroy'])->name('destroy');

    // ── Préstamos ─────────────────────────────────────────────────────────
    Route::get('/prestamos',              [EquipoController::class, 'prestamos'])->name('prestamos.index');
    Route::get('/prestamos/nuevo',        [EquipoController::class, 'prestarForm'])->name('prestamos.create');
    Route::post('/prestamos',             [EquipoController::class, 'prestar'])->name('prestamos.store');
    Route::patch('/prestamos/{prestamo}/devolver', [EquipoController::class, 'devolver'])->name('prestamos.devolver');
    Route::get('/prestamos/{prestamo}/comprobante', [EquipoController::class, 'comprobantePdf'])->name('prestamos.comprobante');

    // ── Alertas ───────────────────────────────────────────────────────────
    Route::post('/verificar-vencidos',    [EquipoController::class, 'verificarVencidos'])->name('verificar-vencidos');
});
