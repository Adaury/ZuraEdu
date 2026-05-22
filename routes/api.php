<?php

use App\Http\Controllers\Api\AsistenciaApiController;
use App\Http\Controllers\Api\ConductaApiController;
use App\Http\Controllers\Api\ObservacionesApiController;
use App\Http\Controllers\Api\PlanEvaluacionApiController;
use App\Http\Controllers\Api\ResultadosEvaluacionApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CafeteriaApiController;
use App\Http\Controllers\Api\CalendarioApiController;
use App\Http\Controllers\Api\CalificacionesApiController;
use App\Http\Controllers\Api\ClassroomApiController;
use App\Http\Controllers\Api\ComunicadosApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DocumentosApiController;
use App\Http\Controllers\Api\DocenteApiController;
use App\Http\Controllers\Api\EncuestasApiController;
use App\Http\Controllers\Api\HorarioApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PagosApiController;
use App\Http\Controllers\Api\SolicitudesApiController;
use App\Http\Controllers\Api\TareasApiController;
use App\Http\Controllers\Api\TransporteApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ZuraEdu SGE v1
|--------------------------------------------------------------------------
| Base URL : /api/v1/
| Auth     : Bearer Token (Sanctum)
| Tenant   : resuelto automáticamente desde user.tenant_id en rutas protegidas
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Mapa de la API ────────────────────────────────────────────────
    Route::get('/', fn () => response()->json([
        'api'     => 'ZuraEdu SGE',
        'version' => 'v1',
        'docs'    => url('/api/v1'),
        'endpoints' => [
            'auth'         => ['POST /auth/login', 'POST /auth/logout', 'GET /auth/me'],
            'dashboard'    => ['GET /dashboard'],
            'calificaciones'=> ['GET /calificaciones', 'GET /calificaciones/hijo/{id}'],
            'asistencia'   => ['GET /asistencia', 'GET /asistencia/hijo/{id}'],
            'horario'      => ['GET /horario', 'GET /horario/hijo/{id}'],
            'notificaciones'=> ['GET /notificaciones', 'PATCH /notificaciones/{id}/leer', 'POST /notificaciones/leer-todas'],
            'comunicados'  => ['GET /comunicados', 'GET /comunicados/{id}'],
            'calendario'   => ['GET /calendario'],
            'pagos'        => ['GET /pagos', 'GET /pagos/hijo/{id}'],
            'classroom'    => ['GET /classroom', 'GET /classroom/{id}/materiales'],
            'docente'      => ['GET /docente/grupos', 'GET /docente/asistencia/{id}', 'POST /docente/asistencia'],
            'tutor_ia'     => ['POST /ai/chat'],
        ],
    ]))->name('api.v1.index');

    // ── Auth pública (sin tenant) ─────────────────────────────────────
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('login',  [AuthApiController::class, 'login'])->name('api.auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout',           [AuthApiController::class, 'logout'])->name('api.auth.logout');
            Route::get('me',                [AuthApiController::class, 'me'])->name('api.auth.me');
            Route::post('refresh-token',    [AuthApiController::class, 'refreshToken'])->name('api.auth.refresh');
            Route::patch('change-password', [AuthApiController::class, 'changePassword'])->name('api.auth.change-password');
            Route::patch('profile',         [AuthApiController::class, 'updateProfile'])->name('api.auth.profile');
            Route::post('push-token',       [AuthApiController::class, 'registerPushToken'])->name('api.auth.push-token');
            Route::delete('push-token',     [AuthApiController::class, 'removePushToken'])->name('api.auth.push-token.remove');
            Route::post('avatar',           [AuthApiController::class, 'uploadAvatar'])->name('api.auth.avatar');
        });
    });

    // ── Rutas autenticadas + tenant resuelto ──────────────────────────
    Route::middleware(['auth:sanctum', 'api.tenant', 'throttle:120,1'])->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('api.dashboard');

        // Calificaciones
        Route::prefix('calificaciones')->name('api.calificaciones.')->group(function () {
            Route::get('/',                    [CalificacionesApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [CalificacionesApiController::class, 'hijo'])->name('hijo');
        });

        // Asistencia
        Route::prefix('asistencia')->name('api.asistencia.')->group(function () {
            Route::get('/',                    [AsistenciaApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [AsistenciaApiController::class, 'hijo'])->name('hijo');
        });

        // Horario
        Route::prefix('horario')->name('api.horario.')->group(function () {
            Route::get('/',                    [HorarioApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [HorarioApiController::class, 'hijo'])->name('hijo');
        });

        // Notificaciones
        Route::prefix('notificaciones')->name('api.notificaciones.')->group(function () {
            Route::get('/',                          [NotificacionApiController::class, 'index'])->name('index');
            Route::post('leer-todas',                [NotificacionApiController::class, 'marcarTodas'])->name('leer-todas');
            Route::patch('{notificacion}/leer',      [NotificacionApiController::class, 'marcar'])->name('leer');
        });

        // Comunicados
        Route::prefix('comunicados')->name('api.comunicados.')->group(function () {
            Route::get('/',                    [ComunicadosApiController::class, 'index'])->name('index');
            Route::get('{comunicado}',         [ComunicadosApiController::class, 'show'])->name('show');
        });

        // Calendario
        Route::get('calendario',               [CalendarioApiController::class, 'index'])->name('api.calendario');

        // Pagos
        Route::prefix('pagos')->name('api.pagos.')->group(function () {
            Route::get('/',                    [PagosApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',    [PagosApiController::class, 'hijo'])->name('hijo');
        });

        // Classroom (LMS)
        Route::prefix('classroom')->name('api.classroom.')->group(function () {
            Route::get('/',                                    [ClassroomApiController::class, 'index'])->name('index');
            Route::post('/',                                   [ClassroomApiController::class, 'store'])->name('store');
            Route::get('{claseVirtual}/materiales',            [ClassroomApiController::class, 'materiales'])->name('materiales');
            Route::post('{claseVirtual}/materiales',           [ClassroomApiController::class, 'storeMaterial'])->name('materiales.store');
            Route::patch('materiales/{material}/publicar',     [ClassroomApiController::class, 'togglePublicar'])->name('materiales.publicar');
        });

        // Docente — gestión de grupos, asistencia, calificaciones, observaciones y tareas
        Route::prefix('docente')->name('api.docente.')->group(function () {
            Route::get('grupos',                             [DocenteApiController::class, 'grupos'])->name('grupos');
            Route::get('asistencia/{id}',                    [DocenteApiController::class, 'consultarAsistencia'])->name('asistencia.consultar');
            Route::post('asistencia',                        [DocenteApiController::class, 'registrarAsistencia'])->name('asistencia.registrar');
            Route::get('calificaciones/{asignacion}',        [DocenteApiController::class, 'calificaciones'])->name('calificaciones');
            Route::post('calificaciones/{asignacion}/guardar',  [DocenteApiController::class, 'guardarCalificacion'])->name('calificaciones.guardar');
            Route::patch('calificaciones/{asignacion}/publicar', [DocenteApiController::class, 'publicarCalificaciones'])->name('calificaciones.publicar');
            Route::get('observaciones',                      [DocenteApiController::class, 'observaciones'])->name('observaciones');
            Route::post('observaciones',                     [DocenteApiController::class, 'storeObservacion'])->name('observaciones.store');
            Route::get('tareas',                             [DocenteApiController::class, 'tareasDocente'])->name('tareas');
            Route::post('tareas',                            [DocenteApiController::class, 'storeTarea'])->name('tareas.store');
            Route::get('tareas/{tarea}/entregas',            [DocenteApiController::class, 'entregasTarea'])->name('tareas.entregas');
            Route::patch('tareas/{tarea}/calificar',         [DocenteApiController::class, 'calificarEntrega'])->name('tareas.calificar');
            Route::get('conducta',                           [DocenteApiController::class, 'conducta'])->name('conducta');
            Route::post('conducta',                          [DocenteApiController::class, 'guardarConducta'])->name('conducta.store');
            Route::get('plan-evaluacion',                    [DocenteApiController::class, 'planEvaluacion'])->name('plan-evaluacion');
            Route::get('instrumentos',                       [DocenteApiController::class, 'instrumentos'])->name('instrumentos');
            Route::get('riesgo',                             [DocenteApiController::class, 'riesgoGrupo'])->name('riesgo');
        });

        // Mensajería interna
        Route::prefix('mensajes')->name('api.mensajes.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\Api\MensajesApiController::class, 'index'])->name('index');
            Route::get('destinatarios',   [\App\Http\Controllers\Api\MensajesApiController::class, 'destinatarios'])->name('destinatarios');
            Route::get('{id}',            [\App\Http\Controllers\Api\MensajesApiController::class, 'show'])->name('show');
            Route::post('/',              [\App\Http\Controllers\Api\MensajesApiController::class, 'store'])->name('store');
        });

        // Gamificación
        Route::get('gamificacion/mis-puntos',                       [\App\Http\Controllers\Api\GamificacionApiController::class, 'misPuntos'])->name('api.gamificacion.mis-puntos');
        Route::get('gamificacion/hijo/{estudiante}',                [\App\Http\Controllers\Api\GamificacionApiController::class, 'hijoPuntos'])->name('api.gamificacion.hijo');
        Route::get('gamificacion/grupo/{asignacion}',               [\App\Http\Controllers\Api\GamificacionApiController::class, 'grupoPuntos'])->name('api.gamificacion.grupo');
        Route::post('gamificacion/grupo/{asignacion}/asignar',      [\App\Http\Controllers\Api\GamificacionApiController::class, 'asignarPuntos'])->name('api.gamificacion.asignar');

        // Academic Risk Score
        Route::prefix('riesgo')->name('api.riesgo.')->group(function () {
            Route::get('mi-score',           [\App\Http\Controllers\Api\RiesgoApiController::class, 'miScore'])->name('mi-score');
            Route::get('hijo/{estudiante}',  [\App\Http\Controllers\Api\RiesgoApiController::class, 'hijoScore'])->name('hijo');
        });

        // Tutor IA — chat académico (Estudiante, Representante, Docente)
        Route::post('ai/chat', [\App\Http\Controllers\Api\TutorIaApiController::class, 'chat'])
             ->middleware('throttle:30,1')
             ->name('api.ai.chat');

        // Encuestas de satisfacción
        Route::prefix('encuestas')->name('api.encuestas.')->group(function () {
            Route::get('/',                       [EncuestasApiController::class, 'index'])->name('index');
            Route::get('{encuesta}',              [EncuestasApiController::class, 'show'])->name('show');
            Route::post('{encuesta}/responder',   [EncuestasApiController::class, 'responder'])->name('responder');
        });

        // Tareas
        Route::prefix('tareas')->name('api.tareas.')->group(function () {
            Route::get('/',                       [TareasApiController::class, 'index'])->name('index');
            Route::get('hijo/{estudiante}',       [TareasApiController::class, 'hijo'])->name('hijo');
        });

        // Cafetería
        Route::prefix('cafeteria')->name('api.cafeteria.')->group(function () {
            Route::get('saldo',                   [CafeteriaApiController::class, 'saldo'])->name('saldo');
            Route::get('saldo-hijo/{estudiante}', [CafeteriaApiController::class, 'saldoHijo'])->name('saldo-hijo');
        });

        // Transporte
        Route::prefix('transporte')->name('api.transporte.')->group(function () {
            Route::get('mi-ruta',                     [TransporteApiController::class, 'miRuta'])->name('mi-ruta');
            Route::get('ruta-hijo/{estudiante}',      [TransporteApiController::class, 'rutaHijo'])->name('ruta-hijo');
        });

        // Documentos (hub de PDFs con token temporal)
        Route::prefix('documentos')->name('api.documentos.')->group(function () {
            Route::get('info',                        [DocumentosApiController::class, 'info'])->name('info');
            Route::get('info-hijo/{estudiante}',      [DocumentosApiController::class, 'infoHijo'])->name('info-hijo');
        });

        // Observaciones (Estudiante ve las suyas; Representante ve las de su hijo)
        Route::prefix('observaciones')->name('api.observaciones.')->group(function () {
            Route::get('/',                    [ObservacionesApiController::class, 'misObservaciones'])->name('index');
            Route::get('hijo/{estudiante}',    [ObservacionesApiController::class, 'hijoObservaciones'])->name('hijo');
        });

        // Resultados de evaluación (Estudiante y Representante)
        Route::prefix('mis-resultados')->name('api.resultados.')->group(function () {
            Route::get('/',                    [ResultadosEvaluacionApiController::class, 'misResultados'])->name('index');
            Route::get('hijo/{estudiante}',    [ResultadosEvaluacionApiController::class, 'hijoResultados'])->name('hijo');
        });

        // Conducta (Estudiante ve la suya; Representante ve la de su hijo)
        Route::prefix('conducta')->name('api.conducta.')->group(function () {
            Route::get('/',                    [ConductaApiController::class, 'miConducta'])->name('index');
            Route::get('hijo/{estudiante}',    [ConductaApiController::class, 'hijoConducta'])->name('hijo');
        });

        // Plan de Evaluación (Estudiante y Representante)
        Route::prefix('plan-evaluacion')->name('api.plan-evaluacion.')->group(function () {
            Route::get('/',                    [PlanEvaluacionApiController::class, 'miPlan'])->name('index');
            Route::get('hijo/{estudiante}',    [PlanEvaluacionApiController::class, 'hijoPlan'])->name('hijo');
        });

        // Solicitudes (estudiante + representante)
        Route::prefix('solicitudes')->name('api.solicitudes.')->group(function () {
            Route::get('/',                           [SolicitudesApiController::class, 'index'])->name('index');
            Route::post('/',                          [SolicitudesApiController::class, 'store'])->name('store');
            Route::get('{id}',                        [SolicitudesApiController::class, 'show'])->name('show');
        });

        // Reconocimientos (Estudiante ve los suyos; Representante ve los de su hijo)
        Route::prefix('reconocimientos')->name('api.reconocimientos.')->group(function () {
            Route::get('/',                         [\App\Http\Controllers\Api\ReconocimientosApiController::class, 'misReconocimientos'])->name('index');
            Route::get('hijo/{estudiante}',         [\App\Http\Controllers\Api\ReconocimientosApiController::class, 'hijoReconocimientos'])->name('hijo');
        });

        // Salud (Representante ve la ficha de salud de su hijo)
        Route::prefix('salud')->name('api.salud.')->group(function () {
            Route::get('hijo/{estudiante}',         [\App\Http\Controllers\Api\SaludApiController::class, 'saludHijo'])->name('hijo');
        });

        // Evaluaciones de desempeño docente
        Route::get('docente/mis-evaluaciones',      [\App\Http\Controllers\Api\EvaluacionesDocenteApiController::class, 'misEvaluaciones'])->name('api.docente.mis-evaluaciones');

        // Reuniones del docente
        Route::get('docente/mis-reuniones',         [\App\Http\Controllers\Api\ReunionesApiController::class, 'misReuniones'])->name('api.docente.mis-reuniones');
    });
});
