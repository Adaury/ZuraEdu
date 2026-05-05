{{--
    Sidebar Portal Estudiante
    activeKey: dashboard | boletin | horario | asistencia | observaciones |
               comunicados | encuestas | classroom | tareas | eventos |
               mis-documentos | mensajes | mis-puntos
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp

{{-- ── PRINCIPAL ── --}}
<div class="prt-sidebar-section">Mi Espacio</div>

<a href="{{ route('portal.estudiante.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.estudiante.boletin') }}"
   class="prt-sidebar-link {{ $ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Mi Boletín
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

{{-- ── AULA VIRTUAL ── --}}
<div class="prt-sidebar-section mt-2">Aula Virtual</div>

<a href="{{ route('portal.estudiante.classroom.index') }}"
   class="prt-sidebar-link {{ $ak === 'classroom' ? 'active' : '' }}">
    <i class="bi bi-easel2-fill"></i>Mi Classroom
</a>
<a href="{{ route('portal.estudiante.tareas') }}"
   class="prt-sidebar-link {{ $ak === 'tareas' ? 'active' : '' }}">
    <i class="bi bi-check2-square"></i>Mis Tareas
</a>

{{-- ── VIDA ESCOLAR ── --}}
<div class="prt-sidebar-section mt-2">Vida Escolar</div>

<a href="{{ route('portal.estudiante.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
<a href="{{ route('portal.estudiante.eventos') }}"
   class="prt-sidebar-link {{ $ak === 'eventos' ? 'active' : '' }}">
    <i class="bi bi-calendar-event-fill"></i>Eventos
</a>
<a href="{{ route('portal.estudiante.encuestas') }}"
   class="prt-sidebar-link {{ $ak === 'encuestas' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Encuestas
</a>

{{-- ── DOCUMENTOS ── --}}
<div class="prt-sidebar-section mt-2">Documentos</div>

<a href="{{ route('portal.estudiante.mis-documentos') }}"
   class="prt-sidebar-link {{ $ak === 'mis-documentos' ? 'active' : '' }}">
    <i class="bi bi-folder2-open"></i>Mis Documentos
</a>
<a href="{{ route('admin.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php try { $msgEst = \App\Models\Mensaje::recibidos(auth()->id())->noLeidos()->count(); } catch(\Exception $e){ $msgEst=0; } @endphp
    @if($msgEst > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgEst }}</span>
    @endif
</a>

{{-- ── CUENTA ── --}}
<div class="prt-sidebar-section mt-3">Cuenta</div>
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
