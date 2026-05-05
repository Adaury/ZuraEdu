<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\ComunicadoController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\PeriodoController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\EspecialidadTecnicaController;
use App\Http\Controllers\Admin\MallaCurricularController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\MensajeController;
use App\Http\Controllers\Admin\SearchController;

// ── Chat IA ───────────────────────────────────────────────────────────────
Route::post('chat/send', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:30,1');

// ── Búsqueda global ───────────────────────────────────────────────────────
Route::get('search', [SearchController::class, 'search'])->name('search');

// ── Centro de Ayuda ───────────────────────────────────────────────────────
Route::get('ayuda', fn () => view('admin.ayuda.index'))->name('ayuda');

// ── Mensajes Internos ─────────────────────────────────────────────────────
Route::prefix('mensajes')->name('mensajes.')->group(function () {
    Route::get('/',                    [MensajeController::class, 'index'])->name('index');
    Route::get('/nuevo',               [MensajeController::class, 'create'])->name('create');
    Route::post('/',                   [MensajeController::class, 'store'])->name('store');
    Route::get('/conteo',              [MensajeController::class, 'conteo'])->name('conteo');
    Route::get('/{mensaje}',           [MensajeController::class, 'show'])->name('show');
    Route::patch('/{mensaje}/archivar',[MensajeController::class, 'archivar'])->name('archivar');
});

// ── Comunicados ───────────────────────────────────────────────────────────
Route::get('comunicados/lista/pdf',        [ComunicadoController::class, 'listaPdf'])->name('comunicados.lista-pdf');
Route::get('comunicados/lista/excel',      [ComunicadoController::class, 'listaExcel'])->name('comunicados.lista-excel');
Route::get('comunicados/mis',           [ComunicadoController::class, 'misComunicados'])->name('comunicados.mis');
Route::get('comunicados/{comunicado}/pdf', [ComunicadoController::class, 'pdf'])->name('comunicados.pdf');
Route::resource('comunicados', ComunicadoController::class)->except(['show']);

// ── Año Escolar y Períodos ────────────────────────────────────────────────
Route::get('school-years/lista/pdf',   [SchoolYearController::class, 'listaPdf'])->name('school-years.lista-pdf');
Route::get('school-years/lista/excel', [SchoolYearController::class, 'listaExcel'])->name('school-years.lista-excel');
Route::resource('school-years', SchoolYearController::class)->except(['show']);
Route::get('school-years/{schoolYear}/matricula-masiva',  [SchoolYearController::class, 'matriculaMasivaIndex'])->name('school-years.matricula-masiva');
Route::post('school-years/{schoolYear}/matricula-masiva', [SchoolYearController::class, 'matriculaMasivaStore'])->name('school-years.matricula-masiva.store');
Route::get('periodos/lista/pdf',   [PeriodoController::class, 'listaPdf'])->name('periodos.lista-pdf');
Route::get('periodos/lista/excel', [PeriodoController::class, 'listaExcel'])->name('periodos.lista-excel');
Route::resource('periodos', PeriodoController::class)->except(['show']);
Route::get('periodos/{periodo}/checklist',  [\App\Http\Controllers\Admin\PeriodoController::class, 'checklist'])->name('periodos.checklist');
Route::post('periodos/{periodo}/cerrar',    [\App\Http\Controllers\Admin\PeriodoController::class, 'cerrar'])->name('periodos.cerrar');

// ── Áreas ─────────────────────────────────────────────────────────────────
Route::get('areas',            [AreaController::class, 'index'])->name('areas.index');
Route::get('areas/academica',  [AreaController::class, 'academica'])->name('areas.academica');
Route::get('areas/tecnica',    [AreaController::class, 'tecnica'])->name('areas.tecnica');

// ── Especialidades Técnicas ───────────────────────────────────────────────
Route::get('areas/especialidades',                                       [EspecialidadTecnicaController::class, 'index'])->name('especialidades.index');
Route::get('areas/especialidades/create',                                [EspecialidadTecnicaController::class, 'create'])->name('especialidades.create');
Route::post('areas/especialidades',                                      [EspecialidadTecnicaController::class, 'store'])->name('especialidades.store');
Route::get('areas/especialidades/{especialidad}/edit',                   [EspecialidadTecnicaController::class, 'edit'])->name('especialidades.edit');
Route::put('areas/especialidades/{especialidad}',                        [EspecialidadTecnicaController::class, 'update'])->name('especialidades.update');
Route::delete('areas/especialidades/{especialidad}',                     [EspecialidadTecnicaController::class, 'destroy'])->name('especialidades.destroy');
Route::post('areas/especialidades/{especialidad}/asignar-docente',       [EspecialidadTecnicaController::class, 'asignarDocente'])->name('especialidades.asignarDocente');
Route::delete('areas/especialidades/{especialidad}/docentes/{docente}',  [EspecialidadTecnicaController::class, 'removerDocente'])->name('especialidades.removerDocente');

// ── Malla Curricular ──────────────────────────────────────────────────────
Route::get('malla-curricular',              [MallaCurricularController::class, 'index'])->name('malla.index');
Route::get('malla-curricular/matriz',       [MallaCurricularController::class, 'matriz'])->name('malla.matriz');
Route::get('malla-curricular/matriz/pdf',   [MallaCurricularController::class, 'matrizPdf'])->name('malla.matriz.pdf');
Route::get('malla-curricular/matriz/excel', [MallaCurricularController::class, 'matrizExcel'])->name('malla.matriz.excel');
Route::get('malla-curricular/create',       [MallaCurricularController::class, 'create'])->name('malla.create');
Route::post('malla-curricular',             [MallaCurricularController::class, 'store'])->name('malla.store');
Route::get('malla-curricular/{malla}/edit', [MallaCurricularController::class, 'edit'])->name('malla.edit');
Route::put('malla-curricular/{malla}',      [MallaCurricularController::class, 'update'])->name('malla.update');
Route::delete('malla-curricular/{malla}',   [MallaCurricularController::class, 'destroy'])->name('malla.destroy');

// ── Log de actividad ──────────────────────────────────────────────────────
Route::get('sistema/actividad',          [SistemaController::class, 'activityLog'])->name('sistema.actividad');
Route::get('sistema/actividad/excel',    [SistemaController::class, 'activityLogExcel'])->name('sistema.actividad.excel');
Route::get('sistema/actividad/pdf',     [SistemaController::class, 'activityLogPdf'])->name('sistema.actividad.pdf');
Route::get('sistema/estadisticas',       [SistemaController::class, 'estadisticas'])->name('sistema.estadisticas');
Route::get('sistema/reporte-ejecutivo',  [SistemaController::class, 'reporteEjecutivoPdf'])->name('sistema.reporte-ejecutivo');
Route::get('sistema/reporte-anual',      [SistemaController::class, 'reporteAnualPdf'])->name('sistema.reporte-anual');
Route::get('sistema/ficha-institucional',[SistemaController::class, 'fichaInstitucionalPdf'])->name('sistema.ficha-institucional');

// ── Gestión de Usuarios ───────────────────────────────────────────────────
Route::middleware('can:gestionar-usuarios')->group(function () {
    Route::get('usuarios',                    [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('usuarios/create',             [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('usuarios',                   [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('usuarios/lista/pdf',          [UsuarioController::class, 'listaPdf'])->name('usuarios.lista-pdf');
    Route::get('usuarios/lista/excel',        [UsuarioController::class, 'listaExcel'])->name('usuarios.lista-excel');
    Route::get('usuarios/pendientes',         [UsuarioController::class, 'pendientes'])->name('usuarios.pendientes');
    Route::get('usuarios/{usuario}/edit',     [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('usuarios/{usuario}',          [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('usuarios/{usuario}',       [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('usuarios/{usuario}/toggle',  [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle');
    Route::post('usuarios/{usuario}/aprobar',         [UsuarioController::class, 'aprobar'])->name('usuarios.aprobar');
    Route::post('usuarios/{usuario}/rechazar',        [UsuarioController::class, 'rechazar'])->name('usuarios.rechazar');
    Route::post('usuarios/{usuario}/reset-password',  [UsuarioController::class, 'resetPassword'])->name('usuarios.reset-password');
});

// ── Landing & Login (Admin + Director + Coordinador) ──────────────────────
Route::middleware('role:Administrador|Director|Coordinador Académico|Coordinador Primer Ciclo|Coordinador Segundo Ciclo')->group(function () {
    Route::get('sistema/landing',        [SistemaController::class, 'landingIndex'])->name('sistema.landing');
    Route::post('sistema/landing',       [SistemaController::class, 'landingUpdate'])->name('sistema.landing.update');
});

// ── Sistema (solo Administrador) ──────────────────────────────────────────
Route::middleware('role:Administrador')->group(function () {
    // Backup
    Route::get('sistema/backup',           [BackupController::class, 'index'])->name('sistema.backup');
    Route::post('sistema/backup/crear',    [BackupController::class, 'crear'])->name('sistema.backup.crear');
    Route::get('sistema/backup/descargar', [BackupController::class, 'descargar'])->name('sistema.backup.descargar');
    Route::post('sistema/backup/eliminar', [BackupController::class, 'eliminar'])->name('sistema.backup.eliminar');

    // Config institucional
    Route::get('sistema',                  [SistemaController::class, 'index'])->name('sistema.index');
    Route::post('sistema/update',          [SistemaController::class, 'update'])->name('sistema.update');
    Route::post('sistema/logo',            [SistemaController::class, 'uploadLogo'])->name('sistema.logo');
    Route::post('sistema/logo/delete',     [SistemaController::class, 'deleteLogo'])->name('sistema.logo.delete');
    Route::post('sistema/favicon',         [SistemaController::class, 'uploadFavicon'])->name('sistema.favicon');
    Route::post('sistema/favicon/delete',  [SistemaController::class, 'deleteFavicon'])->name('sistema.favicon.delete');
    Route::post('sistema/limpiar-datos',   [SistemaController::class, 'limpiarDatos'])->name('sistema.limpiar-datos');

    // Demo & Trial
    Route::get('sistema/demo-trial',          [\App\Http\Controllers\Admin\DemoTrialController::class, 'index'])->name('sistema.demo-trial');
    Route::post('sistema/demo-trial/toggle',  [\App\Http\Controllers\Admin\DemoTrialController::class, 'toggleDemo'])->name('sistema.demo.toggle');
    Route::post('sistema/demo-trial/crear',   [\App\Http\Controllers\Admin\DemoTrialController::class, 'crearUsuariosDemo'])->name('sistema.demo.crear');
    Route::post('sistema/demo-trial/trial',   [\App\Http\Controllers\Admin\DemoTrialController::class, 'saveTrial'])->name('sistema.trial.save');
    Route::post('sistema/demo-trial/desactivar', [\App\Http\Controllers\Admin\DemoTrialController::class, 'desactivarTrial'])->name('sistema.trial.desactivar');

    // Login config
    Route::get('sistema/login-config',   [SistemaController::class, 'loginIndex'])->name('sistema.login-config');
    Route::post('sistema/login-config',  [SistemaController::class, 'loginUpdate'])->name('sistema.login-config.update');

    // WhatsApp
    Route::get('sistema/whatsapp',        [SistemaController::class, 'whatsappIndex'])->name('sistema.whatsapp');
    Route::post('sistema/whatsapp',       [SistemaController::class, 'whatsappUpdate'])->name('sistema.whatsapp.update');
    Route::get('sistema/email-notif',     [SistemaController::class, 'emailNotifIndex'])->name('sistema.email-notif');
    Route::post('sistema/email-notif',    [SistemaController::class, 'emailNotifUpdate'])->name('sistema.email-notif.update');
});
