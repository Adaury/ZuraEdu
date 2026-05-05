<?php

use App\Http\Controllers\Admin\BibliotecaController;

// ── Módulo de Biblioteca Escolar ──────────────────────────────────────────
Route::prefix('biblioteca')->name('biblioteca.')->group(function () {

    // ── Libros ────────────────────────────────────────────────────────────
    Route::get('/',                    [BibliotecaController::class, 'index'])->name('index');
    Route::get('/libros/crear',        [BibliotecaController::class, 'create'])->name('libros.create');
    Route::post('/libros',             [BibliotecaController::class, 'store'])->name('libros.store');
    Route::get('/libros/{libro}/editar', [BibliotecaController::class, 'edit'])->name('libros.edit');
    Route::put('/libros/{libro}',      [BibliotecaController::class, 'update'])->name('libros.update');
    Route::delete('/libros/{libro}',   [BibliotecaController::class, 'destroy'])->name('libros.destroy');

    // ── Préstamos ─────────────────────────────────────────────────────────
    Route::get('/prestamos',           [BibliotecaController::class, 'prestamos'])->name('prestamos.index');
    Route::get('/prestamos/nuevo',     [BibliotecaController::class, 'prestarForm'])->name('prestamos.create');
    Route::post('/prestamos',          [BibliotecaController::class, 'prestar'])->name('prestamos.store');
    Route::patch('/prestamos/{prestamo}/devolver', [BibliotecaController::class, 'devolver'])->name('prestamos.devolver');

    // ── Alertas ───────────────────────────────────────────────────────────
    Route::post('/verificar-vencidos', [BibliotecaController::class, 'verificarVencidos'])->name('verificar-vencidos');
});
