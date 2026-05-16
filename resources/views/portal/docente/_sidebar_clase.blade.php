{{--
    Sidebar contextual para vistas de asignación del portal docente.
    Uso: @include('portal.docente._sidebar_clase', ['activeKey' => 'calificaciones', 'asignacion' => $asignacion])
    activeKey: asistencia|calificaciones|estudiantes|observaciones|boletines|
               recursos|planes|instrumentos|plan-evaluacion|planificacion|tareas|
               mis-estadisticas|mis-planificaciones|mis-estudiantes|classroom
--}}
@php
$ak  = $activeKey ?? '';
@endphp

{{-- ── MI PORTAL ── --}}
<div class="prt-sidebar-section">Mi Portal</div>

<a href="{{ route('portal.docente.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.docente.asistencia-rapida') }}"
   class="prt-sidebar-link {{ $ak === 'asistencia-rapida' ? 'active' : '' }}"
   style="{{ $ak === 'asistencia-rapida' ? '' : 'color:#f59e0b;' }}">
    <i class="bi bi-lightning-charge-fill"></i>Asistencia Rápida
</a>
<a href="{{ route('portal.docente.horario') }}"
   class="prt-sidebar-link {{ $ak === 'horario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Mi Horario
</a>
<a href="{{ route('portal.docente.calendario') }}"
   class="prt-sidebar-link {{ $ak === 'calendario' ? 'active' : '' }}">
    <i class="bi bi-calendar-event-fill"></i>Calendario Escolar
</a>
<a href="{{ route('portal.docente.classroom.index') }}"
   class="prt-sidebar-link {{ $ak === 'classroom' ? 'active' : '' }}">
    <i class="bi bi-easel2-fill"></i>Mi Classroom
</a>
<a href="{{ route('portal.docente.mis-estudiantes') }}"
   class="prt-sidebar-link {{ $ak === 'mis-estudiantes' ? 'active' : '' }}">
    <i class="bi bi-people-fill"></i>Mis Estudiantes
</a>
<a href="{{ route('portal.docente.mis-estadisticas') }}"
   class="prt-sidebar-link {{ $ak === 'mis-estadisticas' ? 'active' : '' }}">
    <i class="bi bi-bar-chart-fill"></i>Estadísticas
</a>
<a href="{{ route('portal.docente.banco-preguntas.index') }}"
   class="prt-sidebar-link {{ $ak === 'banco-preguntas' ? 'active' : '' }}"
   style="{{ $ak === 'banco-preguntas' ? '' : 'color:#8b5cf6;' }}">
    <i class="bi bi-collection-fill"></i>Banco de Preguntas
    @php try { $totalBancoSb = \App\Models\BancoPregunta::where('docente_id', auth()->user()->docente?->id ?? 0)->count(); } catch(\Exception $e){ $totalBancoSb=0; } @endphp
    @if($totalBancoSb > 0)
    <span style="background:#8b5cf6;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $totalBancoSb }}</span>
    @endif
</a>
@php
try {
    $docenteSb = auth()->user()->docente ?? null;
    $tutoriasSb = $docenteSb ? \App\Models\Tutoria::where('docente_id', $docenteSb->id)
        ->where('activo', true)
        ->count() : 0;
} catch(\Exception $e){ $tutoriasSb = 0; }
@endphp
@if($tutoriasSb > 0)
<a href="{{ route('portal.docente.mis-tutorias') }}"
   class="prt-sidebar-link {{ $ak === 'mis-tutorias' ? 'active' : '' }}">
    <i class="bi bi-person-hearts"></i>Mis Tutorías
    <span style="background:#7c3aed;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $tutoriasSb }}</span>
</a>
@endif

{{-- ── ESTA CLASE ── --}}
@if(isset($asignacion))
<div class="prt-sidebar-section mt-2">
    {{ $asignacion->asignatura?->nombre ?? 'Esta Clase' }}
</div>

<a href="{{ route('portal.docente.asistencia', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'asistencia' ? 'active' : '' }}">
    <i class="bi bi-calendar-check-fill"></i>Asistencia
</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'calificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-check"></i>Calificaciones
</a>
<a href="{{ route('portal.docente.estudiantes', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'estudiantes' ? 'active' : '' }}">
    <i class="bi bi-people-fill"></i>Estudiantes
</a>
<a href="{{ route('portal.docente.observaciones', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'observaciones' ? 'active' : '' }}">
    <i class="bi bi-chat-square-text"></i>Observaciones
