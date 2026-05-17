@extends('layouts.portal')
@section('page-title', 'Seguimiento de Tareas — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'tareas', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.tareas.index', $asignacion) }}" class="prt-nav-item"><i class="bi bi-check2-square"></i>Tareas</a>
<a href="{{ route('portal.docente.tareas.seguimiento', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-graph-up"></i>Seguimiento</a>
@endsection

@push('styles')
<style>
.seg-table { width:100%;border-collapse:collapse;font-size:.8rem; }
.seg-table th {
    background:#1e293b;color:#fff;padding:.55rem .65rem;text-align:left;
    font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;
}
.seg-table th.center { text-align:center; }
.seg-table td { padding:.55rem .65rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.seg-table tbody tr:hover td { background:#f8faff; }

.tipo-badge {
    display:inline-block;padding:.18rem .55rem;border-radius:99px;
    font-size:.67rem;font-weight:700;color:#fff;
}
.pct-bar { height:8px;border-radius:99px;background:#e2e8f0;overflow:hidden;width:80px;display:inline-block;vertical-align:middle; }
.pct-fill { height:8px;border-radius:99px;transition:.4s; }

.record-btn {
    border:none;border-radius:7px;padding:.28rem .6rem;font-size:.72rem;font-weight:700;cursor:pointer;
    display:inline-flex;align-items:center;gap:.25rem;transition:.15s;
}
.record-btn:disabled { opacity:.5;cursor:not-allowed; }

.stat-num { font-size:1rem;font-weight:800; }
.stat-lbl { font-size:.65rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}"
       style="color:#3b82f6;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Tareas
    </a>
    <span style="color:#cbd5e1;">›</span>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-graph-up" style="color:#3b82f6;margin-right:.35rem;"></i>
            Seguimiento — {{ $asignacion->asignatura?->nombre ?? '—' }}
        </h1>
        <div style="font-size:.72rem;color:#64748b;">
            {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
            &nbsp;·&nbsp; {{ $totalEstudiantes }} estudiante(s)
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
</div>

{{-- KPIs globales --}}
@php
    $totalTareas    = $tareas->count();
    $totalEntregas  = collect($statsMap)->sum('entregadas');
    $totalPend      = collect($statsMap)->sum('pendientes');
    $pctGlobal      = ($totalTareas * $totalEstudiantes) > 0
        ? round($totalEntregas / ($totalTareas * $totalEstudiantes) * 100)
        : 0;
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.65rem;margin-bottom:1.2rem;">
    <div class="prt-card" style="text-align:center;padding:.85rem .5rem;">
        <div class="stat-num" style="color:#1e293b;">{{ $totalTareas }}</div>
        <div class="stat-lbl">Tareas</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.85rem .5rem;">
        <div class="stat-num" style="color:#1e293b;">{{ $totalEstudiantes }}</div>
        <div class="stat-lbl">Estudiantes</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.85rem .5rem;">
        <div class="stat-num" style="color:#3b82f6;">{{ $totalEntregas }}</div>
        <div class="stat-lbl">Entregadas</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.85rem .5rem;">
        <div class="stat-num" style="color:#f59e0b;">{{ $totalPend }}</div>
        <div class="stat-lbl">Pendientes</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.85rem .5rem;">
        <div class="stat-num" style="color:{{ $pctGlobal >= 80 ? '#10b981' : ($pctGlobal >= 50 ? '#f59e0b' : '#ef4444') }};">{{ $pctGlobal }}%</div>
        <div class="stat-lbl">Cumplimiento</div>
    </div>
</div>

{{-- Tabla --}}
<div class="prt-card" style="overflow-x:auto;padding:0;">
    @if($tareas->isEmpty())
    <div style="text-align:center;padding:2.5rem;color:#94a3b8;">
        <i class="bi bi-check2-square" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
        <p style="margin:0;font-size:.85rem;">No hay tareas creadas para esta asignatura.</p>
        <a href="{{ route('portal.docente.tareas.create', $asignacion) }}"
           style="display:inline-flex;align-items:center;gap:.35rem;margin-top:.75rem;background:#3b82f6;color:#fff;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;">
            <i class="bi bi-plus-lg"></i>Crear primera tarea
        </a>
    </div>
    @else
    <table class="seg-table">
        <thead>
            <tr>
                <th style="min-width:180px;">Tarea / Actividad</th>
                <th>Tipo</th>
                <th class="center">Límite</th>
                <th class="center">Entregadas</th>
                <th class="center">Revisadas</th>
                <th class="center">Pendientes</th>
                <th style="min-width:130px;">Cumplimiento</th>
                <th class="center" style="min-width:160px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tareas as $t)
            @php $s = $statsMap[$t->id]; @endphp
            <tr id="row-t{{ $t->id }}">
                <td>
                    <div style="font-weight:700;font-size:.85rem;color:#1e293b;">{{ $t->titulo }}</div>
                    @if($t->puntos_valor)
                    <div style="font-size:.68rem;color:#94a3b8;">{{ $t->puntos_valor }} pts</div>
                    @endif
                </td>
                <td>
                    <span class="tipo-badge" style="background:{{ $t->tipo_color }};">{{ $t->tipo_label }}</span>
                </td>
                <td style="text-align:center;white-space:nowrap;">
                    <span style="font-size:.8rem;font-weight:600;color:{{ $t->esta_vencida ? '#ef4444' : '#1e293b' }};">
                        {{ $t->fecha_limite->format('d/m/Y') }}
                    </span>
                    @if($t->esta_vencida)
                    <div style="font-size:.65rem;color:#ef4444;font-weight:600;">Vencida</div>
                    @endif
                </td>
                <td style="text-align:center;font-weight:700;color:#3b82f6;">{{ $s['entregadas'] }}</td>
                <td style="text-align:center;font-weight:700;color:#10b981;">{{ $s['revisadas'] }}</td>
                <td style="text-align:center;font-weight:700;color:{{ $s['pendientes'] > 0 ? '#f59e0b' : '#10b981' }};">
                    {{ $s['pendientes'] }}
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div class="pct-bar">
                            <div class="pct-fill" style="width:{{ $s['pct'] }}%;background:{{ $s['pct'] >= 80 ? '#10b981' : ($s['pct'] >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
                        </div>
                        <span style="font-size:.78rem;font-weight:800;color:{{ $s['pct'] >= 80 ? '#10b981' : ($s['pct'] >= 50 ? '#f59e0b' : '#ef4444') }};">{{ $s['pct'] }}%</span>
                    </div>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap;">
                        <a href="{{ route('portal.docente.tareas.entregas', [$asignacion, $t]) }}"
                           style="background:#dbeafe;color:#1d4ed8;border-radius:7px;padding:.28rem .65rem;font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;">
                            <i class="bi bi-people-fill"></i>Ver entregas
                        </a>
                        @if($s['pendientes'] > 0)
                        <button class="record-btn"
                            id="rbtn-{{ $t->id }}"
                            onclick="enviarRecordatorio({{ $t->id }}, {{ $asignacion->id }}, {{ $s['pendientes'] }})"
                            style="background:#fef3c7;color:#d97706;">
                            <i class="bi bi-bell-fill"></i>Recordatorio ({{ $s['pendientes'] }})
                        </button>
                        @else
                        <span style="font-size:.72rem;color:#10b981;font-weight:600;display:inline-flex;align-items:center;gap:.2rem;">
                            <i class="bi bi-check-circle-fill"></i>Todos entregaron
                        </span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Toast de notificación --}}
<div id="toast-recordatorio"
     style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;background:#1e293b;color:#fff;border-radius:10px;padding:.75rem 1.1rem;font-size:.82rem;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.25);display:flex;align-items:center;gap:.5rem;max-width:320px;">
    <i class="bi bi-bell-fill" style="color:#f59e0b;"></i>
    <span id="toast-msg">Recordatorio enviado.</span>
</div>

@push('scripts')
<script>
const CSRF_SEG = '{{ csrf_token() }}';

async function enviarRecordatorio(tareaId, asignId, pendientes) {
    if (!confirm(`¿Enviar recordatorio a los ${pendientes} estudiante(s) que aún no entregaron?`)) return;

    const btn = document.getElementById(`rbtn-${tareaId}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Enviando...'; }

    try {
        const r = await fetch(`/portal/docente/asignacion/${asignId}/tareas/${tareaId}/recordatorio`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_SEG,'Accept':'application/json' },
            body: '{}'
        });
        const d = await r.json();
        mostrarToast(d.mensaje ?? 'Recordatorio enviado.');
        if (btn) { btn.innerHTML = '<i class="bi bi-check-lg"></i> Enviado'; btn.style.background='#d1fae5'; btn.style.color='#065f46'; }
    } catch(e) {
        if (btn) { btn.disabled = false; btn.innerHTML = `<i class="bi bi-bell-fill"></i>Recordatorio (${pendientes})`; }
    }
}

function mostrarToast(msg) {
    const t = document.getElementById('toast-recordatorio');
    document.getElementById('toast-msg').textContent = msg;
    t.style.display = 'flex';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}
</script>
@endpush

@endsection
