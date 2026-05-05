<?php

use App\Http\Controllers\Admin\CalificacionController;
use App\Http\Controllers\Admin\CalificacionAcademicaController;
use App\Http\Controllers\Admin\AsistenciaController;
use App\Http\Controllers\Admin\BoletinController;
use App\Http\Controllers\Admin\BoletinConfigController;
use App\Http\Controllers\Admin\ConfigCalificacionController;
use App\Http\Controllers\Admin\IndicadorController;
use App\Http\Controllers\Admin\RegistroController;
use App\Http\Controllers\Admin\CompetenciaController;
use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\FamiliaProfesionalController;
use App\Http\Controllers\Admin\PlanificacionController;
use App\Http\Controllers\Admin\PlanClaseController;
use App\Http\Controllers\Admin\InstrumentoController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\ObservacionController;

// ── Asignaturas ───────────────────────────────────────────────────────────
Route::middleware('can:gestionar-asignaturas')->group(function () {
    Route::get('asignaturas/lista/pdf',         [AsignaturaController::class, 'listaPdf'])->name('asignaturas.lista-pdf');
    Route::get('asignaturas/lista/excel',       [AsignaturaController::class, 'listaExcel'])->name('asignaturas.lista-excel');
    Route::post('asignaturas/{asignatura}/ras', [AsignaturaController::class, 'guardarRas'])->name('asignaturas.guardar-ras');
    Route::resource('asignaturas', AsignaturaController::class)->except(['show']);
});

// ── Familias Profesionales (Área Técnica - Segundo Ciclo) ─────────────────
Route::middleware('can:gestionar-asignaturas')->group(function () {
    Route::get('familias/lista/pdf',                               [FamiliaProfesionalController::class, 'listaPdf'])->name('familias.lista-pdf');
    Route::get('familias/lista/excel',                             [FamiliaProfesionalController::class, 'listaExcel'])->name('familias.lista-excel');
    Route::get('familias',                                         [FamiliaProfesionalController::class, 'index'])->name('familias.index');
    Route::post('familias',                                        [FamiliaProfesionalController::class, 'store'])->name('familias.store');
    Route::put('familias/{familia}',                               [FamiliaProfesionalController::class, 'update'])->name('familias.update');
    Route::delete('familias/{familia}',                            [FamiliaProfesionalController::class, 'destroy'])->name('familias.destroy');
    Route::patch('familias/{familia}/toggle',                      [FamiliaProfesionalController::class, 'toggleActivo'])->name('familias.toggle');
    Route::post('familias/{familia}/asignaturas',                  [FamiliaProfesionalController::class, 'asignarAsignatura'])->name('familias.asignaturas.asignar');
    Route::delete('familias/{familia}/asignaturas/{asignatura}',   [FamiliaProfesionalController::class, 'quitarAsignatura'])->name('familias.asignaturas.quitar');
});

