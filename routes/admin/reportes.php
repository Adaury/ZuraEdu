<?php

use App\Http\Controllers\Admin\ReportesController;
use App\Http\Controllers\Admin\RendimientoController;
use App\Http\Controllers\Admin\AlertaController;
use App\Http\Controllers\Admin\CalendarioController;

// ── Reportes Institucionales ──────────────────────────────────────────────
Route::get('reportes',                    [ReportesController::class, 'index'])->name('reportes.index');
Route::get('reportes/consolidado',        [ReportesController::class, 'consolidado'])->name('reportes.consolidado');
Route::get('reportes/consolidado/pdf',    [ReportesController::class, 'consolidadoPdf'])->name('reportes.consolidado.pdf');
Route::get('reportes/consolidado/excel',  [ReportesController::class, 'consolidadoExcel'])->name('reportes.consolidado.excel');
Route::get('reportes/situacion',          [ReportesController::class, 'situacion'])->name('reportes.situacion');
Route::get('reportes/situacion/pdf',      [ReportesController::class, 'situacionPdf'])->name('reportes.situacion.pdf');
Route::get('reportes/asistencia',         [ReportesController::class, 'asistencia'])->name('reportes.asistencia');
Route::get('reportes/asistencia/excel',   [ReportesController::class, 'asistenciaExcel'])->name('reportes.asistencia.excel');
Route::get('reportes/asistencia/pdf',     [ReportesController::class, 'asistenciaPdf'])->name('reportes.asistencia.pdf');
Route::get('reportes/situacion/excel',    [ReportesController::class, 'situacionExcel'])->name('reportes.situacion.excel');

// ── Dashboard de Rendimiento ──────────────────────────────────────────────
Route::get('rendimiento',             [RendimientoController::class, 'dashboard'])->name('rendimiento.dashboard');
Route::get('rendimiento/pdf',         [RendimientoController::class, 'dashboardPdf'])->name('rendimiento.pdf');
Route::get('rendimiento/excel',       [RendimientoController::class, 'dashboardExcel'])->name('rendimiento.excel');
Route::get('rendimiento/por-grupo/pdf',  [RendimientoController::class, 'porGrupoPdf'])->name('rendimiento.porGrupo.pdf');
Route::get('rendimiento/por-grupo/excel',[RendimientoController::class, 'porGrupoExcel'])->name('rendimiento.porGrupo.excel');
Route::get('rendimiento/por-grupo',      [RendimientoController::class, 'porGrupo'])->name('rendimiento.porGrupo');
Route::get('rendimiento/por-area/pdf',   [RendimientoController::class, 'porAreaPdf'])->name('rendimiento.porArea.pdf');
Route::get('rendimiento/por-area/excel', [RendimientoController::class, 'porAreaExcel'])->name('rendimiento.porArea.excel');
Route::get('rendimiento/por-area',       [RendimientoController::class, 'porArea'])->name('rendimiento.porArea');
Route::get('rendimiento/semaforo',       [RendimientoController::class, 'semaforo'])->name('rendimiento.semaforo');
Route::get('rendimiento/semaforo/pdf',   [RendimientoController::class, 'semaforoPdf'])->name('rendimiento.semaforo.pdf');
Route::get('rendimiento/semaforo/excel', [RendimientoController::class, 'semaforoExcel'])->name('rendimiento.semaforo.excel');
Route::post('rendimiento/recalcular',    [RendimientoController::class, 'recalcular'])->name('rendimiento.recalcular');
Route::get('rendimiento/recuperaciones',      [RendimientoController::class, 'recuperaciones'])->name('rendimiento.recuperaciones');
Route::get('rendimiento/recuperaciones/pdf',   [RendimientoController::class, 'recuperacionesPdf'])->name('rendimiento.recuperaciones.pdf');
Route::get('rendimiento/recuperaciones/excel', [RendimientoController::class, 'recuperacionesExcel'])->name('rendimiento.recuperaciones.excel');
Route::get('rendimiento/rezagados',         [RendimientoController::class, 'rezagados'])->name('rendimiento.rezagados');
Route::get('rendimiento/rezagados/pdf',     [RendimientoController::class, 'rezagadosPdf'])->name('rendimiento.rezagados.pdf');
Route::get('rendimiento/rezagados/excel',   [RendimientoController::class, 'rezagadosExcel'])->name('rendimiento.rezagados.excel');

// ── Alertas ───────────────────────────────────────────────────────────────
Route::get('alertas',                      [AlertaController::class, 'index'])->name('alertas.index');
Route::get('alertas/pdf',                  [AlertaController::class, 'pdf'])->name('alertas.pdf');
Route::get('alertas/excel',                [AlertaController::class, 'excel'])->name('alertas.excel');
Route::get('alertas/conteo',               [AlertaController::class, 'conteo'])->name('alertas.conteo');
Route::patch('alertas/{alerta}/leer',      [AlertaController::class, 'marcarLeida'])->name('alertas.leer');
Route::post('alertas/leer-todas',          [AlertaController::class, 'marcarTodasLeidas'])->name('alertas.leerTodas');
Route::delete('alertas/{alerta}',          [AlertaController::class, 'destroy'])->name('alertas.destroy');
Route::post('alertas/generar-academicas',  [AlertaController::class, 'generarAcademicas'])->name('alertas.generarAcademicas');

// ── Calendario Académico ──────────────────────────────────────────────────
Route::get('calendario',               [CalendarioController::class, 'index'])->name('calendario.index');
Route::get('calendario/excel',         [CalendarioController::class, 'excel'])->name('calendario.excel');
Route::get('calendario/pdf',           [CalendarioController::class, 'pdf'])->name('calendario.pdf');
Route::get('calendario/api',           [CalendarioController::class, 'api'])->name('calendario.api');
Route::get('calendario/create',        [CalendarioController::class, 'create'])->name('calendario.create');
Route::post('calendario',              [CalendarioController::class, 'store'])->name('calendario.store');
Route::get('calendario/{evento}/edit', [CalendarioController::class, 'edit'])->name('calendario.edit');
Route::put('calendario/{evento}',      [CalendarioController::class, 'update'])->name('calendario.update');
Route::delete('calendario/{evento}',   [CalendarioController::class, 'destroy'])->name('calendario.destroy');
