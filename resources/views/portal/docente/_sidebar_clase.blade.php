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
<a href="{{ route('portal.docente.mi-carnet') }}"
   class="prt-sidebar-link {{ $ak === 'mi-carnet' ? 'active' : '' }}">
    <i class="bi bi-credit-card-2-front-fill" style="{{ $ak === 'mi-carnet' ? '' : 'color:#0ea5e9;' }}"></i>Mi Carnet+
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
@php try { $__gamifSb = !app()->bound('tenant') || (app()->bound('tenant') && app('tenant')?->can('gamificacion')); } catch(\Exception $e){ $__gamifSb = false; } @endphp
@if($__gamifSb)
<a href="{{ route('portal.docente.gamificacion') }}"
   class="prt-sidebar-link {{ $ak === 'gamificacion' ? 'active' : '' }}">
    <i class="bi bi-trophy-fill" style="color:#f59e0b;"></i>Gamificación
</a>
@endif
@php
try { $evDocFeature = !app()->bound('tenant') || (app()->bound('tenant') && app('tenant')?->can('evaluaciones_docentes')); } catch(\Exception $e){ $evDocFeature = false; }
if ($evDocFeature) {
    try {
        $misEvsCount = \App\Models\EvaluacionDocente::where('docente_id', auth()->user()->docente?->id ?? 0)->count();
    } catch(\Exception $e){ $misEvsCount = 0; }
}
@endphp
@if(!empty($evDocFeature) && !empty($misEvsCount) && $misEvsCount > 0)
<a href="{{ route('portal.docente.mis-evaluaciones') }}"
   class="prt-sidebar-link {{ $ak === 'mis-evaluaciones' ? 'active' : '' }}"
   style="{{ $ak === 'mis-evaluaciones' ? '' : 'color:#6366f1;' }}">
    <i class="bi bi-clipboard2-check-fill"></i>Mis Evaluaciones
</a>
@endif
@php
try { $reunionesFeature = !app()->bound('tenant') || (app()->bound('tenant') && app('tenant')?->can('reuniones')); } catch(\Exception $e){ $reunionesFeature = false; }
@endphp
@if(!empty($reunionesFeature))
<a href="{{ route('portal.docente.mis-reuniones') }}"
   class="prt-sidebar-link {{ $ak === 'mis-reuniones' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Mis Reuniones
</a>
@endif
<a href="{{ route('portal.docente.rubricas.index') }}"
   class="prt-sidebar-link {{ $ak === 'rubricas' ? 'active' : '' }}"
   style="{{ $ak === 'rubricas' ? '' : 'color:#ec4899;' }}">
    <i class="bi bi-table"></i>Rúbricas
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
<a href="{{ route('portal.docente.asistencia.alertas', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'asistencia-alertas' ? 'active' : '' }}"
   style="{{ $ak === 'asistencia-alertas' ? '' : 'color:#ef4444;' }}">
    <i class="bi bi-bell-fill"></i>Alertas de Inasistencias
</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'calificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-check"></i>Calificaciones
</a>
<a href="{{ route('portal.docente.conducta.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'conducta' ? 'active' : '' }}">
    <i class="bi bi-person-check-fill"></i>Conducta
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
<a href="{{ route('portal.docente.acta-calificaciones', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'acta-calificaciones' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-spreadsheet-fill"></i>Acta de Calificaciones
</a>
<a href="{{ route('portal.docente.consolidado-periodo', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'consolidado-periodo' ? 'active' : '' }}">
    <i class="bi bi-clipboard-data-fill"></i>Consolidado del Período
</a>
<a href="{{ route('portal.docente.rendimiento', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'rendimiento' ? 'active' : '' }}">
    <i class="bi bi-graph-up-arrow"></i>Rendimiento
</a>
<a href="{{ route('portal.docente.historial-notas', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'historial-notas' ? 'active' : '' }}">
    <i class="bi bi-activity"></i>Comparativa P1→P4
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
<a href="{{ route('portal.docente.planif-anual.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'planif-anual' ? 'active' : '' }}">
    <i class="bi bi-map-fill"></i>Plan Anual (Unidades)
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

<a href="{{ route('portal.docente.comint.index') }}"
   class="prt-sidebar-link {{ $ak === 'comint' ? 'active' : '' }}">
    <i class="bi bi-envelope-paper-fill"></i>Comunicados Internos
    @php
    try {
        $__uid = auth()->id();
        $comintUnread = \Illuminate\Support\Facades\Cache::remember('t'.(tenant_id()??0).'_user_'.$__uid.'_comint_unread', 120, function () use ($__uid) {
            $user = auth()->user();
            if (!$user) return 0;
            $tipos = ['todos'];
            if ($user->hasRole('Docente')) $tipos[] = 'docentes';
            if ($user->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador'])) {
                $tipos[] = 'coordinadores'; $tipos[] = 'docentes';
            }
            return \App\Models\Comunicado::internos()->publicados()
                ->whereIn('tipo_destinatarios', $tipos)
                ->whereDoesntHave('lecturas', fn($q) => $q->where('user_id', $__uid))
                ->count();
        });
    } catch(\Exception $e){ $comintUnread = 0; }
    @endphp
    @if($comintUnread > 0)
    <span class="comint-badge-sb" style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $comintUnread }}</span>
    @endif
</a>

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
<a href="{{ route('admin.rubricas.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
    <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
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
