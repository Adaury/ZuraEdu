@extends('layouts.portal')

@section('page-title', 'Portal Docente — ' . ($docente->nombre_completo ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    <div class="prt-sidebar-section">Mi Portal</div>
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-sidebar-link {{ request()->routeIs('portal.docente.dashboard') ? 'active' : '' }}">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia-rapida') }}" class="prt-sidebar-link" style="color:#f59e0b;">
        <i class="bi bi-lightning-charge-fill"></i>Asistencia Rápida
    </a>
    <a href="{{ route('portal.docente.mis-estadisticas') }}" class="prt-sidebar-link">
        <i class="bi bi-bar-chart-fill"></i>Mis Estadísticas
    </a>
    <a href="{{ route('portal.docente.classroom.index') }}" class="prt-sidebar-link {{ request()->routeIs('portal.docente.classroom*') ? 'active' : '' }}">
        <i class="bi bi-easel2-fill" style="color:#3B82F6;"></i>Classroom
    </a>
    <a href="{{ route('portal.docente.mis-estudiantes') }}" class="prt-sidebar-link {{ request()->routeIs('portal.docente.mis-estudiantes') ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i>Mis Estudiantes
    </a>
    <a href="{{ route('portal.docente.mis-planificaciones') }}" class="prt-sidebar-link {{ request()->routeIs('portal.docente.mis-planificaciones*') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i>Mis Planificaciones
    </a>
    <a href="#rendimiento" class="prt-sidebar-link"><i class="bi bi-bar-chart-line"></i>Rendimiento</a>
    <a href="#mi-horario" class="prt-sidebar-link"><i class="bi bi-calendar-week"></i>Mi Horario</a>
    <a href="#notificaciones" class="prt-sidebar-link"><i class="bi bi-bell"></i>Notificaciones</a>
    <a href="{{ route('portal.docente.mensajes.index') }}" class="prt-sidebar-link">
        <i class="bi bi-envelope-fill"></i>Mensajes
        @php try { $__uid = auth()->id(); $msgDoc = \Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $msgDoc=0; } @endphp
        @if($msgDoc > 0)<span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgDoc }}</span>@endif
    </a>
    <a href="{{ route('portal.docente.comint.index') }}" class="prt-sidebar-link">
        <i class="bi bi-envelope-paper-fill"></i>Comunicados Internos
        @php
        try {
            $__uidCi = auth()->id();
            $comintUnreadDb = \Illuminate\Support\Facades\Cache::remember('t'.(tenant_id()??0).'_user_'.$__uidCi.'_comint_unread', 120, function () use ($__uidCi) {
                $user = auth()->user();
                if (!$user) return 0;
                $tipos = ['todos'];
                if ($user->hasRole('Docente')) $tipos[] = 'docentes';
                if ($user->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador'])) {
                    $tipos[] = 'coordinadores'; $tipos[] = 'docentes';
                }
                return \App\Models\Comunicado::internos()->publicados()
                    ->whereIn('tipo_destinatarios', $tipos)
                    ->whereDoesntHave('lecturas', fn($q) => $q->where('user_id', $__uidCi))
                    ->count();
            });
        } catch(\Exception $e){ $comintUnreadDb = 0; }
        @endphp
        @if($comintUnreadDb > 0)
        <span class="comint-badge-sb" style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $comintUnreadDb }}</span>
        @endif
    </a>
    @if($suplencias->isNotEmpty())
    <a href="#suplencias" class="prt-sidebar-link">
        <i class="bi bi-person-fill-exclamation" style="color:#d97706;"></i>Suplencias
        <span style="background:#d97706;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $suplencias->count() }}</span>
    </a>
    @endif

    @if($asignaciones->isNotEmpty())
    <div class="prt-sidebar-section mt-2">Mis Materias</div>
    @foreach($asignaciones as $asig)
    @php
        $grupoNombre = $asig->grupo?->nombre_completo
            ?? (($asig->grupo?->grado?->nombre ?? '') . ' ' . ($asig->grupo?->seccion?->nombre ?? ''));
    @endphp
    <div class="prt-class-item">
        <div class="prt-class-label">
            <i class="bi bi-book-fill" style="color:#6366f1;font-size:.8rem;"></i>
            <span>{{ Str::limit($asig->asignatura?->nombre ?? '—', 18) }}</span>
            <small class="d-block text-truncate" style="font-size:.65rem;color:#94a3b8;padding-left:1.4rem;">{{ $grupoNombre }}</small>
        </div>
        <div class="prt-class-actions">
            <a href="{{ route('portal.docente.asistencia', $asig) }}" class="prt-class-btn" style="--cb:#10b981;" title="Asistencia">
                <i class="bi bi-calendar-check-fill"></i><span>Asist.</span>
            </a>
            <a href="{{ route('portal.docente.calificaciones', $asig) }}" class="prt-class-btn" style="--cb:#6366f1;" title="Calificaciones">
                <i class="bi bi-journal-check"></i><span>Notas</span>
            </a>
            <a href="{{ route('portal.docente.estudiantes', $asig) }}" class="prt-class-btn" style="--cb:#2563eb;" title="Estudiantes">
                <i class="bi bi-people-fill"></i><span>Alum.</span>
            </a>
            <a href="{{ route('portal.docente.boletines', $asig) }}" class="prt-class-btn" style="--cb:#f59e0b;" title="Boletines">
                <i class="bi bi-file-earmark-text-fill"></i><span>Bolet.</span>
            </a>
            <a href="{{ route('portal.docente.observaciones', $asig) }}" class="prt-class-btn" style="--cb:#ec4899;" title="Observaciones">
                <i class="bi bi-chat-square-text-fill"></i><span>Obs.</span>
            </a>
            <a href="{{ route('portal.docente.recursos', $asig) }}" class="prt-class-btn" style="--cb:#2563eb;" title="Recursos">
                <i class="bi bi-folder-fill"></i><span>Rec.</span>
            </a>
            @if($asig->area === 'tecnica')
            <a href="{{ route('portal.docente.planificacion.index', $asig) }}" class="prt-class-btn" style="--cb:#7c3aed;" title="Planificaciones">
                <i class="bi bi-journal-text"></i><span>Planif.</span>
            </a>
            @else
            <a href="{{ route('portal.docente.planes-clase.index', $asig) }}" class="prt-class-btn" style="--cb:#0891b2;" title="Planes de Clase">
                <i class="bi bi-journal-text"></i><span>Planes</span>
            </a>
            @endif
            <a href="{{ route('portal.docente.instrumentos.index', $asig) }}" class="prt-class-btn" style="--cb:#7c3aed;" title="Instrumentos de Evaluación">
                <i class="bi bi-clipboard-check-fill"></i><span>Instrum.</span>
            </a>
        </div>
    </div>
    @endforeach
    @endif

    @if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
    <div class="prt-sidebar-section mt-2">Dirección</div>
    <a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
    </a>
    <a href="{{ route('admin.rubricas.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
        <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
    </a>
    @endif

    <div class="prt-sidebar-section mt-2">Cuenta</div>
    <a href="{{ route('perfil.show') }}" class="prt-sidebar-link">
        <i class="bi bi-person-circle"></i>Mi Perfil
    </a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;background:transparent;">
            <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
        </button>
    </form>
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item {{ request()->routeIs('portal.docente.dashboard') ? 'active' : '' }}">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="#mis-clases" class="prt-nav-item">
        <i class="bi bi-journal-text"></i>Clases
    </a>
    <a href="#rendimiento" class="prt-nav-item">
        <i class="bi bi-bar-chart-fill"></i>Stats
    </a>
    <a href="#mi-horario" class="prt-nav-item">
        <i class="bi bi-calendar-week"></i>Horario
    </a>
