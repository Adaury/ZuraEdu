<?php

use App\Http\Controllers\Admin\DocenteController;
use App\Http\Controllers\Admin\DocenteSetupController;
use App\Http\Controllers\Admin\EstudianteController;
use App\Http\Controllers\Admin\GrupoController;
use App\Http\Controllers\Admin\MatriculaController;
use App\Http\Controllers\Admin\AsignacionController;
use App\Http\Controllers\Admin\PerfilDocenteController;
use App\Http\Controllers\Admin\PerfilEstudianteController;

// ── Docentes ──────────────────────────────────────────────────────────────
Route::middleware('can:gestionar-docentes')->group(function () {
    Route::get('docentes/por-area/pdf',           [DocenteController::class, 'porAreaPdf'])->name('docentes.porArea.pdf');
    Route::get('docentes/por-area/excel',         [DocenteController::class, 'porAreaExcel'])->name('docentes.porArea.excel');
    Route::get('docentes/por-area',               [DocenteController::class, 'porArea'])->name('docentes.porArea');
    Route::get('docentes/import',                 [DocenteController::class, 'import'])->name('docentes.import');
    Route::post('docentes/import',                [DocenteController::class, 'importStore'])->name('docentes.importStore');
    Route::post('docentes/import/preview',        [DocenteController::class, 'importPreview'])->name('docentes.importPreview');
    Route::post('docentes/import/confirm',        [DocenteController::class, 'importConfirm'])->name('docentes.importConfirm');
    Route::get('docentes/plantilla/descargar',    [DocenteController::class, 'downloadTemplate'])->name('docentes.plantilla.descargar');
    Route::get('docentes/lista/excel',            [DocenteController::class, 'listaExcel'])->name('docentes.lista-excel');
    Route::get('docentes/lista/pdf',              [DocenteController::class, 'listaPdf'])->name('docentes.lista-pdf');
    Route::resource('docentes', DocenteController::class);
});

// ── Estudiantes ───────────────────────────────────────────────────────────
Route::middleware('can:gestionar-estudiantes')->group(function () {
    Route::get('estudiantes/import',              [EstudianteController::class, 'import'])->name('estudiantes.import');
    Route::post('estudiantes/import',             [EstudianteController::class, 'importStore'])->name('estudiantes.importStore');
    Route::post('estudiantes/import/preview',     [EstudianteController::class, 'importPreview'])->name('estudiantes.importPreview');
    Route::post('estudiantes/import/confirm',     [EstudianteController::class, 'importConfirm'])->name('estudiantes.importConfirm');
    Route::get('estudiantes/plantilla/descargar', [EstudianteController::class, 'downloadTemplate'])->name('estudiantes.plantilla.descargar');
    Route::get('estudiantes/lista/excel',         [EstudianteController::class, 'listaExcel'])->name('estudiantes.lista-excel');
    Route::get('estudiantes/lista/pdf',           [EstudianteController::class, 'listaPdf'])->name('estudiantes.lista-pdf');
    Route::get('representantes/lista/pdf',         [EstudianteController::class, 'representantesPdf'])->name('representantes.lista-pdf');
    Route::get('representantes/lista/excel',      [EstudianteController::class, 'representantesExcel'])->name('representantes.lista-excel');
    Route::resource('estudiantes', EstudianteController::class);
});

// ── Grupos / Secciones ────────────────────────────────────────────────────
Route::middleware('can:gestionar-grupos')->group(function () {
    Route::get('grupos/lista/excel',             [GrupoController::class, 'gruposExcel'])->name('grupos.lista-excel-general');
    Route::get('grupos/lista/pdf',               [GrupoController::class, 'gruposPdf'])->name('grupos.lista-pdf-general');
    Route::patch('grupos/{grupo}/tutor',         [GrupoController::class, 'updateTutor'])->name('grupos.updateTutor');
    Route::get('grupos/{grupo}/lista-pdf',       [GrupoController::class, 'listaPdf'])->name('grupos.lista-pdf');
    Route::get('grupos/{grupo}/carnets-pdf',     [GrupoController::class, 'carnetsPdf'])->name('grupos.carnets-pdf');
    Route::get('grupos/{grupo}/lista-excel',       [GrupoController::class, 'listaExcel'])->name('grupos.lista-excel');
    Route::get('grupos/{grupo}/notas-excel',       [GrupoController::class, 'notasExcel'])->name('grupos.notas-excel');
    Route::get('grupos/{grupo}/asistencia-excel',  [GrupoController::class, 'asistenciaExcel'])->name('grupos.asistencia-excel');
    Route::get('grupos/{grupo}/asistencia-pdf',    [GrupoController::class, 'asistenciaPdf'])->name('grupos.asistencia-pdf');
    Route::get('grupos/{grupo}/notas-pdf',         [GrupoController::class, 'notasPdf'])->name('grupos.notas-pdf');
    Route::resource('grupos', GrupoController::class);
    Route::post('secciones',              [\App\Http\Controllers\Admin\SeccionController::class, 'store'])->name('secciones.store');
    Route::put('secciones/{seccion}',     [\App\Http\Controllers\Admin\SeccionController::class, 'update'])->name('secciones.update');
    Route::delete('secciones/{seccion}',  [\App\Http\Controllers\Admin\SeccionController::class, 'destroy'])->name('secciones.destroy');
});

