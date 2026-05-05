{{--
    Sidebar reutilizable para el portal estudiante.
    Uso: @include('portal.estudiante._sidebar', ['activeKey' => 'dashboard'])
    Valores de activeKey: dashboard|boletin|planificaciones|recursos|asistencia|observaciones
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp
<div class="prt-sidebar-section">Navegación</div>
<a href="{{ route('portal.estudiante.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.estudiante.boletin') }}"
   class="prt-sidebar-link {{ $ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Mi Boletín
</a>
<a href="{{ route('portal.estudiante.planificaciones') }}"
   class="prt-sidebar-link {{ $ak === 'planificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Planificaciones
</a>
<a href="{{ route('portal.estudiante.horario') }}"
   class="prt-sidebar-link {{ $ak === 'horario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Mi Horario
</a>
<a href="{{ route('portal.estudiante.asistencia') }}"
   class="prt-sidebar-link {{ $ak === 'asistencia' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check"></i>Mi Asistencia
</a>
<a href="{{ route('portal.estudiante.observaciones') }}"
   class="prt-sidebar-link {{ $ak === 'observaciones' ? 'active' : '' }}">
    <i class="bi bi-chat-square-text"></i>Observaciones
</a>
<a href="{{ route('portal.estudiante.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
@if(isset($asignacion))
<div class="prt-sidebar-section mt-2">Esta Materia</div>
<a href="{{ route('portal.estudiante.recursos', $asignacion) }}"
   class="prt-sidebar-link {{ $ak === 'recursos' ? 'active' : '' }}">
    <i class="bi bi-folder-fill"></i>Recursos
</a>
@endif
<a href="{{ route('admin.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php $msgEst = \App\Models\Mensaje::recibidos(auth()->id())->noLeidos()->count(); @endphp
    @if($msgEst > 0)<span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgEst }}</span>@endif
</a>
<div class="prt-sidebar-section mt-3">Cuenta</div>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
        <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
    </button>
</form>
