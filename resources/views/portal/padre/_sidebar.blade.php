{{--
    Sidebar reutilizable para el portal padre/representante.
    Uso: @include('portal.padre._sidebar', ['activeKey' => 'dashboard'])
    Valores de activeKey: dashboard|hijo|boletin|recursos
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp
<div class="prt-sidebar-section">Mi Panel</div>
<a href="{{ route('portal.padre.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
@if(isset($estudiante))
<a href="{{ route('portal.padre.hijo', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'hijo' ? 'active' : '' }}">
    <i class="bi bi-person-fill"></i>{{ $estudiante->nombres }}
</a>
<a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Boletín
</a>
<a href="{{ route('portal.padre.hijo.horario', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'horario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Horario
</a>
<a href="{{ route('portal.padre.hijo.asistencia', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'asistencia' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check"></i>Asistencia
</a>
<a href="{{ route('portal.padre.hijo.observaciones', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'observaciones' ? 'active' : '' }}">
    <i class="bi bi-chat-square-text"></i>Observaciones
</a>
<a href="{{ route('portal.padre.hijo.planificaciones', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'planificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i>Planificaciones
</a>
@if(isset($asignacion))
<div class="prt-sidebar-section mt-2">Esta Materia</div>
<a href="{{ route('portal.padre.hijo.recursos', [$estudiante, $asignacion]) }}"
   class="prt-sidebar-link {{ $ak === 'recursos' ? 'active' : '' }}">
    <i class="bi bi-folder-fill"></i>Recursos
</a>
@endif
@endif
<a href="{{ route('portal.padre.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
<a href="{{ route('admin.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php $msgPad = \App\Models\Mensaje::recibidos(auth()->id())->noLeidos()->count(); @endphp
    @if($msgPad > 0)<span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgPad }}</span>@endif
</a>
<div class="prt-sidebar-section mt-3">Cuenta</div>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
        <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
    </button>
</form>
