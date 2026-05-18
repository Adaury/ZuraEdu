@extends('layouts.portal')

@section('page-title', 'Portal del Representante')
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'dashboard'])
    <div class="prt-sidebar-section mt-2">En esta página</div>
    <a href="#mis-hijos" class="prt-sidebar-link"><i class="bi bi-people-fill"></i>Mis Hijos</a>
    <a href="#noticias" class="prt-sidebar-link"><i class="bi bi-megaphone"></i>Noticias</a>
    <a href="#notificaciones" class="prt-sidebar-link"><i class="bi bi-bell"></i>Notificaciones</a>
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item active">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="#mis-hijos" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Hijos
    </a>
    <a href="#noticias" class="prt-nav-item">
        <i class="bi bi-megaphone"></i>Noticias
    </a>
    <a href="#notificaciones" class="prt-nav-item">
        <i class="bi bi-bell"></i>Notif.
    </a>
    <form method="POST" action="{{ route('logout') }}" class="prt-nav-item" style="background:none;border:none;">
        @csrf<button type="submit" style="background:none;border:none;padding:0;color:#64748b;display:flex;flex-direction:column;align-items:center;gap:.15rem;font-size:.62rem;">
            <i class="bi bi-box-arrow-right" style="font-size:1.2rem;color:#ef4444;"></i>Salir
        </button>
    </form>
@endsection

@section('content')