// ── Calificaciones ────────────────────────────────────────────────────────
Route::middleware('can:ver-calificaciones')->group(function () {
    Route::get('calificaciones',                          [CalificacionController::class,         'index'])->name('calificaciones.index');
    Route::get('calificaciones/import',                   [CalificacionController::class,         'import'])->name('calificaciones.import');
    Route::get('calificaciones/plantilla/descargar',      [CalificacionController::class,         'downloadTemplate'])->name('calificaciones.plantilla.descargar');
    Route::get('calificaciones/grilla',                   [CalificacionController::class,         'grilla'])->name('calificaciones.grilla');
    Route::get('calificaciones/resumen/excel',             [CalificacionController::class,         'resumenExcel'])->name('calificaciones.resumen.excel');
    Route::get('calificaciones/resumen/pdf',               [CalificacionController::class,         'resumenPdf'])->name('calificaciones.resumen.pdf');
    Route::get('calificaciones/progreso/pdf',             [CalificacionController::class,         'progresoPdf'])->name('calificaciones.progreso.pdf');
    Route::get('calificaciones/progreso/excel',           [CalificacionController::class,         'progresoExcel'])->name('calificaciones.progreso.excel');
    Route::get('calificaciones/resumen',                  [CalificacionController::class,         'resumen'])->name('calificaciones.resumen');
    Route::get('calificaciones/ranking',                  [CalificacionController::class,         'ranking'])->name('calificaciones.ranking');
    Route::get('calificaciones/ranking/pdf',              [CalificacionController::class,         'rankingPdf'])->name('calificaciones.ranking.pdf');
    Route::get('calificaciones/ranking/excel',            [CalificacionController::class,         'rankingExcel'])->name('calificaciones.ranking.excel');
    Route::get('calificaciones/acta/{asignacion}',        [CalificacionController::class,         'actaPdf'])->name('calificaciones.acta-pdf');
    Route::get('calificaciones/acta/{asignacion}/excel',  [CalificacionController::class,         'actaExcel'])->name('calificaciones.acta-excel');
    Route::get('calificaciones/planilla-academica',       [CalificacionAcademicaController::class,'planillaAcademica'])->name('calificaciones.planilla-academica');
    Route::get('calificaciones/planilla/pdf',             [CalificacionAcademicaController::class,'exportarPlanillaPdf'])->name('calificaciones.planilla.pdf');
    Route::get('calificaciones/planilla/excel',           [CalificacionAcademicaController::class,'exportarPlanillaExcel'])->name('calificaciones.planilla.excel');
});

Route::middleware('can:ingresar-calificaciones')->group(function () {
    Route::post('calificaciones/import',                  [CalificacionController::class,         'importStore'])->name('calificaciones.importStore')->middleware('throttle:10,1');
    Route::post('calificaciones/guardar',                 [CalificacionController::class,         'guardar'])->name('calificaciones.guardar');
    Route::post('calificaciones/publicar',                [CalificacionController::class,         'publicar'])->name('calificaciones.publicar');
    Route::post('calificaciones/guardar-academica',       [CalificacionAcademicaController::class,'guardarAcademica'])->name('calificaciones.guardar-academica');
    Route::post('calificaciones/publicar-academica',      [CalificacionAcademicaController::class,'publicarAcademica'])->name('calificaciones.publicar-academica');
    Route::post('calificaciones/guardar-ra-pesos',        [CalificacionController::class,         'guardarRaPesos'])->name('calificaciones.guardar-ra-pesos');
});

// ── Config Calificaciones / RA ────────────────────────────────────────────
Route::middleware('can:gestionar-configuracion')->group(function () {
    Route::get('config/calificacion',   [ConfigCalificacionController::class, 'index'])->name('config.calificacion');
    Route::post('config/calificacion',  [ConfigCalificacionController::class, 'update'])->name('config.calificacion.update');
    Route::get('config/ra',             [ConfigCalificacionController::class, 'indexRa'])->name('config.ra');
    Route::post('config/ra',            [ConfigCalificacionController::class, 'updateRa'])->name('config.ra.update');
    Route::get('config/ra/datos',       [ConfigCalificacionController::class, 'getRaDatos'])->name('config.ra.datos');
});

