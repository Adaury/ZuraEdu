<?php

use App\Http\Controllers\Admin\HorarioController;

// ── Horarios (módulo principal) ───────────────────────────────────────────
// Vista propia del docente: visible para todo el personal docente-capable, no solo gestión.
Route::prefix('horarios')->name('horarios.')->middleware('can:ingresar-calificaciones')->group(function () {
    Route::get('/mi-horario',                        [HorarioController::class, 'miHorario'])->name('mi-horario');
    Route::get('/horario-docente',                   [HorarioController::class, 'horarioDocente'])->name('horario-docente');
    Route::get('/horario-docente/pdf',               [HorarioController::class, 'horarioDocentePdf'])->name('horario-docente.pdf');
});

Route::prefix('horarios')->name('horarios.')->middleware('can:gestionar-asignaciones')->group(function () {
    Route::get('/',                                  [HorarioController::class, 'index'])->name('index');
    Route::get('/vista-maestra',                     [HorarioController::class, 'vistaMaestra'])->name('vista-maestra');
    Route::get('/vista-maestra/pdf',                 [HorarioController::class, 'vistaMaestraPdf'])->name('vista-maestra.pdf');
    Route::get('/vista-maestra/excel',               [HorarioController::class, 'vistaMaestraExcel'])->name('vista-maestra.excel');
    Route::post('/generar',                          [HorarioController::class, 'generar'])->name('generar')->middleware('throttle:3,5');

    // Suplencias (rutas estáticas antes del parámetro dinámico /{horario})
    Route::get('/suplencias',                        [HorarioController::class, 'suplencias'])->name('suplencias');
    Route::post('/suplencias',                       [HorarioController::class, 'suplenciaStore'])->name('suplencias.store');
    Route::put('/suplencias/{suplencia}',            [HorarioController::class, 'suplenciaUpdate'])->name('suplencias.update');
    Route::get('/suplencias/pdf',                    [HorarioController::class, 'suplenciasPdf'])->name('suplencias.pdf');
    Route::get('/suplencias/excel',                  [HorarioController::class, 'suplenciasExcel'])->name('suplencias.excel');

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

    // Configuración global
    Route::get('/config/general',                    [HorarioController::class, 'configuracion'])->name('configuracion');
    Route::post('/config/general',                   [HorarioController::class, 'configuracionGuardar'])->name('configuracion.guardar');

    // Rutas con parámetro dinámico (deben ir AL FINAL para no capturar rutas estáticas)
    Route::post('/{horario}/publicar',               [HorarioController::class, 'publicar'])->name('publicar');
    Route::post('/{horario}/regenerar',              [HorarioController::class, 'regenerar'])->name('regenerar');
    Route::patch('/detalle/{detalle}/mover',         [HorarioController::class, 'moverDetalle'])->name('detalle.mover');
    Route::delete('/{horario}/limpiar',              [HorarioController::class, 'limpiar'])->name('limpiar');

    // Celdas del horario (CRUD manual)
    Route::post('/{horario}/detalles',               [HorarioController::class, 'detalleStore'])->name('detalle.store');
    Route::put('/{horario}/detalles/{detalle}',      [HorarioController::class, 'detalleUpdate'])->name('detalle.update');
    Route::delete('/{horario}/detalles/{detalle}',   [HorarioController::class, 'detalleDestroy'])->name('detalle.destroy');
    Route::get('/{horario}/detalles/form-data',      [HorarioController::class, 'detalleFormData'])->name('detalle.form-data');

    Route::get('/{horario}',                         [HorarioController::class, 'show'])->name('show');
});
