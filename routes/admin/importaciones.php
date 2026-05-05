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
    ->name('importaciones.index');

// ── Módulo 1: Calificaciones académicas ──────────────────────────────────
Route::prefix('importaciones/calificaciones')->name('importaciones.calificaciones')->group(function () {

    // GET  /admin/importaciones/calificaciones            → formulario
    Route::get('/',          [ImportacionController::class, 'calificacionesForm'])
        ->name('');                         // → admin.importaciones.calificaciones

    // GET  /admin/importaciones/calificaciones/plantilla  → descarga CSV/XLSX
    Route::get('/plantilla', [ImportacionController::class, 'calificacionesPlantilla'])
        ->name('.plantilla');               // → admin.importaciones.calificaciones.plantilla

    // POST /admin/importaciones/calificaciones            → procesar archivo
    Route::post('/',         [ImportacionController::class, 'calificacionesImportar'])
        ->name('.importar');                // → admin.importaciones.calificaciones.importar
});

// ── Módulo 2: Estudiantes ─────────────────────────────────────────────────
Route::prefix('importaciones/estudiantes')->name('importaciones.estudiantes')->group(function () {

    // GET  /admin/importaciones/estudiantes               → formulario
    Route::get('/',          [ImportacionController::class, 'estudiantesForm'])
        ->name('');                         // → admin.importaciones.estudiantes

    // GET  /admin/importaciones/estudiantes/plantilla     → descarga CSV/XLSX
    Route::get('/plantilla', [ImportacionController::class, 'estudiantesPlantilla'])
        ->name('.plantilla');               // → admin.importaciones.estudiantes.plantilla

    // POST /admin/importaciones/estudiantes               → procesar archivo
    Route::post('/',         [ImportacionController::class, 'estudiantesImportar'])
        ->name('.importar');                // → admin.importaciones.estudiantes.importar
});