// ── Asistencia ────────────────────────────────────────────────────────────
Route::middleware('can:ver-asistencia')->group(function () {
    Route::get('asistencia',                        [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::get('asistencia/import',                 [AsistenciaController::class, 'import'])->name('asistencia.import');
    Route::get('asistencia/plantilla/descargar',    [AsistenciaController::class, 'downloadTemplate'])->name('asistencia.plantilla.descargar');
    Route::get('asistencia/reporte-mensual',        [AsistenciaController::class, 'reporteMensualPdf'])->name('asistencia.reporteMensual');
    Route::get('asistencia/reporte-mensual/excel',  [AsistenciaController::class, 'reporteMensualExcel'])->name('asistencia.reporteMensual.excel');
    Route::get('asistencia/estudiante/{matricula}', [AsistenciaController::class, 'reporteEstudiante'])->name('asistencia.reporteEstudiante');
    Route::get('asistencia/{asignacion}/registrar', [AsistenciaController::class, 'registrar'])->name('asistencia.registrar');
    Route::get('asistencia/{asignacion}/historial', [AsistenciaController::class, 'historial'])->name('asistencia.historial');
    Route::get('asistencia/{asignacion}/grilla/pdf', [AsistenciaController::class, 'grillaPdf'])->name('asistencia.grilla.pdf');
    Route::get('asistencia/{asignacion}/grilla',    [AsistenciaController::class, 'grilla'])->name('asistencia.grilla');
    Route::get('asistencia/{asignacion}/reporte/pdf',   [AsistenciaController::class, 'reportePdf'])->name('asistencia.reporte.pdf');
    Route::get('asistencia/{asignacion}/reporte',       [AsistenciaController::class, 'reporte'])->name('asistencia.reporte');
    Route::get('asistencia/{asignacion}/reporte/excel', [AsistenciaController::class, 'reporteExcel'])->name('asistencia.reporte.excel');
    Route::get('asistencia/{asignacion}/lista-blanco',    [AsistenciaController::class, 'listaBlancoPdf'])->name('asistencia.lista-blanco');
    Route::get('asistencia/{asignacion}/historial/excel', [AsistenciaController::class, 'historialExcel'])->name('asistencia.historial.excel');
    Route::get('asistencia/{asignacion}/historial/pdf',  [AsistenciaController::class, 'historialPdf'])->name('asistencia.historial.pdf');
});

Route::middleware('can:ingresar-asistencia')->group(function () {
    Route::post('asistencia/import',                [AsistenciaController::class, 'importStore'])->name('asistencia.importStore')->middleware('throttle:10,1');
    Route::post('asistencia/toggle',                [AsistenciaController::class, 'toggleEstado'])->name('asistencia.toggle');
    Route::post('asistencia/marcar-todos',          [AsistenciaController::class, 'marcarTodos'])->name('asistencia.marcarTodos');
    Route::post('asistencia/{asignacion}/guardar',  [AsistenciaController::class, 'guardar'])->name('asistencia.guardar');
});

// ── Boletines ─────────────────────────────────────────────────────────────
Route::middleware('can:ver-boletines')->group(function () {
    Route::get('boletines',                                  [BoletinController::class, 'index'])->name('boletines.index');
    Route::get('boletines/grupo',                            [BoletinController::class, 'grupo'])->name('boletines.grupo');
    Route::get('boletines/zip',                              [BoletinController::class, 'zipGrupo'])->name('boletines.zip');
    Route::get('boletines/{matricula}/{periodo}/ver',        [BoletinController::class, 'verEstudiante'])->name('boletines.ver');
    Route::get('boletines/{matricula}/{periodo}/pdf',        [BoletinController::class, 'pdf'])->name('boletines.pdf');
    Route::get('boletines/{matricula}/pdf-anual',            [BoletinController::class, 'pdfAnual'])->name('boletines.pdf-anual');
    Route::post('boletines/{matricula}/{periodo}/observacion',[BoletinController::class, 'guardarObservacion'])->name('boletines.obs.guardar');
    Route::delete('boletines/observacion/{observacion}',     [BoletinController::class, 'eliminarObservacion'])->name('boletines.obs.eliminar');
});

Route::middleware('can:gestionar-configuracion')->group(function () {
    Route::get('boletines/config',  [BoletinConfigController::class, 'index'])->name('boletines.config');
    Route::post('boletines/config', [BoletinConfigController::class, 'update'])->name('boletines.config.update');
});

// ── Indicadores de Aprendizaje ────────────────────────────────────────────
Route::middleware('can:gestionar-indicadores')->group(function () {
    Route::get('indicadores',                       [IndicadorController::class, 'index'])->name('indicadores.index');
    Route::post('indicadores',                      [IndicadorController::class, 'store'])->name('indicadores.store');
    Route::put('indicadores/{indicador}',           [IndicadorController::class, 'update'])->name('indicadores.update');
    Route::delete('indicadores/{indicador}',        [IndicadorController::class, 'destroy'])->name('indicadores.destroy');
    Route::get('indicadores/lista/pdf',              [IndicadorController::class, 'listaPdf'])->name('indicadores.lista-pdf');
    Route::get('indicadores/lista/excel',            [IndicadorController::class, 'listaExcel'])->name('indicadores.lista-excel');
    Route::get('indicadores/evaluaciones',          [IndicadorController::class, 'evaluaciones'])->name('indicadores.evaluaciones');
    Route::get('indicadores/evaluaciones/pdf',      [IndicadorController::class, 'evaluacionesPdf'])->name('indicadores.evaluaciones.pdf');
    Route::get('indicadores/evaluaciones/excel',    [IndicadorController::class, 'evaluacionesExcel'])->name('indicadores.evaluaciones.excel');
    Route::post('indicadores/evaluaciones/guardar', [IndicadorController::class, 'guardarEvaluacion'])->name('indicadores.evaluaciones.guardar');
});

// ── Registro Académico MINERD ─────────────────────────────────────────────
Route::prefix('registro')->name('registro.')->group(function () {
    Route::get('/',                              [RegistroController::class, 'index'])->name('index');
    Route::get('/{grupo}/calificaciones',        [RegistroController::class, 'calificaciones'])->name('calificaciones');
    Route::get('/{grupo}/calificaciones/pdf',    [RegistroController::class, 'calificacionesPdf'])->name('calificaciones.pdf');
    Route::get('/{grupo}/exportar-excel',        [RegistroController::class, 'exportarExcel'])->name('exportarExcel');
    Route::get('/{grupo}',                       [RegistroController::class, 'show'])->name('show');
    Route::post('/guardar',                      [RegistroController::class, 'guardar'])->name('guardar');
    Route::post('/guardar-lote',                 [RegistroController::class, 'guardarLote'])->name('guardar-lote');
    Route::post('/observacion',                  [RegistroController::class, 'guardarObservacion'])->name('observacion');
    Route::post('/{grupo}/calcular-promociones', [RegistroController::class, 'calcularPromociones'])->name('calcular-promociones');
    Route::get('/{grupo}/exportar-pdf',          [RegistroController::class, 'exportarPdf'])->name('exportarPdf');
});

// ── Competencias e Indicadores ────────────────────────────────────────────
Route::prefix('competencias')->name('competencias.')->group(function () {
    Route::get('/',                          [CompetenciaController::class, 'index'])->name('index');
    Route::post('/ce',                       [CompetenciaController::class, 'storeCompetencia'])->name('ce.store');
    Route::post('/ce/{competencia}',         [CompetenciaController::class, 'updateCompetencia'])->name('ce.update');
    Route::delete('/ce/{competencia}',       [CompetenciaController::class, 'destroyCompetencia'])->name('ce.destroy');
    Route::post('/il',                       [CompetenciaController::class, 'storeIndicador'])->name('il.store');
    Route::post('/il/{indicador}',           [CompetenciaController::class, 'updateIndicador'])->name('il.update');
    Route::delete('/il/{indicador}',         [CompetenciaController::class, 'destroyIndicador'])->name('il.destroy');
    Route::post('/ce/reordenar',             [CompetenciaController::class, 'reordenarCompetencias'])->name('ce.reordenar');
    Route::post('/il/reordenar',             [CompetenciaController::class, 'reordenarIndicadores'])->name('il.reordenar');
});


// ── Planes de Clase ───────────────────────────────────────────────────────
Route::prefix('planes-clase')->name('planes-clase.')->group(function () {
    Route::get('/',                          [PlanClaseController::class, 'index'])->name('index');
    Route::get('/crear',                     [PlanClaseController::class, 'create'])->name('create');
    Route::post('/',                         [PlanClaseController::class, 'store'])->name('store');
    Route::get('/{planesClase}',             [PlanClaseController::class, 'show'])->name('show');
    Route::get('/{planesClase}/editar',      [PlanClaseController::class, 'edit'])->name('edit');
    Route::put('/{planesClase}',             [PlanClaseController::class, 'update'])->name('update');
    Route::delete('/{planesClase}',          [PlanClaseController::class, 'destroy'])->name('destroy');
    Route::get('/{planesClase}/descargar',   [PlanClaseController::class, 'download'])->name('download');
});

// ── Instrumentos de Evaluación ────────────────────────────────────────────
Route::prefix('instrumentos')->name('instrumentos.')->group(function () {
    Route::get('/',                               [InstrumentoController::class, 'index'])->name('index');
    Route::get('/crear',                          [InstrumentoController::class, 'create'])->name('create');
    Route::post('/',                              [InstrumentoController::class, 'store'])->name('store');
    Route::get('/lista/pdf',                      [InstrumentoController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/lista/excel',                    [InstrumentoController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/{instrumento}',                  [InstrumentoController::class, 'show'])->name('show');
    Route::post('/{instrumento}/registrar',       [InstrumentoController::class, 'registrar'])->name('registrar');
    Route::delete('/{instrumento}',               [InstrumentoController::class, 'destroy'])->name('destroy');
});

// ── Auditoría de Calificaciones ───────────────────────────────────────────
Route::get('calificaciones/auditoria', [CalificacionController::class, 'auditoria'])->name('calificaciones.auditoria');

// ── Observaciones de Docentes ─────────────────────────────────────────────
Route::get('observaciones',                       [ObservacionController::class, 'index'])->name('observaciones.index');
Route::get('observaciones/pdf',                   [ObservacionController::class, 'pdf'])->name('observaciones.pdf');
Route::get('observaciones/excel',                 [ObservacionController::class, 'excel'])->name('observaciones.excel');
Route::delete('observaciones/{observacion}',      [ObservacionController::class, 'destroy'])->name('observaciones.destroy');
Route::patch('observaciones/{observacion}/privada',[ObservacionController::class, 'togglePrivada'])->name('observaciones.toggle-privada');

// ── Homepage Editor ───────────────────────────────────────────────────────
Route::get('homepage',  [HomepageController::class, 'edit'])->name('homepage.edit');
Route::post('homepage', [HomepageController::class, 'update'])->name('homepage.update');

// ── Planificaciones Área Técnica ──────────────────────────────────────────
Route::prefix('planificacion')->name('planificacion.')->group(function () {
    Route::get('/',                                [PlanificacionController::class, 'index'])->name('index');
    Route::get('/nueva/ra',                        [PlanificacionController::class, 'createRa'])->name('create-ra');
    Route::post('/nueva/ra',                       [PlanificacionController::class, 'storeRa'])->name('store-ra');
    Route::get('/nueva/actividad',                 [PlanificacionController::class, 'createActividad'])->name('create-actividad');
    Route::post('/nueva/actividad',                [PlanificacionController::class, 'storeActividad'])->name('store-actividad');
    Route::get('/lista/pdf',                       [PlanificacionController::class, 'listaPdf'])->name('lista-pdf');
    Route::get('/lista/excel',                     [PlanificacionController::class, 'listaExcel'])->name('lista-excel');
    Route::get('/cumplimiento/excel',              [PlanificacionController::class, 'cumplimientoExcel'])->name('cumplimiento-excel');
    Route::get('/cumplimiento/pdf',               [PlanificacionController::class, 'cumplimientoPdf'])->name('cumplimiento-pdf');
    Route::get('/{planificacion}',                 [PlanificacionController::class, 'show'])->name('show');
    Route::get('/{planificacion}/pdf',             [PlanificacionController::class, 'pdf'])->name('pdf');
    Route::get('/{planificacion}/editar',          [PlanificacionController::class, 'edit'])->name('edit');
    Route::put('/{planificacion}',                 [PlanificacionController::class, 'update'])->name('update');
    Route::delete('/{planificacion}',              [PlanificacionController::class, 'destroy'])->name('destroy');
    Route::patch('/{planificacion}/publicado',     [PlanificacionController::class, 'togglePublicado'])->name('toggle-publicado');
});
