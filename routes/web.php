<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Portal\PortalEstudianteController;
use App\Http\Controllers\Portal\PortalPadreController;
use App\Http\Controllers\Portal\PortalDocenteController;
use App\Http\Controllers\Portal\PlanificacionDocenteController;
use App\Http\Controllers\Portal\PlanClaseDocenteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocenteSetupController;
use App\Http\Controllers\PortalRepresentanteController;

/*
|--------------------------------------------------------------------------
| Web Routes — SGE PSAC
|--------------------------------------------------------------------------
|
| Las rutas del panel admin están organizadas en archivos separados bajo
| routes/admin/ e incluidas dentro del grupo de middleware admin.access.
|
*/

// ══════════════════════════════════════════════════════════════════════════
//  RUTAS PÚBLICAS
// ══════════════════════════════════════════════════════════════════════════

Route::get('/', fn () => view('landing'))->name('landing');

Route::get('/demo/{rol}', [AuthController::class, 'demoLogin'])
    ->name('demo.login')
    ->where('rol', 'admin|docente|padre|estudiante');

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');

Route::get('/register',  [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.post')->middleware(['guest', 'throttle:5,1']);

Route::get('/ayuda/registro', fn () => view('help.registro'))->name('help.registro');

// ── Recuperación de contraseña ─────────────────────────────────────────────
Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request')->middleware('guest');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('guest');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset')->middleware('guest');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Conteo de notificaciones (polling) ───────────────────────────────────
Route::get('/notificaciones/conteo', function () {
    if (! auth()->check()) return response()->json(['count' => 0]);
    $count = \App\Models\Notificacion::where('user_id', auth()->id())->noLeidas()->count();
    return response()->json(['count' => $count]);
})->name('notificaciones.conteo')->middleware(['auth', 'activo']);

// ── Perfil de usuario (todos los roles) ──────────────────────────────────
Route::middleware(['auth', 'activo'])->group(function () {
    Route::get('/perfil',              [\App\Http\Controllers\ProfileController::class, 'show'])->name('perfil.show');
    Route::post('/perfil',             [\App\Http\Controllers\ProfileController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/foto',        [\App\Http\Controllers\ProfileController::class, 'uploadPhoto'])->name('perfil.foto');
    Route::delete('/perfil/foto',      [\App\Http\Controllers\ProfileController::class, 'deletePhoto'])->name('perfil.foto.delete');
    Route::post('/perfil/password',    [\App\Http\Controllers\ProfileController::class, 'changePassword'])->name('perfil.password');
});

Route::get('/change-password',  [AuthController::class, 'showChangePassword'])->name('password.change')->middleware('auth');
Route::post('/change-password', [AuthController::class, 'updateChangePassword'])->name('password.change.update')->middleware('auth');

// Portal del Representante (URL firmada, sin login)
Route::get('/portal/representante/{estudiante}', [PortalRepresentanteController::class, 'show'])
    ->name('portal.representante')
    ->middleware('signed');

// ── Verificación pública de matrícula (sin login) ─────────────────────────
Route::get('/verificar-matricula',  [\App\Http\Controllers\VerificacionMatriculaController::class, 'index'])->name('verificar-matricula');
Route::post('/verificar-matricula', [\App\Http\Controllers\VerificacionMatriculaController::class, 'buscar'])->name('verificar-matricula.buscar')->middleware('throttle:10,1');

// ══════════════════════════════════════════════════════════════════════════
//  PORTALES AUTENTICADOS (multi-rol)
// ══════════════════════════════════════════════════════════════════════════

// ── Portal Estudiante ─────────────────────────────────────────────────────
Route::prefix('portal/estudiante')->name('portal.estudiante.')->middleware(['auth', 'activo', 'role:Estudiante'])->group(function () {
    Route::get('/',          [PortalEstudianteController::class, 'dashboard'])->name('dashboard');
    Route::get('/notificaciones',  [PortalEstudianteController::class, 'notificaciones'])->name('notificaciones');
    Route::get('/boletin',         [PortalEstudianteController::class, 'boletin'])->name('boletin');
    Route::get('/boletin/pdf',     [PortalEstudianteController::class, 'boletinPdf'])->name('boletin.pdf');
    Route::get('/horario',         [PortalEstudianteController::class, 'horario'])->name('horario');
    Route::get('/horario/pdf',     [PortalEstudianteController::class, 'horarioPdf'])->name('horario.pdf');
    Route::get('/horario/excel',   [PortalEstudianteController::class, 'horarioExcel'])->name('horario.excel');
    Route::get('/notas/excel',       [PortalEstudianteController::class, 'notasExcel'])->name('notas.excel');
    Route::get('/notas/pdf',        [PortalEstudianteController::class, 'notasPdf'])->name('notas.pdf');
    Route::get('/asistencia/pdf',    [PortalEstudianteController::class, 'asistenciaPdf'])->name('asistencia.pdf');
    Route::get('/asistencia/excel',  [PortalEstudianteController::class, 'asistenciaExcel'])->name('asistencia.excel');
    Route::get('/asistencia',       [PortalEstudianteController::class, 'asistencia'])->name('asistencia');
    Route::get('/observaciones/pdf',   [PortalEstudianteController::class, 'observacionesPdf'])->name('observaciones.pdf');
    Route::get('/observaciones/excel', [PortalEstudianteController::class, 'observacionesExcel'])->name('observaciones.excel');
    Route::get('/observaciones',       [PortalEstudianteController::class, 'observaciones'])->name('observaciones');
    Route::get('/constancia',       [PortalEstudianteController::class, 'constancia'])->name('constancia');
    Route::get('/comunicados/pdf',   [PortalEstudianteController::class, 'comunicadosPdf'])->name('comunicados.pdf');
    Route::get('/comunicados/excel', [PortalEstudianteController::class, 'comunicadosExcel'])->name('comunicados.excel');
    Route::get('/comunicados',       [PortalEstudianteController::class, 'comunicados'])->name('comunicados');
    Route::get('/planificaciones/pdf',   [PortalEstudianteController::class, 'planificacionesPdf'])->name('planificaciones.pdf');
    Route::get('/planificaciones/excel', [PortalEstudianteController::class, 'planificacionesExcel'])->name('planificaciones.excel');
    Route::get('/planificaciones',       [PortalEstudianteController::class, 'planificaciones'])->name('planificaciones');
    Route::get('/asignacion/{asignacion}/recursos/pdf',   [PortalEstudianteController::class, 'recursosPdf'])->name('recursos.pdf');
    Route::get('/asignacion/{asignacion}/recursos/excel', [PortalEstudianteController::class, 'recursosExcel'])->name('recursos.excel');
    Route::get('/asignacion/{asignacion}/recursos',       [PortalEstudianteController::class, 'recursos'])->name('recursos');
    Route::patch('/notificaciones/{notificacion}/leer', [PortalEstudianteController::class, 'marcarNotificacionLeida'])->name('notif.leer');
    Route::post('/notificaciones/leer-todas',           [PortalEstudianteController::class, 'marcarTodasLeidas'])->name('notif.leer-todas');
});

// ── Portal Padre / Representante ──────────────────────────────────────────
Route::prefix('portal/padre')->name('portal.padre.')->middleware(['auth', 'activo', 'role:Representante'])->group(function () {
    Route::get('/',                                    [PortalPadreController::class, 'dashboard'])->name('dashboard');
    Route::get('/hijo/{estudiante}/observaciones/pdf',   [PortalPadreController::class, 'observacionesHijoPdf'])->name('hijo.observaciones.pdf');
    Route::get('/hijo/{estudiante}/observaciones/excel', [PortalPadreController::class, 'observacionesHijoExcel'])->name('hijo.observaciones.excel');
    Route::get('/hijo/{estudiante}/observaciones',       [PortalPadreController::class, 'observacionesHijo'])->name('hijo.observaciones');
    Route::get('/hijo/{estudiante}/asistencia',          [PortalPadreController::class, 'asistenciaHijo'])->name('hijo.asistencia');
    Route::get('/hijo/{estudiante}/asistencia/pdf',      [PortalPadreController::class, 'asistenciaHijoPdf'])->name('hijo.asistencia.pdf');
    Route::get('/hijo/{estudiante}/asistencia/excel',    [PortalPadreController::class, 'asistenciaHijoExcel'])->name('hijo.asistencia.excel');
    Route::get('/hijo/{estudiante}/horario',           [PortalPadreController::class, 'horarioHijo'])->name('hijo.horario');
    Route::get('/hijo/{estudiante}/horario/pdf',          [PortalPadreController::class, 'horarioPdf'])->name('hijo.horario.pdf');
    Route::get('/hijo/{estudiante}/horario/excel',        [PortalPadreController::class, 'horarioExcel'])->name('hijo.horario.excel');
    Route::get('/hijo/{estudiante}/planificaciones/pdf',   [PortalPadreController::class, 'planificacionesHijoPdf'])->name('hijo.planificaciones.pdf');
    Route::get('/hijo/{estudiante}/planificaciones/excel', [PortalPadreController::class, 'planificacionesHijoExcel'])->name('hijo.planificaciones.excel');
    Route::get('/hijo/{estudiante}/planificaciones',       [PortalPadreController::class, 'planificacionesHijo'])->name('hijo.planificaciones');
    Route::get('/notificaciones',               [PortalPadreController::class, 'notificaciones'])->name('notificaciones');
    Route::get('/hijo/{estudiante}',             [PortalPadreController::class, 'hijo'])->name('hijo');
    Route::get('/hijo/{estudiante}/boletin',       [PortalPadreController::class, 'boletin'])->name('hijo.boletin');
    Route::get('/hijo/{estudiante}/boletin/pdf',   [PortalPadreController::class, 'boletinPdf'])->name('hijo.boletin.pdf');
    Route::get('/hijo/{estudiante}/constancia',      [PortalPadreController::class, 'constancia'])->name('hijo.constancia');
    Route::get('/hijo/{estudiante}/estado-cuenta',   [PortalPadreController::class, 'estadoCuenta'])->name('hijo.estado-cuenta');
    Route::get('/hijo/{estudiante}/notas/excel',     [PortalPadreController::class, 'notasExcel'])->name('hijo.notas.excel');
    Route::get('/hijo/{estudiante}/notas-pdf',       [PortalPadreController::class, 'notasPdf'])->name('hijo.notas-pdf');
    Route::get('/hijo/{estudiante}/asignacion/{asignacion}/recursos/pdf',   [PortalPadreController::class, 'hijosRecursosPdf'])->name('hijo.recursos.pdf');
    Route::get('/hijo/{estudiante}/asignacion/{asignacion}/recursos/excel', [PortalPadreController::class, 'hijosRecursosExcel'])->name('hijo.recursos.excel');
    Route::get('/hijo/{estudiante}/asignacion/{asignacion}/recursos',       [PortalPadreController::class, 'hijosRecursos'])->name('hijo.recursos');
    Route::get('/comunicados/pdf',               [PortalPadreController::class, 'comunicadosPdf'])->name('comunicados.pdf');
    Route::get('/comunicados/excel',             [PortalPadreController::class, 'comunicadosExcel'])->name('comunicados.excel');
    Route::get('/comunicados',                   [PortalPadreController::class, 'comunicados'])->name('comunicados');
    Route::post('/notificaciones/leer-todas',    [PortalPadreController::class, 'marcarTodasLeidas'])->name('notif.leer-todas');
});

// ── Portal Docente ────────────────────────────────────────────────────────
Route::prefix('portal/docente')->name('portal.docente.')->middleware(['auth', 'activo', 'role:Docente'])->group(function () {
    Route::get('/',                                               [PortalDocenteController::class, 'dashboard'])->name('dashboard');
    Route::get('/setup',                                          [DocenteSetupController::class, 'show'])->name('setup');
    Route::get('/horario',                                        [PortalDocenteController::class, 'horario'])->name('horario');
    Route::get('/horario/pdf',                                    [PortalDocenteController::class, 'horarioPdf'])->name('horario.pdf');
    Route::get('/horario/excel',                                  [PortalDocenteController::class, 'horarioExcel'])->name('horario.excel');
    Route::post('/setup',                                         [DocenteSetupController::class, 'store'])->name('setup.store');
    Route::get('/asignacion/{asignacion}/asistencia',             [PortalDocenteController::class, 'asistencia'])->name('asistencia');
    Route::post('/asignacion/{asignacion}/asistencia',            [PortalDocenteController::class, 'guardarAsistencia'])->name('asistencia.guardar');
    Route::get('/asignacion/{asignacion}/asistencia/plantilla',   [PortalDocenteController::class, 'descargarPlantillaAsistencia'])->name('asistencia.plantilla');
    Route::get('/asignacion/{asignacion}/asistencia/pdf',         [PortalDocenteController::class, 'exportarAsistenciaPdf'])->name('asistencia.pdf');
    Route::get('/asignacion/{asignacion}/asistencia/excel',       [PortalDocenteController::class, 'exportarAsistenciaExcel'])->name('asistencia.excel');
    Route::post('/asignacion/{asignacion}/asistencia/importar',   [PortalDocenteController::class, 'importarAsistencia'])->name('asistencia.importar');
    Route::get('/asignacion/{asignacion}/estudiantes/excel',      [PortalDocenteController::class, 'estudiantesExcel'])->name('estudiantes.excel');
    Route::get('/asignacion/{asignacion}/estudiantes',            [PortalDocenteController::class, 'estudiantes'])->name('estudiantes');
    Route::get('/asignacion/{asignacion}/calificaciones',                 [PortalDocenteController::class, 'calificaciones'])->name('calificaciones');
    Route::post('/asignacion/{asignacion}/calificaciones',                [PortalDocenteController::class, 'guardarCalificaciones'])->name('calificaciones.guardar');
    Route::get('/asignacion/{asignacion}/calificaciones/plantilla',       [PortalDocenteController::class, 'descargarPlantillaCalificaciones'])->name('calificaciones.plantilla');
    Route::get('/asignacion/{asignacion}/calificaciones/exportar-pdf',   [PortalDocenteController::class, 'exportarCalificacionesPdf'])->name('calificaciones.exportar-pdf');
    Route::get('/asignacion/{asignacion}/calificaciones/exportar-excel', [PortalDocenteController::class, 'exportarCalificacionesExcel'])->name('calificaciones.exportar-excel');
    Route::post('/asignacion/{asignacion}/calificaciones/importar',       [PortalDocenteController::class, 'importarCalificaciones'])->name('calificaciones.importar');
    Route::patch('/asignacion/{asignacion}/calificaciones/acad/celda',   [PortalDocenteController::class, 'guardarCeldaAcad'])->name('calificaciones.acad.celda');
    Route::post('/asignacion/{asignacion}/pesos-ra',                      [PortalDocenteController::class, 'guardarPesosRa'])->name('pesos-ra.guardar');
    Route::get('/asignacion/{asignacion}/estudiantes/pdf',        [PortalDocenteController::class, 'estudiantesPdf'])->name('estudiantes.pdf');
    Route::get('/asignacion/{asignacion}/observaciones',          [PortalDocenteController::class, 'observaciones'])->name('observaciones');
    Route::get('/asignacion/{asignacion}/observaciones/pdf',     [PortalDocenteController::class, 'observacionesPdf'])->name('observaciones.pdf');
    Route::get('/asignacion/{asignacion}/observaciones/excel',   [PortalDocenteController::class, 'observacionesExcel'])->name('observaciones.excel');
    Route::post('/asignacion/{asignacion}/observaciones',         [PortalDocenteController::class, 'guardarObservacion'])->name('observaciones.guardar');
    Route::get('/asignacion/{asignacion}/boletines',              [PortalDocenteController::class, 'boletines'])->name('boletines');
    Route::get('/asignacion/{asignacion}/boletines/zip',          [PortalDocenteController::class, 'boletinesZip'])->name('boletines.zip');
    Route::get('/asignacion/{asignacion}/acta-pdf',               [PortalDocenteController::class, 'actaPdf'])->name('acta.pdf');
    Route::get('/asignacion/{asignacion}/boletin/{matricula}',     [PortalDocenteController::class, 'verBoletin'])->name('boletin.ver');
    Route::get('/asignacion/{asignacion}/boletin/{matricula}/pdf', [PortalDocenteController::class, 'pdfBoletin'])->name('boletin.pdf');
    // Recursos por materia
    Route::get('/asignacion/{asignacion}/recursos',               [PortalDocenteController::class, 'recursos'])->name('recursos');
    Route::get('/asignacion/{asignacion}/recursos/pdf',           [PortalDocenteController::class, 'recursosPdf'])->name('recursos.pdf');
    Route::get('/asignacion/{asignacion}/recursos/excel',         [PortalDocenteController::class, 'recursosExcel'])->name('recursos.excel');
    Route::post('/asignacion/{asignacion}/recursos',              [PortalDocenteController::class, 'guardarRecurso'])->name('recursos.guardar');
    Route::delete('/asignacion/{asignacion}/recursos/{recurso}',  [PortalDocenteController::class, 'eliminarRecurso'])->name('recursos.eliminar');
    Route::patch('/asignacion/{asignacion}/recursos/{recurso}/toggle', [PortalDocenteController::class, 'toggleRecurso'])->name('recursos.toggle');
    Route::get('/mis-planificaciones/pdf',     [PortalDocenteController::class, 'misPlanificacionesPdf'])->name('mis-planificaciones.pdf');
    Route::get('/mis-planificaciones/excel',   [PortalDocenteController::class, 'misPlanificacionesExcel'])->name('mis-planificaciones.excel');
    Route::get('/mis-planificaciones',         [PortalDocenteController::class, 'misPlanificaciones'])->name('mis-planificaciones');
    Route::post('/notificaciones/leer-todas',  [PortalDocenteController::class, 'marcarTodasLeidas'])->name('notif.leer-todas');
    Route::get('/notificaciones',              [PortalDocenteController::class, 'notificaciones'])->name('notificaciones');
    // Planes de Clase
    Route::prefix('/asignacion/{asignacion}/planes-clase')->name('planes-clase.')->group(function () {
        Route::get('/lista/pdf',                 [PlanClaseDocenteController::class, 'planesListaPdf'])->name('lista-pdf');
        Route::get('/lista/excel',               [PlanClaseDocenteController::class, 'planesListaExcel'])->name('lista-excel');
        Route::get('/',                          [PlanClaseDocenteController::class, 'planesIndex'])->name('index');
        Route::get('/crear',                     [PlanClaseDocenteController::class, 'planesCreate'])->name('create');
        Route::post('/',                         [PlanClaseDocenteController::class, 'planesStore'])->name('store');
        Route::get('/{planClase}',               [PlanClaseDocenteController::class, 'planesShow'])->name('show');
        Route::patch('/{planClase}/toggle',      [PlanClaseDocenteController::class, 'planesToggle'])->name('toggle');
        Route::delete('/{planClase}',            [PlanClaseDocenteController::class, 'planesDestroy'])->name('destroy');
        Route::get('/{planClase}/descargar',     [PlanClaseDocenteController::class, 'planesDownload'])->name('download');
        Route::get('/{planClase}/pdf',           [PlanClaseDocenteController::class, 'planesPdf'])->name('pdf');
    });

    // Instrumentos de Evaluación
    Route::prefix('/asignacion/{asignacion}/instrumentos')->name('instrumentos.')->group(function () {
        Route::get('/lista/pdf',                 [PlanClaseDocenteController::class, 'instrumentosListaPdf'])->name('lista-pdf');
        Route::get('/lista/excel',               [PlanClaseDocenteController::class, 'instrumentosListaExcel'])->name('lista-excel');
        Route::get('/',                          [PlanClaseDocenteController::class, 'instrumentosIndex'])->name('index');
        Route::get('/crear',                     [PlanClaseDocenteController::class, 'instrumentosCreate'])->name('create');
        Route::post('/',                         [PlanClaseDocenteController::class, 'instrumentosStore'])->name('store');
        Route::get('/{instrumento}',             [PlanClaseDocenteController::class, 'instrumentosShow'])->name('show');
        Route::post('/{instrumento}/guardar',    [PlanClaseDocenteController::class, 'instrumentosGuardar'])->name('guardar');
        Route::get('/{instrumento}/pdf',         [PlanClaseDocenteController::class, 'instrumentosPdf'])->name('pdf');
    });

    // Planificaciones (Área Técnica)
    Route::prefix('/asignacion/{asignacion}/planificacion')->name('planificacion.')->group(function () {
        Route::get('/',                          [PlanificacionDocenteController::class, 'index'])->name('index');
        Route::get('/nueva/ra',                  [PlanificacionDocenteController::class, 'createRa'])->name('create-ra');
        Route::post('/nueva/ra',                 [PlanificacionDocenteController::class, 'storeRa'])->name('store-ra');
        Route::get('/nueva/actividad',           [PlanificacionDocenteController::class, 'createActividad'])->name('create-actividad');
        Route::post('/nueva/actividad',          [PlanificacionDocenteController::class, 'storeActividad'])->name('store-actividad');
        Route::get('/{planificacion}',           [PlanificacionDocenteController::class, 'show'])->name('show');
        Route::get('/{planificacion}/editar',    [PlanificacionDocenteController::class, 'edit'])->name('edit');
        Route::put('/{planificacion}',           [PlanificacionDocenteController::class, 'update'])->name('update');
        Route::patch('/{planificacion}/publicado',[PlanificacionDocenteController::class, 'togglePublicado'])->name('toggle-publicado');
        Route::delete('/{planificacion}',        [PlanificacionDocenteController::class, 'destroy'])->name('destroy');
    });
});

// ══════════════════════════════════════════════════════════════════════════
//  PANEL ADMIN (protegido)
// ══════════════════════════════════════════════════════════════════════════

Route::prefix('admin')->name('admin.')->middleware(['auth', 'activo', 'admin.access'])->group(function () {

    Route::get('/dashboard',       [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'statsJson'])->name('dashboard.stats');

    require __DIR__ . '/admin/personas.php';
    require __DIR__ . '/admin/academico.php';
    require __DIR__ . '/admin/horarios.php';
    require __DIR__ . '/admin/reportes.php';
    require __DIR__ . '/admin/sistema.php';
    require __DIR__ . '/admin/pagos.php';
});