{{-- ── Saludo ───────────────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#1e3a5f 0%,#0ea5e9 100%);border-radius:14px;padding:1.25rem 1.5rem;color:#fff;margin-bottom:1rem;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:130px;height:130px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
    <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;flex-shrink:0;">
        {{ strtoupper(substr($representante->nombres ?? 'R', 0, 1)) }}
    </div>
    <div>
        <div style="font-size:1rem;font-weight:800;margin-bottom:.2rem;">Bienvenido, {{ $representante->nombres }}</div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.75);">
            <i class="bi bi-people-fill me-1"></i>{{ $hijos->count() }} hijo{{ $hijos->count() !== 1 ? 's' : '' }} registrado{{ $hijos->count() !== 1 ? 's' : '' }}
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    @if($totalNoLeidas ?? 0 > 0)
    <div style="margin-left:auto;background:rgba(255,255,255,.15);border-radius:10px;padding:.55rem .85rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;line-height:1;">{{ $totalNoLeidas }}</div>
        <div style="font-size:.62rem;color:rgba(255,255,255,.7);">Alertas</div>
    </div>
    @endif
</div>

{{-- ── Mis hijos ────────────────────────────────────────────────────── --}}
<div id="mis-hijos">
    <h2 style="font-size:.88rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem;">
        <i class="bi bi-people-fill me-1"></i>Mis Hijos
    </h2>

    @forelse($hijos as $hijo)
    @php
        $matricula     = $hijo->_matricula;
        $promedio      = $hijo->_promedio;
        $alertas       = $hijo->_alertas;
        $hijosPuntos   = $hijo->_puntos;
        $hijosInsignia = $hijo->_insignias;
        $promedioColor = $promedio === null ? '#6b7280' : ($promedio >= 80 ? '#15803d' : ($promedio >= 60 ? '#d97706' : '#dc2626'));
    @endphp
    <a href="{{ route('portal.padre.hijo', $hijo) }}" class="prt-card hijo-card" style="margin-bottom:1rem;display:block;text-decoration:none;cursor:pointer;transition:box-shadow .2s,transform .15s;" onmouseover="this.style.boxShadow='0 6px 20px rgba(0,0,0,.1)';this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        {{-- Alertas importantes --}}
        @foreach($alertas as $alerta)
        <div class="prt-alert {{ $alerta['tipo'] === 'rendimiento' ? 'prt-alert-danger' : 'prt-alert-warning' }}" style="margin:.75rem .75rem 0;border-radius:9px;">
            <i class="bi bi-{{ $alerta['tipo'] === 'rendimiento' ? 'graph-down-arrow' : 'calendar-x' }}"></i>
            {{ $alerta['texto'] }}
        </div>
        @endforeach

        {{-- Info del hijo --}}
        <div class="dm-list-item" style="padding:1rem;display:flex;align-items:center;gap:1rem;border-bottom:1px solid #f1f5f9;">
            <div class="dm-avatar" style="width:46px;height:46px;border-radius:50%;background:#eff6ff;border:2px solid #bfdbfe;display:flex;align-items:center;justify-content:center;font-weight:900;color:#1d4ed8;font-size:1.1rem;flex-shrink:0;">
                {{ strtoupper(substr($hijo->nombres ?? 'E', 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div class="dm-text-primary" style="font-size:.92rem;font-weight:800;">{{ $hijo->nombre_completo }}</div>
                <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
                    {{ $matricula?->grupo?->nombre_completo ?? 'Sin grupo' }}
                    @if($hijo->pivot->parentesco) &nbsp;·&nbsp; {{ $hijo->pivot->parentesco }} @endif
                </div>
            </div>
            @if($promedio !== null)
            <div class="dm-promedio-badge" style="text-align:center;background:#f8fafc;border-radius:9px;padding:.5rem .75rem;border:1px solid #e2e8f0;">
                <div style="font-size:1.3rem;font-weight:900;color:{{ $promedioColor }};line-height:1;">{{ $promedio }}</div>
                <div class="dm-text-muted" style="font-size:.62rem;color:#9ca3af;">Promedio</div>
            </div>
            @endif
            <div style="color:#94a3b8;flex-shrink:0;"><i class="bi bi-chevron-right"></i></div>
        </div>

        {{-- Gamificación mini --}}
        @if(!empty($gamificacionActiva) && $hijosPuntos !== null)
        <div style="padding:.5rem 1rem;border-top:1px solid #f1f5f9;display:flex;gap:.65rem;align-items:center;">
            <div style="display:flex;align-items:center;gap:.3rem;background:#eef2ff;border-radius:8px;padding:.3rem .6rem;">
                <i class="bi bi-star-fill" style="color:#6366f1;font-size:.75rem;"></i>
                <span style="font-size:.78rem;font-weight:800;color:#4338ca;">{{ number_format($hijosPuntos) }} pts</span>
            </div>
            @if($hijosInsignia > 0)
            <div style="display:flex;align-items:center;gap:.3rem;background:#fef9c3;border-radius:8px;padding:.3rem .6rem;">
                <i class="bi bi-award-fill" style="color:#b45309;font-size:.75rem;"></i>
                <span style="font-size:.78rem;font-weight:800;color:#92400e;">{{ $hijosInsignia }} insignia{{ $hijosInsignia != 1 ? 's' : '' }}</span>
            </div>
            @endif
            <a href="{{ route('portal.padre.hijo.logros', $hijo) }}" onclick="event.stopPropagation();"
               style="margin-left:auto;font-size:.72rem;color:#6366f1;font-weight:700;text-decoration:none;">
                Ver logros →
            </a>
        </div>
        @endif

        {{-- Acciones rápidas --}}
        <div style="padding:.6rem 1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
            <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;padding:.3rem .75rem;font-size:.76rem;font-weight:600;display:inline-flex;align-items:center;gap:.35rem;">
                <i class="bi bi-eye"></i>Ver detalle
            </span>
            <span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:8px;padding:.3rem .75rem;font-size:.76rem;font-weight:600;display:inline-flex;align-items:center;gap:.35rem;">
                <i class="bi bi-journal-check"></i>Notas
            </span>
            <span style="background:#fefce8;color:#92400e;border:1px solid #fde68a;border-radius:8px;padding:.3rem .75rem;font-size:.76rem;font-weight:600;display:inline-flex;align-items:center;gap:.35rem;">
                <i class="bi bi-calendar-check"></i>Asistencia
            </span>
        </div>
    </a>
    @empty
    <div class="prt-card prt-card-body" style="text-align:center;padding:2.5rem;color:#9ca3af;">
        <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
        <div style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:.35rem;">No tienes hijos registrados</div>
        <div style="font-size:.8rem;">Contacta al administrador del centro para vincular a tus hijos.</div>
    </div>
    @endforelse
</div>

{{-- ── Noticias ─────────────────────────────────────────────────────── --}}
@if($comunicados->isNotEmpty())
<div class="prt-card" id="noticias">
    <div class="prt-card-header">
        <i class="bi bi-megaphone" style="color:#3b82f6;font-size:1rem;"></i>
        <h3>Noticias del Centro</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($comunicados as $com)
        <div class="dm-list-item" style="padding:.8rem 1rem;border-bottom:1px solid #f1f5f9;">
            <div class="dm-text-primary" style="font-size:.84rem;font-weight:700;margin-bottom:.25rem;">{{ $com->titulo }}</div>
            <div class="dm-text-muted" style="font-size:.77rem;color:#64748b;">{{ Str::limit(strip_tags($com->cuerpo), 110) }}</div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.3rem;">
                <span style="font-size:.68rem;color:#9ca3af;">{{ $com->published_at?->format('d/m/Y') }}</span>
                <button onclick="verComunicado({{ $com->id }},{{ json_encode($com->titulo) }},{{ json_encode($com->cuerpo) }},{{ json_encode($com->published_at?->format('d/m/Y') ?? '') }})"
                        style="background:none;border:none;color:#3b82f6;font-size:.72rem;font-weight:600;cursor:pointer;padding:0;">
                    Leer completo →
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>
<div id="modalComunicado" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;max-width:540px;width:100%;max-height:80vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:.95rem;color:#1e293b;" id="comTitulo"></div>
            <button onclick="cerrarComunicado()" style="background:none;border:none;font-size:1.2rem;color:#6b7280;cursor:pointer;">×</button>
        </div>
        <div style="padding:1rem 1.25rem;overflow-y:auto;flex:1;">
            <div style="font-size:.77rem;color:#6b7280;margin-bottom:.75rem;" id="comFecha"></div>
            <div style="font-size:.88rem;color:#374151;line-height:1.7;" id="comCuerpo" class="comunicado-body"></div>
        </div>
    </div>
</div>
<script>
function verComunicado(id,titulo,cuerpo,fecha){document.getElementById('comTitulo').textContent=titulo;document.getElementById('comFecha').textContent=fecha;document.getElementById('comCuerpo').innerHTML=cuerpo;document.getElementById('modalComunicado').style.display='flex';}
function cerrarComunicado(){document.getElementById('modalComunicado').style.display='none';}
document.getElementById('modalComunicado').addEventListener('click',function(e){if(e.target===this)cerrarComunicado();});
</script>
@endif

{{-- ── Notificaciones ───────────────────────────────────────────────── --}}
<div class="prt-card" id="notificaciones">
    <div class="prt-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-bell" style="color:#6366f1;font-size:1rem;"></i>
            <h3>Notificaciones</h3>
        </div>
        <div style="display:flex;gap:.5rem;margin-left:auto;">
            @if(($totalNoLeidas ?? 0) > 0)
            <button onclick="marcarTodasLeidas()" class="btn btn-sm"
                    style="font-size:.72rem;background:#eff6ff;color:#1d4ed8;border-radius:7px;border:1px solid #bfdbfe;">
                <i class="bi bi-check-all me-1"></i>Leídas
            </button>
            @endif
            <a href="{{ route('portal.padre.notificaciones') }}"
               style="font-size:.72rem;background:#f1f5f9;color:#374151;border-radius:7px;border:1px solid #e5e7eb;padding:.25rem .6rem;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                <i class="bi bi-list-ul"></i>Ver todas
            </a>
        </div>
    </div>
    <ul class="notif-list">
        @forelse($notificaciones as $notif)
        <li class="notif-item {{ $notif->leida ? '' : 'unread' }}">
            <span class="notif-dot" style="background:{{ $notif->color }};"></span>
            <div class="notif-icon" style="background:{{ $notif->color }}20;color:{{ $notif->color }};">
                <i class="bi {{ $notif->icono }}"></i>
            </div>
            <div style="flex:1;">
                <div class="notif-titulo">{{ $notif->titulo }}</div>
                <div class="notif-msg">{{ $notif->mensaje }}</div>
                <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
            </div>
        </li>
        @empty
        <li style="padding:2rem;text-align:center;color:#9ca3af;font-size:.84rem;">
            <i class="bi bi-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
            Sin notificaciones.
        </li>
        @endforelse
    </ul>
</div>

{{-- ── Próximos eventos del calendario ─────────────────────────────── --}}
@if($eventosCalendario->isNotEmpty())
<div class="prt-card" id="eventos" style="margin-top:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-event" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Próximos Eventos</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($eventosCalendario as $ev)
        @php
            $daysLeft = today()->diffInDays($ev->fecha_inicio, false);
            $color    = $ev->color ?? '#6366f1';
        @endphp
        <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.75rem 1rem;border-bottom:1px solid var(--prt-border);">
            <div style="width:42px;height:42px;border-radius:10px;background:{{ $color }}18;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid {{ $color }}35;">
                <div style="font-size:.95rem;font-weight:900;color:{{ $color }};line-height:1;">{{ $ev->fecha_inicio->format('d') }}</div>
                <div style="font-size:.55rem;font-weight:700;color:{{ $color }};text-transform:uppercase;letter-spacing:.04em;">{{ $ev->fecha_inicio->translatedFormat('M') }}</div>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.84rem;font-weight:700;color:var(--prt-text);">{{ $ev->titulo }}</div>
                @if($ev->descripcion)
                <div style="font-size:.72rem;color:var(--prt-muted);margin-top:.1rem;">{{ $ev->descripcion }}</div>
                @endif
            </div>
            <div style="font-size:.7rem;font-weight:600;white-space:nowrap;flex-shrink:0;color:{{ $daysLeft === 0 ? '#dc2626' : ($daysLeft <= 3 ? '#d97706' : '#6b7280') }};">
                {{ $daysLeft === 0 ? 'Hoy' : ($daysLeft === 1 ? 'Mañana' : "En {$daysLeft} días") }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
async function marcarTodasLeidas() {
    await fetch('{{ route("portal.padre.notif.leer-todas") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    });
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    document.querySelector('.prt-badge')?.remove();
}
</script>
@endpush
