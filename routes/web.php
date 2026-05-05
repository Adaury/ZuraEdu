<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Portal\PortalEstudianteController;
use App\Http\Controllers\Portal\PortalPadreController;
use App\Http\Controllers\Portal\PortalDocenteController;
use App\Http\Controllers\Portal\PlanificacionDocenteController;
use App\Http\Controllers\Portal\PlanClaseDocenteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DemoAutoController;
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

// ── Página de institución suspendida (accesible aunque el tenant no esté activo) ──
Route::get('/suspended', fn () => view('tenant-suspended'))->name('tenant.suspended');

// ── Onboarding / Registro de nuevas instituciones ─────────────────────────
Route::get('/onboarding',  [OnboardingController::class, 'show'])->name('onboarding');
Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store')->middleware('throttle:5,10');

// ── Demo automática (admin completo con datos ficticios) ──────────────────
Route::get('/demo', [DemoAutoController::class, 'enter'])->name('demo.auto')->middleware('throttle:20,1');

Route::get('/demo/{rol}', [AuthController::class, 'demoLogin'])
    ->name('demo.login')
    ->where('rol', 'docente|padre|estudiante');

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

// ── Pre-matrícula pública (sin login) ────────────────────────────────────
Route::get('/inscripcion',              [\App\Http\Controllers\PreMatriculaController::class, 'create'])->name('inscripcion');
Route::post('/inscripcion',             [\App\Http\Controllers\PreMatriculaController::class, 'store'])->name('inscripcion.store')->middleware('throttle:5,1');
Route::get('/inscripcion/confirmacion', [\App\Http\Controllers\PreMatriculaController::class, 'confirmacion'])->name('inscripcion.confirmacion');

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
    Route::get('/logros',                [PortalEstudianteController::class, 'logros'])->name('logros');
    Route::get('/tareas',                [PortalEstudianteController::class, 'tareas'])->name('tareas');
    Route::get('/mis-documentos',        [PortalEstudianteController::class, 'misDocumentos'])->name('mis-documentos');
    Route::get('/asignacion/{asignacion}/recursos/pdf',   [PortalEstudianteController::class, 'recursosPdf'])->name('recursos.pdf');
    Route::get('/asignacion/{asignacion}/recursos/excel', [PortalEstudianteController::class, 'recursosExcel'])->name('recursos.excel');
    Route::get('/asignacion/{asignacion}/recursos',       [PortalEstudianteController::class, 'recursos'])->name('recursos');
    Route::patch('/notificaciones/{notificacion}/leer', [PortalEstudianteController::class, 'marcarNotificacionLeida'])->name('notif.leer');
    Route::post('/notificaciones/leer-todas',           [PortalEstudianteController::class, 'marcarTodasLeidas'])->name('notif.leer-todas');
    Route::get('/encuestas',                            [PortalEstudianteController::class, 'encuestas'])->name('encuestas');
    Route::get('/encuestas/{encuesta}',                 [PortalEstudianteController::class, 'verEncuesta'])->name('encuestas.responder');
    Route::post('/encuestas/{encuesta}',                [PortalEstudianteController::class, 'responderEncuesta'])->name('encuestas.guardar');
    Route::get('/eventos',                              [PortalEstudianteController::class, 'eventos'])->name('eventos');
    Route::post('/eventos/{evento}/inscribirse',        [PortalEstudianteController::class, 'inscribirseEvento'])->name('eventos.inscribirse');
    Route::get('/proyectos',                            [PortalEstudianteController::class, 'proyectos'])->name('proyectos');
    Route::get('/mis-puntos',                           [PortalEstudianteController::class, 'misPuntos'])->name('mis-puntos');
    Route::prefix('classroom')->name('classroom.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\ClassroomEstudianteController::class, 'index'])->name('index');
        Route::get('/tareas-pendientes', [\App\Http\Controllers\Portal\ClassroomEstudianteController::class, 'tareasPendientes'])->name('pendientes');
        Route::get('/{claseVirtual}', [\App\Http\Controllers\Portal\ClassroomEstudianteController::class, 'show'])->name('show');
        Route::post('/{claseVirtual}/material/{material}/entregar', [\App\Http\Controllers\Portal\ClassroomEstudianteController::class, 'entregarTarea'])->name('entregar');
        Route::post('/{claseVirtual}/material/{material}/comentar', [\App\Http\Controllers\Portal\ClassroomEstudianteController::class, 'comentar'])->name('comentar');
        // Chat — estudiante
        Route::get('/{claseVirtual}/chat',              [\App\Http\Controllers\Portal\ClassroomChatController::class, 'index'])->name('chat.index');
        Route::post('/{claseVirtual}/chat',             [\App\Http\Controllers\Portal\ClassroomChatController::class, 'store'])->name('chat.store');
        Route::delete('/{claseVirtual}/chat/{message}', [\App\Http\Controllers\Portal\ClassroomChatController::class, 'destroy'])->name('chat.destroy');
        // Quiz
        Route::get('/{claseVirtual}/material/{material}/quiz', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'iniciar'])->name('quiz.iniciar');
        Route::post('/{claseVirtual}/material/{material}/quiz/comenzar', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'comenzar'])->name('quiz.comenzar');
        Route::get('/{claseVirtual}/material/{material}/quiz/{intento}', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'tomar'])->name('quiz.tomar');
        Route::post('/quiz/{intento}/guardar', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'guardarRespuesta'])->name('quiz.guardar');
        Route::post('/{claseVirtual}/material/{material}/quiz/{intento}/enviar', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'enviar'])->name('quiz.enviar');
        Route::get('/{claseVirtual}/material/{material}/quiz/{intento}/resultado', [\App\Http\Controllers\Portal\QuizEstudianteController::class, 'resultado'])->name('quiz.resultado');
    });
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
    Route::get('/encuestas',                     [PortalPadreController::class, 'encuestas'])->name('encuestas');
    Route::get('/encuestas/{encuesta}',          [PortalPadreController::class, 'verEncuesta'])->name('encuestas.responder');
    Route::post('/encuestas/{encuesta}',         [PortalPadreController::class, 'responderEncuesta'])->name('encuestas.guardar');
    Route::get('/hijo/{estudiante}/documentos',  [PortalPadreController::class, 'documentosHijo'])->name('hijo.documentos');
    Route::get('/hijo/{estudiante}/classroom', [\App\Http\Controllers\Portal\ClassroomPadreController::class, 'index'])->name('hijo.classroom.index');
    Route::get('/hijo/{estudiante}/classroom/{claseVirtual}', [\App\Http\Controllers\Portal\ClassroomPadreController::class, 'show'])->name('hijo.classroom.show');
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
    Route::get('/mis-estadisticas',            [PortalDocenteController::class, 'misEstadisticas'])->name('mis-estadisticas');
    Route::get('/mis-estudiantes',             [PortalDocenteController::class, 'misEstudiantes'])->name('mis-estudiantes');
    Route::prefix('classroom')->name('classroom.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'index'])->name('index');
        Route::get('/{claseVirtual}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'show'])->name('show');
        Route::get('/{claseVirtual}/personas', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'personas'])->name('personas');
        Route::get('/{claseVirtual}/calificaciones', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'calificacionesResumen'])->name('calificaciones');
        Route::get('/{claseVirtual}/recursos', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'recursos'])->name('recursos');
        Route::post('/{claseVirtual}/recursos', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'guardarRecurso'])->name('recursos.guardar');
        Route::delete('/{claseVirtual}/recursos/{recurso}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'eliminarRecurso'])->name('recursos.eliminar');
        Route::get('/{claseVirtual}/material/nuevo', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'crearMaterial'])->name('crear_material');
        Route::post('/{claseVirtual}/material', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'guardarMaterial'])->name('guardar_material');
        Route::get('/{claseVirtual}/material/{material}/editar', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'editarMaterial'])->name('editar_material');
        Route::put('/{claseVirtual}/material/{material}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'actualizarMaterial'])->name('actualizar_material');
        Route::delete('/{claseVirtual}/material/{material}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'eliminarMaterial'])->name('eliminar_material');
        Route::get('/{claseVirtual}/material/{material}/entregas', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'verEntregas'])->name('entregas');
        Route::get('/{claseVirtual}/material/{material}/entrega/{entrega}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'verEntregaDetalle'])->name('entrega_detalle');
        Route::patch('/{claseVirtual}/entrega/{entrega}/calificar', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'calificarEntrega'])->name('calificar_entrega');
        Route::patch('/{claseVirtual}/entrega/{entrega}/devolver', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'devolverEntrega'])->name('devolver_entrega');
        Route::post('/{claseVirtual}/material/{material}/archivo', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'subirArchivo'])->name('subir_archivo');
        Route::delete('/{claseVirtual}/archivo/{archivo}', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'eliminarArchivo'])->name('eliminar_archivo');
        Route::post('/{claseVirtual}/sincronizar-notas', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'sincronizarNotas'])->name('sincronizar_notas');
        Route::post('/{claseVirtual}/generar-codigo', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'generarCodigo'])->name('generar_codigo');
        // Meeting (Jitsi)
        Route::post('/{claseVirtual}/meeting/iniciar',  [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'iniciarMeeting'])->name('meeting.iniciar');
        Route::post('/{claseVirtual}/meeting/terminar', [\App\Http\Controllers\Portal\ClassroomDocenteController::class, 'terminarMeeting'])->name('meeting.terminar');
        // Chat — docente
        Route::get('/{claseVirtual}/chat',              [\App\Http\Controllers\Portal\ClassroomChatController::class, 'index'])->name('chat.index');
        Route::post('/{claseVirtual}/chat',             [\App\Http\Controllers\Portal\ClassroomChatController::class, 'store'])->name('chat.store');
        Route::patch('/{claseVirtual}/chat/{message}/pin', [\App\Http\Controllers\Portal\ClassroomChatController::class, 'togglePin'])->name('chat.pin');
        Route::delete('/{claseVirtual}/chat/{message}', [\App\Http\Controllers\Portal\ClassroomChatController::class, 'destroy'])->name('chat.destroy');
        // Quiz management (docente)
        Route::get('/{claseVirtual}/material/{material}/quiz/crear',      [\App\Http\Controllers\Portal\QuizDocenteController::class, 'crear'])->name('quiz.crear');
        Route::post('/{claseVirtual}/material/{material}/quiz',           [\App\Http\Controllers\Portal\QuizDocenteController::class, 'guardar'])->name('quiz.guardar');
        Route::get('/{claseVirtual}/material/{material}/quiz/editar',     [\App\Http\Controllers\Portal\QuizDocenteController::class, 'editar'])->name('quiz.editar');
        Route::put('/{claseVirtual}/material/{material}/quiz',            [\App\Http\Controllers\Portal\QuizDocenteController::class, 'actualizar'])->name('quiz.actualizar');
        Route::delete('/{claseVirtual}/material/{material}/quiz',         [\App\Http\Controllers\Portal\QuizDocenteController::class, 'eliminar'])->name('quiz.eliminar');
        Route::get('/{claseVirtual}/material/{material}/quiz/resultados', [\App\Http\Controllers\Portal\QuizDocenteController::class, 'resultados'])->name('quiz.resultados');
    });
    Route::prefix('/asignacion/{asignacion}/tareas')->name('tareas.')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'index'])->name('index');
        Route::get('/crear',               [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'create'])->name('create');
        Route::post('/',                   [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'store'])->name('store');
        Route::get('/{tarea}/editar',      [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'edit'])->name('edit');
        Route::put('/{tarea}',             [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'update'])->name('update');
        Route::delete('/{tarea}',          [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'destroy'])->name('destroy');
        Route::get('/{tarea}/entregas',    [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'entregas'])->name('entregas');
        Route::patch('/{tarea}/calificar', [\App\Http\Controllers\Portal\AgendaDocenteController::class, 'calificar'])->name('calificar');
    });
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

    // ── Módulos base (disponibles en todos los planes) ───────────────────────
    require __DIR__ . '/admin/billing.php';
    require __DIR__ . '/admin/personas.php';
    require __DIR__ . '/admin/academico.php';
    require __DIR__ . '/admin/reportes.php';
    require __DIR__ . '/admin/sistema.php';
    require __DIR__ . '/admin/exportacion_masiva.php';
    require __DIR__ . '/admin/kpis.php';
    require __DIR__ . '/admin/cierre_ano.php';
    require __DIR__ . '/admin/importaciones.php';
    require __DIR__ . '/admin/pre_matriculas.php';
    require __DIR__ . '/admin/galeria.php';
    require __DIR__ . '/admin/eventos.php';
    require __DIR__ . '/admin/encuestas.php';
    require __DIR__ . '/admin/avisos_emergencia.php';
    require __DIR__ . '/admin/soporte.php';
    require __DIR__ . '/admin/recursos.php';

    // ── Plan Pro: módulos intermedios ─────────────────────────────────────────
    Route::middleware('tenant.feature:horarios')->group(function () {
        require __DIR__ . '/admin/horarios.php';
    });
    Route::middleware('tenant.feature:classroom')->group(function () {
        require __DIR__ . '/admin/classroom.php';
    });
    Route::middleware('tenant.feature:disciplina')->group(function () {
        require __DIR__ . '/admin/disciplina.php';
    });
    Route::middleware('tenant.feature:tutorias')->group(function () {
        require __DIR__ . '/admin/tutorias.php';
    });
    Route::middleware('tenant.feature:seguimiento_social')->group(function () {
        require __DIR__ . '/admin/seguimiento_social.php';
    });
    Route::middleware('tenant.feature:gamificacion')->group(function () {
        require __DIR__ . '/admin/gamificacion.php';
    });

    // ── Plan Premium: módulos avanzados ───────────────────────────────────────
    Route::middleware('tenant.feature:pagos')->group(function () {
        require __DIR__ . '/admin/pagos.php';
        require __DIR__ . '/admin/becas.php';
    });
    Route::middleware('tenant.feature:nomina')->group(function () {
        require __DIR__ . '/admin/nomina.php';
    });
    Route::middleware('tenant.feature:biblioteca')->group(function () {
        require __DIR__ . '/admin/biblioteca.php';
    });
    Route::middleware('tenant.feature:inventario')->group(function () {
        require __DIR__ . '/admin/inventario.php';
        require __DIR__ . '/admin/equipos.php';
    });
    Route::middleware('tenant.feature:cafeteria')->group(function () {
        require __DIR__ . '/admin/cafeteria.php';
    });
    Route::middleware('tenant.feature:proyectos')->group(function () {
        require __DIR__ . '/admin/proyectos.php';
    });
    Route::middleware('tenant.feature:reconocimientos')->group(function () {
        require __DIR__ . '/admin/reconocimientos.php';
    });
    Route::middleware('tenant.feature:evaluaciones_docentes')->group(function () {
        require __DIR__ . '/admin/evaluaciones_docentes.php';
    });
    Route::middleware('tenant.feature:transporte')->group(function () {
        require __DIR__ . '/admin/transporte.php';
    });
    Route::middleware('tenant.feature:salud')->group(function () {
        require __DIR__ . '/admin/salud.php';
    });
    Route::middleware('tenant.feature:reuniones')->group(function () {
        require __DIR__ . '/admin/reuniones.php';
    });
});

