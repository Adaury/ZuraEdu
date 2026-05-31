<?php

use App\Http\Controllers\Admin\ImportacionController;

/*
|--------------------------------------------------------------------------
| Rutas — Importaciones Masivas (Admin)
|--------------------------------------------------------------------------
|
| Estas rutas están incluidas dentro del grupo de middleware admin.access
| definido en routes/web.php y comparten el prefijo "admin" y el nombre
| "admin.".
|
| Módulos:
|   1. Calificaciones académicas (comp1_p1 … comp4_p4)
|   2. Lista de estudiantes con matrícula opcional
|
*/

// ── Hub principal ─────────────────────────────────────────────────────────
Route::get('importaciones', [ImportacionController::class, 'index'])
    ->name('importaciones.index')
    ->middleware('role:Administrador|Director|Coordinador Académico|Coordinador Primer Ciclo|Coordinador Segundo Ciclo');

// ── Módulo 1: Calificaciones académicas — requiere permiso de ingreso ─────
Route::prefix('importaciones/calificaciones')->name('importaciones.calificaciones')
    ->middleware('can:ingresar-calificaciones')
    ->group(function () {
        Route::get('/',          [ImportacionController::class, 'calificacionesForm'])->name('');
        Route::get('/plantilla', [ImportacionController::class, 'calificacionesPlantilla'])->name('.plantilla');
        Route::post('/',         [ImportacionController::class, 'calificacionesImportar'])->name('.importar');
    });

// ── Módulo 2: Estudiantes — requiere permiso de gestión ──────────────────
Route::prefix('importaciones/estudiantes')->name('importaciones.estudiantes')
    ->middleware('can:gestionar-estudiantes')
    ->group(function () {
        Route::get('/',          [ImportacionController::class, 'estudiantesForm'])->name('');
        Route::get('/plantilla', [ImportacionController::class, 'estudiantesPlantilla'])->name('.plantilla');
        Route::post('/',         [ImportacionController::class, 'estudiantesImportar'])->name('.importar');
    });
