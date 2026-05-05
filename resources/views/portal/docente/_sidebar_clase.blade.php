{{--
    Sidebar reutilizable para las vistas de asignación del portal docente.
    Uso: @include('portal.docente._sidebar_clase', ['activeKey' => 'planes'])
    Valores de activeKey: asistencia|calificaciones|estudiantes|observaciones|boletines|recursos|planes|instrumentos|planificacion
--}}
@php $ak = $activeKey ?? ''; @endphp
<div class="prt-sidebar-section">Mi Portal</div>
<a href="{{ route('portal.docente.dashboard') }}" class="prt-sidebar-link">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.docente.mis-planificaciones') }}"
   class="prt-sidebar-link {{ $ak === 'mis-planificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Mis Planificaciones
</a>
<div class="prt-sidebar-section mt-2">Esta Clase</div>
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
<a href="{{ route('portal.docente.recursos', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'recursos' ? 'active' : '' }}">
    <i class="bi bi-folder-fill"></i>Recursos
</a>
<a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'planes' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Planes de Clase
</a>
<a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'instrumentos' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Instrumentos
</a>
@if($asignacion->area === 'tecnica')
<a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'planificacion' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Planificaciones
</a>
@endif
<div class="prt-sidebar-section mt-2">Cuenta</div>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
        <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
    </button>
</form>