</a>
<a href="{{ route('portal.docente.boletines', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'boletines' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Boletines
</a>
<a href="{{ route('portal.docente.rendimiento', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'rendimiento' ? 'active' : '' }}">
    <i class="bi bi-graph-up-arrow"></i>Rendimiento
</a>
<a href="{{ route('portal.docente.historial-notas', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'historial-notas' ? 'active' : '' }}">
    <i class="bi bi-activity"></i>Historial de Notas
</a>
<a href="{{ route('portal.docente.comunicado', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'comunicado' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Comunicado
</a>
<a href="{{ route('portal.docente.recursos', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'recursos' ? 'active' : '' }}">
    <i class="bi bi-folder-fill"></i>Recursos
</a>
<a href="{{ route('portal.docente.diario.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'diario' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Diario de Clase
</a>

{{-- ── PLANIFICACIÓN ── --}}
<div class="prt-sidebar-section mt-2">Planificación</div>

<a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'planes' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Planes de Clase
</a>
<a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'instrumentos' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Instrumentos
</a>
<a href="{{ route('portal.docente.plan-evaluacion.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'plan-evaluacion' ? 'active' : '' }}">
    <i class="bi bi-bar-chart-steps"></i>Plan de Evaluación
</a>
<a href="{{ route('portal.docente.tareas.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'tareas' ? 'active' : '' }}">
    <i class="bi bi-check2-square"></i>Tareas / Agenda
</a>
<a href="{{ route('portal.docente.evaluaciones.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'evaluaciones' ? 'active' : '' }}"
   style="{{ $ak === 'evaluaciones' ? '' : 'color:#6366f1;' }}">
    <i class="bi bi-patch-question-fill"></i>Evaluaciones Online
</a>
@if(isset($asignacion) && $asignacion->area === 'tecnica')
<a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'planificacion' ? 'active' : '' }}">
    <i class="bi bi-kanban-fill"></i>Planificaciones Técnicas
</a>
<a href="{{ route('portal.docente.mis-planificaciones') }}"
   class="prt-sidebar-link {{ $ak === 'mis-planificaciones' ? 'active' : '' }}">
    <i class="bi bi-collection-fill"></i>Todas mis Planif.
</a>
@endif
@endif

{{-- ── GESTIONES ── --}}
<div class="prt-sidebar-section mt-2">Gestiones</div>

<a href="{{ route('portal.docente.solicitudes.index') }}"
   class="prt-sidebar-link {{ $ak === 'solicitudes' ? 'active' : '' }}">
    <i class="bi bi-send-fill"></i>Mis Solicitudes
    @php try { $solDocPend = \App\Models\SolicitudDocente::whereHas('docente', fn($d) => $d->where('user_id', auth()->id()))->where('estado','pendiente')->count(); } catch(\Exception $e){ $solDocPend=0; } @endphp
    @if($solDocPend > 0)
    <span style="background:#d97706;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $solDocPend }}</span>
    @endif
</a>

{{-- ── MIS DOCUMENTOS ── --}}
<div class="prt-sidebar-section mt-2">Mis Documentos</div>

<a href="{{ route('portal.docente.constancia-trabajo') }}"
   class="prt-sidebar-link {{ $ak === 'constancia-trabajo' ? 'active' : '' }}"
   target="_blank">
    <i class="bi bi-file-earmark-person-fill"></i>Constancia de Trabajo
</a>
<a href="{{ route('portal.docente.ficha-actividad') }}"
   class="prt-sidebar-link {{ $ak === 'ficha-actividad' ? 'active' : '' }}"
   target="_blank">
    <i class="bi bi-file-earmark-bar-graph-fill"></i>Ficha de Actividad
</a>

{{-- ── DIRECCIÓN ── --}}
@if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
<div class="prt-sidebar-section mt-2">Dirección</div>
<a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
    <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
</a>
@endif

{{-- ── CUENTA ── --}}
<div class="prt-sidebar-section mt-2">Cuenta</div>

<a href="{{ route('portal.docente.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php try { $__duid = auth()->id(); $msgDoc = \Illuminate\Support\Facades\Cache::remember("user_{$__duid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__duid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $msgDoc=0; } @endphp
    @if($msgDoc > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgDoc }}</span>
    @endif
</a>
<a href="{{ route('perfil.show') }}"
   class="prt-sidebar-link {{ $ak === 'perfil' ? 'active' : '' }}">
    <i class="bi bi-person-circle"></i>Mi Perfil
</a>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;background:transparent;">
        <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
    </button>
</form>
