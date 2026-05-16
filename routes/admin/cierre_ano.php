<?php

use App\Http\Controllers\Admin\CierreAnoController;

// ── Cierre de Año Escolar ─────────────────────────────────────────────────
// Acceso restringido a Administrador y Director (verificado en el controlador)

Route::prefix('cierre-ano')->name('cierre-ano.')->group(function () {

    // Pantalla principal del cierre
    Route::get('/', [CierreAnoController::class, 'index'])->name('index');

    // Ejecutar cierre de año (POST con confirmación)
    Route::post('/ejecutar', [CierreAnoController::class, 'ejecutar'])->name('ejecutar');

    // Crear nuevo año escolar (POST) tras el cierre
    Route::post('/crear-nuevo-ano', [CierreAnoController::class, 'crearNuevoAno'])->name('crear-nuevo-ano');

    // Wizard de traslado de estudiantes al nuevo año
    Route::get('/trasladar',  [CierreAnoController::class, 'trasladar'])->name('trasladar');
    Route::post('/trasladar', [CierreAnoController::class, 'ejecutarTraslado'])->name('ejecutar-traslado');

    // Acta de Promoción PDF por grupo
    Route::get('/{grupo}/acta-pdf', [CierreAnoController::class, 'actaPdf'])->name('acta-pdf');

    // Generación masiva de boletines en ZIP
    Route::match(['get', 'post'], '/boletines-masivos', [CierreAnoController::class, 'boletinesMasivos'])->name('boletines-masivos');
});
