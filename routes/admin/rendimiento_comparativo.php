<?php

use App\Http\Controllers\Admin\RendimientoController;

// ── Reportes Comparativos de Rendimiento — requiere ver-estadisticas ─────────
Route::middleware('can:ver-estadisticas')->group(function () {
    Route::get('rendimiento/comparativo',         [RendimientoController::class, 'comparativo'])->name('rendimiento.comparativo');
    Route::get('rendimiento/ranking-asignaturas', [RendimientoController::class, 'rankingAsignaturas'])->name('rendimiento.rankingAsignaturas');
    Route::get('rendimiento/tendencia',           [RendimientoController::class, 'tendenciaGrupo'])->name('rendimiento.tendencia');
});
