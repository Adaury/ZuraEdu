<?php

use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Scheduling\HorarioController as SchHorarioController;

// ── Horarios (módulo principal) ───────────────────────────────────────────
Route::prefix('horarios')->name('horarios.')->group(function () {
    Route::get('/',                                  [HorarioController::class, 'index'])->name('index');
    Route::get('/mi-horario',                        [HorarioController::class, 'miHorario'])->name('mi-horario');
    Route::get('/horario-docente',                   [HorarioController::class, 'horarioDocente'])->name('horario-docente');
    Route::get('/horario-docente/pdf',               [HorarioController::class, 'horarioDocentePdf'])->name('horario-docente.pdf');
    Route::get('/vista-maestra',                     [HorarioController::class, 'vistaMaestra'])->name('vista-maestra');
    Route::get('/vista-maestra/pdf',                 [HorarioController::class, 'vistaMaestraPdf'])->name('vista-maestra.pdf');
    Route::get('/vista-maestra/excel',               [HorarioController::class, 'vistaMaestraExcel'])->name('vista-maestra.excel');
    Route::post('/generar',                          [HorarioController::class, 'generar'])->name('generar')->middleware('throttle:3,5');
    Route::post('/{horario}/publicar',               [HorarioController::class, 'publicar'])->name('publicar');
    Route::post('/{horario}/regenerar',              [HorarioController::class, 'regenerar'])->name('regenerar');
    Route::patch('/detalle/{detalle}/mover',         [HorarioController::class, 'moverDetalle'])->name('detalle.mover');
    Route::delete('/{horario}/limpiar',              [HorarioController::class, 'limpiar'])->name('limpiar');
    Route::get('/{horario}',                         [HorarioController::class, 'show'])->name('show');

    // Celdas del horario (CRUD manual)
    Route::post('/{horario}/detalles',               [HorarioController::class, 'detalleStore'])->name('detalle.store');
    Route::put('/{horario}/detalles/{detalle}',      [HorarioController::class, 'detalleUpdate'])->name('detalle.update');
    Route::delete('/{horario}/detalles/{detalle}',   [HorarioController::class, 'detalleDestroy'])->name('detalle.destroy');
    Route::get('/{horario}/detalles/form-data',      [HorarioController::class, 'detalleFormData'])->name('detalle.form-data');

    // Aulas
    Route::get('/config/aulas',                      [HorarioController::class, 'aulas'])->name('aulas');
    Route::post('/config/aulas',                     [HorarioController::class, 'aulaStore'])->name('aulas.store');
    Route::put('/config/aulas/{aula}',               [HorarioController::class, 'aulaUpdate'])->name('aulas.update');
    Route::delete('/config/aulas/{aula}',            [HorarioController::class, 'aulaDestroy'])->name('aulas.destroy');

    // Franjas
    Route::get('/config/franjas',                    [HorarioController::class, 'franjas'])->name('franjas');
    Route::post('/config/franjas',                   [HorarioController::class, 'franjaStore'])->name('franjas.store');
    Route::put('/config/franjas/{franja}',           [HorarioController::class, 'franjaUpdate'])->name('franjas.update');
    Route::delete('/config/franjas/{franja}',        [HorarioController::class, 'franjaDestroy'])->name('franjas.destroy');

    // Disponibilidad docentes
    Route::get('/config/disponibilidad',             [HorarioController::class, 'disponibilidad'])->name('disponibilidad');
    Route::post('/config/disponibilidad',            [HorarioController::class, 'disponibilidadGuardar'])->name('disponibilidad.guardar');

    // Suplencias
    Route::get('/suplencias',                        [HorarioController::class, 'suplencias'])->name('suplencias');
    Route::post('/suplencias',                       [HorarioController::class, 'suplenciaStore'])->name('suplencias.store');
    Route::put('/suplencias/{suplencia}',            [HorarioController::class, 'suplenciaUpdate'])->name('suplencias.update');
    Route::get('/suplencias/pdf',                    [HorarioController::class, 'suplenciasPdf'])->name('suplencias.pdf');
    Route::get('/suplencias/excel',                  [HorarioController::class, 'suplenciasExcel'])->name('suplencias.excel');

    // Configuración global
    Route::get('/config/general',                    [HorarioController::class, 'configuracion'])->name('configuracion');
    Route::post('/config/general',                   [HorarioController::class, 'configuracionGuardar'])->name('configuracion.guardar');
});

// ── Scheduling (módulo simplificado) ─────────────────────────────────────
Route::prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('/horarios',                          [SchHorarioController::class, 'index'])->name('horarios.index');
    Route::post('/horarios/generar',                 [SchHorarioController::class, 'generar'])->name('horarios.generar');
    Route::get('/horarios/{horario}',                [SchHorarioController::class, 'show'])->name('horarios.show');
    Route::post('/horarios/{horario}/publicar',      [SchHorarioController::class, 'publicar'])->name('horarios.publicar');
    Route::delete('/horarios/{horario}',             [SchHorarioController::class, 'destroy'])->name('horarios.destroy');
    Route::get('/configuracion',                     [SchHorarioController::class, 'configuracion'])->name('configuracion');
    Route::post('/cursos',                           [SchHorarioController::class, 'cursoStore'])->name('cursos.store');
    Route::delete('/cursos/{curso}',                 [SchHorarioController::class, 'cursoDestroy'])->name('cursos.destroy');
    Route::post('/materias',                         [SchHorarioController::class, 'materiaStore'])->name('materias.store');
    Route::delete('/materias/{materia}',             [SchHorarioController::class, 'materiaDestroy'])->name('materias.destroy');
    Route::post('/profesores',                       [SchHorarioController::class, 'profesorStore'])->name('profesores.store');
    Route::delete('/profesores/{profesor}',          [SchHorarioController::class, 'profesorDestroy'])->name('profesores.destroy');
    Route::post('/aulas',                            [SchHorarioController::class, 'aulaStore'])->name('aulas.store');
    Route::delete('/aulas/{aula}',                   [SchHorarioController::class, 'aulaDestroy'])->name('aulas.destroy');
    Route::post('/franjas',                          [SchHorarioController::class, 'franjaStore'])->name('franjas.store');
    Route::delete('/franjas/{franja}',               [SchHorarioController::class, 'franjaDestroy'])->name('franjas.destroy');
    Route::post('/asignaciones',                     [SchHorarioController::class, 'asignacionStore'])->name('asignaciones.store');
    Route::delete('/asignaciones/{asignacion}',      [SchHorarioController::class, 'asignacionDestroy'])->name('asignaciones.destroy');
    Route::post('/disponibilidad',                   [SchHorarioController::class, 'disponibilidadGuardar'])->name('disponibilidad.guardar');
});