@endsection

@push('styles')
<style>
/* ── Sidebar: items por clase ── */
.prt-class-item {
    margin-bottom: .5rem;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--prt-border);
}
.prt-class-label {
    display: flex;
    align-items: flex-start;
    gap: .45rem;
    padding: .5rem .75rem .3rem;
    font-size: .78rem;
    font-weight: 700;
    color: var(--prt-text);
    background: var(--prt-card);
}
.prt-class-label span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.prt-class-actions {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    background: #f0f4ff;
    border-top: 2px solid #c7d7ff;
}
/* Botones solo ícono con etiqueta micro debajo */
.prt-class-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .1rem;
    padding: .45rem .1rem;
    color: var(--cb, #2563eb);
    text-decoration: none;
    transition: background .15s;
    border-right: 1px solid #dde5ff;
}
.prt-class-btn:last-child { border-right: none; }
.prt-class-btn:hover { background: rgba(37,99,235,.1); text-decoration: none; }
.prt-class-btn i { font-size: 1rem; }
.prt-class-btn span {
    font-size: .52rem;
    font-weight: 700;
    color: #64748b;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
}
[data-theme="dark"] .prt-class-item { border-color: #334155; }
[data-theme="dark"] .prt-class-label { background: #1e293b; color: #e2e8f0; }
[data-theme="dark"] .prt-class-actions { background: #0d1b2e; border-top-color: #1e3a5f; }
[data-theme="dark"] .prt-class-btn { border-right-color: #1e3a5f; }
[data-theme="dark"] .prt-class-btn span { color: #475569; }
[data-theme="dark"] .prt-class-btn:hover { background: rgba(59,130,246,.12); }

/* ── Dashboard: cards de accion por materia (6 columnas) ── */
.doc-class-card {
    background: var(--prt-card);
    border: 1px solid var(--prt-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: .85rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    transition: box-shadow .2s;
}
.doc-class-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.doc-class-head {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .85rem 1rem;
    border-bottom: 1px solid var(--prt-border);
}
.doc-class-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.doc-class-name { font-size: .9rem; font-weight: 800; color: var(--prt-text); line-height: 1.15; }
.doc-class-group { font-size: .73rem; color: var(--prt-muted); margin-top: .1rem; }
.doc-class-btns {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    background: #f0f4ff;
    border-top: 2px solid #c7d7ff;
}
.doc-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .3rem;
    padding: .75rem .35rem;
    text-decoration: none;
    border-right: 1px solid #dde5ff;
    transition: background .15s;
    font-size: .68rem;
    font-weight: 700;
    color: var(--btn-color, #2563eb);
}
.doc-action-btn:last-child { border-right: none; }
.doc-action-btn:hover { background: rgba(37,99,235,.08); text-decoration: none; }
.doc-action-btn i { font-size: 1.2rem; }
@media (max-width: 360px) {
    .doc-action-btn { padding: .6rem .2rem; font-size: .6rem; }
    .doc-action-btn i { font-size: 1rem; }
}
[data-theme="dark"] .doc-class-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .doc-class-head { border-bottom-color: #334155; }
[data-theme="dark"] .doc-class-name { color: #e2e8f0; }
[data-theme="dark"] .doc-class-btns { background: #0d1b2e; border-top-color: #1e3a5f; }
[data-theme="dark"] .doc-action-btn { border-right-color: #1e3a5f; }
[data-theme="dark"] .doc-action-btn:hover { background: rgba(59,130,246,.12); }
[data-theme="dark"] .doc-action-btn:hover { background: rgba(255,255,255,.06); }

/* Rendimiento dark mode */
[data-theme="dark"] .rend-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] #rendimiento .rend-pct { background: #052e16; }

/* Stats compactas en móvil (3 columnas) */
@media (max-width: 479px) {
    .doc-stats .prt-stat {
        padding: .6rem .5rem;
        gap: .4rem;
    }
    .doc-stats .prt-stat-icon {
        width: 32px; height: 32px; font-size: .9rem; border-radius: 8px;
        flex-shrink: 0;
    }
    .doc-stats .prt-stat-val { font-size: 1.1rem; }
    .doc-stats .prt-stat-lbl { font-size: .58rem; }
}
</style>
@endpush

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#1e1b4b 0%,#2563eb 100%);border-radius:16px;padding:1.25rem 1.5rem;color:#fff;margin-bottom:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-30px;top:-30px;width:150px;height:150px;background:rgba(255,255,255,.06);border-radius:50%;pointer-events:none;"></div>
    <div style="width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;flex-shrink:0;">
        {{ strtoupper(substr($docente->nombres ?? 'D', 0, 1)) }}
    </div>
    <div style="flex:1;min-width:0;">
        <div style="font-size:1.05rem;font-weight:800;margin-bottom:.2rem;">Bienvenido, {{ $docente->nombres }}</div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.78);">
            <i class="bi bi-person-badge me-1"></i>{{ $docente->nombre_completo }}
            @if($docente->especialidad) · {{ $docente->especialidad }} @endif
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    @if($periodoActivo)
    <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:.45rem .9rem;text-align:center;flex-shrink:0;">
        <div style="font-size:.6rem;text-transform:uppercase;letter-spacing:.08em;opacity:.75;">Período</div>
        <div style="font-size:.95rem;font-weight:800;">{{ $periodoActivo->nombre }}</div>
    </div>
    @endif
</div>

{{-- ── Acceso rápido: Asistencia del día ──────────────────────────── --}}
<a href="{{ route('portal.docente.asistencia-rapida') }}"
   style="display:flex;align-items:center;gap:.85rem;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;padding:.85rem 1.1rem;color:#fff;text-decoration:none;margin-bottom:1rem;box-shadow:0 3px 10px rgba(217,119,6,.3);">
    <div style="width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.3rem;">
        <i class="bi bi-lightning-charge-fill"></i>
    </div>
    <div style="flex:1;min-width:0;">
        <div style="font-size:.92rem;font-weight:800;line-height:1.2;">Asistencia Rápida</div>
        <div style="font-size:.73rem;opacity:.88;margin-top:.1rem;">Toma la asistencia de todas tus clases de hoy en un solo lugar</div>
    </div>
    <i class="bi bi-arrow-right-circle-fill" style="font-size:1.4rem;opacity:.85;flex-shrink:0;"></i>
</a>

{{-- ── Stats ────────────────────────────────────────────────────────── --}}
<div class="prt-stats doc-stats" style="grid-template-columns:repeat(3,1fr);">
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-journal-text"></i></div>
        <div>
            <div class="prt-stat-val">{{ $stats['asignaturas'] }}</div>
            <div class="prt-stat-lbl">Materias</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-diagram-3-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $stats['grupos'] }}</div>
            <div class="prt-stat-lbl">Grupos</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $stats['estudiantes'] }}</div>
            <div class="prt-stat-lbl">Estudiantes</div>
        </div>
    </div>
</div>

{{-- ── Mis Clases — tarjeta por materia ────────────────────────────── --}}
<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:.6rem;margin-top:.25rem;" id="mis-clases">
    <i class="bi bi-journals me-1"></i>Mis Clases Asignadas
</div>

@forelse($asignaciones as $asig)
@php
    $iconColors = ['#6366f1','#2563eb','#10b981','#f59e0b','#ef4444','#ec4899','#06b6d4','#8b5cf6'];
    $colorIdx   = $loop->index % count($iconColors);
    $iconColor  = $iconColors[$colorIdx];
    $iconBg     = $iconColor . '1a';
    $grupoNombre = $asig->grupo?->nombre_completo
        ?? (($asig->grupo?->grado?->nombre ?? '') . ' ' . ($asig->grupo?->seccion?->nombre ?? ''));
@endphp
<div class="doc-class-card">
    <a href="{{ route('portal.docente.asistencia', $asig) }}" class="doc-class-head" style="text-decoration:none;display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;border-bottom:1px solid var(--prt-border);transition:background .15s;" onmouseover="this.style.background='{{ $iconColor }}0d'" onmouseout="this.style.background=''">
        <div class="doc-class-icon" style="background:{{ $iconColor }}1a;color:{{ $iconColor }};">
            <i class="bi bi-book-fill"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="doc-class-name">{{ $asig->asignatura?->nombre ?? '—' }}</div>
            <div class="doc-class-group"><i class="bi bi-people me-1"></i>{{ $grupoNombre }}</div>
        </div>
        <span style="background:{{ $iconColor }}15;color:{{ $iconColor }};border-radius:6px;padding:.2rem .55rem;font-size:.68rem;font-weight:700;white-space:nowrap;">
            Activa <i class="bi bi-chevron-right" style="font-size:.6rem;"></i>
        </span>
    </a>
    <div class="doc-class-btns" style="grid-template-columns:repeat({{ $asig->area === 'tecnica' ? 7 : 6 }},1fr);">
        <a href="{{ route('portal.docente.asistencia', $asig) }}"
           class="doc-action-btn" style="--btn-color:#10b981;">
            <i class="bi bi-calendar-check-fill"></i>Asistencia
        </a>
        <a href="{{ route('portal.docente.calificaciones', $asig) }}"
           class="doc-action-btn" style="--btn-color:#6366f1;">
            <i class="bi bi-journal-check"></i>Notas
        </a>
        <a href="{{ route('portal.docente.estudiantes', $asig) }}"
           class="doc-action-btn" style="--btn-color:#2563eb;">
            <i class="bi bi-people-fill"></i>Alumnos
        </a>
        <a href="{{ route('portal.docente.boletines', $asig) }}"
           class="doc-action-btn" style="--btn-color:#f59e0b;">
            <i class="bi bi-file-earmark-text-fill"></i>Boletines
        </a>
        <a href="{{ route('portal.docente.observaciones', $asig) }}"
           class="doc-action-btn" style="--btn-color:#ec4899;">
            <i class="bi bi-chat-square-text-fill"></i>Observ.
        </a>
        <a href="{{ route('portal.docente.recursos', $asig) }}"
           class="doc-action-btn" style="--btn-color:#2563eb;">
            <i class="bi bi-folder-fill"></i>Recursos
        </a>
        @if($asig->area === 'tecnica')
        <a href="{{ route('portal.docente.planificacion.index', $asig) }}"
           class="doc-action-btn" style="--btn-color:#7c3aed;">
            <i class="bi bi-journal-text"></i>Planif.
        </a>
        @endif
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem 1rem;">
    <i class="bi bi-journal-x" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
    <div style="font-weight:700;color:#64748b;margin-bottom:.3rem;">Sin asignaciones activas</div>
    <div style="font-size:.8rem;color:#94a3b8;">No tienes materias asignadas para este año escolar.</div>
</div>
@endforelse

{{-- ── Rendimiento por Materia ──────────────────────────────────────── --}}
@if($asignaciones->isNotEmpty() && !empty($rendimiento))
<div id="rendimiento" style="margin-top:.25rem;">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:.6rem;">
        <i class="bi bi-bar-chart-fill me-1" style="color:#6366f1;"></i>Rendimiento por Materia
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.75rem;">
    @foreach($asignaciones as $asig)
    @php
        $r = $rendimiento[$asig->id] ?? null;
        $iconColors2 = ['#6366f1','#2563eb','#10b981','#f59e0b','#ef4444','#ec4899','#06b6d4','#8b5cf6'];
        $col2 = $iconColors2[$loop->index % count($iconColors2)];
    @endphp
    @if($r)
    <div class="rend-card" style="border-radius:14px;border:1px solid var(--prt-border);background:var(--prt-card);overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        {{-- Cabecera --}}
        <div style="background:{{ $col2 }}18;padding:.6rem .9rem;border-bottom:1px solid {{ $col2 }}22;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-book-fill" style="color:{{ $col2 }};font-size:.9rem;"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.8rem;font-weight:800;color:var(--prt-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $asig->asignatura?->nombre ?? '—' }}
                </div>
                <div style="font-size:.65rem;color:var(--prt-muted);">
                    {{ $asig->grupo?->nombre_completo ?? '—' }}
                </div>
            </div>
            <span style="background:{{ $col2 }};color:#fff;border-radius:20px;padding:.15rem .55rem;font-size:.65rem;font-weight:800;">
                {{ $r['total'] }} alum.
            </span>
        </div>
        {{-- Contenido --}}
        <div style="padding:.7rem .9rem;display:flex;align-items:center;gap:.9rem;">
            {{-- Donut chart --}}
            <div style="position:relative;width:72px;height:72px;flex-shrink:0;">
                <canvas id="rend-chart-{{ $asig->id }}" width="72" height="72"></canvas>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;pointer-events:none;">
                    @if($r['promedio'] !== null)
                        <span style="font-size:.9rem;font-weight:900;color:{{ $col2 }};line-height:1;">{{ $r['promedio'] }}</span>
                        <span style="font-size:.5rem;color:var(--prt-muted);text-transform:uppercase;letter-spacing:.05em;line-height:1.2;">prom.</span>
                    @else
                        <span style="font-size:.65rem;color:var(--prt-muted);">—</span>
                    @endif
                </div>
            </div>
            {{-- Stats --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem;">
                    <span style="width:9px;height:9px;border-radius:50%;background:#22c55e;flex-shrink:0;"></span>
                    <span style="font-size:.73rem;color:var(--prt-text);">Aprobados</span>
                    <span style="margin-left:auto;font-size:.78rem;font-weight:800;color:#15803d;">{{ $r['aprobados'] }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem;">
                    <span style="width:9px;height:9px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>
                    <span style="font-size:.73rem;color:var(--prt-text);">Reprobados</span>
                    <span style="margin-left:auto;font-size:.78rem;font-weight:800;color:#dc2626;">{{ $r['reprobados'] }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem;">
                    <span style="width:9px;height:9px;border-radius:50%;background:#94a3b8;flex-shrink:0;"></span>
                    <span style="font-size:.73rem;color:var(--prt-text);">Sin nota</span>
                    <span style="margin-left:auto;font-size:.78rem;font-weight:800;color:#64748b;">{{ $r['sin_nota'] }}</span>
                </div>
                @if($r['pct_asist'] !== null)
                <div style="margin-top:.2rem;background:#f0fdf4;border-radius:6px;padding:.2rem .5rem;display:flex;align-items:center;gap:.35rem;">
                    <i class="bi bi-calendar-check" style="color:#16a34a;font-size:.7rem;"></i>
                    <span style="font-size:.68rem;color:#15803d;font-weight:700;">Asistencia: {{ $r['pct_asist'] }}%</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    @endforeach
    </div>
</div>
@endif

{{-- ── Mi Horario ───────────────────────────────────────────────────── --}}
<div class="prt-card" id="mi-horario" style="margin-top:.5rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-week" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Mi Horario Semanal</h3>
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.docente.horario.pdf') }}" target="_blank"
           style="margin-left:auto;background:#1e3a6e;color:#fff;border-radius:7px;padding:.3rem .75rem;font-size:.75rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            <i class="bi bi-file-earmark-text"></i>PDF
        </a>
        @endif
    </div>
    <div class="prt-card-body" style="padding:.5rem;">
        @if($horarioActivo && !empty($gridHorario))
            {{-- ── Horario publicado con detalles ──────────────────────── --}}
            <div class="table-responsive">
            <table class="sch-table">
                <thead>
                    <tr>
                        <th style="width:52px;">Hora</th>
                        @foreach($diasConfig as $dia)
                            <th>{{ ucfirst($dia === 'miercoles' ? 'Mié' : substr($dia, 0, 3)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#f97316']; $ci = 0; $cMap = []; @endphp
                    @foreach($franjasHorario as $franja)
                        @if($franja->es_recreo)
                            <tr class="sch-recreo"><td colspan="{{ count($diasConfig) + 1 }}"><i class="bi bi-cup-hot me-1"></i>Recreo {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}–{{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}</td></tr>
                        @else
                            <tr>
                                <td class="franja-col">
                                    {{ $franja->nombre ?? 'F'.$franja->numero }}<br>
                                    <span style="font-size:.6rem;color:#9ca3af;">{{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}</span>
                                </td>
                                @foreach($diasConfig as $dia)
                                    <td>
                                        @if(isset($gridHorario[$franja->id][$dia]))
                                            @php
                                                $d = $gridHorario[$franja->id][$dia];
                                                $aId = $d->asignacion?->asignatura_id ?? 0;
                                                if (!isset($cMap[$aId])) { $cMap[$aId] = $palette[$ci % count($palette)]; $ci++; }
                                            @endphp
                                            <div class="sch-cell" style="background:{{ $cMap[$aId] }};">
                                                {{ Str::limit($d->asignacion?->asignatura?->nombre ?? '—', 14) }}
                                                @php $g = $d->asignacion?->grupo; @endphp
                                                @if($g)<div style="font-size:.6rem;opacity:.85;">{{ $g->nombre_completo ?? '' }}</div>@endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            </div>

        @elseif($asignaciones->isNotEmpty())
            {{-- ── Sin horario publicado pero con asignaciones: mostrar carga académica ── --}}
            @if($horarioActivo)
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:.55rem .9rem;margin-bottom:.85rem;font-size:.78rem;color:#92400e;display:flex;align-items:center;gap:.5rem;">
                <i class="bi bi-info-circle-fill"></i>
                Aún no tienes clases asignadas en el horario publicado. La administración debe incluirte en el horario.
            </div>
            @else
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.55rem .9rem;margin-bottom:.85rem;font-size:.78rem;color:#1e40af;display:flex;align-items:center;gap:.5rem;">
                <i class="bi bi-info-circle-fill"></i>
                El horario detallado aún no ha sido publicado por la administración. Aquí está tu carga académica asignada:
            </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.65rem;">
                @foreach($asignaciones as $asig)
                @php
                    $color = $asig->asignatura?->color ?? '#1e3a6e';
                    $grupo = $asig->grupo;
                    $grado = $grupo?->grado;
                @endphp
                <div style="border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;background:#fff;">
                    <div style="background:{{ $color }};padding:.5rem .75rem;display:flex;align-items:center;gap:.5rem;">
                        <i class="bi bi-journal-bookmark-fill" style="color:rgba(255,255,255,.85);font-size:.9rem;"></i>
                        <span style="color:#fff;font-size:.8rem;font-weight:700;line-height:1.2;">
                            {{ $asig->asignatura?->nombre ?? '—' }}
                        </span>
                    </div>
                    <div style="padding:.55rem .75rem;font-size:.78rem;color:#374151;">
                        <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.25rem;">
                            <i class="bi bi-people-fill" style="color:#6366f1;font-size:.8rem;"></i>
                            <span style="font-weight:600;">{{ $grupo?->nombre_completo ?? '—' }}</span>
                        </div>
                        @if($grado)
                        <div style="color:#6b7280;font-size:.72rem;">
                            <i class="bi bi-mortarboard me-1"></i>{{ $grado->nombre }}
                        </div>
                        @endif
                        @if($asig->asignatura?->horas_semanales)
                        <div style="color:#9ca3af;font-size:.7rem;margin-top:.2rem;">
                            <i class="bi bi-clock me-1"></i>{{ $asig->asignatura->horas_semanales }} h/semana
                        </div>
                        @endif
                    </div>
                    <div style="padding:.35rem .75rem;border-top:1px solid #f1f5f9;display:flex;gap:.4rem;">
                        <a href="{{ route('portal.docente.calificaciones', $asig) }}"
                           style="flex:1;text-align:center;font-size:.68rem;color:#6366f1;text-decoration:none;font-weight:600;">
                            <i class="bi bi-journal-check me-1"></i>Notas
                        </a>
                        <a href="{{ route('portal.docente.asistencia', $asig) }}"
                           style="flex:1;text-align:center;font-size:.68rem;color:#10b981;text-decoration:none;font-weight:600;">
                            <i class="bi bi-calendar-check me-1"></i>Asistencia
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

        @else
            {{-- ── Sin horario y sin asignaciones ─────────────────────── --}}
            <div style="text-align:center;padding:2rem;color:#9ca3af;font-size:.84rem;">
                <i class="bi bi-calendar3-week" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No tienes materias asignadas aún. Contacta a la administración.
            </div>
        @endif
    </div>
</div>

{{-- ── Noticias ─────────────────────────────────────────────────────── --}}
@if($comunicados->isNotEmpty())
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-megaphone" style="color:#3b82f6;font-size:1rem;"></i>
        <h3>Noticias del Centro</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($comunicados as $com)
        <div class="dm-list-item" style="padding:.8rem 1rem;border-bottom:1px solid #f1f5f9;">
            <div class="dm-text-primary" style="font-size:.84rem;font-weight:700;color:#1e293b;margin-bottom:.2rem;">{{ $com->titulo }}</div>
            <div class="dm-text-muted" style="font-size:.76rem;color:#64748b;">{{ Str::limit(strip_tags($com->cuerpo), 120) }}</div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.25rem;">
                <span class="dm-text-muted" style="font-size:.68rem;color:#9ca3af;">{{ $com->published_at?->format('d/m/Y') }}</span>
                <button onclick="verComunicado({{ $com->id }}, {{ json_encode($com->titulo) }}, {{ json_encode($com->cuerpo) }}, {{ json_encode($com->published_at?->format('d/m/Y') ?? '') }})"
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
            <button onclick="cerrarComunicado()" style="background:none;border:none;font-size:1.2rem;color:#6b7280;cursor:pointer;line-height:1;">×</button>
        </div>
        <div style="padding:1rem 1.25rem;overflow-y:auto;flex:1;">
            <div style="font-size:.77rem;color:#6b7280;margin-bottom:.75rem;" id="comFecha"></div>
            <div style="font-size:.88rem;color:#374151;line-height:1.7;" id="comCuerpo" class="comunicado-body"></div>
        </div>
    </div>
</div>
<script>
function verComunicado(id, titulo, cuerpo, fecha) {
    document.getElementById('comTitulo').textContent = titulo;
    document.getElementById('comFecha').textContent = fecha;
    document.getElementById('comCuerpo').innerHTML = cuerpo;
    document.getElementById('modalComunicado').style.display = 'flex';
}
function cerrarComunicado() { document.getElementById('modalComunicado').style.display = 'none'; }
document.getElementById('modalComunicado').addEventListener('click', function(e){ if(e.target===this) cerrarComunicado(); });
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
            @if($notificaciones->isNotEmpty())
            <button onclick="marcarTodasLeidas()" class="btn btn-sm"
                    style="font-size:.72rem;background:#eff6ff;color:#1d4ed8;border-radius:7px;border:1px solid #bfdbfe;">
                <i class="bi bi-check-all me-1"></i>Leídas
            </button>
            @endif
            <a href="{{ route('portal.docente.notificaciones') }}"
               style="font-size:.72rem;background:#f1f5f9;color:#374151;border-radius:7px;border:1px solid #e5e7eb;padding:.25rem .6rem;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                <i class="bi bi-list-ul"></i>Ver todas
            </a>
        </div>
    </div>
    <ul class="notif-list">
        @forelse($notificaciones as $notif)
        <li class="notif-item unread">
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

{{-- ── Suplencias próximas ─────────────────────────────────────────── --}}
@if($suplencias->isNotEmpty())
<div class="prt-card" id="suplencias" style="margin-top:.75rem;">
    <div class="prt-card-header" style="background:linear-gradient(135deg,#92400e,#d97706);">
        <i class="bi bi-person-fill-exclamation" style="color:#fff;font-size:1rem;"></i>
        <h3 style="color:#fff;margin:0;">Suplencias — Próximos 30 días</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($suplencias as $sup)
        @php
            $esSuplente  = $sup->docente_suplente_id === $docente->id;
            $esOriginal  = $sup->docente_original_id === $docente->id;
            $asig        = $sup->detalle?->asignacion;
            $franja      = $sup->detalle?->franja;
            $daysLeft    = today()->diffInDays($sup->fecha, false);
            $estadoColor = match($sup->estado ?? 'pendiente') {
                'cubierta'   => '#065f46',
                'sin_cubrir' => '#991b1b',
                default      => '#92400e',
            };
        @endphp
        <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.75rem 1rem;border-bottom:1px solid var(--prt-border);">
            <div style="width:44px;height:44px;border-radius:10px;background:{{ $esSuplente ? '#fef3c7' : '#fee2e2' }};display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid {{ $esSuplente ? '#f59e0b' : '#fca5a5' }};">
                <div style="font-size:.95rem;font-weight:900;color:{{ $esSuplente ? '#92400e' : '#991b1b' }};line-height:1;">{{ $sup->fecha->format('d') }}</div>
                <div style="font-size:.55rem;font-weight:700;color:{{ $esSuplente ? '#92400e' : '#991b1b' }};text-transform:uppercase;">{{ $sup->fecha->translatedFormat('M') }}</div>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.84rem;font-weight:700;color:var(--prt-text);">
                    @if($esSuplente)
                        <span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:.1rem .4rem;font-size:.68rem;font-weight:800;">CUBRIENDO</span>
                        a {{ $sup->docenteOriginal?->nombre_completo ?? '—' }}
                    @else
                        <span style="background:#fee2e2;color:#991b1b;border-radius:4px;padding:.1rem .4rem;font-size:.68rem;font-weight:800;">AUSENTE</span>
                        Cubierto por {{ $sup->docenteSuplente?->nombre_completo ?? 'Sin asignar' }}
                    @endif
                </div>
                <div style="font-size:.75rem;color:var(--prt-muted);margin-top:.15rem;">
                    {{ $asig?->asignatura?->nombre ?? '—' }}
                    · {{ $asig?->grupo?->grado?->nombre ?? '' }} {{ $asig?->grupo?->seccion?->nombre ?? '' }}
                    @if($franja) · {{ $franja->hora_inicio ?? '' }}–{{ $franja->hora_fin ?? '' }} @endif
                </div>
                @if($sup->motivo)
                <div style="font-size:.72rem;color:#6b7280;margin-top:.1rem;font-style:italic;">{{ $sup->motivo }}</div>
                @endif
            </div>
            <div style="font-size:.7rem;font-weight:600;white-space:nowrap;flex-shrink:0;color:{{ $daysLeft === 0 ? '#dc2626' : ($daysLeft <= 2 ? '#d97706' : '#6b7280') }};">
                {{ $daysLeft === 0 ? 'Hoy' : ($daysLeft === 1 ? 'Mañana' : "En {$daysLeft}d") }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Gráficas de rendimiento por materia ─────────────────────────────
(function () {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    @foreach($asignaciones as $asig)
    @php $r = $rendimiento[$asig->id] ?? null; @endphp
    @if($r)
    (function () {
        const ctx = document.getElementById('rend-chart-{{ $asig->id }}');
        if (!ctx) return;
        const data = [{{ $r['aprobados'] }}, {{ $r['reprobados'] }}, {{ $r['sin_nota'] }}];
        const total = data.reduce((a, b) => a + b, 0);
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Reprobados', 'Sin nota'],
                datasets: [{
                    data: total > 0 ? data : [1],
                    backgroundColor: total > 0
                        ? ['#22c55e', '#ef4444', '#94a3b8']
                        : [isDark ? '#1e293b' : '#f1f5f9'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                cutout: '68%',
                plugins: { legend: { display: false }, tooltip: { enabled: total > 0 } },
                animation: { duration: 600 },
            }
        });
    })();
    @endif
    @endforeach
})();

async function marcarTodasLeidas() {
    await fetch('{{ route("portal.docente.notif.leer-todas") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
    });
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    document.querySelector('.prt-badge')?.remove();
}
</script>
@endpush

@push('realtime-data')
<script>
{{-- Exponer IDs de grupos del docente para subscripciones Echo --}}
window._SGE_GRUPO_IDS = {!! $asignaciones->pluck('grupo_id')->unique()->values()->toJson() !!};
</script>
@endpush