// ── Matrículas + Asignaciones ─────────────────────────────────────────────
Route::middleware('can:gestionar-matriculas')->group(function () {
    Route::patch('matriculas/{matricula}/cambiar-grupo',  [MatriculaController::class, 'cambiarGrupo'])->name('matriculas.cambiarGrupo');
    Route::get('matriculas/{matricula}/constancia',         [MatriculaController::class, 'constancia'])->name('matriculas.constancia');
    Route::get('matriculas/{matricula}/constancia-estudios',[MatriculaController::class, 'constanciaEstudios'])->name('matriculas.constancia-estudios');
    Route::get('matriculas/lista/pdf',                      [MatriculaController::class, 'listaPdf'])->name('matriculas.lista-pdf');
    Route::get('matriculas/lista/excel',                    [MatriculaController::class, 'listaExcel'])->name('matriculas.lista-excel');
    Route::resource('matriculas', MatriculaController::class)->except(['edit', 'update']);
    Route::get('asignaciones/lista/pdf',   [AsignacionController::class, 'listaPdf'])->name('asignaciones.lista-pdf');
    Route::get('asignaciones/lista/excel', [AsignacionController::class, 'listaExcel'])->name('asignaciones.lista-excel');
    Route::resource('asignaciones', AsignacionController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::patch('asignaciones/{asignacion}/docente', [AsignacionController::class, 'asignarDocente'])->name('asignaciones.asignarDocente');
});

// ── Docente Setup (onboarding) ────────────────────────────────────────────
Route::get('docente/setup',  [DocenteSetupController::class, 'show'])->name('docente.setup');
Route::post('docente/setup', [DocenteSetupController::class, 'store'])->name('docente.setup.store');

// ── Perfiles ──────────────────────────────────────────────────────────────
Route::get('perfiles/mi-perfil',                 [PerfilDocenteController::class,    'miPerfil'])->name('perfiles.miPerfil');
Route::get('perfiles/docentes/{docente}',           [PerfilDocenteController::class, 'show'])->name('perfiles.docente');
Route::get('perfiles/docentes/{docente}/informe',       [PerfilDocenteController::class, 'informePdf'])->name('perfiles.docente.informe-pdf');
Route::get('perfiles/docentes/{docente}/informe-excel', [PerfilDocenteController::class, 'informeExcel'])->name('perfiles.docente.informe-excel');
Route::get('perfiles/estudiantes/{estudiante}',   [PerfilEstudianteController::class, 'show'])->name('perfiles.estudiante');
Route::get('perfiles/estudiantes/{estudiante}/informe-pdf',     [PerfilEstudianteController::class, 'informePdf'])->name('perfiles.estudiante.informe-pdf');
Route::get('perfiles/estudiantes/{estudiante}/informe-excel',   [PerfilEstudianteController::class, 'informeExcel'])->name('perfiles.estudiante.informe-excel');
Route::get('perfiles/estudiantes/{estudiante}/certificado-notas', [PerfilEstudianteController::class, 'certificadoNotas'])->name('perfiles.estudiante.certificado-notas');
Route::get('perfiles/estudiantes/{estudiante}/asistencia-pdf',     [PerfilEstudianteController::class, 'asistenciaPdf'])->name('perfiles.estudiante.asistencia-pdf');
Route::get('perfiles/estudiantes/{estudiante}/asistencia-excel',   [PerfilEstudianteController::class, 'asistenciaExcel'])->name('perfiles.estudiante.asistencia-excel');
Route::get('perfiles/estudiantes/{estudiante}/certificado-conducta',[PerfilEstudianteController::class, 'certificadoConducta'])->name('perfiles.estudiante.certificado-conducta');
Route::get('perfiles/estudiantes/{estudiante}/historial-academico', [PerfilEstudianteController::class, 'historialAcademico'])->name('perfiles.estudiante.historial-academico');
Route::get('perfiles/estudiantes/{estudiante}/historial-pdf',       [PerfilEstudianteController::class, 'historialPdf'])->name('perfiles.estudiante.historial-pdf');
Route::get('estudiantes/{estudiante}/ficha-pdf',            [EstudianteController::class,        'fichaPdf'])->name('estudiantes.ficha-pdf');