// ── Galería pública ───────────────────────────────────────────────────────
Route::get('/galeria', [\App\Http\Controllers\Admin\GaleriaController::class, 'galeriaPublica'])->name('galeria.publica');

// ── Webhook Stripe (público, sin CSRF) ────────────────────────────────────
Route::post('/webhook/stripe', [\App\Http\Controllers\WebhookStripeController::class, 'handle'])
    ->name('webhook.stripe')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ══════════════════════════════════════════════════════════════════════════
//  SUPER ADMIN — Panel de la Plataforma ZuraEdu
// ══════════════════════════════════════════════════════════════════════════
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('dashboard');

    // Gestión de tenants (instituciones)
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/',                              [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('index');
        Route::get('/create',                        [\App\Http\Controllers\SuperAdmin\TenantController::class, 'create'])->name('create');
        Route::post('/',                             [\App\Http\Controllers\SuperAdmin\TenantController::class, 'store'])->name('store');
        Route::post('/exit-panel',                   [\App\Http\Controllers\SuperAdmin\TenantController::class, 'exitPanel'])->name('exit-panel');

        // Rutas con parámetro {tenant} — deben ir después de las estáticas
        Route::get('/{tenant}',                      [\App\Http\Controllers\SuperAdmin\TenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit',                 [\App\Http\Controllers\SuperAdmin\TenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}',                      [\App\Http\Controllers\SuperAdmin\TenantController::class, 'update'])->name('update');
        Route::post('/{tenant}/toggle-estado',       [\App\Http\Controllers\SuperAdmin\TenantController::class, 'toggleEstado'])->name('toggle-estado');
        Route::delete('/{tenant}',                   [\App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy'])->name('destroy');
        Route::post('/{tenant}/enter-panel',         [\App\Http\Controllers\SuperAdmin\TenantController::class, 'enterPanel'])->name('enter-panel');

        // Gestión de suscripciones y módulos
        Route::post('/{tenant}/subscriptions',       [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::post('/{tenant}/toggle-feature',      [\App\Http\Controllers\SuperAdmin\SubscriptionController::class, 'toggleFeature'])->name('toggle-feature');
    });
});
