<?php

use App\Http\Controllers\Admin\SaludController;

// ── Salud Escolar ──────────────────────────────────────────────────────────

// Ficha de salud por estudiante
Route::get('salud/{estudiante}/ficha',
    [SaludController::class, 'ficha'])
    ->name('salud.ficha');

Route::post('salud/{estudiante}/ficha',
    [SaludController::class, 'guardarFicha'])
    ->name('salud.guardar-ficha');

// PDF ficha médica
Route::get('salud/{estudiante}/ficha-pdf',
    [SaludController::class, 'fichaPdf'])
    ->name('salud.ficha-pdf');

// Incidentes médicos
Route::get('salud/incidentes',
    [SaludController::class, 'incidentes'])
    ->name('salud.incidentes');

Route::get('salud/incidentes/crear',
    [SaludController::class, 'crearIncidente'])
    ->name('salud.incidentes.crear');

Route::post('salud/incidentes',
    [SaludController::class, 'guardarIncidente'])
    ->name('salud.incidentes.guardar');

Route::delete('salud/incidentes/{incidente}',
    [SaludController::class, 'eliminarIncidente'])
    ->name('salud.incidentes.eliminar');

// Excel incidentes
Route::get('salud/incidentes/excel',
    [SaludController::class, 'incidentesExcel'])
    ->name('salud.incidentes.excel');

// PDF lista de incidentes
Route::get('salud/incidentes/pdf',
    [SaludController::class, 'incidentesPdf'])
    ->name('salud.incidentes.pdf');
