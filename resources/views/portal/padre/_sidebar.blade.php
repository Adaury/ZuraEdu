{{--
    Sidebar Portal Padre/Representante
    activeKey: dashboard | hijo | boletin | horario | asistencia | observaciones |
               classroom | documentos | comunicados | encuestas | mensajes
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp

{{-- ── PANEL PRINCIPAL ── --}}
<div class="prt-sidebar-section">Mi Panel</div>

<a href="{{ route('portal.padre.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>

{{-- ── SECCIÓN DEL HIJO (condicional) ── --}}
@if(isset($estudiante))
<div class="prt-sidebar-section mt-2" style="display:flex;align-items:center;gap:.4rem;">
    <i class="bi bi-person-circle" style="font-size:.75rem;"></i>
    {{ $estudiante->nombres }}
</div>

<a href="{{ route('portal.padre.hijo', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'hijo' ? 'active' : '' }}">
    <i class="bi bi-grid-1x2-fill"></i>Resumen
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
<a href="{{ route('portal.padre.hijo.classroom.index', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'classroom' ? 'active' : '' }}">
    <i class="bi bi-easel2-fill"></i>Classroom
</a>
<a href="{{ route('portal.padre.hijo.documentos', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'documentos' ? 'active' : '' }}">
    <i class="bi bi-folder2-open"></i>Documentos
</a>
@endif

{{-- ── COMUNICACIÓN ── --}}
<div class="prt-sidebar-section mt-2">Comunicación</div>

<a href="{{ route('portal.padre.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
<a href="{{ route('portal.padre.encuestas') }}"
   class="prt-sidebar-link {{ $ak === 'encuestas' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Encuestas
</a>
<a href="{{ route('admin.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php try { $msgPad = \App\Models\Mensaje::recibidos(auth()->id())->noLeidos()->count(); } catch(\Exception $e){ $msgPad=0; } @endphp
    @if($msgPad > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgPad }}</span>
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
