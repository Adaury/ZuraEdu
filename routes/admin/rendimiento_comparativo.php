<?php

use App\Http\Controllers\Admin\RendimientoController;

// ── Reportes Comparativos de Rendimiento ─────────────────────────────────────
Route::get('rendimiento/comparativo',           [RendimientoController::class, 'comparativo'])->name('rendimiento.comparativo');
Route::get('rendimiento/ranking-asignaturas',   [RendimientoController::class, 'rankingAsignaturas'])->name('rendimiento.rankingAsignaturas');
Route::get('rendimiento/tendencia',             [RendimientoController::class, 'tendenciaGrupo'])->name('rendimiento.tendencia');
