<?php

use App\Http\Controllers\Admin\RegistroAcademicoController;

Route::prefix('registro-academico')->name('registro-academico.')->group(function () {

    // Dashboard principal
    Route::get('/', [RegistroAcademicoController::class, 'dashboard'])->name('dashboard');

    // Estudiantes sin grupo asignado
    Route::get('/sin-grupo', [RegistroAcademicoController::class, 'sinGrupo'])->name('sin-grupo');

    // Bajas / Retiros
    Route::get('/bajas',                                         [RegistroAcademicoController::class, 'bajas'])->name('bajas');
    Route::get('/matriculas/{matricula}/baja',                   [RegistroAcademicoController::class, 'formBaja'])->name('baja.form');
    Route::post('/matriculas/{matricula}/baja',                  [RegistroAcademicoController::class, 'registrarBaja'])->name('baja.registrar');
    Route::patch('/matriculas/{matricula}/reactivar',            [RegistroAcademicoController::class, 'reactivar'])->name('baja.reactivar');

    // Traslados entre instituciones
    Route::get('/traslados',                                     [RegistroAcademicoController::class, 'traslados'])->name('traslados');
    Route::get('/estudiantes/{estudiante}/traslado',             [RegistroAcademicoController::class, 'formTraslado'])->name('traslado.form');
    Route::post('/estudiantes/{estudiante}/traslado',            [RegistroAcademicoController::class, 'registrarTraslado'])->name('traslado.registrar');

    // Reporte consolidado de matrícula
    Route::get('/reporte-consolidado',       [RegistroAcademicoController::class, 'reporteConsolidado'])->name('reporte-consolidado');
    Route::get('/reporte-consolidado/pdf',   [RegistroAcademicoController::class, 'reporteConsolidadoPdf'])->name('reporte-consolidado.pdf');
    Route::get('/reporte-consolidado/excel', [RegistroAcademicoController::class, 'reporteConsolidadoExcel'])->name('reporte-consolidado.excel');
});
