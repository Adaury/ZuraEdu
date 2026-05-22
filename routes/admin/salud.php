<?php

use App\Http\Controllers\Admin\SaludController;

// ── Salud Escolar ──────────────────────────────────────────────────────────

// Dashboard
Route::get('salud',
    [SaludController::class, 'dashboard'])
    ->name('salud.dashboard');

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

Route::get('salud/incidentes/{incidente}/editar',
    [SaludController::class, 'editarIncidente'])
    ->name('salud.incidentes.editar');

Route::put('salud/incidentes/{incidente}',
    [SaludController::class, 'actualizarIncidente'])
    ->name('salud.incidentes.actualizar');

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
